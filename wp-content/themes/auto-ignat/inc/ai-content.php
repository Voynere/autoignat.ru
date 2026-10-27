<?php
/**
 * Живой контент вместо демо: журнал, отзывы, контакты.
 *
 * @package auto-ignat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** URI файла из content/ темы. */
function ai_content_uri( string $rel ): string {
	$rel  = ltrim( $rel, '/' );
	$path = get_theme_file_path( 'content/' . $rel );
	$uri  = get_theme_file_uri( 'content/' . $rel );
	if ( is_readable( $path ) ) {
		$uri = add_query_arg( 'ver', (string) filemtime( $path ), $uri );
	}
	return esc_url( $uri );
}

/** Адрес офиса. */
function ai_office_address(): string {
	return 'г. Владивосток, ул. Днепровская 40а стр. 4, 2 этаж, офис 2.9';
}

/** Короткий адрес для футера. */
function ai_office_address_short(): string {
	return 'г. Владивосток, ул. Днепровская 40а стр. 4';
}

/** Яндекс.Карты — карточка организации. */
function ai_maps_yandex_url(): string {
	return 'https://yandex.ru/maps/org/avtoignat/161848520209/';
}

/** 2ГИС — карточка фирмы. */
function ai_maps_2gis_url(): string {
	return 'https://2gis.ru/vladivostok/firm/70000001110612094';
}

/** Виджет Яндекс.Карт (координаты офиса). */
function ai_maps_embed_url(): string {
	return 'https://yandex.ru/map-widget/v1/?ll=131.924651%2C43.140045&z=16&pt=131.924651%2C43.140045%2Cpm2rdm&l=map';
}

/**
 * Реестр статей журнала из content/journal/.
 *
 * @return array<int, array<string, mixed>>
 */
function ai_journal_registry(): array {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$index = get_theme_file_path( 'content/journal/index.json' );
	if ( ! is_readable( $index ) ) {
		$cache = array();
		return $cache;
	}
	$data = json_decode( (string) file_get_contents( $index ), true );
	if ( ! is_array( $data ) ) {
		$cache = array();
		return $cache;
	}
	$items = array();
	foreach ( $data as $i => $row ) {
		$slug = (string) ( $row['slug'] ?? '' );
		if ( '' === $slug ) {
			continue;
		}
		$cover = (string) ( $row['cover'] ?? '' );
		$items[] = array(
			'slug'     => $slug,
			'kicker'   => sprintf( '%02d • %s', $i + 1, (string) ( $row['kicker'] ?? 'ЖУРНАЛ' ) ),
			'title'    => (string) ( $row['title'] ?? '' ),
			'lead'     => (string) ( $row['lead'] ?? '' ),
			'img'      => $cover ? ai_content_uri( 'journal/' . $slug . '/' . $cover ) : '',
			'featured' => $i < 2,
			'url'      => home_url( '/zhurnal/' . $slug . '/' ),
		);
	}
	$cache = $items;
	return $cache;
}

/** Статья по слагу или null. */
function ai_journal_article( string $slug ): ?array {
	$slug = sanitize_title( $slug );
	if ( '' === $slug ) {
		return null;
	}
	$dir = get_theme_file_path( 'content/journal/' . $slug );
	$meta_file = $dir . '/meta.json';
	$body_file = $dir . '/body.html';
	if ( ! is_readable( $meta_file ) || ! is_readable( $body_file ) ) {
		return null;
	}
	$meta = json_decode( (string) file_get_contents( $meta_file ), true );
	if ( ! is_array( $meta ) ) {
		return null;
	}
	$images = array();
	foreach ( (array) ( $meta['images'] ?? array() ) as $img ) {
		$file = (string) ( $img['file'] ?? '' );
		if ( '' === $file ) {
			continue;
		}
		$images[] = array(
			'src' => ai_content_uri( 'journal/' . $slug . '/' . $file ),
			'alt' => (string) ( $img['alt'] ?? '' ),
		);
	}
	return array(
		'slug'   => $slug,
		'title'  => (string) ( $meta['title'] ?? '' ),
		'lead'   => (string) ( $meta['lead'] ?? '' ),
		'kicker' => (string) ( $meta['kicker'] ?? 'ЖУРНАЛ' ),
		'cta'    => (string) ( $meta['cta'] ?? '' ),
		'tags'   => array_values( array_filter( array_map( 'strval', (array) ( $meta['tags'] ?? array() ) ) ) ),
		'images' => $images,
		'body'   => (string) file_get_contents( $body_file ),
		'url'    => home_url( '/zhurnal/' . $slug . '/' ),
	);
}

/**
 * Видео-отзывы клиентов.
 *
 * @return array<int, array<string, string>>
 */
function ai_reviews_registry(): array {
	return array(
		array(
			'id'      => '01_eduard',
			'name'    => 'Эдуард',
			'city'    => 'Владивосток',
			'car'     => 'Honda StepWGN Spada 2020',
			'quote'   => 'Машина в отличном состоянии. Хлопот никаких не составили.',
			'desc'    => 'Семейный минивэн из Японии — RP3 G Honda Sensing, 1.5 л, автомат, три ряда, пробег 47 000 км, оценка 3.5. Под ключ — 1 925 000 ₽. Выдача — июль 2026.',
			'price'   => '1 925 000 ₽',
			'video'   => ai_content_uri( 'reviews/01_eduard.mp4' ),
			'poster'  => ai_content_uri( 'reviews/01_eduard.jpg' ),
			'youtube' => 'https://www.youtube.com/watch?v=jwBU-zwGKFw',
		),
		array(
			'id'      => '02_valeriya',
			'name'    => 'Валерия',
			'city'    => 'Хабаровск',
			'car'     => 'Jetour X70 Plus',
			'quote'   => 'Всё прошло замечательно, мне всё понравилось, рекомендую.',
			'desc'    => 'Семиместный кроссовер из Китая, левый руль, 156 л.с., винный кожаный салон. Под ключ — 1 500 000 ₽. Забрали во Владивостоке, уехала в Хабаровск.',
			'price'   => '1 500 000 ₽',
			'video'   => ai_content_uri( 'reviews/02_valeriya.mp4' ),
			'poster'  => ai_content_uri( 'reviews/02_valeriya.jpg' ),
			'youtube' => 'https://www.youtube.com/watch?v=Q1VXuv2GnAs',
		),
		array(
			'id'      => '03_viktor',
			'name'    => 'Виктор',
			'city'    => 'Владивосток',
			'car'     => 'Subaru Forester 2022',
			'quote'   => 'Искал машину с левым рулём. Всё показали, прислали видеообзор — я доволен.',
			'desc'    => 'Subaru Forester 2022 из Китая, левый руль. 2.5, 182 л.с., вариатор, полный привод, пробег 37 600 км. Под ключ — 3 012 000 ₽.',
			'price'   => '3 012 000 ₽',
			'video'   => ai_content_uri( 'reviews/03_viktor.mp4' ),
			'poster'  => ai_content_uri( 'reviews/03_viktor.jpg' ),
			'youtube' => '',
		),
		array(
			'id'      => '04_aleksey',
			'name'    => 'Алексей',
			'city'    => 'Владивосток',
			'car'     => 'Honda Crossroad 2007',
			'quote'   => 'Прошло всё ровно, машиной доволен. Команда сплочённая, всё аккуратно.',
			'desc'    => 'Honda Crossroad 2007 из Японии, правый руль — 1.8, 4WD, пробег 187 000 км, оценка 3.5, без ДТП. Под ключ — 855 000 ₽.',
			'price'   => '855 000 ₽',
			'video'   => ai_content_uri( 'reviews/04_aleksey.mp4' ),
			'poster'  => ai_content_uri( 'reviews/04_aleksey.jpg' ),
			'youtube' => 'https://www.youtube.com/watch?v=QJCEeonZCBo',
		),
	);
}
