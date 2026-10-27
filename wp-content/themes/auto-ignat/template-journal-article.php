<?php
/**
 * Статья журнала из реестра content/journal/.
 *
 * @package auto-ignat
 */

$ai_article = $GLOBALS['ai_journal_article'] ?? null;
if ( ! is_array( $ai_article ) ) {
	status_header( 404 );
	nocache_headers();
	include get_query_template( '404' );
	return;
}

get_header();

$ai_images = $ai_article['images'] ?? array();
$ai_cover  = $ai_images[0] ?? null;
?>
<div class="page-head">
	<div class="wrap">
		<div class="crumbs">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Главная</a>
			<?php echo ai_crumb_chev(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<a href="<?php echo esc_url( home_url( '/zhurnal/' ) ); ?>">Журнал</a>
			<?php echo ai_crumb_chev(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span><?php echo esc_html( $ai_article['title'] ); ?></span>
		</div>
		<div class="kicker" style="margin-bottom:12px"><?php echo esc_html( $ai_article['kicker'] ); ?></div>
		<h1><?php echo esc_html( $ai_article['title'] ); ?></h1>
		<?php if ( ! empty( $ai_article['lead'] ) ) : ?>
			<p class="page-lead"><?php echo esc_html( $ai_article['lead'] ); ?></p>
		<?php endif; ?>
	</div>
</div>

<div class="wrap page-body article-layout">
	<?php if ( $ai_images ) : ?>
		<div class="article-carousel" data-carousel>
			<div class="article-carousel-stage">
				<?php foreach ( $ai_images as $i => $img ) : ?>
					<figure class="article-slide<?php echo 0 === $i ? ' is-on' : ''; ?>" data-slide="<?php echo (int) $i; ?>"<?php echo 0 === $i ? '' : ' hidden'; ?>>
						<img src="<?php echo esc_url( $img['src'] ); ?>" alt="<?php echo esc_attr( $img['alt'] ); ?>" <?php echo 0 === $i ? 'fetchpriority="high"' : 'loading="lazy"'; ?> />
						<?php if ( ! empty( $img['alt'] ) ) : ?>
							<figcaption><?php echo esc_html( $img['alt'] ); ?></figcaption>
						<?php endif; ?>
					</figure>
				<?php endforeach; ?>
			</div>
			<?php if ( count( $ai_images ) > 1 ) : ?>
				<div class="article-carousel-nav">
					<button type="button" class="chip" data-carousel-prev aria-label="Назад">←</button>
					<div class="article-dots" role="tablist">
						<?php foreach ( $ai_images as $i => $img ) : ?>
							<button type="button" class="article-dot<?php echo 0 === $i ? ' is-on' : ''; ?>" data-carousel-goto="<?php echo (int) $i; ?>" aria-label="<?php echo esc_attr( sprintf( 'Слайд %d', $i + 1 ) ); ?>"></button>
						<?php endforeach; ?>
					</div>
					<button type="button" class="chip" data-carousel-next aria-label="Вперёд">→</button>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<article class="prose article-prose">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- тело собрано локально из экранированного HTML.
		echo $ai_article['body'];
		?>
	</article>

	<?php if ( ! empty( $ai_article['cta'] ) ) : ?>
		<aside class="article-cta">
			<p><?php echo esc_html( $ai_article['cta'] ); ?></p>
			<button class="btn" type="button" data-open-modal>Оставить заявку</button>
		</aside>
	<?php endif; ?>

	<p class="article-back">
		<a class="lot-link" href="<?php echo esc_url( home_url( '/zhurnal/' ) ); ?>">← Все статьи журнала</a>
	</p>
</div>

<?php
get_footer();
