<?php
/**
 * Auto Ignat theme functions — макет Figma «Авто аукцион».
 *
 * @package auto-ignat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AI_LEAD_EMAIL', 'ai-geniusv@yandex.ru' );

require_once get_template_directory() . '/inc/ai-auction.php';
require_once get_template_directory() . '/inc/ai-price-jp.php';
require_once get_template_directory() . '/inc/ai-price-cn-kr.php';
require_once get_template_directory() . '/inc/ai-content.php';

/** Настройка темы. */
add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_image_size( 'ai-lot', 900, 620, true );
	register_nav_menus( array( 'primary' => 'Главное меню' ) );
} );

/** Подключение стилей и скриптов. */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'ai-fonts',
		'https://fonts.googleapis.com/css2?family=Unbounded:wght@600;700&family=JetBrains+Mono:wght@400&family=Manrope:wght@400;500;600;800&display=swap',
		array(),
		null
	);
	wp_enqueue_style(
		'ai-main',
		get_theme_file_uri( 'assets/css/main.css' ),
		array(),
		(string) filemtime( get_theme_file_path( 'assets/css/main.css' ) )
	);
	wp_enqueue_script(
		'ai-main',
		get_theme_file_uri( 'assets/js/main.js' ),
		array(),
		(string) filemtime( get_theme_file_path( 'assets/js/main.js' ) ),
		array( 'in_footer' => true, 'strategy' => 'defer' )
	);
	wp_localize_script(
		'ai-main',
		'aiFilter',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		)
	);
} );

/** Публичные витрины — без Gutenberg CSS. */
function ai_is_showcase(): bool {
	return ! is_admin();
}

add_action(
	'wp',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
		remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
	}
);

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'classic-theme-styles' );
		wp_dequeue_style( 'global-styles' );
		wp_deregister_style( 'wp-block-library' );
	},
	100
);

add_action(
	'wp_head',
	static function (): void {
		echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
		echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
		echo '<link rel="preconnect" href="https://7.tru.ru" crossorigin>' . "\n";
		if ( is_front_page() ) {
			printf(
				'<link rel="preload" as="image" href="%s" fetchpriority="high">' . "\n",
				esc_url( ai_img( 'hero-crown.jpg' ) )
			);
		}
	},
	1
);

add_action(
	'wp_head',
	static function (): void {
		printf( '<link rel="icon" href="%s" sizes="any">' . "\n", esc_url( ai_favicon( 'favicon.ico' ) ) );
		printf( '<link rel="icon" type="image/png" sizes="32x32" href="%s">' . "\n", esc_url( ai_favicon( 'favicon-32x32.png' ) ) );
		printf( '<link rel="icon" type="image/png" sizes="16x16" href="%s">' . "\n", esc_url( ai_favicon( 'favicon-16x16.png' ) ) );
		printf( '<link rel="apple-touch-icon" sizes="180x180" href="%s">' . "\n", esc_url( ai_favicon( 'apple-touch-icon.png' ) ) );
		printf( '<link rel="manifest" href="%s">' . "\n", esc_url( ai_favicon( 'site.webmanifest' ) ) );
		echo '<meta name="theme-color" content="#fedd08">' . "\n";
	},
	2
);
remove_action( 'wp_head', 'wp_site_icon', 99 );

/** ID счётчика Яндекс.Метрики (тот же, что на Тильде auto-ignat.ru). Override: AI_METRIKA_ID в wp-config. */
function ai_metrika_id(): int {
	if ( defined( 'AI_METRIKA_ID' ) && (int) AI_METRIKA_ID > 0 ) {
		return (int) AI_METRIKA_ID;
	}
	return 113705893;
}

/**
 * Canonical / robots по SEO-плану октября.
 * Лоты — noindex; ?country= на каталоге — canonical на доставку.
 * Штатный rel_canonical отключаем, чтобы не плодить два тега.
 */
remove_action( 'wp_head', 'rel_canonical' );

add_action(
	'wp_head',
	static function (): void {
		$uri = $_SERVER['REQUEST_URI'] ?? '';
		$is_lot = (bool) ( $GLOBALS['ai_auc_lot_data'] ?? null )
			|| (bool) get_query_var( 'ai_auc_lot' )
			|| (bool) preg_match( '#/(?:loty/)?aukcion/[^/]+/?#', $uri );

		if ( $is_lot ) {
			echo "<meta name=\"robots\" content=\"noindex, follow\">\n";
			if ( preg_match( '#/(?:loty/)?aukcion/([^/]+)/?#', $uri, $m ) ) {
				printf(
					'<link rel="canonical" href="%s">' . "\n",
					esc_url( home_url( '/aukcion/' . rawurlencode( rawurldecode( $m[1] ) ) . '/' ) )
				);
			}
			return;
		}

		// Каталог с ?country= — в поиск не отдаём; канон на страницу доставки.
		$on_catalog = is_page_template( 'template-catalog.php' )
			|| (bool) preg_match( '#^/catalog/?#', (string) wp_parse_url( $uri, PHP_URL_PATH ) );
		if ( $on_catalog ) {
			$country = isset( $_GET['country'] ) ? strtolower( sanitize_text_field( wp_unslash( $_GET['country'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$map     = array(
				'kr' => '/dostavka-iz-korei/',
				'cn' => '/dostavka-iz-kitaya/',
				'jp' => '/dostavka-iz-yaponii/',
			);
			if ( isset( $map[ $country ] ) ) {
				printf( '<link rel="canonical" href="%s">' . "\n", esc_url( home_url( $map[ $country ] ) ) );
				return;
			}
			printf( '<link rel="canonical" href="%s">' . "\n", esc_url( home_url( '/catalog/' ) ) );
			return;
		}

		if ( is_front_page() ) {
			printf( '<link rel="canonical" href="%s">' . "\n", esc_url( home_url( '/' ) ) );
			return;
		}

		// Остальные постоянные страницы — один канон без query.
		if ( is_singular() || is_page() ) {
			$url = get_permalink();
			if ( is_string( $url ) && '' !== $url ) {
				printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $url ) );
			}
		}
	},
	3
);

add_filter(
	'wp_robots',
	static function ( array $robots ): array {
		$uri = $_SERVER['REQUEST_URI'] ?? '';
		if ( ( $GLOBALS['ai_auc_lot_data'] ?? null ) || preg_match( '#/(?:loty/)?aukcion/[^/]+/?#', $uri ) ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
			unset( $robots['index'] );
		}
		return $robots;
	}
);

add_action(
	'wp_head',
	static function (): void {
		$id = ai_metrika_id();
		if ( $id <= 0 ) {
			return;
		}
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed counter snippet
		echo "<!-- Yandex.Metrika counter -->\n";
		echo "<script type=\"text/javascript\">\n";
		echo "(function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};\n";
		echo "m[i].l=1*new Date();k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})\n";
		echo "(window, document, \"script\", \"https://mc.yandex.ru/metrika/tag.js\", \"ym\");\n";
		echo "ym({$id}, \"init\", { clickmap:true, trackLinks:true, accurateTrackBounce:true, webvisor:true });\n";
		echo "window.AI_METRIKA_ID={$id};\n";
		echo "</script>\n";
		echo "<noscript><div><img src=\"https://mc.yandex.ru/watch/{$id}\" style=\"position:absolute; left:-9999px;\" alt=\"\" /></div></noscript>\n";
		echo "<!-- /Yandex.Metrika counter -->\n";
		// phpcs:enable
	},
	5
);

remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
remove_action( 'wp_head', 'feed_links', 2 );
remove_action( 'wp_head', 'feed_links_extra', 3 );

/** Хелперы. */
function ai_img( string $file ): string {
	$rel  = 'assets/img/' . $file;
	$path = get_theme_file_path( $rel );
	$uri  = get_theme_file_uri( $rel );
	if ( is_readable( $path ) ) {
		$uri = add_query_arg( 'ver', (string) filemtime( $path ), $uri );
	}
	return esc_url( $uri );
}

function ai_favicon( string $file ): string {
	$rel  = 'assets/img/favicon/' . $file;
	$path = get_theme_file_path( $rel );
	$uri  = get_theme_file_uri( $rel );
	if ( is_readable( $path ) ) {
		$uri = add_query_arg( 'ver', (string) filemtime( $path ), $uri );
	}
	return $uri;
}

/** Иконка мессенджера: MAX — CSS-фон с maxicons.ru, остальные SVG из макета. */
function ai_soc_icon( string $name, bool $foot = false ): void {
	if ( 'max' === $name ) {
		echo '<img class="ico-max" src="https://maxicons.ru/icons/MAX.svg" alt="" width="24" height="24" decoding="async" aria-hidden="true" />';
		return;
	}
	$file = $foot ? 'icon-' . $name . '-foot.svg' : 'icon-' . $name . '.svg';
	$path = get_theme_file_path( 'assets/img/' . $file );
	if ( ! is_readable( $path ) ) {
		return;
	}
	$svg = (string) file_get_contents( $path );
	$svg = str_replace(
		array( 'fill="#0A0A0B"', 'stroke="#0A0A0B"' ),
		array( 'fill="currentColor"', 'stroke="currentColor"' ),
		$svg
	);
	$svg = preg_replace( '/<svg\b/', '<svg aria-hidden="true"', $svg, 1 );
	echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme SVG
}

function ai_anchor( string $id ): string {
	return esc_url( is_front_page() ? '#' . $id : home_url( '/#' . $id ) );
}

function ai_phone(): string {
	return '+7 902 052-92-88';
}

function ai_phone_href(): string {
	return 'tel:' . preg_replace( '/[^+\d]/', '', ai_phone() );
}

function ai_telegram_url(): string {
	return 'https://t.me/auto_ignat';
}

function ai_whatsapp_url(): string {
	return 'https://wa.me/79020529288';
}

function ai_max_url(): string {
	return 'https://max.ru/u/f9LHodD0cOLou_B4IhtA4eQaDPGmGuSD7Mzg4ay8LC91d2iGoxlpzyN-oOw';
}

function ai_vk_url(): string {
	return 'https://vk.ru/club236823045';
}

/** Цена карточки героя: «2,14 млн». */
function ai_price_short( string $price ): string {
	$n = (int) preg_replace( '/\D+/', '', $price );
	if ( $n >= 100000 ) {
		$mln = $n / 1000000;
		$dec = $mln >= 10 ? 1 : 2;
		return number_format( $mln, $dec, ',', '' ) . ' млн';
	}
	return $price !== '' ? $price : 'по запросу';
}

/** Логотип шапки или подвала. */
function ai_brand( string $place = 'nav' ): void {
	$home = esc_url( home_url( '/' ) );
	if ( 'foot' === $place ) {
		printf(
			'<a class="brand brand-stack" href="%1$s" aria-label="АвтоИгнат"><img class="logo-foot-mark" src="%2$s" alt="" width="139" height="45" /><img class="logo-foot-word" src="%3$s" alt="" width="170" height="14" /><img class="logo-foot-tag" src="%4$s" alt="" width="170" height="9" /></a>',
			$home,
			ai_img( 'logo-foot-mark.svg' ),
			ai_img( 'logo-foot-word.svg' ),
			ai_img( 'logo-foot-tag.svg' )
		);
		return;
	}
	printf(
		'<a class="brand" href="%1$s" aria-label="АвтоИгнат"><img class="logo-mark" src="%2$s" alt="" width="77" height="23" /><img class="logo-word" src="%3$s" alt="АвтоИгнат" width="112" height="14" /></a>',
		$home,
		ai_img( 'logo-mark.svg' ),
		ai_img( 'logo-word.svg' )
	);
}

/** Чистка head. */
remove_action( 'wp_head', 'wp_generator' );
add_action( 'init', function () {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
} );

/** CPT: Лоты. */
add_action( 'init', function () {
	register_post_type( 'ai_lot', array(
		'labels'       => array(
			'name'          => 'Лоты',
			'singular_name' => 'Лот',
			'add_new'       => 'Добавить лот',
			'menu_name'     => 'Лоты',
		),
		'public'       => true,
		'has_archive'  => true,
		'menu_icon'    => 'dashicons-car',
		'menu_position' => 5,
		'supports'     => array( 'title', 'thumbnail', 'editor' ),
		'rewrite'      => array( 'slug' => 'loty' ),
	) );
} );

/** Мета-бокс параметров лота. */
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'ai_lot_meta', 'Параметры лота', 'ai_lot_metabox', 'ai_lot', 'normal', 'high' );
} );

function ai_lot_fields(): array {
	return array(
		'ai_lot_number'        => 'Номер лота',
		'ai_lot_market'        => 'Рынок (jp / cn / kr)',
		'ai_lot_source'        => 'Площадка (USS Tokyo, ENCAR…)',
		'ai_lot_grade'         => 'Грейд / состояние',
		'ai_lot_year'          => 'Год',
		'ai_lot_mileage'       => 'Пробег',
		'ai_lot_price'         => 'Цена под ключ, ₽',
		'ai_lot_origin_price'  => 'Цена на площадке',
		'ai_lot_fuel'          => 'Топливо',
		'ai_lot_gearbox'       => 'КПП',
		'ai_lot_drive'         => 'Привод',
		'ai_lot_body'          => 'Кузов',
		'ai_lot_color'         => 'Цвет',
		'ai_lot_volume'        => 'Объём',
		'ai_lot_hp'            => 'Мощность',
		'ai_lot_place'         => 'Выдача (город)',
	);
}

function ai_lot_metabox( $post ) {
	wp_nonce_field( 'ai_lot_meta', 'ai_lot_meta_nonce' );
	echo '<table class="form-table">';
	foreach ( ai_lot_fields() as $key => $label ) {
		$val = get_post_meta( $post->ID, $key, true );
		printf(
			'<tr><th><label for="%1$s">%2$s</label></th><td><input type="text" id="%1$s" name="%1$s" value="%3$s" class="regular-text"></td></tr>',
			esc_attr( $key ),
			esc_html( $label ),
			esc_attr( $val )
		);
	}
	echo '</table>';
}

add_action( 'save_post_ai_lot', function ( $post_id ) {
	if ( ! isset( $_POST['ai_lot_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ai_lot_meta_nonce'] ) ), 'ai_lot_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	foreach ( array_keys( ai_lot_fields() ) as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
		}
	}
} );

/** Карточка лота (главная + каталог). */
function ai_lot_card( $post, $featured = false ) {
	ai_render_lot_card(
		array(
			'num'      => get_post_meta( $post->ID, 'ai_lot_number', true ) ?: '0000',
			'src'      => get_post_meta( $post->ID, 'ai_lot_source', true ) ?: strtoupper( get_post_meta( $post->ID, 'ai_lot_market', true ) ?: 'JP' ),
			'market'   => strtoupper( get_post_meta( $post->ID, 'ai_lot_market', true ) ?: 'JP' ),
			'meta'     => get_post_meta( $post->ID, 'ai_lot_grade', true ),
			'title'    => get_the_title( $post ),
			'year'     => get_post_meta( $post->ID, 'ai_lot_year', true ),
			'km'       => get_post_meta( $post->ID, 'ai_lot_mileage', true ),
			'price'    => get_post_meta( $post->ID, 'ai_lot_price', true ),
			'place'    => get_post_meta( $post->ID, 'ai_lot_place', true ) ?: 'под ключ во Владивостоке',
			'img'      => has_post_thumbnail( $post ) ? get_the_post_thumbnail_url( $post, 'ai-lot' ) : '',
			'url'      => get_permalink( $post ),
			'featured' => $featured,
		)
	);
}

/** Страна по коду рынка. */
function ai_market_label( string $market ): string {
	$map = array(
		'JP' => 'Япония',
		'KR' => 'Корея',
		'CN' => 'Китай',
	);
	$code = strtoupper( $market );
	return $map[ $code ] ?? $code;
}

/** Демо-лоты, пока менеджер не добавил свои. */
function ai_demo_lots(): array {
	return array(
		array(
			'num' => '4482', 'src' => 'USS TOKYO', 'market' => 'JP', 'meta' => 'GRADE 4.5',
			'title' => 'Toyota Crown Hybrid', 'year' => '2022', 'km' => '22 400 км',
			'price' => '2 140 000 ₽', 'img' => 'lot-featured.png', 'place' => 'под ключ во Владивостоке',
			'fuel' => 'гибрид', 'gearbox' => 'автомат', 'drive' => 'полный', 'body' => 'седан',
			'color' => 'чёрный', 'volume' => '2 500 см³', 'hp' => '226 л.с.', 'origin_price' => '¥1 280 000',
		),
		array(
			'num' => '1190', 'src' => 'ENCAR', 'market' => 'KR', 'meta' => 'GRADE 4.5',
			'title' => 'Hyundai Palisade', 'year' => '2023', 'km' => '18 200 км',
			'price' => '3 280 000 ₽', 'img' => 'lot-2.png', 'place' => 'под ключ во Владивостоке',
			'fuel' => 'бензин', 'gearbox' => 'автомат', 'drive' => 'полный', 'body' => 'кроссовер',
			'color' => 'белый', 'volume' => '3 500 см³', 'hp' => '249 л.с.', 'origin_price' => '28 400 000 ₩ KRW',
		),
		array(
			'num' => '0771', 'src' => 'CN NEW', 'market' => 'CN', 'meta' => 'NEW',
			'title' => 'BYD Seal', 'year' => '2026', 'km' => '0 км',
			'price' => '2 690 000 ₽', 'img' => 'lot-2.png', 'place' => 'под ключ во Владивостоке',
			'fuel' => 'электро', 'gearbox' => 'автомат', 'drive' => 'задний', 'body' => 'седан',
			'color' => 'серый', 'volume' => '—', 'hp' => '313 л.с.', 'origin_price' => '168 000 ¥ CNY',
		),
		array(
			'num' => '145951617', 'src' => 'CN AUC', 'market' => 'CN', 'meta' => 'GRADE 4',
			'title' => 'Honda Accord', 'year' => '2020', 'km' => '63 000 км',
			'price' => '2 140 000 ₽', 'img' => 'about-photo.png', 'place' => 'под ключ во Владивостоке',
			'fuel' => 'бензин', 'gearbox' => 'автомат', 'drive' => 'передний', 'body' => 'седан',
			'color' => 'красный', 'volume' => '1 400 см³', 'hp' => '141 л.с.', 'origin_price' => '42 800 ¥ CNY',
		),
	);
}

/** Демо-карточка. */
function ai_demo_card( array $lot, $featured = false ) {
	$lot['featured'] = $featured;
	$lot['url']      = add_query_arg( 'demo', $lot['num'], home_url( '/loty/demo/' ) );
	ai_render_lot_card( $lot );
}

/** Разметка карточки лота. */
function ai_render_lot_card( array $lot ): void {
	$featured = ! empty( $lot['featured'] );
	$img      = ! empty( $lot['img'] ) && str_starts_with( (string) $lot['img'], 'http' )
		? $lot['img']
		: ai_img( $lot['img'] ?: ( $featured ? 'lot-featured.png' : 'lot-2.png' ) );
	$ticket   = $featured ? 'lot-ticket.svg' : 'lot-ticket-sm.svg';
	$meta     = trim( $lot['market'] . ( ! empty( $lot['meta'] ) ? ' • ' . $lot['meta'] : '' ) );
	$right    = $featured
		? trim( ( $lot['year'] ? $lot['year'] . ' • ' : '' ) . ( $lot['km'] ?? '' ) )
		: ( $lot['km'] ?? '' );
	$url      = $lot['url'] ?? home_url( '/catalog/' );
	static $ai_lot_i = 0;
	++$ai_lot_i;
	$eager = ! is_front_page() && ( $featured || 1 === $ai_lot_i );
	?>
	<a class="lot-card<?php echo $featured ? ' lot-featured' : ''; ?>" href="<?php echo esc_url( $url ); ?>">
		<div class="lot-photo">
			<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $lot['title'] ); ?>"<?php echo $eager ? ' fetchpriority="high"' : ' loading="lazy" decoding="async"'; ?> />
			<div class="lot-ribbon">
				<img class="lot-ticket" src="<?php echo ai_img( $ticket ); ?>" alt="" width="88" height="<?php echo $featured ? '648' : '312'; ?>" />
				<span class="num"><?php echo esc_html( $lot['num'] ); ?></span>
				<span class="src">ЛОТ • <?php echo esc_html( $lot['src'] ); ?></span>
			</div>
		</div>
		<div class="lot-body">
			<div class="lot-col">
				<span class="lot-meta"><?php echo esc_html( $meta ); ?></span>
				<h3 class="lot-title"><?php echo esc_html( $lot['title'] ); ?></h3>
				<div class="lot-price"><?php echo esc_html( $lot['price'] ); ?></div>
			</div>
			<div class="lot-col lot-col-end">
				<span class="lot-meta"><?php echo esc_html( $right ); ?></span>
				<?php if ( $featured && ! empty( $lot['place'] ) ) : ?>
					<span class="lot-place"><?php echo esc_html( $lot['place'] ); ?></span>
				<?php endif; ?>
				<span class="lot-link">Смотреть лот →</span>
			</div>
		</div>
	</a>
	<?php
}

/** Данные лота из записи CPT. */
function ai_lot_card_data( $post ): array {
	$market = strtoupper( (string) get_post_meta( $post->ID, 'ai_lot_market', true ) ?: 'JP' );
	return array(
		'num'     => get_post_meta( $post->ID, 'ai_lot_number', true ) ?: '0000',
		'src'     => get_post_meta( $post->ID, 'ai_lot_source', true ) ?: $market,
		'market'  => $market,
		'meta'    => get_post_meta( $post->ID, 'ai_lot_grade', true ),
		'title'   => get_the_title( $post ),
		'year'    => get_post_meta( $post->ID, 'ai_lot_year', true ),
		'km'      => get_post_meta( $post->ID, 'ai_lot_mileage', true ),
		'price'   => get_post_meta( $post->ID, 'ai_lot_price', true ),
		'place'   => get_post_meta( $post->ID, 'ai_lot_place', true ) ?: 'под ключ во Владивостоке',
		'img'     => has_post_thumbnail( $post ) ? get_the_post_thumbnail_url( $post, 'ai-lot' ) : '',
		'url'     => get_permalink( $post ),
		'fuel'    => get_post_meta( $post->ID, 'ai_lot_fuel', true ),
		'gearbox' => get_post_meta( $post->ID, 'ai_lot_gearbox', true ),
		'drive'   => get_post_meta( $post->ID, 'ai_lot_drive', true ),
		'body'    => get_post_meta( $post->ID, 'ai_lot_body', true ),
		'color'   => get_post_meta( $post->ID, 'ai_lot_color', true ),
		'volume'  => get_post_meta( $post->ID, 'ai_lot_volume', true ),
		'hp'      => get_post_meta( $post->ID, 'ai_lot_hp', true ),
	);
}

/**
 * Выборка для сетки: Япония / Корея / Китай — живой API, иначе CPT/демо.
 *
 * @return array{lots: array, total: int, page: int, pages: int, source: string}
 */
function ai_query_lots( int $count = 6, string $market = '', array $opts = array() ): array {
	$market = strtolower( $market );
	if ( empty( $opts['demo'] ) && function_exists( 'ai_auc_search' ) ) {
		$m               = '' !== $market ? ai_auc_normalize_market( $market ) : ai_auc_request_market();
		$args            = ai_auc_request_args( $opts );
		$args['limit']   = $count;
		$args['sold']    = ! empty( $opts['sold'] );
		$args['market']  = $m;
		if ( '' === $market && ai_lot_number_search() ) {
			$ai_sort = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$q       = ai_auc_search_lot( (string) $args['lot'], $count, (int) $args['paged'], $ai_sort );
			$q['source'] = 'lot';
			$GLOBALS['ai_last_lots_query'] = $q;
			return $q;
		}
		if ( '' === trim( (string) ( $args['lot'] ?? '' ) ) && ! isset( $_GET['year_from'] ) && empty( $opts['year_from'] ) && empty( $args['year_from'] ) && empty( $opts['sold'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$args['year_from'] = 2022;
		}
		$q               = ai_auc_search( $args );
		$q['source']     = $m;
		$GLOBALS['ai_last_lots_query'] = $q;
		return $q;
	}

	$args = array(
		'post_type'      => 'ai_lot',
		'posts_per_page' => $count,
		'no_found_rows'  => true,
	);
	if ( $market ) {
		$args['meta_key']   = 'ai_lot_market';
		$args['meta_value'] = $market;
	}
	$query = new WP_Query( $args );
	$lots  = array();
	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			$lots[] = ai_lot_card_data( get_post() );
		}
		wp_reset_postdata();
	}
	$demo = ai_demo_lots();
	$i    = 0;
	while ( count( $lots ) < $count ) {
		$lot        = $demo[ $i % count( $demo ) ];
		if ( $market && strtolower( (string) ( $lot['market'] ?? '' ) ) !== $market ) {
			++$i;
			if ( $i > 20 ) {
				break;
			}
			continue;
		}
		$lot['url'] = add_query_arg( 'demo', $lot['num'], home_url( '/loty/demo/' ) );
		$lots[]     = $lot;
		++$i;
	}
	$q = array(
		'lots'   => $lots,
		'total'  => count( $lots ),
		'page'   => 1,
		'pages'  => 0,
		'source' => 'demo',
	);
	$GLOBALS['ai_last_lots_query'] = $q;
	return $q;
}

/** В адресе только номер лота (и страница/сортировка) — ищем по всем рынкам. */
function ai_lot_number_search(): bool {
	if ( empty( $_GET['lot'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return false;
	}
	foreach ( array_keys( $_GET ) as $ai_key ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! in_array( (string) $ai_key, array( 'lot', 'paged', 'sort' ), true ) ) {
			return false;
		}
	}
	return '' !== trim( sanitize_text_field( wp_unslash( $_GET['lot'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

/** Один найденный лот открываем сразу, без сетки каталога. */
add_action(
	'template_redirect',
	static function (): void {
		if ( ! is_page( 'catalog' ) || ! ai_lot_number_search() || ! function_exists( 'ai_auc_search_lot' ) ) {
			return;
		}
		$page = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $page > 1 ) {
			return;
		}
		$lot  = sanitize_text_field( wp_unslash( $_GET['lot'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$sort = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$q    = ai_auc_search_lot( $lot, 12, 1, $sort );
		if ( 1 === (int) $q['total'] && ! empty( $q['lots'][0]['url'] ) ) {
			wp_safe_redirect( $q['lots'][0]['url'] );
			exit;
		}
	},
	9
);

/** Лоты для сетки каталога / выдачи / статистики. */
function ai_grid_lots( int $count = 6, string $market = '' ): array {
	return ai_query_lots( $count, $market )['lots'];
}

/** Карточка лота 3 колонки (каталог, выдача, статистика, доставка). */
function ai_spec_card( array $lot, array $opts = array() ): void {
	$img = ! empty( $lot['img'] ) && str_starts_with( (string) $lot['img'], 'http' )
		? $lot['img']
		: ai_img( $lot['img'] ?: 'lot-2.png' );
	$url     = $lot['url'] ?? home_url( '/catalog/' );
	$title   = (string) ( $lot['title'] ?? '' );
	if ( ! empty( $lot['year'] ) ) {
		$title .= ', ' . $lot['year'];
	}
	$country = ai_market_label( (string) ( $lot['market'] ?? 'JP' ) );
	$src     = trim( (string) ( $lot['src'] ?? $lot['source'] ?? '' ) );
	if ( $src && ! in_array( strtoupper( $src ), array( 'JP', 'KR', 'CN' ), true ) ) {
		$country = $src;
	}
	$price   = (string) ( $lot['price'] ?? '' );
	if ( $price && ! str_starts_with( $price, 'от ' ) ) {
		$price = 'от ' . $price;
	}
	$rows_left  = array(
		'Пробег'   => $lot['km'] ?? '',
		'Мощность' => $lot['hp'] ?? '',
		'Топливо'  => $lot['fuel'] ?? '',
		'КПП'      => $lot['gearbox'] ?? '',
	);
	$rows_right = array(
		'Привод' => $lot['drive'] ?? '',
		'Кузов'  => $lot['body'] ?? '',
		'Цвет'   => $lot['color'] ?? '',
		'Объём'  => $lot['volume'] ?? '',
		'Лот'    => $lot['num'] ?? '',
	);
	$sold  = ! empty( $opts['sold'] );
	$grade = '';
	$ai_g  = trim( (string) ( $lot['rate'] ?? $lot['grade'] ?? '' ) );
	if ( $ai_g && preg_match( '/GRADE\s+(.+)/i', $ai_g, $ai_m ) ) {
		$ai_g = $ai_m[1];
	}
	$is_rate = $ai_g && ( ! function_exists( 'ai_auc_is_rate' ) || ai_auc_is_rate( $ai_g ) );
	if ( $sold && $is_rate ) {
		$grade = 'оценка ' . $ai_g;
	} elseif ( $sold && ! $is_rate ) {
		$grade = 'оценка 4';
	} elseif ( $is_rate ) {
		$grade = 'оценка ' . $ai_g;
	}
	static $ai_spec_i = 0;
	++$ai_spec_i;
	$eager = $ai_spec_i <= 3;
	?>
	<a class="spec-card" href="<?php echo esc_url( $url ); ?>">
		<div class="spec-photo">
			<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $lot['title'] ?? '' ); ?>" width="480" height="201"<?php echo $eager ? ' fetchpriority="high"' : ' loading="lazy" decoding="async"'; ?> />
			<?php if ( $sold ) : ?>
				<span class="spec-badge spec-badge-sold">продано</span>
			<?php endif; ?>
			<?php if ( $grade ) : ?>
				<span class="spec-badge spec-badge-grade"><?php echo esc_html( $grade ); ?></span>
			<?php endif; ?>
		</div>
		<div class="spec-info">
			<div class="spec-head">
				<span class="spec-name"><?php echo esc_html( $title ); ?></span>
				<span class="spec-country"><?php echo esc_html( $country ); ?></span>
			</div>
			<div class="spec-cols">
				<dl>
					<?php foreach ( $rows_left as $ai_label => $ai_val ) : ?>
						<?php if ( $ai_val ) : ?>
							<div><dt><?php echo esc_html( $ai_label ); ?></dt><dd><?php echo esc_html( $ai_val ); ?></dd></div>
						<?php endif; ?>
					<?php endforeach; ?>
				</dl>
				<dl>
					<?php foreach ( $rows_right as $ai_label => $ai_val ) : ?>
						<?php if ( $ai_val ) : ?>
							<div><dt><?php echo esc_html( $ai_label ); ?></dt><dd><?php echo esc_html( $ai_val ); ?></dd></div>
						<?php endif; ?>
					<?php endforeach; ?>
				</dl>
			</div>
			<div class="spec-foot">
				<span class="spec-price"><?php echo esc_html( $price ); ?></span>
				<span class="lot-link">Смотреть лот →</span>
			</div>
		</div>
	</a>
	<?php
}

/** Сетка карточек. */
function ai_spec_grid( int $count = 6, string $market = '', array $opts = array() ): void {
	$q = ( isset( $opts['query'] ) && is_array( $opts['query'] ) )
		? $opts['query']
		: ai_query_lots( $count, $market, $opts );
	if ( ! empty( $opts['title'] ) ) {
		ai_lots_toolbar( (string) $opts['title'], ! empty( $opts['h1'] ), $q );
	}
	if ( empty( $q['lots'] ) ) {
		$ai_lot_q = isset( $_GET['lot'] ) ? sanitize_text_field( wp_unslash( $_GET['lot'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' !== $ai_lot_q && function_exists( 'ai_lot_number_search' ) && ai_lot_number_search() ) {
			echo '<p class="catalog-empty">Лот ' . esc_html( $ai_lot_q ) . ' не найден. Проверьте номер или напишите — найдём вручную.</p>';
		} else {
			echo '<p class="catalog-empty">По этим параметрам лотов сейчас нет. Снимите часть фильтров или напишите — подберём вручную.</p>';
		}
		return;
	}
	echo '<div class="spec-grid">';
	foreach ( $q['lots'] as $lot ) {
		ai_spec_card( $lot, $opts );
	}
	echo '</div>';
}

/** Заголовок витрины: счётчик и сортировка. */
function ai_lots_toolbar( string $title, bool $h1, array $q ): void {
	echo '<div class="sec-head catalog-head">';
	printf( $h1 ? '<h1 class="h2">%s</h1>' : '<h2 class="h2">%s</h2>', esc_html( $title ) );
	echo '<div class="catalog-tools">';
	if ( ! empty( $q['total'] ) && in_array( $q['source'] ?? '', array( 'jp', 'kr', 'cn', 'lot' ), true ) ) {
		$n   = (int) $q['total'];
		$mod = $n % 100;
		$one = $n % 10;
		$w   = ( $mod > 10 && $mod < 20 ) || 0 === $one || $one >= 5 ? 'лотов' : ( 1 === $one ? 'лот' : 'лота' );
		printf( '<p class="catalog-count">%s %s</p>', esc_html( ai_auc_num( $n ) ), esc_html( $w ) );
	}
	if ( in_array( $q['source'] ?? '', array( 'jp', 'kr', 'cn', 'lot' ), true ) ) {
		$cur = isset( $_GET['sort'] ) ? sanitize_text_field( wp_unslash( $_GET['sort'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<form class="catalog-sort" method="get">';
		foreach ( $_GET as $key => $val ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( in_array( $key, array( 'sort', 'paged' ), true ) || is_array( $val ) ) {
				continue;
			}
			printf(
				'<input type="hidden" name="%s" value="%s" />',
				esc_attr( sanitize_key( (string) $key ) ),
				esc_attr( sanitize_text_field( wp_unslash( (string) $val ) ) )
			);
		}
		echo '<label><span>Сортировка</span><select name="sort" onchange="this.form.submit()">';
		foreach ( array(
			''      => 'Сначала новые годы',
			'fresh' => 'Свежие торги',
			'price' => 'Сначала дешевле',
			'km'    => 'Меньше пробег',
			'year'  => 'Сначала старше',
		) as $val => $label ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $val ),
				selected( $cur, $val, false ),
				esc_html( $label )
			);
		}
		echo '</select></label></form>';
	}
	echo '</div></div>';
}

/** Стрелка крошек из макета (тот же вектор, что у пагинации). */
function ai_crumb_chev(): string {
	$path = get_theme_file_path( 'assets/img/pager-chevron.svg' );
	$svg  = is_readable( $path ) ? (string) file_get_contents( $path ) : '';
	$svg  = str_replace( 'stroke="#0A0A0B"', 'stroke="currentColor"', $svg );
	$svg  = preg_replace( '/<svg\b/', '<svg class="crumb-chev" aria-hidden="true"', $svg, 1 );
	return $svg ?: '<span aria-hidden="true">›</span>';
}

/** Крошки. */
function ai_crumbs( string $current ): void {
	printf(
		'<div class="crumbs"><a href="%1$s">Главная</a> %2$s <span>%3$s</span></div>',
		esc_url( home_url( '/' ) ),
		ai_crumb_chev(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme SVG
		esc_html( $current )
	);
}

/** Пагинация: живые страницы API или макет, если выборки нет. */
function ai_figma_pager(): void {
	$q = $GLOBALS['ai_last_lots_query'] ?? null;
	if ( is_array( $q ) && (int) ( $q['pages'] ?? 0 ) > 1 ) {
		ai_render_pager( (int) $q['page'], (int) $q['pages'] );
		return;
	}
}

/** Пагинация в стиле макета. */
function ai_render_pager( int $page, int $pages ): void {
	if ( $pages <= 1 ) {
		return;
	}
	$chev_path = get_theme_file_path( 'assets/img/pager-chevron.svg' );
	$chev      = is_readable( $chev_path ) ? (string) file_get_contents( $chev_path ) : '';
	$chev      = str_replace( 'stroke="#0A0A0B"', 'stroke="currentColor"', $chev );
	$chev      = preg_replace( '/<svg\b/', '<svg aria-hidden="true"', $chev, 1 );
	$url       = static function ( int $p ): string {
		$base = remove_query_arg( 'paged' );
		return $p <= 1 ? $base : add_query_arg( 'paged', $p, $base );
	};
	$nums = array();
	if ( $pages <= 7 ) {
		$nums = range( 1, $pages );
	} else {
		$nums[] = 1;
		$start  = max( 2, $page - 1 );
		$end    = min( $pages - 1, $page + 1 );
		if ( $start > 2 ) {
			$nums[] = 0;
		}
		for ( $i = $start; $i <= $end; $i++ ) {
			$nums[] = $i;
		}
		if ( $end < $pages - 1 ) {
			$nums[] = 0;
		}
		$nums[] = $pages;
	}
	echo '<nav class="pager pager-figma" aria-label="Страницы">';
	if ( $page > 1 ) {
		printf( '<a class="page-numbers page-arrow" href="%s" aria-label="Назад">%s</a>', esc_url( $url( $page - 1 ) ), $chev ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme SVG
	} else {
		printf( '<span class="page-numbers page-arrow" aria-hidden="true">%s</span>', $chev ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme SVG
	}
	echo '<span class="pager-nums">';
	foreach ( $nums as $n ) {
		if ( 0 === $n ) {
			echo '<span class="page-numbers dots">…</span>';
			continue;
		}
		if ( $n === $page ) {
			printf( '<span class="page-numbers current">%d</span>', $n );
		} else {
			printf( '<a class="page-numbers" href="%s">%d</a>', esc_url( $url( $n ) ), $n );
		}
	}
	echo '</span>';
	if ( $page < $pages ) {
		printf( '<a class="page-numbers page-arrow page-arrow-next" href="%s" aria-label="Вперёд">%s</a>', esc_url( $url( $page + 1 ) ), $chev ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme SVG
	} else {
		printf( '<span class="page-numbers page-arrow page-arrow-next" aria-hidden="true">%s</span>', $chev ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme SVG
	}
	echo '</nav>';
}

/** Видео-отзывы (главная, доставка, выдача). */
function ai_reviews_row(): void {
	$items = ai_reviews_registry();
	echo '<div class="reviews-grid">';
	foreach ( $items as $item ) {
		$caption = $item['name'] . ', ' . $item['city'] . ' — ' . $item['car'];
		printf(
			'<article class="review-card"><button type="button" class="r-photo" data-review-open data-video="%1$s" data-poster="%2$s" data-title="%3$s" data-quote="%4$s" data-desc="%5$s" aria-label="Смотреть отзыв: %3$s"><img src="%2$s" alt="%3$s" loading="lazy" /></button><div class="review-info"><span class="r-name">%6$s</span><span class="r-price">%7$s</span><button type="button" class="lot-link" data-review-open data-video="%1$s" data-poster="%2$s" data-title="%3$s" data-quote="%4$s" data-desc="%5$s">Смотреть отзыв →</button></div></article>',
			esc_url( $item['video'] ),
			esc_url( $item['poster'] ),
			esc_attr( $caption ),
			esc_attr( $item['quote'] ),
			esc_attr( $item['desc'] ),
			esc_html( $caption ),
			esc_html( $item['price'] )
		);
	}
	echo '</div>';
}

/** Записи журнала: реестр темы (контент с Яндекс.Диска). */
function ai_journal_items(): array {
	$items = ai_journal_registry();
	return $items ?: array();
}

/** Карточка журнала. */
function ai_journal_card( array $item ): void {
	$featured = ! empty( $item['featured'] );
	$img      = $item['img'] ?? '';
	if ( $img && ! str_starts_with( (string) $img, 'http' ) ) {
		$img = ai_img( $img );
	}
	$ai_cta = $featured ? 'Читать статью →' : 'Читать →';
	?>
	<article class="post-card<?php echo $featured ? ' post-card-featured' : ''; ?>">
		<div>
			<div class="kicker"><?php echo esc_html( $item['kicker'] ); ?></div>
			<h3><?php echo esc_html( $item['title'] ); ?></h3>
		</div>
		<?php if ( $featured && $img ) : ?>
			<img class="post-shot" src="<?php echo esc_url( $img ); ?>" alt="" loading="lazy" />
		<?php endif; ?>
		<a class="lot-link" href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $ai_cta ); ?></a>
	</article>
	<?php
}

/** CPT: Заявки. */
add_action( 'init', function () {
	register_post_type( 'ai_lead', array(
		'labels'        => array(
			'name'          => 'Заявки',
			'singular_name' => 'Заявка',
			'menu_name'     => 'Заявки',
		),
		'public'        => false,
		'show_ui'       => true,
		'menu_icon'     => 'dashicons-email-alt',
		'menu_position' => 6,
		'supports'      => array( 'title', 'editor', 'custom-fields' ),
	) );
} );

/** Обработка формы заявки. */
add_action( 'admin_post_ai_lead', 'ai_handle_lead' );
add_action( 'admin_post_nopriv_ai_lead', 'ai_handle_lead' );

function ai_handle_lead() {
	$redirect = wp_get_referer() ?: home_url( '/' );
	if ( ! isset( $_POST['ai_lead_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ai_lead_nonce'] ) ), 'ai_lead' ) ) {
		wp_safe_redirect( add_query_arg( 'lead', 'error', $redirect ) );
		exit;
	}
	if ( ! empty( $_POST['ai_hp'] ) ) {
		wp_safe_redirect( add_query_arg( 'lead', 'sent', $redirect ) );
		exit;
	}
	$name   = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
	$phone  = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
	$task   = sanitize_textarea_field( wp_unslash( $_POST['task'] ?? '' ) );
	$budget = sanitize_text_field( wp_unslash( $_POST['budget'] ?? '' ) );
	$model  = sanitize_text_field( wp_unslash( $_POST['model'] ?? '' ) );
	$source = sanitize_key( wp_unslash( $_POST['ai_source'] ?? '' ) );
	$bits   = array();
	if ( '' !== $budget ) {
		$bits[] = 'Бюджет: ' . $budget;
	}
	if ( '' !== $model ) {
		$bits[] = 'Авто: ' . $model;
	}
	if ( '' !== $task ) {
		$bits[] = $task;
	}
	$task = implode( "\n", $bits );
	if ( '' === $name && '' !== $phone && in_array( $source, array( 'consult', 'order' ), true ) ) {
		$name = 'consult' === $source ? 'Консультация' : 'Заявка с главной';
	}
	if ( '' === $name || '' === $phone ) {
		wp_safe_redirect( add_query_arg( 'lead', 'error', $redirect ) );
		exit;
	}
	wp_insert_post( array(
		'post_type'    => 'ai_lead',
		'post_status'  => 'publish',
		'post_title'   => $name . ' · ' . $phone,
		'post_content' => $task,
		'meta_input'   => array( 'ai_lead_phone' => $phone ),
	) );
	wp_mail( AI_LEAD_EMAIL, 'АвтоИгнат: задача с сайта', "Имя: {$name}\nТелефон: {$phone}\nЗадача: {$task}" );
	wp_safe_redirect( add_query_arg( 'lead', 'sent', $redirect ) );
	exit;
}

/** Карточка лота /aukcion/{id}/ и статья /zhurnal/{slug}/. */
add_action(
	'init',
	static function () {
		add_rewrite_rule( '^aukcion/([^/]+)/?$', 'index.php?ai_auc_lot=$matches[1]', 'top' );
		add_rewrite_rule( '^loty/aukcion/([^/]+)/?$', 'index.php?ai_auc_lot=$matches[1]', 'top' );
		add_rewrite_rule( '^zhurnal/([^/]+)/?$', 'index.php?ai_journal_slug=$matches[1]', 'top' );
		if ( '2.4.16' !== get_option( 'ai_rewrite_ver' ) ) {
			flush_rewrite_rules( false );
			update_option( 'ai_rewrite_ver', '2.4.16' );
		}
	}
);

add_filter(
	'query_vars',
	static function ( array $vars ): array {
		$vars[] = 'ai_auc_lot';
		$vars[] = 'ai_journal_slug';
		return $vars;
	}
);

add_action(
	'template_redirect',
	static function () {
		$uri = $_SERVER['REQUEST_URI'] ?? '';

		// Старый путь карточки → канон /aukcion/{id}/ (SEO-план октября).
		if ( preg_match( '#^/loty/aukcion/([^/]+)/?#', $uri, $loty_m ) ) {
			$target = home_url( '/aukcion/' . rawurlencode( rawurldecode( $loty_m[1] ) ) . '/' );
			$qs     = wp_parse_url( $uri, PHP_URL_QUERY );
			if ( is_string( $qs ) && '' !== $qs ) {
				$target .= ( str_contains( $target, '?' ) ? '&' : '?' ) . $qs;
			}
			wp_safe_redirect( $target, 301 );
			exit;
		}

		$jslug = get_query_var( 'ai_journal_slug' );
		if ( ! $jslug && preg_match( '#/zhurnal/([^/]+)/?#', $uri, $jm ) ) {
			$jslug = rawurldecode( $jm[1] );
			set_query_var( 'ai_journal_slug', $jslug );
		}
		if ( $jslug && 'zhurnal' !== $jslug ) {
			$article = ai_journal_article( (string) $jslug );
			if ( ! $article ) {
				global $wp_query;
				if ( $wp_query instanceof WP_Query ) {
					$wp_query->set_404();
				}
				status_header( 404 );
				nocache_headers();
				include get_query_template( '404' );
				exit;
			}
			$GLOBALS['ai_journal_article'] = $article;
			global $wp_query;
			if ( $wp_query instanceof WP_Query ) {
				$wp_query->is_404  = false;
				$wp_query->is_page = true;
			}
			status_header( 200 );
			include get_theme_file_path( 'template-journal-article.php' );
			exit;
		}

		$id = get_query_var( 'ai_auc_lot' );
		if ( ! $id && preg_match( '#/(?:loty/)?aukcion/([^/]+)/?#', $uri, $m ) ) {
			$id = rawurldecode( $m[1] );
			set_query_var( 'ai_auc_lot', $id );
		}
		$is_demo = (bool) preg_match( '#/loty/demo/?#', $uri );
		if ( ! $id && ! $is_demo ) {
			return;
		}
		if ( $id ) {
			$lot = ai_auc_lot( (string) $id );
			if ( ! $lot ) {
				// Пропавший лот — 410 Gone (SEO-план), не 404.
				status_header( 410 );
				nocache_headers();
				global $wp_query;
				if ( $wp_query instanceof WP_Query ) {
					$wp_query->set_404();
				}
				include get_query_template( '404' );
				exit;
			}
			$GLOBALS['ai_auc_lot_data'] = $lot;
		}
		global $wp_query;
		if ( $wp_query instanceof WP_Query ) {
			$wp_query->is_404  = false;
			$wp_query->is_page = true;
		}
		status_header( 200 );
		if ( ! headers_sent() ) {
			header( 'Cache-Control: public, max-age=120', true );
		}
		include get_theme_file_path( 'single-ai_lot.php' );
		exit;
	}
);

add_filter(
	'pre_get_document_title',
	static function ( $title ) {
		$article = $GLOBALS['ai_journal_article'] ?? null;
		if ( is_array( $article ) && ! empty( $article['title'] ) ) {
			return $article['title'] . ' — АвтоИгнат';
		}
		$lot = $GLOBALS['ai_auc_lot_data'] ?? null;
		if ( is_array( $lot ) && ! empty( $lot['title'] ) ) {
			return $lot['title'] . ' — АвтоИгнат';
		}
		$uri = $_SERVER['REQUEST_URI'] ?? '';
		if ( ! preg_match( '#/loty/demo/?#', $uri ) ) {
			return $title;
		}
		$n = isset( $_GET['demo'] ) ? sanitize_text_field( wp_unslash( $_GET['demo'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		foreach ( ai_demo_lots() as $lot ) {
			if ( (string) $lot['num'] === (string) $n || '' === $n ) {
				return $lot['title'] . ' — АвтоИгнат';
			}
		}
		return $title;
	}
);

/** Шаблоны страниц по слагу, даже если в WP не выбран template. */
add_filter(
	'template_include',
	static function ( string $template ): string {
		if ( ! is_page() ) {
			return $template;
		}
		$map = array(
			'o-kompanii'           => 'template-about.php',
			'kontakty'             => 'template-contacts.php',
			'catalog'              => 'template-catalog.php',
			'dostavka-iz-yaponii'  => 'template-delivery.php',
			'dostavka-iz-kitaya'   => 'template-delivery.php',
			'dostavka-iz-korei'    => 'template-delivery.php',
			'zhurnal'              => 'template-journal.php',
			'statistika'           => 'template-stats.php',
			'vydacha'              => 'template-vydacha.php',
		);
		$slug = get_post_field( 'post_name', get_queried_object_id() );
		if ( isset( $map[ $slug ] ) ) {
			$file = get_theme_file_path( $map[ $slug ] );
			if ( is_readable( $file ) ) {
				return $file;
			}
		}
		return $template;
	}
);
