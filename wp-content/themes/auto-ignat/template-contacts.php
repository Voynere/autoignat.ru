<?php
/**
 * Template Name: Контакты
 *
 * @package auto-ignat
 */

get_header();
?>
<div class="page-head">
	<div class="wrap">
		<?php ai_crumbs( 'Контакты' ); ?>
		<h1>Контакты</h1>
	</div>
</div>

<div class="wrap page-body">
	<div class="contact-grid">
		<div class="contact-stack">
			<div class="contact-card">
				<div class="kicker">Адрес</div>
				<p><?php echo esc_html( ai_office_address() ); ?></p>
			</div>
			<div class="contact-card">
				<div class="kicker">Общая поддержка</div>
				<p><a href="mailto:ai-geniusv@yandex.ru">ai-geniusv@yandex.ru</a></p>
				<p><a class="mono" href="<?php echo esc_attr( ai_phone_href() ); ?>"><?php echo esc_html( ai_phone() ); ?></a></p>
				<p>Telegram: <a href="<?php echo esc_url( ai_telegram_url() ); ?>" target="_blank" rel="noopener">@auto_ignat</a></p>
				<p>WhatsApp: <a href="<?php echo esc_url( ai_whatsapp_url() ); ?>" target="_blank" rel="noopener"><?php echo esc_html( ai_phone() ); ?></a></p>
				<p>MAX: <a href="<?php echo esc_url( ai_max_url() ); ?>" target="_blank" rel="noopener">написать в MAX</a></p>
			</div>
			<div class="contact-card">
				<div class="kicker">На картах</div>
				<p><a href="<?php echo esc_url( ai_maps_yandex_url() ); ?>" target="_blank" rel="noopener">Мы на Яндекс Картах</a></p>
				<p><a href="<?php echo esc_url( ai_maps_2gis_url() ); ?>" target="_blank" rel="noopener">Мы в 2ГИС</a></p>
			</div>
			<div class="contact-card">
				<div class="kicker">Юридическая информация</div>
				<p>ИП Корчагин П. А.</p>
				<p>ИНН 250209342589</p>
				<p>ОГРНИП 323253600036250</p>
			</div>
			<div class="contact-card">
				<div class="kicker">Покупателям</div>
				<?php get_template_part( 'template-parts/soc' ); ?>
				<button class="btn" data-open-modal type="button">Оставить задачу</button>
			</div>
		</div>
		<div class="map-frame">
			<iframe
				title="Карта — АвтоИгнат, Владивосток"
				src="<?php echo esc_url( ai_maps_embed_url() ); ?>"
				allowfullscreen
				loading="lazy"></iframe>
		</div>
	</div>
</div>

<?php
get_footer();
