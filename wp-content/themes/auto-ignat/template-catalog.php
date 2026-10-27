<?php
/**
 * Template Name: Каталог
 *
 * Каталог лотов с фильтром и пагинацией.
 *
 * @package auto-ignat
 */

get_header();
?>
<div class="page-head">
	<div class="wrap">
		<?php ai_crumbs( 'Каталог' ); ?>
	</div>
</div>

<div class="wrap page-body">
	<?php
	get_template_part(
		'template-parts/filter',
		null,
		array(
			'live_makes' => true,
		)
	);
	$ai_lot_q = isset( $_GET['lot'] ) ? sanitize_text_field( wp_unslash( $_GET['lot'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	ai_spec_grid( 12, '', array( 'title' => '' !== $ai_lot_q ? 'Лот ' . $ai_lot_q : 'Каталог', 'h1' => true ) );
	ai_figma_pager();
	?>
</div>

<?php
get_footer();
