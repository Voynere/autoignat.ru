<?php
/**
 * Обычная страница.
 *
 * @package auto-ignat
 */

get_header();
?>
<div class="page-head">
	<div class="wrap">
		<div class="crumbs"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Главная</a> <?php echo ai_crumb_chev(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme SVG ?> <span><?php echo esc_html( get_the_title() ); ?></span></div>
		<h1><?php echo esc_html( get_the_title() ); ?></h1>
	</div>
</div>

<div class="wrap page-body">
	<?php while ( have_posts() ) : the_post(); ?>
		<div class="prose">
			<?php the_content(); ?>
		</div>
	<?php endwhile; ?>
</div>

<?php
get_footer();
