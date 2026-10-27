<?php
/**
 * Шапка сайта.
 *
 * @package auto-ignat
 */
?><!DOCTYPE html>
<html lang="ru" data-theme="light" <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta name="color-scheme" content="light dark" />
	<script>
	(function () {
		try {
			var t = localStorage.getItem("ai-theme");
			if (t === "dark" || t === "light") {
				document.documentElement.setAttribute("data-theme", t);
			}
		} catch (e) {}
	})();
	</script>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="top">
	<div class="wrap nav">
		<button class="burger" id="burger" aria-label="Меню" aria-controls="mobile-menu" aria-expanded="false" type="button">
			<svg class="burger-ico burger-ico-open" viewBox="0 0 24 24" width="22" height="22" fill="none" aria-hidden="true">
				<path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
			</svg>
			<svg class="burger-ico burger-ico-close" viewBox="0 0 24 24" width="22" height="22" fill="none" aria-hidden="true">
				<path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
			</svg>
		</button>
		<?php ai_brand(); ?>

		<nav class="menu" aria-label="Главное меню">
			<a href="<?php echo ai_anchor( 'hits' ); ?>">Хиты!</a>
			<a href="<?php echo esc_url( home_url( '/o-kompanii/' ) ); ?>">О компании<span class="menu-mark" aria-hidden="true"></span></a>
			<a href="<?php echo esc_url( home_url( '/kontakty/' ) ); ?>">Контакты</a>
		</nav>

		<div class="nav-meta">
			<a class="nav-city" href="<?php echo esc_url( home_url( '/kontakty/' ) ); ?>">
				<svg class="nav-pin" viewBox="0 0 16 16" width="16" height="16" aria-hidden="true" fill="none">
					<path d="M8 14.2s4.2-3.15 4.2-6.55a4.2 4.2 0 1 0-8.4 0C3.8 11.05 8 14.2 8 14.2Z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/>
					<circle cx="8" cy="7.6" r="1.45" stroke="currentColor" stroke-width="1.3"/>
				</svg>
				Владивосток
			</a>
			<a class="nav-phone mono" href="<?php echo esc_attr( ai_phone_href() ); ?>"><?php echo esc_html( ai_phone() ); ?></a>
		</div>

		<div class="nav-quick">
			<a class="nav-icon" href="<?php echo esc_attr( ai_phone_href() ); ?>" aria-label="Позвонить, <?php echo esc_attr( ai_phone() ); ?>">
				<svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
					<path d="M8 3.6h2.1c.4 0 .8.3.9.7l.8 2.6c.1.4 0 .8-.3 1.1L9.9 9.4a11.2 11.2 0 0 0 4.7 4.7l1.4-1.6c.3-.3.7-.4 1.1-.3l2.6.8c.4.1.7.5.7.9V16a1.7 1.7 0 0 1-1.6 1.7A14.6 14.6 0 0 1 6.3 7.2 1.7 1.7 0 0 1 8 5.6v-2Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
				</svg>
			</a>
			<div class="nav-chat" data-nav-chat>
				<button class="nav-icon" type="button" aria-expanded="false" aria-controls="nav-chat-menu" aria-label="Мессенджеры">
					<svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">
						<path d="M5.6 6.2h12.8a1.6 1.6 0 0 1 1.6 1.6v6a1.6 1.6 0 0 1-1.6 1.6h-6.4L7.4 18.6v-3.2H5.6a1.6 1.6 0 0 1-1.6-1.6v-6a1.6 1.6 0 0 1 1.6-1.6Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
						<path d="M8 9.7h8M8 12.5h5.2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
					</svg>
				</button>
				<div class="nav-chat-menu" id="nav-chat-menu" hidden>
					<a href="<?php echo esc_url( ai_telegram_url() ); ?>" target="_blank" rel="noopener nofollow">Telegram</a>
					<a href="<?php echo esc_url( ai_whatsapp_url() ); ?>" target="_blank" rel="noopener nofollow">WhatsApp</a>
					<a href="<?php echo esc_url( ai_max_url() ); ?>" target="_blank" rel="noopener nofollow">MAX</a>
				</div>
			</div>
		</div>

		<div class="nav-cta">
			<div class="nav-socials">
				<?php get_template_part( 'template-parts/soc', null, array( 'place' => 'bar' ) ); ?>
				<button class="theme-toggle" type="button" data-theme-toggle aria-label="Тёмная тема">
					<svg class="theme-ico theme-ico-sun" viewBox="0 0 24 24" fill="none" aria-hidden="true">
						<circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.6"/>
						<path d="M12 3v2.2M12 18.8V21M3 12h2.2M18.8 12H21M5.6 5.6l1.6 1.6M16.8 16.8l1.6 1.6M18.4 5.6l-1.6 1.6M7.2 16.8l-1.6 1.6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
					</svg>
					<svg class="theme-ico theme-ico-moon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
						<path d="M15.2 4.2A7.8 7.8 0 1 0 19.8 14 6.2 6.2 0 0 1 15.2 4.2Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
					</svg>
				</button>
			</div>
			<button class="btn" data-open-modal type="button">Заказать звонок</button>
		</div>
	</div>

	<nav class="subnav" aria-label="Каталог и направления">
		<div class="wrap subnav-track">
			<a class="ticker-item" href="<?php echo esc_url( home_url( '/catalog/' ) ); ?>">
				<svg class="subnav-ico" viewBox="0 0 16 16" width="16" height="16" aria-hidden="true" fill="none">
					<path d="M2.75 4.25h10.5M2.75 8h10.5M2.75 11.75h10.5" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/>
				</svg>
				Каталог
			</a>
			<a class="ticker-item" href="<?php echo esc_url( home_url( '/dostavka-iz-kitaya/' ) ); ?>">
				<svg class="subnav-flag" viewBox="0 0 30 20" width="18" height="12" aria-hidden="true">
					<rect width="30" height="20" fill="#DE2910"/>
					<g fill="#FFDE00">
						<polygon points="5.00,2.00 5.67,4.07 7.85,4.07 6.09,5.35 6.76,7.43 5.00,6.15 3.24,7.43 3.91,5.35 2.15,4.07 4.33,4.07"/>
						<polygon points="9.14,2.51 9.62,1.97 9.25,1.34 9.91,1.63 10.39,1.08 10.33,1.80 11.00,2.09 10.29,2.25 10.22,2.97 9.85,2.35"/>
						<polygon points="11.01,4.14 11.66,3.82 11.56,3.10 12.07,3.62 12.72,3.30 12.38,3.95 12.88,4.47 12.17,4.34 11.83,4.99 11.73,4.27"/>
						<polygon points="11.04,6.73 11.76,6.70 11.96,6.00 12.21,6.68 12.94,6.66 12.37,7.10 12.62,7.79 12.01,7.38 11.44,7.83 11.64,7.13"/>
						<polygon points="9.22,8.38 9.90,8.63 10.35,8.06 10.32,8.79 11.00,9.05 10.30,9.24 10.26,9.96 9.87,9.36 9.16,9.55 9.62,8.98"/>
					</g>
				</svg>
				Авто из Китая
			</a>
			<a class="ticker-item" href="<?php echo esc_url( home_url( '/dostavka-iz-korei/' ) ); ?>">
				<svg class="subnav-flag" viewBox="0 0 36 24" width="18" height="12" aria-hidden="true">
					<rect width="36" height="24" fill="#fff"/>
					<g transform="translate(18 12) rotate(-33.69)">
						<path fill="#CD2E3A" d="M0-6a6 6 0 0 1 0 12 3 3 0 0 1 0-6 3 3 0 0 0 0-6z"/>
						<path fill="#0047A0" d="M0-6a6 6 0 0 0 0 12 3 3 0 0 0 0-6 3 3 0 0 1 0-6z"/>
					</g>
					<g fill="#141414">
						<g transform="translate(8.4 6.1) rotate(-50)">
							<rect x="-3.5" y="-1.65" width="7" height=".8"/>
							<rect x="-3.5" y="-.4" width="7" height=".8"/>
							<rect x="-3.5" y=".85" width="7" height=".8"/>
						</g>
						<g transform="translate(27.6 17.9) rotate(-50)">
							<rect x="-3.5" y="-1.65" width="3" height=".8"/>
							<rect x=".5" y="-1.65" width="3" height=".8"/>
							<rect x="-3.5" y="-.4" width="3" height=".8"/>
							<rect x=".5" y="-.4" width="3" height=".8"/>
							<rect x="-3.5" y=".85" width="3" height=".8"/>
							<rect x=".5" y=".85" width="3" height=".8"/>
						</g>
						<g transform="translate(27.6 6.1) rotate(40)">
							<rect x="-3.5" y="-1.65" width="3" height=".8"/>
							<rect x=".5" y="-1.65" width="3" height=".8"/>
							<rect x="-3.5" y="-.4" width="7" height=".8"/>
							<rect x="-3.5" y=".85" width="3" height=".8"/>
							<rect x=".5" y=".85" width="3" height=".8"/>
						</g>
						<g transform="translate(8.4 17.9) rotate(40)">
							<rect x="-3.5" y="-1.65" width="7" height=".8"/>
							<rect x="-3.5" y="-.4" width="3" height=".8"/>
							<rect x=".5" y="-.4" width="3" height=".8"/>
							<rect x="-3.5" y=".85" width="7" height=".8"/>
						</g>
					</g>
				</svg>
				Авто из Кореи
			</a>
			<a class="ticker-item" href="<?php echo esc_url( home_url( '/dostavka-iz-yaponii/' ) ); ?>">
				<svg class="subnav-dot" viewBox="0 0 8 8" width="8" height="8" aria-hidden="true">
					<circle cx="4" cy="4" r="4" fill="#E23B2E"/>
				</svg>
				Авто из Японии
			</a>
			<a class="ticker-item" href="<?php echo esc_url( home_url( '/statistika/' ) ); ?>">Статистика</a>
		</div>
	</nav>
</header>

<div class="drawer-overlay" id="drawer-overlay" hidden></div>
<aside class="drawer" id="mobile-menu" hidden aria-hidden="true" aria-label="Мобильное меню">
	<nav class="drawer-nav">
		<a href="<?php echo ai_anchor( 'hits' ); ?>">Хиты</a>
		<a href="<?php echo esc_url( home_url( '/o-kompanii/' ) ); ?>">О компании</a>
		<a href="<?php echo esc_url( home_url( '/kontakty/' ) ); ?>">Контакты</a>
		<a href="<?php echo esc_url( home_url( '/catalog/' ) ); ?>">Каталог</a>
		<a href="<?php echo esc_url( home_url( '/zhurnal/' ) ); ?>">Журнал</a>
		<a href="<?php echo esc_url( home_url( '/dostavka-iz-kitaya/' ) ); ?>">Авто из Китая</a>
		<a href="<?php echo esc_url( home_url( '/dostavka-iz-korei/' ) ); ?>">Авто из Кореи</a>
		<a href="<?php echo esc_url( home_url( '/dostavka-iz-yaponii/' ) ); ?>">Авто из Японии</a>
		<a href="<?php echo esc_url( home_url( '/statistika/' ) ); ?>">Статистика</a>
	</nav>
	<div class="drawer-contacts">
		<a class="drawer-phone mono" href="<?php echo esc_attr( ai_phone_href() ); ?>"><?php echo esc_html( ai_phone() ); ?></a>
		<a class="drawer-mail" href="mailto:ai-geniusv@yandex.ru">ai-geniusv@yandex.ru</a>
		<p class="drawer-addr"><?php echo esc_html( ai_office_address_short() ); ?></p>
	</div>
</aside>

<main>
