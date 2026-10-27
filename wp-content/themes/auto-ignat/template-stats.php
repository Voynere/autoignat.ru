<?php
/**
 * Template Name: Статистика продаж
 *
 * @package auto-ignat
 */

get_header();
?>
<div class="page-head">
	<div class="wrap">
		<?php ai_crumbs( 'Статистика' ); ?>
	</div>
</div>

<div class="wrap page-body">
	<?php
	get_template_part(
		'template-parts/filter',
		null,
		array(
			'action'       => get_permalink(),
			'hide_country' => true,
			'live_makes'   => true,
		)
	);
	?>
	<?php ai_spec_grid( 12, 'jp', array( 'sold' => true, 'year_from' => 2022, 'title' => 'Проданные лоты' ) ); ?>
	<?php ai_figma_pager(); ?>
</div>

<?php
get_footer();
