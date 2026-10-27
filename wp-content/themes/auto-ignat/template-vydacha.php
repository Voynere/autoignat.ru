<?php
/**
 * Template Name: Выдача
 *
 * Видео-отзывы клиентов и кейсы выдачи.
 *
 * @package auto-ignat
 */

get_header();
?>
<div class="page-head">
	<div class="wrap">
		<?php ai_crumbs( 'Выдача' ); ?>
		<h1>Выдача и отзывы</h1>
		<p class="page-lead">Клиенты после получения машины — короткие вертикальные отзывы. Ниже — развёрнутые кейсы на YouTube.</p>
	</div>
</div>

<div class="wrap page-body">
	<?php ai_reviews_row(); ?>

	<section class="cases-block" style="margin-top:48px">
		<div class="sec-head">
			<h2 class="h2">Кейсы выдачи</h2>
		</div>
		<ul class="cases-list">
			<li><a href="https://www.youtube.com/watch?v=Q1VXuv2GnAs" target="_blank" rel="noopener">Jetour X70 Plus / Валерия</a></li>
			<li><a href="https://www.youtube.com/watch?v=hTalCv4ZPvw" target="_blank" rel="noopener">Suzuki Jimny 2020 / Галина</a></li>
			<li><a href="https://www.youtube.com/watch?v=sKZovwbECYo" target="_blank" rel="noopener">Toyota Levin 2022</a></li>
			<li><a href="https://www.youtube.com/watch?v=jwBU-zwGKFw" target="_blank" rel="noopener">Honda StepWGN Spada 2020 / Эдуард</a></li>
			<li><a href="https://www.youtube.com/watch?v=QJCEeonZCBo" target="_blank" rel="noopener">Honda Crossroad 2007 / Алексей</a></li>
		</ul>
	</section>
</div>

<?php
get_footer();
