<?php
/**
 * Одна запись.
 *
 * @package auto-ignat
 */

get_header();
?>
<div class="page-head">
	<div class="wrap">
		<div class="crumbs"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Главная</a> <?php echo ai_crumb_chev(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme SVG ?> <a href="<?php echo esc_url( home_url( '/zhurnal/' ) ); ?>">Журнал</a></div>
		<h1><?php echo esc_html( get_the_title() ); ?></h1>
	</div>
</div>

<div class="wrap page-body">
	<?php while ( have_posts() ) : the_post(); ?>
		<article class="prose">
			<div class="kicker" style="margin-bottom:16px"><?php echo esc_html( get_the_date() ); ?></div>
			<?php the_content(); ?>
		</article>
	<?php endwhile; ?>
</div>

<?php
get_footer();
