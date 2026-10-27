<?php
/**
 * Template Name: Журнал
 *
 * @package auto-ignat
 */

get_header();

$ai_items    = ai_journal_items();
$ai_featured = array_slice( $ai_items, 0, 2 );
$ai_rest     = array_slice( $ai_items, 2 );
?>
<div class="page-head">
	<div class="wrap">
		<?php ai_crumbs( 'Журнал' ); ?>
		<h1>Журнал Авто Игната</h1>
	</div>
</div>

<div class="wrap page-body">
	<?php if ( $ai_featured ) : ?>
	<div class="journal-featured">
		<?php
		foreach ( $ai_featured as $ai_item ) {
			$ai_item['featured'] = true;
			ai_journal_card( $ai_item );
		}
		?>
	</div>
	<?php endif; ?>
	<?php if ( $ai_rest ) : ?>
		<div class="journal-grid">
			<?php
			foreach ( $ai_rest as $ai_item ) {
				$ai_item['featured'] = false;
				ai_journal_card( $ai_item );
			}
			?>
		</div>
	<?php endif; ?>
	<?php if ( ! $ai_items ) : ?>
		<p>Статьи скоро появятся.</p>
	<?php endif; ?>
</div>

<?php
get_footer();
