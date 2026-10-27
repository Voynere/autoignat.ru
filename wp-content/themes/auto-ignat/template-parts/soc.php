<?php
/**
 * Кнопки мессенджеров. Макет Figma: чёрные на светлом, золотые в тёмной теме.
 *
 * @package auto-ignat
 */

$ai_args  = ( isset( $args ) && is_array( $args ) ) ? $args : array();
$ai_place = (string) ( $ai_args['place'] ?? 'nav' );
$ai_foot  = 'foot' === $ai_place;
$ai_bar   = 'bar' === $ai_place;
$ai_soc   = $ai_bar
	? array(
		array( 'max', ai_max_url(), 'MAX' ),
		array( 'tg', ai_telegram_url(), 'Telegram' ),
		array( 'wa', ai_whatsapp_url(), 'WhatsApp' ),
	)
	: array(
		array( 'tg', ai_telegram_url(), 'Telegram' ),
		array( 'wa', ai_whatsapp_url(), 'WhatsApp' ),
		array( 'max', ai_max_url(), 'MAX' ),
		array( 'vk', ai_vk_url(), 'VK' ),
	);
$ai_class = 'soc' . ( $ai_foot ? ' soc-foot' : '' ) . ( $ai_bar ? ' soc-bar' : '' );
?>
<div class="<?php echo esc_attr( $ai_class ); ?>">
	<?php foreach ( $ai_soc as $ai_s ) : ?>
		<a href="<?php echo esc_url( $ai_s[1] ); ?>" target="_blank" rel="noopener nofollow" aria-label="<?php echo esc_attr( $ai_s[2] ); ?>">
			<?php ai_soc_icon( $ai_s[0], $ai_foot ); ?>
		</a>
	<?php endforeach; ?>
</div>
