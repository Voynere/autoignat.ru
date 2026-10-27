<?php
/**
 * Главная страница.
 *
 * @package auto-ignat
 */

get_header();

$ai_home_lots = function_exists( 'ai_auc_fresh' ) ? ai_auc_fresh( 17 ) : array();
$ai_hits      = array_slice( $ai_home_lots, 0, 8 );
$ai_fresh     = array_slice( $ai_home_lots, 8, 9 );
if ( ! $ai_fresh ) {
	$ai_fresh = array_slice( $ai_home_lots, 0, 3 );
}
?>

<section class="hero hero-offer">
	<div class="wrap hero-offer-card">
		<div class="hero-offer-copy">
			<h1>Авто из Азии с выгодой до&nbsp;40% от&nbsp;рыночной цены</h1>
			<div class="hero-actions">
				<button class="btn" type="button" data-open-modal>Рассчитать стоимость</button>
				<details class="price-drop">
					<summary>Смотреть цены</summary>
					<div class="price-drop-menu">
						<a href="<?php echo esc_url( home_url( '/catalog/' ) ); ?>">Япония</a>
						<a href="<?php echo esc_url( home_url( '/catalog/?country=kr' ) ); ?>">Корея</a>
						<a href="<?php echo esc_url( home_url( '/catalog/?country=cn' ) ); ?>">Китай</a>
					</div>
				</details>
			</div>
		</div>
		<div class="hero-offer-car" aria-hidden="true">
			<img src="<?php echo ai_img( 'hero-lexus.png' ); ?>" alt="" width="1400" height="638" fetchpriority="high" />
		</div>
	</div>
</section>

<?php if ( $ai_hits ) : ?>
<section id="hits">
	<div class="wrap">
		<div class="hits-head">
			<h2 class="h2">Топ популярных автомобилей</h2>
			<div class="hits-tools">
				<a class="hits-more" href="<?php echo esc_url( home_url( '/catalog/' ) ); ?>">Смотреть все авто</a>
				<div class="hits-nav">
					<button class="hits-arrow" type="button" data-hits-dir="-1" aria-label="Предыдущие автомобили" disabled>
						<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M14 6 8 12l6 6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
					</button>
					<button class="hits-arrow" type="button" data-hits-dir="1" aria-label="Следующие автомобили">
						<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M10 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
					</button>
				</div>
			</div>
		</div>
		<div class="hits-row">
			<?php
			foreach ( $ai_hits as $ai_lot ) {
				ai_render_lot_card( $ai_lot );
			}
			?>
		</div>
	</div>
</section>
<?php endif; ?>

<section id="filter">
	<div class="wrap">
		<h2 class="h2">Подберите автомобиль по параметрам</h2>
		<?php
		get_template_part(
			'template-parts/filter',
			null,
			array(
				'picker' => true,
			)
		);
		?>
	</div>
</section>

<?php
/* Скрыто с главной по просьбе заказчика: блок «Заказ авто из Японии» с фото команды. Разметка сохранена и места в вёрстке не занимает.
<section id="order">
	<div class="wrap">
		<div class="promo promo-order">
			<form class="promo-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<div class="promo-kicker">
					<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3.6 14.2 5.6 9.5A2 2 0 0 1 7.4 8.3h8.8a2 2 0 0 1 1.8 1.1l2.4 4.8" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M3.2 14.2h17.6V16a1.2 1.2 0 0 1-1.2 1.2H4.4A1.2 1.2 0 0 1 3.2 16v-1.8Z" fill="none" stroke="currentColor" stroke-width="1.5"/><circle cx="7.2" cy="17.1" r="1.35" fill="none" stroke="currentColor" stroke-width="1.4"/><circle cx="16.8" cy="17.1" r="1.35" fill="none" stroke="currentColor" stroke-width="1.4"/></svg>
					Надёжно · Выгодно · Под ключ
				</div>
				<h2 class="order-title">
					<span class="order-title-top">
						Заказ авто
						<span class="order-flag">
							<svg class="order-flag-plate" viewBox="0 0 60 40" aria-hidden="true"><rect width="60" height="40" rx="1.5" fill="#fff"/><circle cx="30" cy="20" r="11" fill="#bc002d"/></svg>
							<svg class="order-flag-arrow" viewBox="0 0 32 78" aria-hidden="true"><path d="M20 2C25 20 24 48 9.2 69.2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M7.4 66.2 5.7 74.1 12.6 69.8Z" fill="currentColor"/></svg>
						</span>
					</span>
					<span class="order-title-sub">из Японии</span>
				</h2>
				<input type="hidden" name="action" value="ai_lead" />
				<input type="hidden" name="ai_source" value="order" />
				<?php wp_nonce_field( 'ai_lead', 'ai_lead_nonce' ); ?>
				<input type="text" name="ai_hp" value="" class="ai-hp" aria-hidden="true" tabindex="-1" autocomplete="off" />
				<label class="promo-field">
					<svg class="promo-ico" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.2" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M5 19.2c1.4-3 3.8-4.4 7-4.4s5.6 1.4 7 4.4" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
					<input name="name" placeholder="Ваше имя" required autocomplete="name" />
				</label>
				<label class="promo-field">
					<svg class="promo-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M8.2 4.8h2.1l1.1 2.6-1.3 1.3a12 12 0 0 0 5.2 5.2l1.3-1.3 2.6 1.1v2.1c0 .7-.6 1.3-1.3 1.3A14.2 14.2 0 0 1 6.9 6.1c0-.7.6-1.3 1.3-1.3Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
					<input name="phone" type="tel" inputmode="numeric" placeholder="+7 (___) ___-__-__" required autocomplete="tel" />
				</label>
				<label class="promo-field promo-field-budget">
					<svg class="promo-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5h6.1a3.4 3.4 0 0 1 0 6.8H8M8 8.4h7.2M8 15.2h5.6M8 5v14" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
					<span class="promo-stack">
						<input name="budget" placeholder="Бюджет" inputmode="decimal" aria-label="Бюджет" />
						<span class="promo-stack-hint">от 2 000 000 ₽</span>
					</span>
				</label>
				<label class="promo-field">
					<svg class="promo-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 14.5 6.2 9.2A2 2 0 0 1 8 8h8a2 2 0 0 1 1.8 1.2L20 14.5M4 14.5h16M6.5 17.2a1.3 1.3 0 1 0 0-2.6 1.3 1.3 0 0 0 0 2.6Zm11 0a1.3 1.3 0 1 0 0-2.6 1.3 1.3 0 0 0 0 2.6Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
					<input name="model" placeholder="Марка и модель авто" />
				</label>
				<button class="btn promo-submit" type="submit">Отправить заявку →</button>
				<p class="promo-note">Нажимая на кнопку, вы даёте своё согласие<br>на <a href="<?php echo esc_url( home_url( '/politika-konfidencialnosti/' ) ); ?>">обработку персональных данных</a></p>
			</form>
			<div class="promo-visual" aria-hidden="true">
				<img class="promo-photo" src="<?php echo ai_img( 'order-team.png' ); ?>" alt="" width="1084" height="833" decoding="async" />
			</div>
		</div>
	</div>
</section>
*/
?>

<section id="markets">
	<div class="wrap">
		<div class="sec-head">
			<h2 class="h2">Стираем границы</h2>
		</div>
		<div class="markets">
			<a class="market" href="<?php echo esc_url( home_url( '/dostavka-iz-kitaya/' ) ); ?>">
				<div>
					<div class="kicker">Новые и электро</div>
					<div class="market-big">Китай</div>
					<p>Левый руль, BYD, Li, Zeekr — без пробега по РФ.</p>
				</div>
				<span class="market-foot"><span class="market-more">Подробнее</span><img class="flag flag-round" src="<?php echo ai_img( 'flag-cn.svg' ); ?>" alt="" width="36" height="36" /></span>
			</a>
			<a class="market" href="<?php echo esc_url( home_url( '/dostavka-iz-yaponii/' ) ); ?>">
				<div>
					<div class="kicker">USS / JSS</div>
					<div class="market-big">Япония</div>
					<p>Правый руль, пробег, грейд и честный аукционный лист.</p>
				</div>
				<span class="market-foot"><span class="market-more">Подробнее</span><img class="flag flag-round" src="<?php echo ai_img( 'flag-jp.svg' ); ?>" alt="" width="36" height="36" /></span>
			</a>
			<a class="market" href="<?php echo esc_url( home_url( '/dostavka-iz-korei/' ) ); ?>">
				<div>
					<div class="kicker">Encar</div>
					<div class="market-big">Корея</div>
					<p>Левый руль, свежие Hyundai и Kia, гибриды и комплектации.</p>
				</div>
				<span class="market-foot"><span class="market-more">Подробнее</span><img class="flag flag-round" src="<?php echo ai_img( 'flag-kr.svg' ); ?>" alt="" width="36" height="36" /></span>
			</a>
		</div>
	</div>
</section>

<section id="lots">
	<div class="wrap">
		<div class="sec-head">
			<h2 class="h2">Выкупленные авто</h2>
			<div class="hits-tools">
				<a class="btn" href="<?php echo esc_url( home_url( '/catalog/' ) ); ?>">Смотреть лоты</a>
				<div class="hits-nav">
					<button class="hits-arrow" type="button" data-lots-dir="-1" aria-label="Предыдущие автомобили" disabled>
						<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M14 6 8 12l6 6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
					</button>
					<button class="hits-arrow" type="button" data-lots-dir="1" aria-label="Следующие автомобили">
						<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M10 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
					</button>
				</div>
			</div>
		</div>
		<div class="hits-row">
			<?php
			if ( $ai_fresh ) {
				foreach ( $ai_fresh as $ai_lot ) {
					ai_render_lot_card( $ai_lot );
				}
			} else {
				$ai_lots = new WP_Query(
					array(
						'post_type'      => 'ai_lot',
						'posts_per_page' => 9,
						'no_found_rows'  => true,
					)
				);
				if ( $ai_lots->have_posts() ) {
					while ( $ai_lots->have_posts() ) {
						$ai_lots->the_post();
						ai_lot_card( get_post(), false );
					}
					wp_reset_postdata();
				} else {
					foreach ( ai_demo_lots() as $ai_demo ) {
						ai_demo_card( $ai_demo, false );
					}
				}
			}
			?>
		</div>
	</div>
</section>

<section id="flow">
	<div class="wrap">
		<div class="sec-head">
			<h2 class="h2">Как идёт сделка</h2>
		</div>
		<div class="steps steps-deal">
			<div class="step">
				<span class="chip-num">01</span>
				<h3>Оставить заявку</h3>
				<p>Страна, бюджет, кузов. В ответ — короткий список лотов, не общий каталог.</p>
			</div>
			<div class="step">
				<span class="chip-num">02</span>
				<h3>Заключить договор</h3>
				<p>Лично в офисе или по электронной почте.</p>
			</div>
			<div class="step">
				<span class="chip-num">03</span>
				<h3>Торги</h3>
				<p>Ставка на аукционе или выкуп. Лист и потолок цены — у вас.</p>
			</div>
			<div class="step">
				<span class="chip-num">04</span>
				<h3>Доставка и таможня в РФ</h3>
				<p>Срок от 3 недель. Фото с каждого плеча маршрута.</p>
			</div>
			<div class="step">
				<span class="chip-num">05</span>
				<h3>Выдача и отправка</h3>
				<p>Доставим до транспортной компании или выдадим с нашей стоянки.</p>
			</div>
			<svg class="deal-rail" aria-hidden="true" focusable="false"></svg>
			<svg class="deal-marker-rail" aria-hidden="true" focusable="false"></svg>
		</div>
	</div>
</section>

<section id="pavel">
	<div class="wrap">
		<div class="pavel-card">
			<div class="pavel-copy">
				<p class="pavel-pill">
					<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><circle cx="12" cy="8" r="3.1" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M5.6 19.2c1.15-3.15 3.35-4.6 6.4-4.6s5.25 1.45 6.4 4.6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
					Личный менеджер <span class="pavel-pill-sep">|</span> На связи 7 дней в неделю
				</p>
				<h2 class="pavel-title" id="pavel-title">Павел Корчагин<span class="pavel-dot">.</span><br>Лично</h2>
				<p class="pavel-lead">Я и моя команда привезем вам автомобиль из Японии, Кореи или Китая так, что <span class="pavel-em">результат</span> будет полностью соответствовать вашим ожиданиям.</p>
				<ul class="pavel-points">
					<li>
						<span class="pavel-ico" aria-hidden="true">
							<svg viewBox="0 0 24 24" width="22" height="22"><path d="M12 3.6 19 6.2v5.3c0 4-2.7 6.9-7 8.6-4.3-1.7-7-4.6-7-8.6V6.2L12 3.6Z" fill="none" stroke="#fff" stroke-width="1.7" stroke-linejoin="round"/><path d="M8.7 12.1 11 14.3l4.4-4.5" fill="none" stroke="#fff" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
						</span>
						<span><strong>Всё под ключ</strong><span>от поиска до выдачи</span></span>
					</li>
					<li>
						<span class="pavel-ico" aria-hidden="true">
							<svg viewBox="0 0 24 24" width="22" height="22"><path d="M4.2 14.4 6.3 9.6A1.8 1.8 0 0 1 8 8.4h8a1.8 1.8 0 0 1 1.7 1.2l2.1 4.8M4.2 14.4h15.6V17H4.2v-2.6Z" fill="none" stroke="#fff" stroke-width="1.7" stroke-linejoin="round"/><circle cx="7.4" cy="17.2" r="1.35" fill="#fff"/><circle cx="16.6" cy="17.2" r="1.35" fill="#fff"/></svg>
						</span>
						<span><strong>Прозрачные условия</strong><span>без скрытых платежей</span></span>
					</li>
					<li>
						<span class="pavel-ico" aria-hidden="true">
							<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#fff" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m11 17 2 2a1 1 0 1 0 3-3"/><path d="m14 14 2.5 2.5a1 1 0 1 0 3-3l-3.88-3.88a3 3 0 0 0-4.24 0l-.88.88a1 1 0 1 1-3-3l2.81-2.81a5.79 5.79 0 0 1 7.06-.87l.47.28a2 2 0 0 0 1.42.25L21 4"/><path d="m21 3 1 11h-2"/><path d="M3 3 2 14l6.5 6.5a1 1 0 1 0 3-3"/><path d="M3 4h8"/></svg>
						</span>
						<span><strong>Личная ответственность</strong><span>на каждом этапе</span></span>
					</li>
				</ul>
				<div class="pavel-cta-row">
					<button class="pavel-cta" type="button" data-open-modal>Получить консультацию директора →</button>
					<div class="pavel-write">
						<span class="pavel-write-label">Напишите нам:</span>
						<div class="pavel-msgs">
							<a class="pavel-msg" href="<?php echo esc_url( ai_telegram_url() ); ?>" target="_blank" rel="noopener nofollow">
								<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="#2AABEE" d="M12 3.4A8.6 8.6 0 1 0 20.6 12 8.6 8.6 0 0 0 12 3.4Zm3.95 5.75-1.42 6.7c-.08.38-.36.48-.72.3l-2.02-1.49-.93.9a.45.45 0 0 1-.42.1l.14-2.05 3.74-3.38c.16-.14-.04-.22-.25-.1l-4.62 2.91-1.96-.61c-.42-.13-.43-.42-.09-.62l7.84-3.02c.36-.14.67.09.71.46Z"/></svg>
								Telegram
							</a>
							<a class="pavel-msg" href="<?php echo esc_url( ai_whatsapp_url() ); ?>" target="_blank" rel="noopener nofollow">
								<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="#25D366" d="M12.04 3.5A8.2 8.2 0 0 0 5.2 16.3L4.2 20l3.8-1a8.2 8.2 0 0 0 4.04 1.08 8.22 8.22 0 0 0 0-16.44Zm4.78 11.62c-.2.56-.98 1.02-1.6 1.16-.42.08-.98.16-2.84-.6-2.38-1-3.9-3.46-4.02-3.62-.12-.16-.98-1.3-.98-2.48s.62-1.76 1.04-2.08c.1-.08.24-.14.38-.14h.28c.12 0 .26 0 .4.32.16.38.54 1.32.58 1.42.06.1.1.22.02.36-.08.14-.12.22-.24.34-.12.12-.24.26-.1.5.14.24.62 1.02 1.32 1.66.92.82 1.66 1.08 1.9 1.2.24.12.38.1.52-.06.14-.16.58-.68.74-.9.16-.24.3-.2.5-.12.2.08 1.28.6 1.5.72.22.1.36.16.42.26.04.08.04.5-.16 1.06Z"/></svg>
								WhatsApp
							</a>
							<a class="pavel-msg" href="<?php echo esc_url( ai_max_url() ); ?>" target="_blank" rel="noopener nofollow">
								<img class="ico-max" src="https://maxicons.ru/icons/MAX.svg" alt="" width="22" height="22" decoding="async" />
								MAX
							</a>
						</div>
					</div>
				</div>
			</div>
			<div class="pavel-visual">
				<img class="pavel-photo" src="<?php echo ai_img( 'pavel-korchagin.png' ); ?>" alt="Павел Корчагин, руководитель компании" width="1544" height="2400" decoding="async" />
				<div class="pavel-mosaic">
					<img src="<?php echo ai_img( 'handover-subaru.jpg' ); ?>" alt="Выдача белого Subaru: Павел с клиентом" width="774" height="1400" decoding="async" />
					<img src="<?php echo ai_img( 'handover-honda.jpg' ); ?>" alt="Выдача чёрного Honda: Павел с клиентом" width="765" height="1400" decoding="async" />
					<img src="<?php echo ai_img( 'handover-jetour.jpg' ); ?>" alt="Выдача чёрного кроссовера: Павел с клиенткой" width="783" height="1400" decoding="async" />
					<img src="<?php echo ai_img( 'handover-vw.jpg' ); ?>" alt="Зимняя выдача: Павел с клиенткой и документами" width="960" height="1179" decoding="async" />
				</div>
			</div>
		</div>
	</div>
</section>

<section id="blog">
	<div class="wrap">
		<div class="sec-head">
			<h2 class="h2">Блог АвтоИгната</h2>
			<a class="btn" href="<?php echo esc_url( home_url( '/zhurnal/' ) ); ?>">Смотреть статьи</a>
		</div>
		<div class="posts posts-blog">
			<?php
			$ai_blog_url = static function ( string $slug ): string {
				return home_url( '/zhurnal/' . $slug . '/' );
			};
			$ai_jp = $ai_blog_url( 'yaponiya-ili-kitay-2026' );
			$ai_body = $ai_blog_url( 'snachala-kuzov' );
			$ai_fuel = $ai_blog_url( 'gibrid-ili-benzin' );
			?>
			<article class="post-card">
				<a class="post-shot" href="<?php echo esc_url( $ai_jp ); ?>">
					<img src="<?php echo ai_img( 'blog-jp-cn.jpg' ); ?>" alt="Белый компактвэн и светлый кроссовер, разделены по диагонали" width="1280" height="720" loading="lazy" />
					<span class="post-pill">Япония</span>
				</a>
				<div class="post-copy">
					<div class="kicker">01 • Япония / Китай</div>
					<h3>Япония или Китай: что везти в 2026</h3>
					<a class="btn post-more" href="<?php echo esc_url( $ai_jp ); ?>">Подробнее</a>
				</div>
			</article>
			<article class="post-card">
				<a class="post-shot" href="<?php echo esc_url( $ai_body ); ?>">
					<img src="<?php echo ai_img( 'blog-fit.jpg' ); ?>" alt="Белый компактный хэтчбек целиком в кадре" width="1600" height="1000" loading="lazy" />
					<span class="post-overlay"><strong>Сначала кузов, потом марка</strong></span>
				</a>
				<div class="post-copy">
					<div class="kicker">02 • Кузов</div>
					<h3>Сначала кузов, потом марка</h3>
					<a class="btn post-more" href="<?php echo esc_url( $ai_body ); ?>">Подробнее</a>
				</div>
			</article>
			<article class="post-card">
				<a class="post-shot" href="<?php echo esc_url( $ai_fuel ); ?>">
					<img src="<?php echo ai_img( 'blog-prius.jpg' ); ?>" alt="Синий гибридный хэтчбек, вид спереди три четверти" width="1600" height="1000" loading="lazy" />
					<span class="post-overlay"><strong>Где кто выигрывает</strong></span>
				</a>
				<div class="post-copy">
					<div class="kicker">03 • Топливо</div>
					<h3>Гибрид или бензин: что брать, если честно</h3>
					<a class="btn post-more" href="<?php echo esc_url( $ai_fuel ); ?>">Подробнее</a>
				</div>
			</article>
		</div>
	</div>
</section>

<section id="reviews">
	<div class="wrap">
		<div class="sec-head">
			<h2 class="h2">Видео-отзывы</h2>
			<a class="btn" href="<?php echo esc_url( home_url( '/vydacha/' ) ); ?>">Смотреть отзывы</a>
		</div>
		<?php ai_reviews_row(); ?>
	</div>
</section>

<section id="desk">
	<div class="wrap">
		<div class="promo promo-consult">
			<div class="consult-copy">
				<h2><span class="consult-line">Подберите автомобиль</span><span class="consult-line">с нашим <span class="consult-em">экспертом</span></span></h2>
				<p class="consult-lead">На бесплатной консультации вы сможете:</p>
				<ul class="consult-list">
					<li>
						<svg class="consult-check" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="11" fill="#fff6c4"/><path d="M7.1 12.3 10.4 15.5 17 8.6" fill="none" stroke="#141414" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
						<span>Задать необходимые вопросы</span>
					</li>
					<li>
						<svg class="consult-check" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="11" fill="#fff6c4"/><path d="M7.1 12.3 10.4 15.5 17 8.6" fill="none" stroke="#141414" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
						<span>Подробно узнать о стоимости под ключ</span>
					</li>
					<li>
						<svg class="consult-check" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="11" fill="#fff6c4"/><path d="M7.1 12.3 10.4 15.5 17 8.6" fill="none" stroke="#141414" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
						<span>Без обязательств, договора и прочего</span>
					</li>
				</ul>
			</div>
			<div class="consult-side">
				<h3>Напишите нам в удобный<br>для вас мессенджер</h3>
				<div class="consult-msgs">
					<a class="msg-btn msg-btn-max" href="<?php echo esc_url( ai_max_url() ); ?>" target="_blank" rel="noopener nofollow">
						<span class="msg-ico">
							<img class="ico-max" src="https://maxicons.ru/icons/MAX.svg" alt="" width="24" height="24" decoding="async" />
							<span class="msg-badge">НОВЫЙ</span>
						</span>
						MAX
					</a>
					<a class="msg-btn" href="<?php echo esc_url( ai_whatsapp_url() ); ?>" target="_blank" rel="noopener nofollow">
						<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10" fill="#25D366"/><path fill="#fff" d="M16.62 14.12c-.2.56-.99 1.02-1.4 1.1-.36.08-.82.1-1.32-.08-.3-.12-.7-.26-1.2-.5-2.08-.9-3.44-3-3.54-3.14-.1-.14-.86-1.14-.86-2.18s.54-1.56.74-1.78c.16-.18.38-.26.52-.26h.4c.12 0 .28-.02.44.34.16.4.56 1.38.6 1.48.06.1.08.22.02.36-.08.14-.12.22-.22.34-.1.12-.2.26-.28.36-.1.1-.2.22-.08.42.1.2.48.82 1.04 1.32.72.66 1.32.86 1.52.96.2.1.32.08.44-.06.12-.14.48-.56.6-.76.12-.2.24-.16.4-.1.16.06 1.04.5 1.22.58.18.1.3.14.34.22.06.1.06.48-.14.96z"/></svg>
						WhatsApp
					</a>
					<a class="msg-btn" href="<?php echo esc_url( ai_telegram_url() ); ?>" target="_blank" rel="noopener nofollow">
						<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10" fill="#2AABEE"/><path fill="#fff" d="M17.15 7.72 6.72 11.68c-.68.26-.67.64-.12.8l2.54.8 5.86-3.7c.28-.17.54-.08.32.11l-4.74 4.28-.18 2.14c.26 0 .36-.1.5-.26l1.22-1.18 2.54 1.87c.46.26.8.12.92-.44l1.68-7.9c.16-.7-.26-1.02-.81-1.28z"/></svg>
						Telegram
					</a>
				</div>
				<p class="consult-or"><span>или оставьте заявку:</span></p>
				<form class="promo-form consult-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ai_lead" />
					<input type="hidden" name="ai_source" value="consult" />
					<?php wp_nonce_field( 'ai_lead', 'ai_lead_nonce' ); ?>
					<input type="text" name="ai_hp" value="" class="ai-hp" aria-hidden="true" tabindex="-1" autocomplete="off" />
					<div class="consult-grid">
						<label class="promo-field consult-field">
							<svg class="promo-ico" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="8" r="3.15" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M5.1 19.2c1.35-3.05 3.7-4.5 6.9-4.5s5.55 1.45 6.9 4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
							<input name="name" placeholder="Ваше имя" required autocomplete="name" />
						</label>
						<label class="promo-field consult-field">
							<svg class="promo-ico" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8.2 4.8h2.1l1.1 2.6-1.3 1.3a12 12 0 0 0 5.2 5.2l1.3-1.3 2.6 1.1v2.1c0 .7-.6 1.3-1.3 1.3A14.2 14.2 0 0 1 6.9 6.1c0-.7.6-1.3 1.3-1.3Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
							<span class="consult-flag" aria-hidden="true">
								<svg class="consult-flag-ico" viewBox="0 0 22 16"><rect width="22" height="16" rx="2" fill="#fff"/><path d="M0 5.4h22v5.3H0z" fill="#1C4FA0"/><path d="M0 10.7h22V14a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-3.3Z" fill="#E23B2E"/><rect width="22" height="16" rx="2" fill="none" stroke="rgba(0,0,0,.15)"/></svg>
							</span>
							<input name="phone" type="tel" inputmode="tel" placeholder="+7 (000) 000-00-00" required autocomplete="tel" />
						</label>
						<label class="promo-field consult-field consult-budget">
							<svg class="promo-ico" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8 4.8h6.2a3.5 3.5 0 0 1 0 7H8M8 8.3h7.4M8 15.1h5.5M8 4.8V19" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
							<span class="consult-stack">
								<span class="consult-stack-k">Бюджет</span>
								<input name="budget" placeholder="2 000 000 ₽" inputmode="decimal" aria-label="Бюджет" autocomplete="off" />
							</span>
						</label>
						<label class="promo-field consult-field">
							<svg class="promo-ico" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4.2 14.5 6.3 9.6A1.8 1.8 0 0 1 8 8.4h8a1.8 1.8 0 0 1 1.7 1.2l2.1 4.9M4.2 14.5h15.6V17H4.2v-2.5Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><circle cx="7.5" cy="17.15" r="1.2" fill="currentColor"/><circle cx="16.5" cy="17.15" r="1.2" fill="currentColor"/></svg>
							<input name="task" placeholder="Ваши предпочтения" autocomplete="off" />
						</label>
					</div>
					<button class="btn promo-submit" type="submit">Отправить заявку →</button>
					<p class="promo-note">Нажимая на кнопку, вы даёте своё согласие на <a href="<?php echo esc_url( home_url( '/politika-konfidencialnosti/' ) ); ?>">обработку персональных данных</a></p>
				</form>
			</div>
		</div>
	</div>
</section>

<?php
get_footer();
