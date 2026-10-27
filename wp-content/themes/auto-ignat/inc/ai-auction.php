<?php
/**
 * Клиент API автоаукционов AJ System / avto.jp.
 *
 * Таблицы: main/stats (Япония, JPY), korea (Корея, KRW), china (Китай, CNY).
 * Эталон: https://ajes.com/api/search (v3.1) и sql2json.
 * Правила: не выгружаем все лоты — каждый показ = SQL к API;
 * кэш справочников, счётчиков и карточек; на витрине — stale-while-revalidate.
 *
 * @package auto-ignat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'AI_AUC_CODE' ) ) {
	define( 'AI_AUC_CODE', 'IGNbVfReupSvg' );
}
if ( ! defined( 'AI_AUC_SERVER' ) ) {
	define( 'AI_AUC_SERVER', '87.242.72.57' ); // RU CDN.
}
if ( ! defined( 'AI_AUC_JPY_FALLBACK' ) ) {
	define( 'AI_AUC_JPY_FALLBACK', 0.55 );
}
if ( ! defined( 'AI_AUC_KRW_FALLBACK' ) ) {
	define( 'AI_AUC_KRW_FALLBACK', 0.065 );
}
if ( ! defined( 'AI_AUC_CNY_FALLBACK' ) ) {
	define( 'AI_AUC_CNY_FALLBACK', 11.5 );
}
if ( ! defined( 'AI_AUC_USD_FALLBACK' ) ) {
	define( 'AI_AUC_USD_FALLBACK', 90.0 );
}
if ( ! defined( 'AI_AUC_IP' ) ) {
	define( 'AI_AUC_IP', '8.1.1.1' ); // стабильный ip= для лицензии API.
}
if ( ! defined( 'AI_AUC_STALE_TTL' ) ) {
	define( 'AI_AUC_STALE_TTL', 6 * HOUR_IN_SECONDS );
}

/** HTTP GET с таймаутом; возвращает тело или null. */
function ai_auc_http( string $url, int $timeout = 20 ): ?string {
	$res = wp_remote_get(
		$url,
		array(
			'timeout'    => $timeout,
			'user-agent' => 'auto-ignat.ru (+auction integration)',
		)
	);
	if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
		return null;
	}
	$body = wp_remote_retrieve_body( $res );
	return '' !== $body ? $body : null;
}

/**
 * Параллельные GET. Ключи ответа совпадают с ключами $urls.
 *
 * @param array<string,string> $urls
 * @return array<string,?string>
 */
function ai_auc_http_multi( array $urls, int $timeout = 8 ): array {
	$out = array();
	if ( ! $urls ) {
		return $out;
	}
	if ( ! function_exists( 'curl_multi_init' ) ) {
		foreach ( $urls as $key => $url ) {
			$out[ $key ] = ai_auc_http( (string) $url, $timeout );
		}
		return $out;
	}
	$mh = curl_multi_init();
	if ( false === $mh ) {
		foreach ( $urls as $key => $url ) {
			$out[ $key ] = ai_auc_http( (string) $url, $timeout );
		}
		return $out;
	}
	$handles = array();
	foreach ( $urls as $key => $url ) {
		$ch = curl_init( (string) $url );
		if ( false === $ch ) {
			$out[ $key ] = null;
			continue;
		}
		curl_setopt_array(
			$ch,
			array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT        => $timeout,
				CURLOPT_CONNECTTIMEOUT => 4,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_USERAGENT      => 'auto-ignat.ru (+auction integration)',
			)
		);
		curl_multi_add_handle( $mh, $ch );
		$handles[ $key ] = $ch;
	}
	$running = 0;
	do {
		$status = curl_multi_exec( $mh, $running );
		if ( CURLM_OK !== $status ) {
			break;
		}
		if ( $running ) {
			curl_multi_select( $mh, 1.0 );
		}
	} while ( $running );
	foreach ( $handles as $key => $ch ) {
		$code          = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$body          = curl_multi_getcontent( $ch );
		$out[ $key ]   = ( 200 === $code && is_string( $body ) && '' !== $body ) ? $body : null;
		curl_multi_remove_handle( $mh, $ch );
		curl_close( $ch );
	}
	curl_multi_close( $mh );
	return $out;
}

/** 4 с на витрине, 12 с на stats, 20 с на cron / прогреве. */
function ai_auc_http_timeout( string $sql = '' ): int {
	if ( ( defined( 'AI_AUC_WARMING' ) && AI_AUC_WARMING ) || wp_doing_cron() ) {
		return 20;
	}
	if ( $sql && preg_match( '/\bfrom\s+stats\b/i', $sql ) ) {
		return 12;
	}
	return 4;
}

function ai_auc_is_warming(): bool {
	return ( defined( 'AI_AUC_WARMING' ) && AI_AUC_WARMING ) || wp_doing_cron();
}

/** IP для параметра API. Посетительский IP часть провайдеров режет — держим лицензионный. */
function ai_auc_client_ip(): string {
	return AI_AUC_IP;
}

/** Допустимые таблицы SQL. */
function ai_auc_tables(): array {
	return array( 'main', 'stats', 'korea', 'china' );
}

/** jp / kr / cn из slug или GET. */
function ai_auc_normalize_market( string $market ): string {
	$m = strtolower( trim( $market ) );
	if ( in_array( $m, array( 'kr', 'korea', 'корея' ), true ) ) {
		return 'kr';
	}
	if ( in_array( $m, array( 'cn', 'china', 'китай' ), true ) ) {
		return 'cn';
	}
	return 'jp';
}

/** Рынок витрины: GET country, иначе аргумент. */
function ai_auc_request_market( string $fallback = '' ): string {
	$country = isset( $_GET['country'] ) ? sanitize_text_field( wp_unslash( $_GET['country'] ) ) : $fallback; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return ai_auc_normalize_market( (string) $country );
}

/** Таблица API по рынку. */
function ai_auc_market_table( string $market, bool $sold = false ): string {
	$m = ai_auc_normalize_market( $market );
	if ( 'kr' === $m ) {
		return 'korea';
	}
	if ( 'cn' === $m ) {
		return 'china';
	}
	return $sold ? 'stats' : 'main';
}

function ai_auc_normalize_table( string $table ): string {
	return in_array( $table, ai_auc_tables(), true ) ? $table : 'main';
}

function ai_auc_table_market( string $table ): string {
	return array(
		'korea' => 'KR',
		'china' => 'CN',
	)[ $table ] ?? 'JP';
}

function ai_auc_table_ccy( string $table ): string {
	return array(
		'korea' => 'KRW',
		'china' => 'CNY',
	)[ $table ] ?? 'JPY';
}

function ai_auc_is_stock_table( string $table ): bool {
	return in_array( $table, array( 'korea', 'china' ), true );
}

/** Текст API: сущности и пробелы. */
function ai_auc_text( string $s ): string {
	$s = html_entity_decode( $s, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$s = wp_strip_all_tags( $s );
	return trim( preg_replace( '/\s+/', ' ', $s ) );
}

/**
 * Распаковать transient: новый пакет {__ai, rows, ts} или старый сырой массив строк.
 *
 * @return array{rows: array, ts: int}|null
 */
function ai_auc_unwrap( $hit ): ?array {
	if ( ! is_array( $hit ) ) {
		return null;
	}
	if ( ! empty( $hit['__ai'] ) && isset( $hit['rows'] ) && is_array( $hit['rows'] ) ) {
		return array(
			'rows' => $hit['rows'],
			'ts'   => (int) ( $hit['ts'] ?? 0 ),
		);
	}
	return array(
		'rows' => $hit,
		'ts'   => 0,
	);
}

/** Фоновый refresh одного SQL — один lock, без stampede. */
function ai_auc_schedule_refresh( string $sql, int $ttl ): void {
	$lock = 'ai_auc_lk_' . md5( $sql );
	if ( get_transient( $lock ) ) {
		return;
	}
	set_transient( $lock, 1, 2 * MINUTE_IN_SECONDS );
	if ( ! wp_next_scheduled( 'ai_auc_refresh_sql', array( $sql, $ttl ) ) ) {
		wp_schedule_single_event( time(), 'ai_auc_refresh_sql', array( $sql, $ttl ) );
	}
}

/**
 * SQL-запрос к API → массив строк (JSON) или null при ошибке/таймауте.
 * Пустой ответ не кэшируем, чтобы 404 и сбои не залипали.
 * Свежие данные отдаём сразу; устаревшие — тоже сразу, refresh в фоне.
 */
function ai_auc_api( string $sql, int $ttl = 1800, bool $refresh = false ): ?array {
	$sql = trim( preg_replace( '/\s+/', ' ', $sql ) );
	$key = 'ai_auc_' . md5( $sql );
	static $memo = array();
	if ( ! $refresh && isset( $memo[ $key ] ) ) {
		return $memo[ $key ];
	}
	$pack  = ai_auc_unwrap( get_transient( $key ) );
	$age   = $pack ? ( time() - (int) $pack['ts'] ) : PHP_INT_MAX;
	$fresh = $pack && $age < $ttl;
	if ( ai_auc_is_warming() ) {
		$refresh = true;
	}
	if ( ! $refresh && $pack && $fresh ) {
		$memo[ $key ] = $pack['rows'];
		return $pack['rows'];
	}
	if ( ! $refresh && $pack ) {
		$memo[ $key ] = $pack['rows'];
		ai_auc_schedule_refresh( $sql, $ttl );
		return $pack['rows'];
	}

	$url  = 'http://' . AI_AUC_SERVER . '/api/?ip=' . rawurlencode( ai_auc_client_ip() )
		. '&json&code=' . AI_AUC_CODE
		. '&sql=' . rawurlencode( $sql );
	$body = ai_auc_http( $url, ai_auc_http_timeout( $sql ) );
	if ( null === $body ) {
		return $pack ? $pack['rows'] : null;
	}
	$rows = json_decode( $body, true );
	if ( ! is_array( $rows ) || isset( $rows['error'] ) ) {
		return $pack ? $pack['rows'] : null;
	}
	if ( $rows ) {
		set_transient(
			$key,
			array(
				'__ai' => 1,
				'rows' => $rows,
				'ts'   => time(),
			),
			AI_AUC_STALE_TTL
		);
		$memo[ $key ] = $rows;
	}
	return $rows;
}

add_action(
	'ai_auc_refresh_sql',
	static function ( $sql, $ttl ): void {
		if ( ! defined( 'AI_AUC_WARMING' ) ) {
			define( 'AI_AUC_WARMING', true );
		}
		ai_auc_api( (string) $sql, (int) $ttl, true );
	},
	10,
	2
);

/** Безопасная строка для SQL (латиница/цифры/пробел, до 64 знаков). */
function ai_auc_sql_str( string $s ): string {
	$s = preg_replace( '/[^a-zA-Z0-9 +\-_.]/', '', $s );
	return substr( trim( (string) $s ), 0, 64 );
}

/** Число с пробелами: 2140000 → «2 140 000». */
function ai_auc_num( $n ): string {
	return number_format( (int) round( (float) $n ), 0, ',', ' ' );
}

/** Курсы ЦБ + JPY с панели auc.autoignat.ru. */
function ai_auc_fx_fetch(): array {
	$t     = min( 8, ai_auc_http_timeout() );
	$rates = array();

	// Курсы AJ (раз в час), приоритет как советовал Михаил.
	$body = ai_auc_http( 'http://auc.autoignat.ru/currency', min( 5, $t ) );
	$data = $body ? json_decode( $body, true ) : null;
	if ( is_array( $data ) ) {
		$map = array(
			'JPY' => 'jpy',
			'USD' => 'usd',
			'EUR' => 'eur',
			'KRW' => 'krw',
			'CNY' => 'cny',
		);
		foreach ( $map as $code => $key ) {
			if ( ! empty( $data[ $key ] ) && (float) $data[ $key ] > 0 ) {
				// jpy/krw в ответе — за 100 единиц валюты.
				$val = (float) $data[ $key ];
				$rates[ $code ] = in_array( $code, array( 'JPY', 'KRW' ), true ) ? $val / 100 : $val;
			}
		}
	}

	$body = ai_auc_http( 'https://www.cbr-xml-daily.ru/daily_json.js', $t );
	$data = $body ? json_decode( $body, true ) : null;
	foreach ( array( 'JPY', 'KRW', 'CNY', 'USD' ) as $code ) {
		if ( ! empty( $rates[ $code ] ) || empty( $data['Valute'][ $code ]['Value'] ) ) {
			continue;
		}
		$nom = (float) ( $data['Valute'][ $code ]['Nominal'] ?: 1 );
		if ( $nom > 0 ) {
			$rates[ $code ] = (float) $data['Valute'][ $code ]['Value'] / $nom;
		}
	}
	return $rates;
}

function ai_auc_fx_fallback( string $code ): float {
	return array(
		'JPY' => AI_AUC_JPY_FALLBACK,
		'KRW' => AI_AUC_KRW_FALLBACK,
		'CNY' => AI_AUC_CNY_FALLBACK,
		'USD' => AI_AUC_USD_FALLBACK,
	)[ $code ] ?? 0.0;
}

/**
 * Курс валюты → ₽. На витрине любой сохранённый курс; сеть — только cron/прогрев.
 *
 * @return array<string,float>
 */
function ai_auc_fx_rates(): array {
	static $memo = null;
	if ( null !== $memo ) {
		return $memo;
	}
	$hit   = get_transient( 'ai_auc_fx_rates' );
	$rates = array();
	$ts    = 0;
	if ( is_array( $hit ) && ! empty( $hit['rates'] ) && is_array( $hit['rates'] ) ) {
		$rates = array_map( 'floatval', $hit['rates'] );
		$ts    = (int) ( $hit['ts'] ?? 0 );
	} else {
		$old = get_transient( 'ai_auc_jpy_rate' );
		if ( is_array( $old ) && ! empty( $old['rate'] ) ) {
			$rates['JPY'] = (float) $old['rate'];
			$ts           = (int) ( $old['ts'] ?? 0 );
		} elseif ( is_numeric( $old ) && (float) $old > 0 ) {
			$rates['JPY'] = (float) $old;
		}
	}
	$fresh   = $rates && ( time() - $ts ) < HOUR_IN_SECONDS;
	$warming = ai_auc_is_warming();
	if ( $rates && ( $fresh || ! $warming ) ) {
		$memo = $rates;
		return $memo;
	}
	$fetched = ai_auc_fx_fetch();
	if ( $fetched ) {
		$rates = array_merge( $rates, $fetched );
		set_transient(
			'ai_auc_fx_rates',
			array(
				'rates' => $rates,
				'ts'    => time(),
			),
			AI_AUC_STALE_TTL
		);
		if ( ! empty( $rates['JPY'] ) ) {
			set_transient(
				'ai_auc_jpy_rate',
				array(
					'rate' => $rates['JPY'],
					'ts'   => time(),
				),
				AI_AUC_STALE_TTL
			);
		}
	}
	$memo = $rates;
	return $memo;
}

function ai_auc_fx( string $code ): float {
	$code  = strtoupper( $code );
	$rates = ai_auc_fx_rates();
	$rate  = (float) ( $rates[ $code ] ?? 0 );
	if ( $rate <= 0 ) {
		$rate = ai_auc_fx_fallback( $code );
	}
	if ( 'JPY' === $code ) {
		$rate = (float) apply_filters( 'ai_auc_jpy_rate', $rate );
	}
	return $rate;
}

/** Курс JPY→RUB. */
function ai_auc_jpy_rate(): float {
	return ai_auc_fx( 'JPY' );
}

/** Сумма в ₽ (округление до сотен). */
function ai_auc_to_rub( $amount, string $ccy = 'JPY' ): int {
	return (int) ( round( ( (float) $amount ) * ai_auc_fx( $ccy ) / 100 ) * 100 );
}

/** ¥ → ₽. */
function ai_auc_rub( $jpy ): int {
	return ai_auc_to_rub( $jpy, 'JPY' );
}

function ai_auc_ccy_symbol( string $ccy ): string {
	return array(
		'JPY' => '¥',
		'KRW' => '₩',
		'CNY' => '¥',
	)[ strtoupper( $ccy ) ] ?? '';
}

/**
 * Сумма в валюте площадки (без путаницы ¥-йена / ¥-юань).
 * JP: ¥1 280 000 · CN: 168 000 ¥ CNY · KR: 28 400 000 ₩ KRW.
 */
function ai_auc_format_ccy( $amount, string $ccy ): string {
	$ccy = strtoupper( $ccy );
	$n   = ai_auc_num( (int) round( (float) $amount ) );
	if ( 'CNY' === $ccy ) {
		return $n . ' ¥ CNY';
	}
	if ( 'KRW' === $ccy ) {
		return $n . ' ₩ KRW';
	}
	if ( 'JPY' === $ccy ) {
		return '¥' . $n;
	}
	$sym = ai_auc_ccy_symbol( $ccy );
	return $sym ? ( $sym . $n ) : ( $n . ' ' . $ccy );
}

/** Краткое имя валюты для подписей (юани / воны / йены). */
function ai_auc_ccy_unit_ru( string $ccy ): string {
	return array(
		'JPY' => 'йен',
		'CNY' => 'юаней',
		'KRW' => 'вон',
	)[ strtoupper( $ccy ) ] ?? strtoupper( $ccy );
}

/** Топливо по коду TIME: P,D,E,H,HE,L,C,O. Пусто в JP = бензин. */
function ai_auc_fuel( string $time, bool $assume_petrol = true ): string {
	$map  = array(
		'P'  => 'бензин',
		'D'  => 'дизель',
		'E'  => 'электро',
		'H'  => 'гибрид',
		'HE' => 'гибрид',
		'L'  => 'газ (LPG)',
		'C'  => 'газ (CNG)',
		'O'  => 'другое',
	);
	$code = strtoupper( trim( explode( ',', $time )[0] ) );
	if ( '' === $code ) {
		return $assume_petrol ? 'бензин' : '';
	}
	return $map[ $code ] ?? $time;
}

/** Топливо из комплектации, если TIME пустой (Корея/Китай). */
function ai_auc_fuel_from_text( string $s ): string {
	$u = strtoupper( $s );
	if ( preg_match( '/\b(EV|ELECTRIC|BEV)\b/', $u ) || str_contains( $u, 'ELECTRO' ) ) {
		return 'электро';
	}
	if ( preg_match( '/\b(PHEV|HEV|HYBRID|DM-I|DMI|ERE)\b/', $u ) ) {
		return 'гибрид';
	}
	if ( preg_match( '/\b(DIESEL|CRDI)\b/', $u ) ) {
		return 'дизель';
	}
	if ( preg_match( '/\b(GASOLINE|PETROL|GDI)\b/', $u ) ) {
		return 'бензин';
	}
	return '';
}

/** КПП: AT/FAT/IA→автомат, MT/FM→механика, CVT/CA→вариатор. */
function ai_auc_gearbox( string $kpp ): string {
	$code = strtoupper( trim( explode( ',', $kpp )[0] ) );
	$map  = array(
		'AT'  => 'автомат',
		'FAT' => 'автомат',
		'IA'  => 'автомат',
		'IAT' => 'автомат',
		'FA'  => 'автомат',
		'DAT' => 'автомат',
		'4AT' => 'автомат',
		'5AT' => 'автомат',
		'6AT' => 'автомат',
		'MT'  => 'механика',
		'FM'  => 'механика',
		'F5'  => 'механика',
		'D5'  => 'механика',
		'M5'  => 'механика',
		'5MT' => 'механика',
		'6MT' => 'механика',
		'CVT' => 'вариатор',
		'CA'  => 'вариатор',
	);
	if ( isset( $map[ $code ] ) ) {
		return $map[ $code ];
	}
	if ( preg_match( '/^[DFM]\d$/', $code ) ) {
		return 'механика';
	}
	return $kpp;
}

/** Привод по PRIV. */
function ai_auc_drive( string $priv ): string {
	$code = strtoupper( trim( $priv ) );
	if ( '' === $code ) {
		return '';
	}
	if ( str_contains( $code, '4WD' ) || str_contains( $code, 'AWD' ) ) {
		return 'полный';
	}
	if ( in_array( $code, array( 'FF', 'FC' ), true ) ) {
		return 'передний';
	}
	if ( in_array( $code, array( 'FR', 'MR', 'RR', 'MIDSHIP' ), true ) ) {
		return 'задний';
	}
	return '';
}

/** Привод из комплектации, если PRIV пустой. */
function ai_auc_drive_from_text( string $s ): string {
	$u = strtoupper( $s );
	if ( preg_match( '/\b(AWD|4WD|4MATIC|XDRIVE|QUATTRO)\b/', $u ) ) {
		return 'полный';
	}
	if ( preg_match( '/\b(2WD|FWD)\b/', $u ) ) {
		return 'передний';
	}
	if ( preg_match( '/\bRWD\b/', $u ) ) {
		return 'задний';
	}
	return '';
}

/** Оценка аукциона, не комплектация. */
function ai_auc_is_rate( string $s ): bool {
	return (bool) preg_match( '/^(?:[0-5](?:\.\d)?|[RS]|RA|\*+)$/i', trim( $s ) );
}

/** Цвет: BASIC-слова API → русский. */
function ai_auc_color( string $color ): string {
	$map = array(
		'WHITE'    => 'белый',
		'PEARL'    => 'перламутр',
		'BLACK'    => 'чёрный',
		'SILVER'   => 'серебристый',
		'GRAY'     => 'серый',
		'GREY'     => 'серый',
		'BLUE'     => 'синий',
		'NAVY'     => 'тёмно-синий',
		'RED'      => 'красный',
		'WINE'     => 'бордовый',
		'MAROON'   => 'бордовый',
		'GREEN'    => 'зелёный',
		'GOLD'     => 'золотой',
		'BROWN'    => 'коричневый',
		'BEIGE'    => 'бежевый',
		'YELLOW'   => 'жёлтый',
		'ORANGE'   => 'оранжевый',
		'PURPLE'   => 'фиолетовый',
		'PINK'     => 'розовый',
		'LAVENDER' => 'сиреневый',
	);
	$up = strtoupper( trim( $color ) );
	if ( isset( $map[ $up ] ) ) {
		return $map[ $up ];
	}
	foreach ( $map as $en => $ru ) {
		if ( '' !== $up && str_contains( $up, $en ) ) {
			return $ru;
		}
	}
	return $color;
}

/** Мощность: PW бывает «52,64» → берём максимум. */
function ai_auc_hp( string $pw ): string {
	$vals = array_filter( array_map( 'intval', explode( ',', $pw ) ) );
	return $vals ? (string) max( $vals ) : '';
}

/** Title Case с короткими токенами (N BOX) и аббревиатурами марок. */
function ai_auc_pretty( string $s ): string {
	$s = trim( $s );
	if ( '' === $s ) {
		return '';
	}
	$acr   = array( 'BYD', 'BMW', 'KIA', 'GMC', 'MG', 'VW', 'GAC', 'SAIC', 'KG', 'EV' );
	$parts = preg_split( '/\s+/', $s );
	$out   = array();
	foreach ( $parts as $w ) {
		$up = strtoupper( $w );
		if ( strlen( $w ) <= 2 || in_array( $up, $acr, true ) ) {
			$out[] = $up;
		} else {
			$out[] = ucfirst( strtolower( $w ) );
		}
	}
	return implode( ' ', $out );
}

/** Площадка: Title Case без повторов слов. */
function ai_auc_pretty_place( string $s ): string {
	$s     = ai_auc_pretty( $s );
	$seen  = array();
	$out   = array();
	foreach ( preg_split( '/\s+/', $s ) as $w ) {
		$key = strtolower( $w );
		if ( isset( $seen[ $key ] ) ) {
			continue;
		}
		$seen[ $key ] = true;
		$out[]        = $w;
	}
	return implode( ' ', $out );
}

/** База картинки без &h= / &w= (три размера по регламенту). */
function ai_auc_img_base( string $src ): string {
	$src = trim( $src );
	if ( '' === $src ) {
		return '';
	}
	return (string) preg_replace( '/[&?](?:h|w)=\d+/', '', $src );
}

/** Одна картинка в 3 размерах: big / medium / small. */
function ai_auc_img_sizes( string $src ): array {
	$base = ai_auc_img_base( $src );
	if ( '' === $base ) {
		return array( 'big' => '', 'medium' => '', 'small' => '' );
	}
	return array(
		'big'    => $base,
		'medium' => $base . '&w=320',
		'small'  => $base . '&h=50',
	);
}

/** Все фото лота. Аукционный лист (первый кадр API) — в конец галереи. */
function ai_auc_gallery( string $raw ): array {
	$all = array_values( array_filter( array_map( 'trim', explode( '#', $raw ) ) ) );
	$photos = array();
	$sheet  = null;
	foreach ( $all as $i => $src ) {
		$sizes = ai_auc_img_sizes( $src );
		if ( ! $sizes['big'] ) {
			continue;
		}
		if ( 0 === $i && count( $all ) > 1 ) {
			$sizes['sheet'] = true;
			$sheet          = $sizes;
			continue;
		}
		$sizes['sheet'] = false;
		$photos[]       = $sizes;
	}
	if ( $sheet ) {
		$photos[] = $sheet;
	}
	return $photos;
}

/** Первая картинка лота в 3 размерах (для совместимости). */
function ai_auc_imgs( string $raw ): array {
	$gal = ai_auc_gallery( $raw );
	return $gal[0] ?? array( 'big' => '', 'medium' => '', 'small' => '' );
}

/** Цена лота в валюте таблицы: JP — средняя (AVG_PRICE); KR/CN — складской FINISH. */
function ai_auc_list_price( array $r, string $table = 'main' ): float {
	if ( ai_auc_is_stock_table( $table ) ) {
		$finish = (float) ( $r['FINISH'] ?? 0 );
		$start  = (float) ( $r['START'] ?? 0 );
		return $finish > 0 ? $finish : $start;
	}
	if ( 'SOLD' === strtoupper( (string) ( $r['STATUS'] ?? '' ) ) && (float) ( $r['FINISH'] ?? 0 ) > 0 ) {
		return (float) $r['FINISH'];
	}
	$avg = (float) ( $r['AVG_PRICE'] ?? 0 );
	return $avg > 0 ? $avg : (float) ( $r['START'] ?? 0 );
}

/** Цена лота JP в йенах: средняя; FINISH — только у проданных. */
function ai_auc_jpy_price( array $r ): float {
	return ai_auc_list_price( $r, 'main' );
}

/** Кузов по модели / объёму (KUZOV в API — код шасси). */
function ai_auc_body_map(): array {
	return array(
		'седан'     => array( 'CAMRY', 'CROWN', 'MARK X', 'ACCORD', 'CIVIC', 'AXELA', 'MAZDA3', 'IMPREZA', 'TEANA', 'SKYLINE', 'IS', 'ES', 'GS', 'LS', 'S-CLASS', 'E-CLASS', 'C-CLASS', 'SONATA', 'K5', 'GRANDEUR', 'G80', 'G70', 'MODEL 3', 'SEAL', 'HAN', 'OCTAVIA', 'PREFACE' ),
		'кроссовер' => array( 'HARRIER', 'RAV4', 'RAV 4', 'CX-5', 'CX-8', 'CX-30', 'CX-3', 'X-TRAIL', 'XTRAIL', 'FORESTER', 'XV', 'OUTLANDER', 'VEZEL', 'C-HR', 'CH-R', 'NX', 'RX', 'UX', 'VENUE', 'ESCAPE', 'TUCSON', 'SANTA FE', 'PALISADE', 'SPORTAGE', 'SORENTO', 'GV80', 'GV70', 'TANG', 'SONG', 'YUAN', 'MODEL Y', 'XC60', 'X6', 'X5', 'BOYUE' ),
		'пикап'     => array( 'HILUX', 'RANGER', 'NAVARA', 'TRITON', 'AMAROK', 'CANTER', 'ELF', 'DUTRO' ),
		'купе'      => array( 'GR86', '86', 'BRZ', 'MUSTANG', 'SUPRA', 'GT-R', 'GTR' ),
		'универсал' => array( 'LEVORG', 'LEGACY', 'CALDINA', 'FIELDER', 'SHUTTLE', 'WINGROAD' ),
		'минивен'   => array( 'ALPHARD', 'VELLFIRE', 'SERENA', 'NOAH', 'VOXY', 'STEP WGN', 'STEPWGN', 'ODYSSEY', 'ELGRAND', 'DELICA', 'CARNIVAL', 'STARIA' ),
		'джип'      => array( 'LAND CRUISER FJ', 'FJ CRUISER', 'LAND CRUISER', 'PRADO', 'JIMNY', 'PATROL', 'PAJERO', 'G-CLASS', 'G CLASS', 'MOHAVE', 'TELLURIDE' ),
		'хэтчбек'   => array( 'FIT', 'NOTE', 'AQUA', 'YARIS', 'VITZ', 'DEMIO', 'MAZDA2', 'GOLF', 'POLO', 'SWIFT', 'SEAGULL', 'DOLPHIN' ),
		'лифтбек'   => array( 'PRIUS', 'INSIGHT', 'SAI', 'GRACE' ),
		'открытый'  => array( 'ROADSTER', 'MX-5', 'MX5', 'S660', 'BEETLE' ),
	);
}

function ai_auc_guess_body( array $r ): string {
	$cc   = (int) ( $r['ENG_V'] ?? 0 );
	$time = strtoupper( trim( (string) ( $r['TIME'] ?? '' ) ) );
	if ( $cc > 0 && $cc <= 660 && 'E' !== $time ) {
		return 'кейкар';
	}
	$model = strtoupper( preg_replace( '/\s+/', ' ', trim( (string) ( $r['MODEL_NAME'] ?? '' ) ) ) );
	$pairs = array();
	foreach ( ai_auc_body_map() as $body => $models ) {
		foreach ( $models as $name ) {
			$pairs[] = array( $body, strtoupper( $name ) );
		}
	}
	usort(
		$pairs,
		static function ( $a, $b ) {
			return strlen( $b[1] ) <=> strlen( $a[1] );
		}
	);
	foreach ( $pairs as $pair ) {
		$name = $pair[1];
		if ( $model === $name ) {
			return $pair[0];
		}
		if ( preg_match( '/(?:^|[\s\-])' . preg_quote( $name, '/' ) . '(?:[\s\-]|$)/', $model ) ) {
			return $pair[0];
		}
	}
	return '';
}

/** Пробег: 0 → «без пробега». */
function ai_auc_km( $m ): string {
	$n = (int) $m;
	if ( $n <= 0 ) {
		return 'без пробега';
	}
	return ai_auc_num( $n ) . ' км';
}

/** Год выпуска: прячем мусор вроде 2030. */
function ai_auc_year( $y ): string {
	$y   = (int) $y;
	$max = (int) gmdate( 'Y' ) + 1;
	if ( $y < 1985 || $y > $max ) {
		return '';
	}
	return (string) $y;
}

/** Строка API → массив данных карточки темы. */
function ai_auc_map_row( array $r, bool $full = false, string $table = 'main' ): array {
	$table   = ai_auc_normalize_table( $table );
	$market  = ai_auc_table_market( $table );
	$ccy     = ai_auc_table_ccy( $table );
	$gallery = ai_auc_gallery( (string) ( $r['IMAGES'] ?? '' ) );
	$card    = $gallery[0] ?? array( 'big' => '', 'medium' => '', 'small' => '' );
	$make    = ai_auc_pretty( ai_auc_text( (string) ( $r['MARKA_NAME'] ?? '' ) ) );
	$model   = ai_auc_pretty( ai_auc_text( (string) ( $r['MODEL_NAME'] ?? '' ) ) );
	$amount  = ai_auc_list_price( $r, $table );
	$rate    = trim( (string) ( $r['RATE'] ?? '' ) );
	$grade   = ai_auc_text( (string) ( $r['GRADE'] ?? '' ) );
	$body    = ai_auc_guess_body( $r );
	$hp      = ai_auc_hp( (string) ( $r['PW'] ?? '' ) );
	$id      = (string) ( $r['ID'] ?? '' );
	$info    = $r['INFO'] ?? '';
	if ( is_array( $info ) ) {
		$info = implode( ', ', array_filter( array_map( 'strval', $info ) ) );
	}
	$info = ai_auc_text( (string) $info );

	$auction = ai_auc_pretty_place( ai_auc_text( (string) ( $r['AUCTION'] ?? '' ) ) );
	if ( '' === $auction ) {
		$auction = array(
			'KR' => 'ENCAR',
			'CN' => 'CN AUC',
		)[ $market ] ?? 'JP';
	}

	$date = (string) ( $r['AUCTION_DATE'] ?? '' );
	if ( str_starts_with( $date, '0000' ) ) {
		$date = '';
	}

	$time = (string) ( $r['TIME'] ?? '' );
	$fuel = ai_auc_fuel( $time, 'JP' === $market );
	if ( '' === $fuel ) {
		$fuel = ai_auc_fuel_from_text( $grade );
	}

	$drive = ai_auc_drive( (string) ( $r['PRIV'] ?? '' ) );
	if ( '' === $drive ) {
		$drive = ai_auc_drive_from_text( $grade . ' ' . $auction );
	}

	$volume = '';
	$eng    = $r['ENG_V'] ?? '';
	if ( '' !== $eng && null !== $eng && ! str_contains( $fuel, 'электро' ) ) {
		$volume = ai_auc_num( $eng ) . ' см³';
	}

	$lhdrive = (string) ( $r['LHDRIVE'] ?? '0' );
	if ( ai_auc_is_stock_table( $table ) ) {
		$lhdrive = '1';
	}

	$lot = array(
		'id'           => $id,
		'num'          => (string) ( $r['LOT'] ?? '' ),
		'src'          => $auction,
		'source'       => $auction,
		'market'       => $market,
		'meta'         => ai_auc_is_rate( $rate ) ? ( 'GRADE ' . $rate ) : $grade,
		'grade'        => ai_auc_is_rate( $rate ) ? $rate : '',
		'title'        => trim( $make . ' ' . $model ),
		'make'         => $make,
		'model'        => $model,
		'year'         => ai_auc_year( $r['YEAR'] ?? '' ),
		'km'           => ( $r['MILEAGE'] ?? '' ) !== '' ? ai_auc_km( $r['MILEAGE'] ) : '',
		'price'        => $amount > 0 ? ai_auc_num( ai_auc_to_rub( $amount, $ccy ) ) . ' ₽' : '',
		'origin_price' => $amount > 0 ? ai_auc_format_ccy( $amount, $ccy ) : '',
		'jpy'          => 'JPY' === $ccy ? $amount : 0,
		'amount'       => $amount,
		'start'        => (float) ( $r['START'] ?? 0 ),
		'finish'       => (float) ( $r['FINISH'] ?? 0 ),
		'avg'          => (float) ( $r['AVG_PRICE'] ?? 0 ),
		'ccy'          => $ccy,
		'place'        => 'под ключ во Владивостоке',
		'img'          => $card['medium'],
		'thumb'        => $card['small'],
		'big'          => $card['big'],
		'gallery'      => $gallery,
		'url'          => $id ? home_url( '/aukcion/' . rawurlencode( $id ) . '/' ) : '',
		'fuel'         => $fuel,
		'gearbox'      => ai_auc_gearbox( (string) ( $r['KPP'] ?? '' ) ),
		'drive'        => $drive,
		'body'         => $body,
		'chassis'      => (string) ( $r['KUZOV'] ?? '' ),
		'color'        => ai_auc_color( ai_auc_text( (string) ( $r['COLOR'] ?? '' ) ) ),
		'volume'       => $volume,
		'eng_v'        => (int) ( $r['ENG_V'] ?? 0 ),
		'hp'           => $hp !== '' ? $hp . ' л.с.' : '',
		'auction_date' => $date,
		'status'       => (string) ( $r['STATUS'] ?? '' ),
		'rate'         => ai_auc_is_rate( $rate ) ? $rate : '',
		'equip'        => $grade,
		'avg_string'   => (string) ( $r['AVG_STRING'] ?? '' ),
		'serial'       => (string) ( $r['SERIAL'] ?? '' ),
		'info'         => $info,
		'lhdrive'      => $lhdrive,
		'sanction'     => (string) ( $r['SANCTION'] ?? '0' ),
		'time'         => $time,
		'table'        => $table,
		'price_calc'   => null,
		'price_basis'  => '',
	);

	if ( 'JP' === $market && function_exists( 'ai_price_jp_calc' ) ) {
		$calc = ai_price_jp_calc( $r, $full );
		if ( $calc ) {
			$lot['price_calc']  = $calc;
			$lot['price_basis'] = (string) $calc['lot_basis'];
			$lot['price']       = $calc['total_label'];
			$lot['price_reg']   = $calc['total_reg_label'];
		}
	} elseif ( in_array( $market, array( 'CN', 'KR' ), true ) && function_exists( 'ai_price_cnkr_calc' ) ) {
		$calc = ai_price_cnkr_calc( $r, $full, $market );
		if ( $calc ) {
			$lot['price_calc']  = $calc;
			$lot['price_basis'] = (string) $calc['lot_basis'];
			$lot['price']       = $calc['total_label'];
			$lot['price_reg']   = $calc['total_reg_label'];
		}
	}

	if ( $full && ! $gallery && $lot['img'] ) {
		$lot['gallery'] = array( ai_auc_img_sizes( $lot['img'] ) );
	}

	return $lot;
}

/** Литры из фильтра (1 / 3.5) → см³; значения > 20 считаем уже см³. */
function ai_auc_cc( $v ): int {
	$v = (float) str_replace( ',', '.', (string) $v );
	if ( $v <= 0 ) {
		return 0;
	}
	return $v <= 20 ? (int) round( $v * 1000 ) : (int) round( $v );
}

/** Нормализация марки для SQL. */
function ai_auc_make_sql( string $make ): string {
	$key = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', $make ) );
	$map = array(
		'MERCEDESBENZ' => "marka_name IN ('BENZ','MERCEDES','MERCEDES-BENZ','MERCEDESBENZ','MERCEDES BENZ')",
		'MERCEDES'     => "marka_name IN ('BENZ','MERCEDES','MERCEDES-BENZ','MERCEDES BENZ')",
		'ALFAROMEO'    => "marka_name='ALFAROMEO'",
		'LANDROVER'    => "marka_name IN ('LANDROVER','LAND ROVER')",
		'GEELY'        => "marka_name IN ('GEELY','GEELY AUTO')",
		'GEELYAUTO'    => "marka_name IN ('GEELY','GEELY AUTO')",
		'LIAUTO'       => "marka_name IN ('LI','LI AUTO','LIXIANG')",
		'LI'           => "marka_name IN ('LI','LI AUTO','LIXIANG')",
		'ZEEKR'        => "marka_name IN ('ZEEKR','ZEEKR AUTO')",
		'SAICMAXUS'    => "marka_name IN ('SAIC MAXUS','MAXUS')",
		'MAXUS'        => "marka_name IN ('SAIC MAXUS','MAXUS')",
	);
	if ( isset( $map[ $key ] ) ) {
		return $map[ $key ];
	}
	$name = strtoupper( ai_auc_sql_str( $make ) );
	return '' !== $name ? "marka_name='{$name}'" : '';
}

/** Европейские марки на аукционе. Имена как в MARKA_NAME. */
function ai_auc_euro_sql(): string {
	$names = array(
		'AUDI', 'BMW', 'BENZ', 'MERCEDES', 'MERCEDES-BENZ', 'MERCEDES BENZ',
		'VOLKSWAGEN', 'VW', 'VOLVO', 'PORSCHE', 'MINI', 'JAGUAR',
		'LAND ROVER', 'LANDROVER', 'PEUGEOT', 'CITROEN', 'RENAULT', 'FIAT',
		'ALFA ROMEO', 'ALFAROMEO', 'OPEL', 'SKODA', 'SEAT', 'FERRARI',
		'LAMBORGHINI', 'BENTLEY', 'ROLLS-ROYCE', 'ROLLS ROYCE', 'MASERATI', 'SMART',
	);
	$in = array();
	foreach ( $names as $name ) {
		$safe = ai_auc_sql_str( $name );
		if ( '' !== $safe ) {
			$in[] = "'" . $safe . "'";
		}
	}
	return $in ? 'marka_name IN (' . implode( ',', $in ) . ')' : '';
}

/** Число из GROUP BY count(*): API кладёт его в TAG* или COUNT. */
function ai_auc_agg_count( array $row ): int {
	foreach ( array( 'TAG1', 'TAG0', 'COUNT', 'count' ) as $key ) {
		if ( isset( $row[ $key ] ) && is_numeric( $row[ $key ] ) ) {
			return (int) $row[ $key ];
		}
	}
	foreach ( $row as $key => $val ) {
		if ( ! is_scalar( $val ) || ! is_numeric( $val ) ) {
			continue;
		}
		$up = strtoupper( (string) $key );
		if ( str_contains( $up, 'COUNT' ) || str_starts_with( $up, 'TAG' ) ) {
			return (int) $val;
		}
	}
	return 0;
}

/**
 * Площадки, у которых CDN отдаёт заглушку 1×1 вместо фото.
 * Пока проверка не прошла — MOTA Kuruma (все 142 лота с датой-заглушкой).
 *
 * @return list<string>
 */
function ai_auc_nophoto_auctions(): array {
	$cached = get_transient( 'ai_auc_nophoto_auctions' );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	return array( 'MOTA Kuruma' );
}

/** true — заглушка, false — снимок, null — CDN не ответил. */
function ai_auc_image_is_stub( string $url ): ?bool {
	$base = ai_auc_img_base( $url );
	if ( '' === $base ) {
		return true;
	}
	$key = 'ai_auc_imgstub_' . md5( $base );
	$hit = get_transient( $key );
	if ( 0 === $hit || 1 === $hit ) {
		return (bool) $hit;
	}
	$body = ai_auc_http( $base . '&w=320', 6 );
	if ( null === $body ) {
		return null;
	}
	$stub = str_starts_with( $body, 'GIF' ) && strlen( $body ) < 200;
	set_transient( $key, $stub ? 1 : 0, $stub ? 30 * MINUTE_IN_SECONDS : 6 * HOUR_IN_SECONDS );
	return $stub;
}

/** Раз в прогрев: если фото площадки ожили, возвращаем её на витрину. */
function ai_auc_refresh_nophoto_auctions(): void {
	$blocked = array();
	foreach ( array( 'MOTA Kuruma' ) as $name ) {
		$safe = ai_auc_sql_str( $name );
		if ( '' === $safe ) {
			continue;
		}
		$rows = ai_auc_api(
			"SELECT IMAGES FROM main WHERE AUCTION LIKE '%{$safe}%' AND AUCTION_TYPE!=1 LIMIT 1",
			30 * MINUTE_IN_SECONDS,
			true
		);
		$img = '';
		if ( ! empty( $rows[0]['IMAGES'] ) ) {
			$parts = array_values( array_filter( array_map( 'trim', explode( '#', (string) $rows[0]['IMAGES'] ) ) ) );
			$img   = (string) ( $parts[1] ?? $parts[0] ?? '' );
		}
		if ( false === ai_auc_image_is_stub( $img ) ) {
			continue;
		}
		$blocked[] = $name;
	}
	set_transient( 'ai_auc_nophoto_auctions', $blocked, 6 * HOUR_IN_SECONDS );
}

/** WHERE: качество фото + фильтры витрины. */
function ai_auc_where( array $args, string $table = 'main' ): string {
	$table = ai_auc_normalize_table( $table );
	$parts = array();
	if ( in_array( $table, array( 'main', 'stats' ), true ) ) {
		$parts[] = 'AUCTION_TYPE!=1';
	}
	if ( 'hdm' === $table ) {
		$parts[] = 'IS_STAT=0';
	}

	$make = ai_auc_sql_str( (string) ( $args['make'] ?? '' ) );
	if ( $make ) {
		$sql = ai_auc_make_sql( $make );
		if ( $sql ) {
			$parts[] = $sql;
		}
	}

	$euro = (string) ( $args['euro'] ?? '' );
	if ( '1' === $euro || 'yes' === $euro ) {
		$euro_sql = ai_auc_euro_sql();
		if ( $euro_sql ) {
			$parts[] = $euro_sql;
		}
	}

	$model = ai_auc_sql_str( (string) ( $args['model'] ?? '' ) );
	if ( $model ) {
		$like    = strtoupper( $model );
		$parts[] = "model_name LIKE '%{$like}%'";
	}

	$year_from = (int) ( $args['year_from'] ?? 0 );
	$year_to   = (int) ( $args['year_to'] ?? 0 );
	if ( $year_from >= 1980 && $year_from <= 2100 ) {
		$parts[] = 'year>=' . $year_from;
	}
	if ( $year_to >= 1980 && $year_to <= 2100 ) {
		$parts[] = 'year<=' . $year_to;
	}

	$engine = (string) ( $args['engine'] ?? '' );
	if ( $engine && 'любой' !== $engine ) {
		$fuel = array(
			'бензин'  => "(TIME='' OR TIME='P')",
			'дизель'  => "TIME='D'",
			'электро' => "TIME='E'",
			'гибрид'  => "TIME IN ('H','HE')",
		);
		if ( isset( $fuel[ $engine ] ) ) {
			$parts[] = $fuel[ $engine ];
		}
	}

	$body = (string) ( $args['body'] ?? '' );
	$body = strtr( $body, array( 'минивэн' => 'минивен', 'хэтчбэк' => 'хэтчбек' ) );
	if ( $body && 'любой' !== $body ) {
		if ( 'кейкар' === $body ) {
			$parts[] = "(eng_v<=660 AND TIME!='E')";
		} elseif ( isset( ai_auc_body_map()[ $body ] ) ) {
			$likes = array();
			foreach ( ai_auc_body_map()[ $body ] as $n ) {
				$likes[] = "model_name LIKE '%" . str_replace( "'", '', $n ) . "%'";
			}
			$parts[] = '(' . implode( ' OR ', $likes ) . ')';
		} else {
			$kuzov = ai_auc_sql_str( $body );
			if ( $kuzov ) {
				$parts[] = "kuzov='{$kuzov}'";
			}
		}
	}

	$vol_from = ai_auc_cc( $args['volume_from'] ?? '' );
	$vol_to   = ai_auc_cc( $args['volume_to'] ?? '' );
	if ( 'кейкар' !== $body ) {
		if ( $vol_from > 0 ) {
			$parts[] = 'eng_v>=' . $vol_from;
		}
		if ( $vol_to > 0 ) {
			$parts[] = 'eng_v<=' . $vol_to;
		}
	}

	$hp_from = (int) ( $args['hp_from'] ?? 0 );
	$hp_to   = (int) ( $args['hp_to'] ?? 0 );
	if ( $hp_from > 0 ) {
		$parts[] = '(pw=\'\' OR pw>=' . $hp_from . ')';
	}
	if ( $hp_to > 0 ) {
		$parts[] = '(pw=\'\' OR pw<=' . $hp_to . ')';
	}

	// JP: RATE = аукционный грейд. CN/KR: RATE пустой / комплектация — фильтр обнуляет склад.
	$grade = ai_auc_sql_str( (string) ( $args['grade'] ?? '' ) );
	if ( $grade && ! ai_auc_is_stock_table( $table ) ) {
		$parts[] = "rate='{$grade}'";
	}

	$drive = (string) ( $args['drive'] ?? '' );
	if ( 'любой' === $drive ) {
		$drive = '';
	}
	if ( 'полный' === $drive ) {
		if ( ai_auc_is_stock_table( $table ) ) {
			$parts[] = "(PRIV LIKE '%4WD%' OR PRIV LIKE '%AWD%' OR GRADE LIKE '%AWD%' OR GRADE LIKE '%4WD%' OR GRADE LIKE '%4MATIC%' OR GRADE LIKE '%xDrive%')";
		} else {
			$parts[] = "(PRIV LIKE '%4WD%' OR PRIV LIKE '%AWD%')";
		}
	} elseif ( 'передний' === $drive ) {
		if ( ai_auc_is_stock_table( $table ) ) {
			$parts[] = "(PRIV='FF' OR GRADE LIKE '%2WD%' OR GRADE LIKE '%FWD%')";
		} else {
			$parts[] = "PRIV='FF'";
		}
	} elseif ( 'задний' === $drive ) {
		$parts[] = "(PRIV='FR' OR PRIV='RR' OR PRIV='MIDSHIP')";
	}

	$wheel = (string) ( $args['wheel'] ?? '' );
	if ( 'все' === $wheel || 'любое' === $wheel ) {
		$wheel = '';
	}
	if ( ! ai_auc_is_stock_table( $table ) ) {
		if ( 'левый' === $wheel ) {
			$parts[] = 'LHDRIVE=1';
		} elseif ( 'правый' === $wheel ) {
			$parts[] = 'LHDRIVE=0';
		}
	}

	// SANCTION есть только в JP. На складе CN/KR колонки нет — условие не добавляем.
	$sanction = (string) ( $args['sanction'] ?? '' );
	if ( ( '0' === $sanction || '1' === $sanction ) && ! ai_auc_is_stock_table( $table ) ) {
		$parts[] = "SANCTION='" . $sanction . "'";
	}

	$b_from = (float) str_replace( ',', '.', (string) ( $args['budget_from'] ?? '' ) );
	$b_to   = (float) str_replace( ',', '.', (string) ( $args['budget_to'] ?? '' ) );
	// Степпер на подробной форме по умолчанию показывает 1.5–3.5 и раньше не фильтровал каталог.
	$budget_placeholder = ( abs( $b_from - 1.5 ) < 0.001 && abs( $b_to - 3.5 ) < 0.001 );
	if ( ( $b_from > 0 || $b_to > 0 ) && ! $budget_placeholder ) {
		$fx = ai_auc_fx( ai_auc_table_ccy( $table ) );
		if ( $fx > 0 ) {
			$col = ai_auc_is_stock_table( $table ) ? 'FINISH' : 'AVG_PRICE';
			if ( $b_from > 0 ) {
				$parts[] = $col . '>=' . (int) floor( $b_from * 1000000 / $fx );
			}
			if ( $b_to > 0 ) {
				$parts[] = $col . '<=' . (int) ceil( $b_to * 1000000 / $fx );
			}
			$parts[] = $col . '>0';
		}
	}

	$lot_n = ai_auc_sql_str( (string) ( $args['lot'] ?? '' ) );
	if ( $lot_n ) {
		$parts[] = "lot='{$lot_n}'";
	} elseif ( empty( $args['keep_nophoto'] ) ) {
		foreach ( ai_auc_nophoto_auctions() as $name ) {
			$safe = ai_auc_sql_str( $name );
			if ( '' === $safe ) {
				continue;
			}
			$parts[] = "AUCTION NOT LIKE '%{$safe}%'";
		}
	}

	if ( ! empty( $args['sold'] ) ) {
		$parts[] = 'FINISH>0';
	}

	return $parts ? implode( ' AND ', $parts ) : '1=1';
}

/** Разбор GET-фильтра витрины. */
function ai_auc_request_args( array $extra = array() ): array {
	$q = static function ( string $key ): string {
		return isset( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	};
	$args = array(
		'make'        => $q( 'make' ),
		'model'       => $q( 'model' ),
		'engine'      => $q( 'engine' ),
		'body'        => $q( 'body' ),
		'year_from'   => $q( 'year_from' ),
		'year_to'     => $q( 'year_to' ),
		'wheel'       => $q( 'wheel' ),
		'hp_from'     => $q( 'hp_from' ),
		'hp_to'       => $q( 'hp_to' ),
		'volume_from' => $q( 'volume_from' ),
		'volume_to'   => $q( 'volume_to' ),
		'grade'       => $q( 'grade' ),
		'drive'       => $q( 'drive' ),
		'lot'         => $q( 'lot' ),
		'budget_from' => $q( 'budget_from' ),
		'budget_to'   => $q( 'budget_to' ),
		'sanction'    => $q( 'sanction' ),
		'euro'        => $q( 'euro' ),
		'paged'       => isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	);
	$sorts = array(
		'price' => 'avg_price ASC, year DESC',
		'year'  => 'year ASC, mileage ASC',
		'km'    => 'mileage ASC, year DESC',
		'fresh' => 'auction_date DESC, year DESC',
	);
	$sort = $q( 'sort' );
	if ( isset( $sorts[ $sort ] ) ) {
		$args['order'] = $sorts[ $sort ];
	}
	return array_merge( $args, $extra );
}

function ai_auc_table_ttl( string $table ): int {
	if ( 'stats' === $table ) {
		return DAY_IN_SECONDS;
	}
	if ( ai_auc_is_stock_table( $table ) ) {
		return 12 * HOUR_IN_SECONDS;
	}
	return 30 * MINUTE_IN_SECONDS;
}

/** COUNT(*) → int. */
function ai_auc_count( string $where, string $table = 'main' ): int {
	$table = ai_auc_normalize_table( $table );
	$ttl   = ai_auc_table_ttl( $table );
	$rows  = ai_auc_api( "SELECT COUNT(*) FROM {$table} WHERE {$where}", $ttl );
	if ( ! $rows || ! isset( $rows[0] ) ) {
		return 0;
	}
	return (int) reset( $rows[0] );
}

/**
 * Прогрев пошлины/утиля пакетом, чтобы цена в списке совпала с карточкой.
 *
 * @param array<int,mixed> $rows
 */
function ai_price_prefetch_rows( array $rows, string $table ): void {
	if ( ! $rows ) {
		return;
	}
	$table = ai_auc_normalize_table( $table );
	if ( ai_auc_is_stock_table( $table ) ) {
		if ( function_exists( 'ai_price_cnkr_customs_prefetch' ) ) {
			ai_price_cnkr_customs_prefetch( $rows, ai_auc_table_market( $table ) );
		}
		return;
	}
	if ( function_exists( 'ai_price_jp_customs_prefetch' ) ) {
		ai_price_jp_customs_prefetch( $rows );
	}
}

/**
 * Поиск лотов. Не тянем всю базу — только страница + COUNT.
 *
 * @return array{lots: array, total: int, page: int, pages: int, ok: bool}
 */
function ai_auc_search( array $args = array() ): array {
	$market = (string) ( $args['market'] ?? '' );
	$table  = ! empty( $args['table'] )
		? ai_auc_normalize_table( (string) $args['table'] )
		: ai_auc_market_table( $market, ! empty( $args['sold'] ) );
	$limit  = max( 1, min( 24, (int) ( $args['limit'] ?? 12 ) ) );
	$page   = max( 1, (int) ( $args['paged'] ?? 1 ) );
	$where  = ai_auc_where( $args, $table );
	$ttl    = ai_auc_table_ttl( $table );
	$total  = 0;
	$pages  = 0;
	if ( empty( $args['nocount'] ) ) {
		$total = ai_auc_count( $where, $table );
		$pages = $total ? max( 1, (int) ceil( $total / $limit ) ) : 0;
		// Дальние OFFSET по stats (сотни тысяч строк) API не успевает — держим витрину.
		$max_pages = ! empty( $args['sold'] ) ? 40 : 200;
		if ( $pages > $max_pages ) {
			$pages = $max_pages;
		}
		$page = $pages ? min( $page, $pages ) : 1;
	}
	$offset = ( $page - 1 ) * $limit;
	$order  = ! empty( $args['order'] ) ? $args['order'] : 'year DESC, mileage ASC';
	if ( 'stats' === $table && empty( $args['order'] ) ) {
		$order = 'id DESC';
	}
	if ( ai_auc_is_stock_table( $table ) ) {
		$order = str_ireplace( array( 'avg_price', 'start' ), 'finish', $order );
		$order = str_ireplace( 'auction_date DESC, year DESC', 'year DESC, mileage ASC', $order );
	}
	$order  = preg_replace( '/[^a-zA-Z0-9_, ]/', '', $order );
	// SANCTION есть только в JP main/stats. В china/korea поле отсутствует → API "unknown field" → пустая витрина при живом COUNT.
	$fields = 'ID,LOT,AUCTION,MARKA_NAME,MODEL_NAME,YEAR,ENG_V,PW,KUZOV,GRADE,COLOR,KPP,PRIV,MILEAGE,EQUIP,RATE,START,FINISH,STATUS,TIME,AVG_PRICE,LHDRIVE,IMAGES';
	if ( ! ai_auc_is_stock_table( $table ) ) {
		$fields = 'ID,LOT,AUCTION,MARKA_NAME,MODEL_NAME,YEAR,ENG_V,PW,KUZOV,GRADE,COLOR,KPP,PRIV,MILEAGE,EQUIP,RATE,START,FINISH,STATUS,TIME,SANCTION,AVG_PRICE,LHDRIVE,IMAGES';
	}

	$sql  = "SELECT {$fields} FROM {$table} WHERE {$where} ORDER BY {$order} LIMIT {$offset},{$limit}";
	$rows = ai_auc_api( $sql, $ttl );
	$lots = array();
	if ( is_array( $rows ) ) {
		ai_price_prefetch_rows( $rows, $table );
		foreach ( $rows as $row ) {
			if ( is_array( $row ) && ! empty( $row['ID'] ) ) {
				$lots[] = ai_auc_map_row( $row, true, $table );
			}
		}
	}

	return array(
		'lots'  => $lots,
		'total' => $total,
		'page'  => $page,
		'pages' => $total ? $pages : 0,
		'ok'    => null !== $rows,
	);
}

/**
 * Номер лота сразу в main, korea и china — без фильтра года и страны.
 * Если короткого номера нет, длинный код ищем как ID карточки.
 *
 * @return array{lots: array, total: int, page: int, pages: int, ok: bool}
 */
function ai_auc_search_lot( string $lot, int $limit = 12, int $page = 1, string $sort = '' ): array {
	$lot   = ai_auc_sql_str( $lot );
	$limit = max( 1, min( 24, $limit ) );
	$page  = max( 1, $page );
	$sort  = preg_replace( '/[^a-z]/', '', $sort );
	$key   = $lot . '|' . $limit . '|' . $page . '|' . $sort;
	static $cache = array();
	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}

	$empty = array(
		'lots'  => array(),
		'total' => 0,
		'page'  => 1,
		'pages' => 0,
		'ok'    => '' !== $lot,
	);
	if ( '' === $lot ) {
		$cache[ $key ] = $empty;
		return $empty;
	}

	$found = array();
	$ok    = true;
	foreach ( array( 'main', 'korea', 'china' ) as $table ) {
		$fields = 'ID,LOT,AUCTION,MARKA_NAME,MODEL_NAME,YEAR,ENG_V,PW,KUZOV,GRADE,COLOR,KPP,PRIV,MILEAGE,EQUIP,RATE,START,FINISH,STATUS,TIME,AVG_PRICE,LHDRIVE,IMAGES';
		if ( ! ai_auc_is_stock_table( $table ) ) {
			$fields = 'ID,LOT,AUCTION,MARKA_NAME,MODEL_NAME,YEAR,ENG_V,PW,KUZOV,GRADE,COLOR,KPP,PRIV,MILEAGE,EQUIP,RATE,START,FINISH,STATUS,TIME,SANCTION,AVG_PRICE,LHDRIVE,IMAGES';
		}
		$where = ai_auc_where( array( 'lot' => $lot ), $table );
		$rows  = ai_auc_api(
			"SELECT {$fields} FROM {$table} WHERE {$where} ORDER BY year DESC LIMIT 0,24",
			ai_auc_table_ttl( $table )
		);
		if ( null === $rows ) {
			$ok = false;
			continue;
		}
		ai_price_prefetch_rows( $rows, $table );
		foreach ( $rows as $row ) {
			if ( is_array( $row ) && ! empty( $row['ID'] ) ) {
				$found[] = ai_auc_map_row( $row, true, $table );
			}
		}
	}

	if ( ! $found && strlen( $lot ) >= 6 ) {
		$id = preg_replace( '/[^A-Za-z0-9]/', '', $lot );
		foreach ( array( 'main', 'korea', 'china' ) as $table ) {
			$rows = ai_auc_api( "SELECT {$fields} FROM {$table} WHERE id='{$id}'", ai_auc_table_ttl( $table ) );
			if ( null === $rows ) {
				$ok = false;
				continue;
			}
			if ( ! empty( $rows[0]['ID'] ) && is_array( $rows[0] ) ) {
				ai_price_prefetch_rows( array( $rows[0] ), $table );
				$found[] = ai_auc_map_row( $rows[0], true, $table );
				break;
			}
		}
	}

	$km = static function ( array $row ): int {
		return (int) preg_replace( '/\D/', '', (string) ( $row['km'] ?? '' ) );
	};
	usort(
		$found,
		static function ( array $a, array $b ) use ( $sort, $km ): int {
			return match ( $sort ) {
				'price' => ( $a['amount'] <=> $b['amount'] ),
				'year'  => ( (int) $a['year'] <=> (int) $b['year'] ),
				'km'    => ( $km( $a ) <=> $km( $b ) ),
				'fresh' => strcmp( (string) ( $b['auction_date'] ?? '' ), (string) ( $a['auction_date'] ?? '' ) ),
				default => ( (int) $b['year'] <=> (int) $a['year'] ),
			};
		}
	);

	$total  = count( $found );
	$pages  = $total ? (int) ceil( $total / $limit ) : 0;
	$page   = $pages ? min( $page, $pages ) : 1;
	$result = array(
		'lots'  => array_slice( $found, ( $page - 1 ) * $limit, $limit ),
		'total' => $total,
		'page'  => $page,
		'pages' => $pages,
		'ok'    => $ok,
	);
	$cache[ $key ] = $result;
	return $result;
}

/** Полная карточка по ID. Сначала таблица из кэша, пустые промахи помним 30 мин. */
function ai_auc_lot( string $id ): ?array {
	$id = preg_replace( '/[^A-Za-z0-9]/', '', $id );
	if ( '' === $id ) {
		return null;
	}
	$tables = array( 'main', 'korea', 'china', 'stats' );
	$hint   = get_transient( 'ai_auc_tbl_' . $id );
	if ( is_string( $hint ) && in_array( $hint, $tables, true ) ) {
		$tables = array_values( array_unique( array_merge( array( $hint ), $tables ) ) );
	}
	foreach ( $tables as $table ) {
		$miss = 'ai_auc_miss_' . $table . '_' . $id;
		if ( get_transient( $miss ) ) {
			continue;
		}
		$ttl  = 'stats' === $table ? DAY_IN_SECONDS : ( ai_auc_is_stock_table( $table ) ? 12 * HOUR_IN_SECONDS : 6 * HOUR_IN_SECONDS );
		$rows = ai_auc_api( "SELECT * FROM {$table} WHERE id='{$id}'", $ttl );
		if ( ! empty( $rows[0] ) && is_array( $rows[0] ) ) {
			set_transient( 'ai_auc_tbl_' . $id, $table, DAY_IN_SECONDS );
			return ai_auc_map_row( $rows[0], true, $table );
		}
		set_transient( $miss, 1, 30 * MINUTE_IN_SECONDS );
	}
	return null;
}

/** Справочник марок (кэш 12 ч). */
function ai_auc_vendors( string $table = 'main' ): array {
	$table = in_array( $table, ai_auc_tables(), true ) ? $table : ai_auc_market_table( $table );
	$ttl   = 12 * HOUR_IN_SECONDS;
	$where = in_array( $table, array( 'main', 'stats' ), true ) ? 'AUCTION_TYPE!=1' : '1=1';
	$rows  = ai_auc_api(
		"SELECT marka_name, count(*) FROM {$table} WHERE {$where} GROUP BY marka_name ORDER BY marka_name ASC",
		$ttl
	);
	$out   = array();
	if ( ! is_array( $rows ) ) {
		return $out;
	}
	foreach ( $rows as $row ) {
		$name = trim( (string) ( $row['MARKA_NAME'] ?? '' ) );
		if ( '' === $name ) {
			continue;
		}
		$out[] = array(
			'id'    => $name,
			'label' => ai_auc_pretty( $name ),
			'count' => ai_auc_agg_count( $row ),
		);
	}
	return $out;
}

/** Модели марки (кэш 12 ч). */
function ai_auc_models( string $make, string $table = 'main' ): array {
	$make  = ai_auc_sql_str( $make );
	$table = in_array( $table, ai_auc_tables(), true ) ? $table : ai_auc_market_table( $table );
	if ( '' === $make ) {
		return array();
	}
	$base = in_array( $table, array( 'main', 'stats' ), true ) ? 'AUCTION_TYPE!=1 AND ' : '';
	$sql  = "SELECT model_name, count(*) FROM {$table} WHERE {$base}"
		. ai_auc_make_sql( $make )
		. ' GROUP BY model_name ORDER BY model_name';
	$rows = ai_auc_api( $sql, 12 * HOUR_IN_SECONDS );
	$out  = array();
	if ( ! is_array( $rows ) ) {
		return $out;
	}
	foreach ( $rows as $row ) {
		$name = trim( (string) ( $row['MODEL_NAME'] ?? '' ) );
		if ( '' === $name ) {
			continue;
		}
		$out[] = array(
			'id'    => $name,
			'label' => ai_auc_pretty( $name ),
			'count' => ai_auc_agg_count( $row ),
		);
	}
	return $out;
}

/** Свежие лоты для главной / героя страны. */
function ai_auc_fresh( int $limit = 3, string $market = 'jp' ): array {
	$m = ai_auc_normalize_market( $market );
	$q = ai_auc_search(
		array(
			'limit'     => $limit,
			'year_from' => 2022,
			'order'     => in_array( $m, array( 'kr', 'cn' ), true ) ? 'year DESC, mileage ASC' : 'auction_date DESC, year DESC',
			'nocount'   => true,
			'market'    => $m,
		)
	);
	return $q['lots'];
}

/** JP-витрина: каталог без страны / Япония / статистика. */
function ai_auc_is_jp_context( string $market = '' ): bool {
	return 'jp' === ai_auc_request_market( $market );
}

/** AJAX: марки или модели для выпадающих списков фильтра. */
function ai_auc_ajax_filter_dict(): void {
	$country = isset( $_GET['country'] ) ? sanitize_text_field( wp_unslash( $_GET['country'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$make    = isset( $_GET['make'] ) ? sanitize_text_field( wp_unslash( $_GET['make'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$table   = ai_auc_market_table( $country );
	if ( '' !== $make ) {
		$models = array_map(
			static function ( array $row ): array {
				return array(
					'id'    => (string) ( $row['id'] ?? '' ),
					'label' => (string) ( $row['label'] ?? '' ),
					'count' => (int) ( $row['count'] ?? 0 ),
				);
			},
			ai_auc_models( $make, $table )
		);
		wp_send_json_success( array( 'models' => $models ) );
	}
	$vendors = array_map(
		static function ( array $row ): array {
			return array(
				'id'    => (string) ( $row['id'] ?? '' ),
				'label' => (string) ( $row['label'] ?? '' ),
				'count' => (int) ( $row['count'] ?? 0 ),
			);
		},
		ai_auc_vendors( $table )
	);
	wp_send_json_success( array( 'vendors' => $vendors ) );
}

add_action( 'wp_ajax_ai_filter_dict', 'ai_auc_ajax_filter_dict' );
add_action( 'wp_ajax_nopriv_ai_filter_dict', 'ai_auc_ajax_filter_dict' );

/** Прогрев: курсы, свежие лоты, марки, дефолтные витрины. */
function ai_auc_warm_run(): void {
	if ( ! defined( 'AI_AUC_WARMING' ) ) {
		define( 'AI_AUC_WARMING', true );
	}
	ai_auc_fx_rates();
	ai_auc_refresh_nophoto_auctions();
	ai_auc_fresh( 3 );
	foreach ( array( 'main', 'korea', 'china' ) as $table ) {
		ai_auc_vendors( $table );
	}
	foreach ( array( 'jp', 'kr', 'cn' ) as $market ) {
		ai_auc_search(
			array(
				'limit'     => 12,
				'year_from' => 2022,
				'paged'     => 1,
				'market'    => $market,
			)
		);
	}
	ai_auc_search(
		array(
			'sold'      => true,
			'market'    => 'jp',
			'year_from' => 2022,
			'limit'     => 12,
			'paged'     => 1,
			'order'     => 'id DESC',
		)
	);
}

add_filter(
	'cron_schedules',
	static function ( array $schedules ): array {
		$schedules['ai_auc_15min'] = array(
			'interval' => 15 * MINUTE_IN_SECONDS,
			'display'  => 'Каждые 15 минут (аукцион)',
		);
		return $schedules;
	}
);

add_action(
	'init',
	static function (): void {
		if ( ! wp_next_scheduled( 'ai_auc_warm' ) ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, 'ai_auc_15min', 'ai_auc_warm' );
		}
	}
);

add_action( 'ai_auc_warm', 'ai_auc_warm_run' );
