<?php
/**
 * 404.
 *
 * @package auto-ignat
 */

get_header();
?>
<div class="page-head">
	<div class="wrap">
		<div class="crumbs"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Главная</a> <?php echo ai_crumb_chev(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme SVG ?> <span>404</span></div>
		<h1>Страница не найдена</h1>
	</div>
</div>

<div class="wrap page-body">
	<div class="quote-card" style="max-width:648px">
		<p class="q-text">Такой страницы нет. Есть каталог лотов и живой байер.</p>
		<p><a class="btn" href="<?php echo esc_url( home_url( '/' ) ); ?>">На главную</a></p>
	</div>
</div>

<?php
get_footer();
