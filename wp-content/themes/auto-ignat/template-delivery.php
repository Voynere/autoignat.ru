<?php
/**
 * Template Name: Доставка (страна — по слагу страницы)
 *
 * Страницы «Доставка из Японии / Китая / Кореи».
 *
 * @package auto-ignat
 */

get_header();

$ai_slug   = get_post_field( 'post_name' );
$ai_market = str_contains( $ai_slug, 'yapon' ) ? 'jp' : ( str_contains( $ai_slug, 'kitay' ) ? 'cn' : 'kr' );
$ai_copy   = array(
	'jp' => array(
		'crumb' => 'Доставка авто из Японии',
		'h1'    => 'Япония',
		'hero'  => 'hero-crown.png',
		'check_title' => 'Как проходит проверка',
		'check' => "Перед покупкой автомобиля в Японии мы тщательно проверяем его состояние на японском аукционе:\n— Мы досконально изучаем аукционный лист: фиксируем следы эксплуатации, замечания по техническому состоянию, сверяем данные с фото, а также проверяем все предыдущие торги.\n— Далее всю полученную информацию переводим и расшифровываем с пояснениями экспертов, чтобы вы понимали реальное состояние машины.\nТолько после того как вы ознакомились с данным отчётом и дали нам письменное согласие — мы начинаем торги на аукционе.",
	),
	'cn' => array(
		'crumb' => 'Доставка авто из Китая',
		'h1'    => 'Китай',
		'hero'  => 'about-photo.png',
		'check_title' => 'Как проходит проверка',
		'check' => "Перед покупкой автомобиля в Китае наш партнёр производит выездную диагностику:\n— Осмотр кузова и лакокрасочного покрытия, проверка салона, двигателя, подвески и электроники, включая толщиномер.\n— Всё фиксируется в фото- и видеоотчёте с комментариями на русском языке.\nТакже можем заказать новый автомобиль с завода в Китае по нужной комплектации — особенно для моделей, которых нет в продаже в РФ.",
	),
	'kr' => array(
		'crumb' => 'Доставка авто из Кореи',
		'h1'    => 'Корея',
		'hero'  => 'lot-2.png',
		'check_title' => 'Как проходит проверка',
		'check' => "Перед покупкой любого автомобиля в Южной Корее каждый лот проходит обязательный осмотр:\n— Анализ базы Encar и страховой истории — исключаем серьёзные ДТП, ремонты и иные риски.\n— Эксперт осматривает авто на месте: кузов, двигатель, электронику, толщину ЛКП на видео.\nОтчёт согласовывается с вами, и только потом авто выкупается в Корее и отправляется в Россию.",
	),
);
$ai_page = $ai_copy[ $ai_market ];
$ai_q    = function_exists( 'ai_query_lots' )
	? ai_query_lots( 12, $ai_market )
	: array( 'lots' => array() );
?>
<div class="page-head">
	<div class="wrap">
		<?php ai_crumbs( $ai_page['crumb'] ); ?>
	</div>
</div>

<section class="country-hero">
	<div class="wrap">
		<div class="country-hero-card">
			<img src="<?php echo esc_url( ai_img( $ai_page['hero'] ) ); ?>" alt="<?php echo esc_attr( $ai_page['h1'] ); ?>" />
			<h1><?php echo esc_html( $ai_page['h1'] ); ?></h1>
		</div>
	</div>
</section>

<div class="wrap page-body">
	<?php
	get_template_part(
		'template-parts/filter',
		null,
		array(
			'action'       => get_permalink(),
			'country'      => $ai_market,
			'hide_country' => true,
			'live_makes'   => true,
		)
	);
	$ai_lot_title = array(
		'jp' => 'Лоты из Японии',
		'kr' => 'Лоты из Кореи',
		'cn' => 'Лоты из Китая',
	)[ $ai_market ];
	ai_spec_grid( 12, $ai_market, array( 'title' => $ai_lot_title, 'query' => $ai_q ) );
	ai_figma_pager();
	?>

	<div class="check-duo">
		<div class="check-copy">
			<h2><?php echo esc_html( $ai_page['check_title'] ); ?></h2>
			<p><?php echo nl2br( esc_html( $ai_page['check'] ) ); ?></p>
		</div>
		<figure class="check-video">
			<img src="<?php echo ai_img( 'review-1.png' ); ?>" alt="Видео осмотра" />
			<span class="check-play" aria-hidden="true">▶</span>
		</figure>
	</div>

	<div class="sec-head">
		<h2 class="h2">Отзывы</h2>
		<a class="btn" href="<?php echo esc_url( home_url( '/vydacha/' ) ); ?>">Смотреть отзывы</a>
	</div>
	<?php ai_reviews_row(); ?>
</div>

<?php
get_footer();
