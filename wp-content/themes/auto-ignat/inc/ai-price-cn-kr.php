<?php
/**
 * Калькулятор «под ключ» Китай / Корея (china|korea).
 *
 * Excel «Формула kitay / корей»:
 *   (цена + доставка_по_стране) × (курс_ЦБ × 1.05)
 *   + банк 3000 + брокер/СБКТС/СВХ 95000 + АвтоИгнат 25000 + скрытая 10000
 *   + пошлина + утиль (Calcos / Михаил)
 *   + [не ДВФО] прописка 15000
 *
 * База цены: FINISH (склад), иначе START. AVG у CN/KR обычно 0.
 * Валюта: CNY (юани) / KRW (воны), курс ЦБ+5% — не JPY.
 *
 * @package auto-ignat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Токен Calcos для CN/KR (валюта площадки в шаблоне).
 * Переопределение: AI_CALCOS_TOKEN_CN / AI_CALCOS_TOKEN_KR в wp-config.
 */
function ai_price_cnkr_calcos_token( string $market ): string {
	$market = strtoupper( $market );
	// Дефолты пустые: токены только AI_CALCOS_TOKEN_CN / AI_CALCOS_TOKEN_KR в wp-config.
	$defaults = array(
		'CN' => '',
		'KR' => '',
	);
	if ( 'CN' === $market && defined( 'AI_CALCOS_TOKEN_CN' ) && AI_CALCOS_TOKEN_CN ) {
		return (string) AI_CALCOS_TOKEN_CN;
	}
	if ( 'KR' === $market && defined( 'AI_CALCOS_TOKEN_KR' ) && AI_CALCOS_TOKEN_KR ) {
		return (string) AI_CALCOS_TOKEN_KR;
	}
	$token = apply_filters( 'ai_price_cnkr_calcos_token', $defaults[ $market ] ?? '', $market );
	return is_string( $token ) ? $token : '';
}

/** Доставка по стране в валюте площадки. */
function ai_price_cnkr_local_delivery( string $market ): int {
	return 'KR' === strtoupper( $market ) ? 2000000 : 10000;
}

/**
 * База лота: FINISH, иначе START.
 *
 * @param array<string,mixed> $r
 * @return array{amount:float,basis:string}
 */
function ai_price_cnkr_lot_base( array $r ): array {
	$finish = (float) ( $r['FINISH'] ?? $r['finish'] ?? 0 );
	$start  = (float) ( $r['START'] ?? $r['start'] ?? 0 );
	$amount = (float) ( $r['amount'] ?? 0 );
	if ( $finish > 0 ) {
		return array(
			'amount' => $finish,
			'basis'  => 'finish',
		);
	}
	if ( $start > 0 ) {
		return array(
			'amount' => $start,
			'basis'  => 'start',
		);
	}
	if ( $amount > 0 ) {
		return array(
			'amount' => $amount,
			'basis'  => 'finish',
		);
	}
	return array(
		'amount' => 0.0,
		'basis'  => 'finish',
	);
}

/** Объём: см³ → литры для CN/KR-шаблонов Calcos. */
function ai_price_cnkr_volume_liters( $eng_v ): float {
	$v = (float) $eng_v;
	if ( $v <= 0 ) {
		return 0.0;
	}
	if ( $v >= 50 ) {
		return round( $v / 1000, 2 );
	}
	return round( $v, 2 );
}

/**
 * Ключ и URL Calcos для CN/KR. null — запрос не нужен.
 *
 * @param array{market?:string,price?:float,year?:int,volume_liters?:float,fuel?:int} $args
 * @return array{key:string,url:string}|null
 */
function ai_price_cnkr_calcos_spec( array $args ): ?array {
	$market = strtoupper( (string) ( $args['market'] ?? '' ) );
	if ( ! in_array( $market, array( 'CN', 'KR' ), true ) ) {
		return null;
	}
	$token = ai_price_cnkr_calcos_token( $market );
	if ( '' === $token ) {
		return null;
	}

	$price = max( 0.0, (float) ( $args['price'] ?? 0 ) );
	$year  = (int) ( $args['year'] ?? 0 );
	$vol   = max( 0.0, (float) ( $args['volume_liters'] ?? 0 ) );
	$fuel  = (int) ( $args['fuel'] ?? 1 );
	if ( $price <= 0 || $year < 1985 || $vol <= 0 ) {
		return null;
	}

	$query = http_build_query(
		array(
			'price'    => $price,
			'year'     => $year,
			'volume'   => round( $vol, 2 ),
			'fuel'     => $fuel,
			'tax_mode' => 2,
			'passing'  => 1,
		)
	);
	return array(
		'key' => 'ai_calcos_cnkr_v1_' . md5( wp_json_encode( array( $token, $price, $year, $vol, $fuel ) ) ),
		'url' => 'https://calcos.ajes.com/api/' . $token . '?' . $query,
	);
}

/**
 * Пошлина + утиль через Calcos (токен CN/KR).
 * fiz_info в $ → ₽ по USDRUB ЦБ без +5%.
 *
 * @param array{market:string,price:float,year:int,volume_liters:float,fuel?:int} $args
 * @return array{duty:int,util:int,fiz:int}|null
 */
function ai_price_cnkr_calcos_duty_util( array $args ): ?array {
	$spec = ai_price_cnkr_calcos_spec( $args );
	if ( ! $spec ) {
		return null;
	}

	$cached = get_transient( $spec['key'] );
	if ( is_array( $cached ) && isset( $cached['duty'], $cached['util'] ) ) {
		return $cached;
	}

	$body = function_exists( 'ai_auc_http' ) ? ai_auc_http( $spec['url'], 15 ) : '';
	if ( ( ! $body || ! str_contains( $body, '<fiz' ) ) && function_exists( 'ai_auc_http' ) ) {
		$body = ai_auc_http( preg_replace( '#^https:#', 'http:', $spec['url'] ), 15 );
	}
	$out = ( $body && function_exists( 'ai_price_calcos_parse' ) ) ? ai_price_calcos_parse( $body ) : null;
	if ( ! $out ) {
		return null;
	}
	set_transient( $spec['key'], $out, 6 * HOUR_IN_SECONDS );
	return $out;
}

/**
 * Аргументы Calcos из строки склада — те же, что в полном расчёте.
 *
 * @param array<string,mixed> $r
 * @return array{market:string,price:float,year:int,volume_liters:float,fuel:int}|null
 */
function ai_price_cnkr_customs_args( array $r, string $market ): ?array {
	$base = ai_price_cnkr_lot_base( $r );
	$lot  = (float) $base['amount'];
	if ( $lot <= 0 ) {
		return null;
	}
	$year = (int) ( $r['YEAR'] ?? $r['year'] ?? 0 );
	$eng  = $r['ENG_V'] ?? $r['eng_v'] ?? 0;
	if ( (float) $eng <= 0 && ! empty( $r['volume'] ) ) {
		$eng = preg_replace( '/\D+/', '', (string) $r['volume'] );
	}
	$time = (string) ( $r['TIME'] ?? $r['time'] ?? '' );
	return array(
		'market'        => $market,
		'price'         => $lot,
		'year'          => $year,
		'volume_liters' => ai_price_cnkr_volume_liters( $eng ),
		'fuel'          => function_exists( 'ai_price_calcos_fuel' ) ? ai_price_calcos_fuel( $time ) : 1,
	);
}

/**
 * Пакетный прогрев Calcos для витрины CN/KR.
 *
 * @param array<int,mixed> $rows
 */
function ai_price_cnkr_customs_prefetch( array $rows, string $market ): void {
	if ( ! function_exists( 'ai_auc_http_multi' ) || ! function_exists( 'ai_price_calcos_parse' ) ) {
		return;
	}
	$pending = array();
	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$args = ai_price_cnkr_customs_args( $row, $market );
		$spec = $args ? ai_price_cnkr_calcos_spec( $args ) : null;
		if ( ! $spec ) {
			continue;
		}
		$hit = get_transient( $spec['key'] );
		if ( is_array( $hit ) && isset( $hit['duty'], $hit['util'] ) ) {
			continue;
		}
		$pending[ $spec['key'] ] = $spec['url'];
	}
	if ( ! $pending ) {
		return;
	}
	foreach ( ai_auc_http_multi( $pending, 8 ) as $key => $body ) {
		if ( ! is_string( $body ) ) {
			continue;
		}
		$parsed = ai_price_calcos_parse( $body );
		if ( $parsed ) {
			set_transient( (string) $key, $parsed, 6 * HOUR_IN_SECONDS );
		}
	}
}

/**
 * Полный расчёт CN/KR. Формат ответа совместим с JP (lines / total_*).
 *
 * @param array<string,mixed> $r Строка API или mapped lot.
 * @return array<string,mixed>|null
 */
function ai_price_cnkr_calc( array $r, bool $with_customs = true, string $market = '' ): ?array {
	if ( '' === $market ) {
		$market = strtoupper( (string) ( $r['market'] ?? '' ) );
	}
	$table = strtolower( (string) ( $r['table'] ?? $r['_table'] ?? '' ) );
	if ( ! in_array( $market, array( 'CN', 'KR' ), true ) ) {
		if ( str_contains( $table, 'korea' ) ) {
			$market = 'KR';
		} elseif ( str_contains( $table, 'china' ) ) {
			$market = 'CN';
		} else {
			return null;
		}
	}

	$base = ai_price_cnkr_lot_base( $r );
	$lot  = (float) $base['amount'];
	if ( $lot <= 0 ) {
		return null;
	}

	$ccy          = 'KR' === $market ? 'KRW' : 'CNY';
	$local_deliv  = ai_price_cnkr_local_delivery( $market );
	$broker_rub   = 95000;
	$commission   = 25000;
	$bank_rub     = 3000;
	$hidden_rub   = 10000;
	$reg_rub      = 15000;

	$duty_rub = null;
	$util_rub = null;
	if ( $with_customs ) {
		$customs_args = ai_price_cnkr_customs_args( $r, $market );
		$customs      = $customs_args ? ai_price_cnkr_calcos_duty_util( $customs_args ) : null;
		if ( $customs ) {
			$duty_rub = (int) $customs['duty'];
			$util_rub = (int) $customs['util'];
		}
	}

	$goods_rub    = ai_price_jp_to_rub( $lot + $local_deliv, $ccy );
	$lot_rub      = ai_price_jp_to_rub( $lot, $ccy );
	$delivery_rub = ai_price_jp_to_rub( (float) $local_deliv, $ccy );

	$unit_ru = function_exists( 'ai_auc_ccy_unit_ru' ) ? ai_auc_ccy_unit_ru( $ccy ) : ( 'KRW' === $ccy ? 'вон' : 'юаней' );
	$ccy_note = ai_auc_num( (int) round( $lot ) ) . ' ' . $unit_ru
		. ( 'KRW' === $ccy ? ' (₩ KRW)' : ' (¥ CNY)' );
	$deliv_note = ai_auc_num( $local_deliv ) . ' ' . $unit_ru
		. ( 'KRW' === $ccy ? ' (₩ KRW)' : ' (¥ CNY)' );
	$lines   = array();
	$lines[] = array(
		'key'        => 'lot',
		'label'      => 'finish' === $base['basis'] ? 'Цена на площадке' : 'Стартовая цена лота',
		'amount_rub' => $lot_rub,
		'note'       => 'start' === $base['basis']
			? 'FINISH нет — берём стартовую'
			: $ccy_note,
	);
	$lines[] = array(
		'key'        => 'local_delivery',
		'label'      => 'KR' === $market ? 'Доставка по Корее' : 'Доставка по Китаю',
		'amount_rub' => $delivery_rub,
		'note'       => $deliv_note,
	);
	$lines[] = array(
		'key'        => 'broker',
		'label'      => 'Брокер + СБКТС + СВХ',
		'amount_rub' => $broker_rub,
		'note'       => '',
	);
	$lines[] = array(
		'key'        => 'commission',
		'label'      => 'Комиссия АвтоИгнат',
		'amount_rub' => $commission,
		'note'       => '',
	);
	$lines[] = array(
		'key'        => 'bank',
		'label'      => 'Комиссия банка',
		'amount_rub' => $bank_rub,
		'note'       => '',
	);
	$lines[] = array(
		'key'        => 'duty',
		'label'      => 'Таможенная пошлина',
		'amount_rub' => $duty_rub,
		'note'       => null === $duty_rub ? ( $with_customs ? 'не удалось получить из API' : 'считается на карточке лота' ) : '',
	);
	$lines[] = array(
		'key'        => 'util',
		'label'      => 'Утильсбор',
		'amount_rub' => $util_rub,
		'note'       => null === $util_rub ? ( $with_customs ? 'не удалось получить из API' : 'считается на карточке лота' ) : '',
	);

	// goods_rub уже = (цена + доставка) × ЦБ+5%; плюс ₽-константы и скрытая.
	$subtotal = $goods_rub + $broker_rub + $commission + $bank_rub + $hidden_rub;
	if ( null !== $duty_rub ) {
		$subtotal += $duty_rub;
	}
	if ( null !== $util_rub ) {
		$subtotal += $util_rub;
	}

	$total      = (int) round( $subtotal );
	$total_reg  = (int) round( $subtotal + $reg_rub );
	$incomplete = ( null === $duty_rub || null === $util_rub );

	return array(
		'ok'              => true,
		'incomplete'      => $incomplete,
		'market'          => $market,
		'lot_amount'      => $lot,
		'lot_basis'       => $base['basis'],
		'fx'              => ai_price_jp_fx( $ccy ),
		'fx_usd'          => ai_auc_fx( 'USD' ),
		'lines'           => $lines,
		'hidden_rub'      => $hidden_rub,
		'reg_rub'         => $reg_rub,
		'total_rub'       => $total,
		'total_rub_reg'   => $total_reg,
		'total_label'     => ai_auc_num( $total ) . ' ₽',
		'total_reg_label' => ai_auc_num( $total_reg ) . ' ₽',
	);
}

/**
 * Расчёт из mapped-лота темы.
 *
 * @param array<string,mixed> $lot
 */
function ai_price_cnkr_from_lot( array $lot, bool $with_customs = true ): ?array {
	$market = strtoupper( (string) ( $lot['market'] ?? '' ) );
	if ( ! in_array( $market, array( 'CN', 'KR' ), true ) ) {
		return null;
	}
	$row = array(
		'FINISH'  => (float) ( $lot['finish'] ?? ( $lot['amount'] ?? 0 ) ),
		'START'   => (float) ( $lot['start'] ?? 0 ),
		'amount'  => (float) ( $lot['amount'] ?? 0 ),
		'ENG_V'   => (int) ( $lot['eng_v'] ?? 0 ),
		'TIME'    => (string) ( $lot['time'] ?? '' ),
		'YEAR'    => (int) ( $lot['year'] ?? 0 ),
		'volume'  => (string) ( $lot['volume'] ?? '' ),
		'market'  => $market,
		'table'   => (string) ( $lot['table'] ?? '' ),
	);
	if ( $row['ENG_V'] <= 0 ) {
		$row['ENG_V'] = (int) preg_replace( '/\D+/', '', (string) ( $lot['volume'] ?? '' ) );
	}
	return ai_price_cnkr_calc( $row, $with_customs, $market );
}
