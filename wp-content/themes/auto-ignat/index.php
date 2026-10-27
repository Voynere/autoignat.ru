<?php
/**
 * Журнал (список записей).
 *
 * @package auto-ignat
 */

get_header();
?>
<div class="page-head">
	<div class="wrap">
		<div class="crumbs"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Главная</a> <?php echo ai_crumb_chev(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme SVG ?> <span>Журнал</span></div>
		<h1>Журнал АвтоИгната</h1>
	</div>
</div>

<div class="wrap page-body">
	<?php if ( have_posts() ) : ?>
		<div class="posts">
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<article class="post-card">
					<div class="kicker"><?php echo esc_html( get_the_date() ); ?></div>
					<h3><?php echo esc_html( get_the_title() ); ?></h3>
					<a class="lot-link" href="<?php the_permalink(); ?>">Читать →</a>
				</article>
				<?php
			endwhile;
			?>
		</div>
		<?php
		$ai_links = paginate_links(
			array(
				'prev_text' => '←',
				'next_text' => '→',
				'mid_size'  => 1,
			)
		);
		if ( $ai_links ) {
			echo '<nav class="pager">' . wp_kses_post( $ai_links ) . '</nav>';
		}
		?>
	<?php else : ?>
		<div class="prose">
			<p>Статьи скоро появятся. Следите за обновлениями.</p>
		</div>
	<?php endif; ?>
</div>

<?php
get_footer();
