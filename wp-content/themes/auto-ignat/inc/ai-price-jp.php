<?php
/**
 * Калькулятор «под ключ» для Японии (JP / main|stats).
 *
 * Пошлина + утильсбор (физлицо): AJ Calculator-API (шаблон «пошлина+утиль» в /tools → Другое).
 * Остальные статьи — формулы брокера на бэкенде.
 *
 * @package auto-ignat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Слот «Другое» в /tools после загрузки tax_ru_only.xlsx. */
if ( ! defined( 'AI_CALCOS_SLOT' ) ) {
	define( 'AI_CALCOS_SLOT', 8 );
}
/** Суффикс токена calcos задаётся в wp-config (AI_CALCOS_TOKEN_SUFFIX). */
if ( ! defined( 'AI_CALCOS_TOKEN_SUFFIX' ) ) {
	// Суффикс только из wp-config.php на проде. В публичный git не кладём.
	define( 'AI_CALCOS_TOKEN_SUFFIX', '' );
}

/** Наценка к курсу ЦБ. */
function ai_price_jp_fx_markup(): float {
	return 1.05;
}

/** Курс валюты → ₽ с наценкой +5%. */
function ai_price_jp_fx( string $ccy ): float {
	return ai_auc_fx( strtoupper( $ccy ) ) * ai_price_jp_fx_markup();
}

/** ¥/$ → ₽ целым числом. */
function ai_price_jp_to_rub( float $amount, string $ccy ): int {
	return (int) round( $amount * ai_price_jp_fx( $ccy ) );
}

/**
 * Токен Calculator-API для слота Другое. Кэш 6 ч.
 * /api/calcos отдаёт короткий hash; полный путь = hash + '-' + AI_CALCOS_TOKEN_SUFFIX.
 */
function ai_price_calcos_token(): string {
	static $memo = null;
	if ( null !== $memo ) {
		return $memo;
	}
	$hit = get_transient( 'ai_calcos_token_v2' );
	if ( is_string( $hit ) && '' !== $hit ) {
		$memo = $hit;
		return $memo;
	}
	$url  = 'http://' . AI_AUC_SERVER . '/api/calcos?ip=' . rawurlencode( ai_auc_client_ip() )
		. '&code=' . rawurlencode( AI_AUC_CODE );
	$body = ai_auc_http( $url, 8 );
	$data = $body ? json_decode( $body, true ) : null;
	$slot = (string) AI_CALCOS_SLOT;
	$hash = '';
	if ( is_array( $data ) && ! empty( $data[ $slot ][0] ) ) {
		$hash = (string) $data[ $slot ][0];
	}
	$token = '';
	if ( $hash ) {
		$token = str_contains( $hash, '-' ) ? $hash : ( $hash . '-' . AI_CALCOS_TOKEN_SUFFIX );
	}
	if ( $token ) {
		set_transient( 'ai_calcos_token_v2', $token, 6 * HOUR_IN_SECONDS );
	}
	$memo = $token;
	return $memo;
}

/**
 * Код топлива для calcos (лист «Топливо#fuel» в шаблоне):
 * 1 бензин, 2 дизель, 3 электро, 4 бензиновый гибрид, 5 дизельный гибрид.
 */
function ai_price_calcos_fuel( string $time ): int {
	$code = strtoupper( trim( explode( ',', $time )[0] ) );
	return match ( $code ) {
		'D' => 2,
		'E' => 3,
		'H', 'HE' => 4,
		default => 1, // P / пусто / прочее → бензин.
	};
}

/**
 * Нормализованные аргументы Calcos JP. null — запрос не нужен.
 *
 * @param array{price?:float,year?:int,volume?:int,power?:int,fuel?:int} $args
 * @return array{price:int,year:int,volume:int,power:int,fuel:int}|null
 */
function ai_price_calcos_norm( array $args ): ?array {
	$price  = max( 0, (int) round( (float) ( $args['price'] ?? 0 ) ) );
	$year   = (int) ( $args['year'] ?? 0 );
	$volume = max( 0, (int) ( $args['volume'] ?? 0 ) );
	$power  = max( 0, (int) ( $args['power'] ?? 0 ) );
	$fuel   = (int) ( $args['fuel'] ?? 1 );
	if ( $price <= 0 || $year < 1985 ) {
		return null;
	}
	return array(
		'price'  => $price,
		'year'   => $year,
		'volume' => $volume,
		'power'  => $power,
		'fuel'   => $fuel,
	);
}

function ai_price_calcos_cache_key( string $token, array $norm ): string {
	return 'ai_calcos_v3_' . md5(
		wp_json_encode(
			array( $token, $norm['price'], $norm['year'], $norm['volume'], $norm['power'], $norm['fuel'] )
		)
	);
}

function ai_price_calcos_url( string $token, array $norm ): string {
	$query = http_build_query(
		array(
			'verbose'  => 1,
			'price'    => $norm['price'],
			'year'     => $norm['year'],
			'passing'  => 0,
			'power'    => $norm['power'],
			'volume'   => $norm['volume'],
			'fuel'     => $norm['fuel'],
			'tax_mode' => 2, // физ.лицо (0=минимальная, 1=юр.лицо).
		)
	);
	return 'https://calcos.ajes.com/api/' . $token . '?' . $query;
}

/**
 * fiz / fiz_info в долларах → ₽ по USDRUB_cbrf из ответа, без +5%.
 *
 * @return array{duty:int,util:int,fiz:int}|null
 */
function ai_price_calcos_parse( string $body ): ?array {
	if ( ! str_contains( $body, '<fiz' ) ) {
		return null;
	}
	$usd = 0.0;
	if ( preg_match( '/USDRUB_cbrf:([0-9]+(?:\.[0-9]+)?)/', $body, $m ) ) {
		$usd = (float) $m[1];
	}
	if ( $usd <= 0 ) {
		$usd = ai_auc_fx( 'USD' );
	}
	if ( $usd <= 0 ) {
		return null;
	}

	$duty_usd = 0.0;
	$util_usd = 0.0;
	$fiz_usd  = 0.0;
	if ( preg_match( '/<fiz_info>([^<]+)<\/fiz_info>/', $body, $m ) ) {
		$parts    = array_map( 'trim', explode( '+', $m[1] ) );
		$duty_usd = (float) ( $parts[0] ?? 0 );
		$util_usd = (float) ( $parts[1] ?? 0 );
	}
	if ( preg_match( '/<fiz>([^<]+)<\/fiz>/', $body, $m ) ) {
		$fiz_usd = (float) $m[1];
	}
	if ( $fiz_usd <= 0 && ( $duty_usd > 0 || $util_usd > 0 ) ) {
		$fiz_usd = $duty_usd + $util_usd;
	}
	if ( $fiz_usd <= 0 ) {
		return null;
	}
	if ( 0.0 === $duty_usd && 0.0 === $util_usd ) {
		$duty_usd = $fiz_usd;
	}

	return array(
		'duty' => (int) round( $duty_usd * $usd ),
		'util' => (int) round( $util_usd * $usd ),
		'fiz'  => (int) round( $fiz_usd * $usd ),
	);
}

/**
 * Пошлина + утиль (физлицо) через calcos.ajes.com.
 * В шаблоне #custom лежит в колонке USD — fiz/fiz_info приходят в $, переводим в ₽ по USDRUB_cbrf.
 *
 * @param array{price:float,year:int,volume:int,power:int,fuel:int} $args
 * @return array{duty:int,util:int,fiz:int}|null
 */
function ai_price_calcos_duty_util( array $args ): ?array {
	$token = ai_price_calcos_token();
	$norm  = ai_price_calcos_norm( $args );
	if ( '' === $token || ! $norm ) {
		return null;
	}

	$cache_key = ai_price_calcos_cache_key( $token, $norm );
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) && isset( $cached['duty'], $cached['util'] ) ) {
		return $cached;
	}

	$url  = ai_price_calcos_url( $token, $norm );
	$body = ai_auc_http( $url, 15 );
	if ( ! $body || ! str_contains( $body, '<fiz' ) ) {
		$body = ai_auc_http( 'http://calcos.ajes.com/api/' . $token . '?' . parse_url( $url, PHP_URL_QUERY ), 15 );
	}
	$out = $body ? ai_price_calcos_parse( $body ) : null;
	if ( ! $out ) {
		return null;
	}
	set_transient( $cache_key, $out, 6 * HOUR_IN_SECONDS );
	return $out;
}

/**
 * Аргументы Calcos из строки аукциона — те же, что в полном расчёте.
 *
 * @param array<string,mixed> $r
 * @return array{price:float,year:int,volume:int,power:int,fuel:int}|null
 */
function ai_price_jp_customs_args( array $r ): ?array {
	$base = ai_price_jp_lot_base( $r );
	$lot  = (float) $base['amount'];
	$year = (int) ( $r['YEAR'] ?? $r['year'] ?? 0 );
	if ( $lot <= 0 || $year < 1985 ) {
		return null;
	}
	$vol  = (int) ( $r['ENG_V'] ?? $r['eng_v'] ?? 0 );
	$pw   = (int) preg_replace( '/\D+/', '', (string) ( $r['PW'] ?? $r['hp'] ?? '0' ) );
	$time = (string) ( $r['TIME'] ?? $r['time'] ?? '' );
	return array(
		'price'  => $lot,
		'year'   => $year,
		'volume' => $vol,
		'power'  => $pw,
		'fuel'   => ai_price_calcos_fuel( $time ),
	);
}

/**
 * Пакетный прогрев Calcos для витрины. Дальше расчёт читает тот же transient, что карточка.
 *
 * @param array<int,mixed> $rows
 */
function ai_price_jp_customs_prefetch( array $rows ): void {
	$token = ai_price_calcos_token();
	if ( '' === $token || ! function_exists( 'ai_auc_http_multi' ) ) {
		return;
	}
	$pending = array();
	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$args = ai_price_jp_customs_args( $row );
		$norm = $args ? ai_price_calcos_norm( $args ) : null;
		if ( ! $norm ) {
			continue;
		}
		$key = ai_price_calcos_cache_key( $token, $norm );
		$hit = get_transient( $key );
		if ( is_array( $hit ) && isset( $hit['duty'], $hit['util'] ) ) {
			continue;
		}
		$pending[ $key ] = ai_price_calcos_url( $token, $norm );
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
 * Белый список MHV — только для надбавки +200 000 ¥.
 *
 * @return list<string>
 */
function ai_price_jp_mhv_chassis(): array {
	return array(
		'ZF1', 'ZF2', 'GP4', 'ZE3',
		'MJ55S', 'MM53S', 'MJ44S', 'MS41S', 'MS52S', 'MS92S', 'MM42S',
		'B38A', 'B35A', 'MB36S', 'MB37S', 'B34W', 'B35W', 'B37W', 'B38W', 'B34A', 'B37A',
		'B44W', 'B45W', 'B47W', 'B48W', 'B44A', 'B45A', 'B47A', 'B48A',
		'HA97S', 'MR41S', 'MS52S', 'MR92S', 'FF21S', 'MA36S', 'MA37S',
		'MK53S', 'MK42S', 'ZD53S', 'ZC53S', 'MH55S', 'MH44S', 'MH95S', 'MX91S', 'MN71S',
		'5AA-177084', '5AA-247084', '5AA-205078', 'DAA-205277', 'DAA-205277C',
		'5AA-205277', 'DAA-205077', '4AA-213077C', '4AA-213078C', '4AA-213277C',
		'4AA-213278C', '5AA-118384M', '4AA-247784M', '4AA-247684M',
	);
}

function ai_price_jp_is_mhv( string $chassis ): bool {
	$c = strtoupper( preg_replace( '/\s+/', '', trim( $chassis ) ) );
	if ( '' === $c ) {
		return false;
	}
	foreach ( ai_price_jp_mhv_chassis() as $code ) {
		if ( $c === strtoupper( $code ) ) {
			return true;
		}
	}
	return false;
}

/** Допкомиссия от цены лота (¥). Верх границы не включительно. */
function ai_price_jp_lot_fee( float $lot_jpy ): int {
	$p = (int) floor( $lot_jpy );
	if ( $p <= 999999 ) {
		return 0;
	}
	if ( $p < 1500000 ) {
		return 10000;
	}
	if ( $p < 2000000 ) {
		return 20000;
	}
	if ( $p < 3000000 ) {
		return 40000;
	}
	if ( $p < 4000000 ) {
		return 50000;
	}
	if ( $p < 5000000 ) {
		return 70000;
	}
	return 80000;
}

/** Надбавка «дорогие» лоты (¥). Верх не включительно; от 20 млн — фикс. */
function ai_price_jp_premium_fee( float $lot_jpy ): int {
	$p = (int) floor( $lot_jpy );
	if ( $p < 3000000 ) {
		return 0;
	}
	if ( $p < 5000000 ) {
		return 250000;
	}
	if ( $p < 8000000 ) {
		return 350000;
	}
	if ( $p < 11000000 ) {
		return 500000;
	}
	if ( $p < 13000000 ) {
		return 600000;
	}
	if ( $p < 14000000 ) {
		return 800000;
	}
	if ( $p < 15000000 ) {
		return 850000;
	}
	if ( $p < 17000000 ) {
		return 900000;
	}
	if ( $p < 20000000 ) {
		return 1000000;
	}
	return 1200000;
}

/**
 * Надбавка +200 000 ¥: SANCTION=1, или объём >1900, или цена >6 млн, или MHV до 3 млн.
 *
 * @param array<string,mixed> $r
 */
function ai_price_jp_special_fee( float $lot_jpy, array $r ): int {
	$eng      = (int) ( $r['ENG_V'] ?? $r['eng_v'] ?? 0 );
	$sanction = (string) ( $r['SANCTION'] ?? $r['sanction'] ?? '0' );
	$chassis  = (string) ( $r['KUZOV'] ?? $r['chassis'] ?? '' );

	if ( '1' === $sanction || 1 === (int) $sanction ) {
		return 200000;
	}
	if ( $eng > 1900 ) {
		return 200000;
	}
	if ( $lot_jpy > 6000000 ) {
		return 200000;
	}
	if ( ai_price_jp_is_mhv( $chassis ) && $lot_jpy < 3000000 ) {
		return 200000;
	}
	return 0;
}

/**
 * База лота: AVG_PRICE, иначе START.
 *
 * @param array<string,mixed> $r
 * @return array{amount:float,basis:string}
 */
function ai_price_jp_lot_base( array $r ): array {
	$avg   = (float) ( $r['AVG_PRICE'] ?? $r['avg'] ?? 0 );
	$start = (float) ( $r['START'] ?? $r['start'] ?? 0 );
	if ( $avg > 0 ) {
		return array(
			'amount' => $avg,
			'basis'  => 'avg',
		);
	}
	return array(
		'amount' => $start,
		'basis'  => 'start',
	);
}

/**
 * Полный расчёт JP.
 *
 * @param array<string,mixed> $r Строка API или mapped lot.
 * @return array<string,mixed>|null
 */
function ai_price_jp_calc( array $r, bool $with_customs = true ): ?array {
	$base = ai_price_jp_lot_base( $r );
	$lot  = (float) $base['amount'];
	if ( $lot <= 0 ) {
		return null;
	}

	$fee_lot     = ai_price_jp_lot_fee( $lot );
	$fee_premium = ai_price_jp_premium_fee( $lot );
	$fee_special = ai_price_jp_special_fee( $lot, $r );

	$jp_delivery = 95000;
	$freight_usd = 250.0;

	$broker_rub = 95000;
	$commission = 25000;
	$bank_rub   = 3000;
	$hidden_rub = 10000;
	$reg_rub    = 15000;

	$duty_rub = null;
	$util_rub = null;
	if ( $with_customs ) {
		$customs_args = ai_price_jp_customs_args( $r );
		$customs      = $customs_args ? ai_price_calcos_duty_util( $customs_args ) : null;
		if ( $customs ) {
			$duty_rub = (int) $customs['duty'];
			$util_rub = (int) $customs['util'];
		}
	}

	$lot_rub      = ai_price_jp_to_rub( $lot, 'JPY' );
	$fee1_rub     = ai_price_jp_to_rub( (float) $fee_lot, 'JPY' );
	$fee2_rub     = ai_price_jp_to_rub( (float) $fee_premium, 'JPY' );
	$fee3_rub     = ai_price_jp_to_rub( (float) $fee_special, 'JPY' );
	$delivery_rub = ai_price_jp_to_rub( (float) $jp_delivery, 'JPY' );
	$freight_rub  = ai_price_jp_to_rub( $freight_usd, 'USD' );

	$lines   = array();
	$lines[] = array(
		'key'        => 'lot',
		'label'      => 'avg' === $base['basis'] ? 'Средняя цена лота' : 'Стартовая цена лота',
		'amount_rub' => $lot_rub,
		'note'       => 'start' === $base['basis'] ? 'средней нет — берём стартовую' : '',
	);
	if ( $fee_lot > 0 ) {
		$lines[] = array(
			'key'        => 'lot_fee',
			'label'      => 'Допкомиссия от цены лота',
			'amount_rub' => $fee1_rub,
			'note'       => '',
		);
	}
	if ( $fee_premium > 0 ) {
		$lines[] = array(
			'key'        => 'premium',
			'label'      => 'Надбавка за дорогой лот',
			'amount_rub' => $fee2_rub,
			'note'       => '',
		);
	}
	if ( $fee_special > 0 ) {
		$lines[] = array(
			'key'        => 'special',
			'label'      => 'Надбавка (санкции / объём / MHV)',
			'amount_rub' => $fee3_rub,
			'note'       => '',
		);
	}
	$lines[] = array(
		'key'        => 'jp_delivery',
		'label'      => 'Доставка по Японии',
		'amount_rub' => $delivery_rub,
		'note'       => '',
	);
	$lines[] = array(
		'key'        => 'freight',
		'label'      => 'Фрахт до Владивостока',
		'amount_rub' => $freight_rub,
		'note'       => '',
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

	$subtotal = $lot_rub + $fee1_rub + $fee2_rub + $fee3_rub + $delivery_rub
		+ $freight_rub + $broker_rub + $commission + $bank_rub + $hidden_rub;
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
		'lot_jpy'         => $lot,
		'lot_basis'       => $base['basis'],
		'fx_jpy'          => ai_price_jp_fx( 'JPY' ),
		'fx_usd'          => ai_price_jp_fx( 'USD' ),
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
function ai_price_jp_from_lot( array $lot, bool $with_customs = true ): ?array {
	if ( ( $lot['market'] ?? '' ) !== 'JP' ) {
		return null;
	}
	$row = array(
		'AVG_PRICE' => (float) ( $lot['avg'] ?? 0 ),
		'START'     => (float) ( $lot['start'] ?? ( ( $lot['avg'] ?? 0 ) <= 0 ? ( $lot['amount'] ?? 0 ) : 0 ) ),
		'ENG_V'     => (int) ( $lot['eng_v'] ?? 0 ),
		'KUZOV'     => (string) ( $lot['chassis'] ?? '' ),
		'SANCTION'  => (string) ( $lot['sanction'] ?? '0' ),
		'TIME'      => (string) ( $lot['time'] ?? '' ),
		'YEAR'      => (int) ( $lot['year'] ?? 0 ),
		'PW'        => (string) ( $lot['hp'] ?? '' ),
	);
	if ( $row['ENG_V'] <= 0 ) {
		$row['ENG_V'] = (int) preg_replace( '/\D+/', '', (string) ( $lot['volume'] ?? '' ) );
	}
	return ai_price_jp_calc( $row, $with_customs );
}
