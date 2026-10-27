<?php
/**
 * Подвал сайта.
 *
 * @package auto-ignat
 */
?>
</main>

<footer class="site">
	<div class="wrap">
		<form class="foot-lot" method="get" action="<?php echo esc_url( home_url( '/catalog/' ) ); ?>">
			<label class="foot-lot-label" for="foot-lot">Номер лота</label>
			<div class="foot-lot-row">
				<input id="foot-lot" name="lot" type="search" inputmode="numeric" autocomplete="off" maxlength="32" placeholder="4482" required />
				<button class="btn" type="submit">Найти →</button>
			</div>
		</form>
	</div>
	<div class="wrap foot">
		<div class="foot-brand">
			<?php ai_brand( 'foot' ); ?>
			<p class="legal">
				ИП Корчагин П. А.<br>
				ИНН 250209342589<br>
				ОГРНИП 323253600036250<br>
				<?php echo esc_html( ai_office_address_short() ); ?>
			</p>
			<a class="foot-phone" href="<?php echo esc_attr( ai_phone_href() ); ?>">
				<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M8.2 4.8h2.1l1.1 2.6-1.3 1.3a12 12 0 0 0 5.2 5.2l1.3-1.3 2.6 1.1v2.1c0 .7-.6 1.3-1.3 1.3A14.2 14.2 0 0 1 6.9 6.1c0-.7.6-1.3 1.3-1.3Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
				<?php echo esc_html( ai_phone() ); ?>
			</a>
		</div>

		<nav aria-label="Популярные марки">
			<h4>Популярные марки</h4>
			<ul>
				<li><a href="<?php echo esc_url( home_url( '/catalog/?make=Toyota' ) ); ?>">Toyota</a></li>
				<li><a href="<?php echo esc_url( home_url( '/catalog/?make=Honda' ) ); ?>">Honda</a></li>
				<li><a href="<?php echo esc_url( home_url( '/catalog/?make=Subaru' ) ); ?>">Subaru</a></li>
				<li><a href="<?php echo esc_url( home_url( '/catalog/?make=Mazda' ) ); ?>">Mazda</a></li>
				<li><a href="<?php echo esc_url( home_url( '/catalog/?make=Lexus' ) ); ?>">Lexus</a></li>
				<li><a href="<?php echo esc_url( home_url( '/catalog/?make=BMW' ) ); ?>">BMW</a></li>
				<li><a href="<?php echo esc_url( home_url( '/catalog/?make=Mercedes-Benz' ) ); ?>">Mercedes-Benz</a></li>
			</ul>
		</nav>

		<nav aria-label="Подборки">
			<h4>Подборки</h4>
			<ul>
				<li><a href="<?php echo esc_url( home_url( '/catalog/?hp_to=160' ) ); ?>">До 160 л.с.</a></li>
				<li><a href="<?php echo esc_url( home_url( '/catalog/?year_from=2025' ) ); ?>">Новые авто</a></li>
				<li><a href="<?php echo esc_url( home_url( '/catalog/' ) ); ?>">Все лоты</a></li>
				<li><a href="<?php echo esc_url( home_url( '/statistika/' ) ); ?>">Статистика</a></li>
				<li><a href="<?php echo esc_url( home_url( '/catalog/?sort=price' ) ); ?>">До 1.5 млн ₽</a></li>
				<li><a href="<?php echo esc_url( home_url( '/catalog/?wheel=правый' ) ); ?>">Правый руль</a></li>
				<li><a href="<?php echo esc_url( home_url( '/catalog/?engine=гибрид' ) ); ?>">Гибриды</a></li>
			</ul>
		</nav>

		<nav aria-label="О компании">
			<h4>О компании</h4>
			<ul>
				<li><a href="<?php echo esc_url( home_url( '/catalog/' ) ); ?>">Аукционы онлайн</a></li>
				<li><a href="<?php echo esc_url( home_url( '/kontakty/' ) ); ?>">Контакты</a></li>
				<li><a href="<?php echo ai_anchor( 'desk' ); ?>">Услуги</a></li>
				<li><a href="<?php echo esc_url( home_url( '/o-kompanii/' ) ); ?>">Гарантии</a></li>
				<li><a href="<?php echo esc_url( home_url( '/vydacha/' ) ); ?>">Отзывы</a></li>
				<li><a href="<?php echo esc_url( home_url( '/zhurnal/' ) ); ?>">Статьи</a></li>
			</ul>
		</nav>

		<aside class="foot-sub">
			<div class="foot-sub-row">
				<div class="foot-sub-link">
					<svg class="foot-sub-plane" viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path d="M21.2 4.4 2.9 11.2c-.9.3-.9.8-.2 1l4.6 1.4 1.8 5.4c.2.6.1.8.7.8.3 0 .5-.1.7-.3l2.4-2.3 5.1 3.8c.8.5 1.4.2 1.6-.8l3.2-15.2c.3-1.2-.4-1.8-1.6-1.4Z" fill="#f6d207"/></svg>
					<span class="foot-sub-copy">
						<strong>Подпишись</strong>
						<span>Будь в курсе новых лотов, акций и полезных новостей</span>
					</span>
				</div>
				<span class="foot-sub-go" aria-hidden="true">
					<svg viewBox="0 0 24 24" width="22" height="22"><path d="M5 12h12M13 6l6 6-6 6" fill="none" stroke="#f6d207" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
				</span>
			</div>
			<?php /* Ссылки только там, где они уже есть в теме: Telegram, MAX, VK (soc.php), YouTube-ролик отзыва. Профилей Rutube, TikTok, Instagram и Дзена нет — иконки без выдуманного адреса. Стрелка не ссылка. */ ?>
			<div class="foot-soc">
				<span class="foot-soc-ico" role="img" aria-label="YouTube"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="#FF0000" d="M21.6 7.2a2.4 2.4 0 0 0-1.7-1.7C18.2 5 12 5 12 5s-6.2 0-7.9.5a2.4 2.4 0 0 0-1.7 1.7A25 25 0 0 0 2 12a25 25 0 0 0 .4 4.8 2.4 2.4 0 0 0 1.7 1.7C5.8 19 12 19 12 19s6.2 0 7.9-.5a2.4 2.4 0 0 0 1.7-1.7A25 25 0 0 0 22 12a25 25 0 0 0-.4-4.8Z"/><path fill="#fff" d="M10.2 15.2V8.8L15.8 12l-5.6 3.2Z"/></svg></span>
				<span class="foot-soc-ico" role="img" aria-label="Rutube"><img class="foot-soc-img" src="<?php echo ai_img( 'soc/rutube.png' ); ?>" alt="" width="20" height="20" decoding="async" /></span>
				<a class="foot-soc-ico" href="<?php echo esc_url( ai_vk_url() ); ?>" target="_blank" rel="noopener nofollow" aria-label="ВКонтакте"><img class="foot-soc-img" src="<?php echo ai_img( 'soc/vk.png' ); ?>" alt="" width="20" height="20" decoding="async" /></a>
				<span class="foot-soc-ico" role="img" aria-label="TikTok"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="#111" d="M14.1 3.1c.35 2.05 1.55 3.55 3.6 4v2.35a6.2 6.2 0 0 1-3.6-1.15v5.85a4.85 4.85 0 1 1-4.85-4.85c.3 0 .6.03.88.08v2.5a2.45 2.45 0 1 0 1.72 2.32V3.1h2.25Z"/></svg></span>
				<a class="foot-soc-ico" href="<?php echo esc_url( ai_telegram_url() ); ?>" target="_blank" rel="noopener nofollow" aria-label="Telegram"><img class="foot-soc-img" src="<?php echo ai_img( 'soc/telegram.png' ); ?>" alt="" width="20" height="20" decoding="async" /></a>
				<a class="foot-soc-ico" href="<?php echo esc_url( ai_max_url() ); ?>" target="_blank" rel="noopener nofollow" aria-label="MAX"><img class="ico-max" src="https://maxicons.ru/icons/MAX.svg" alt="" width="24" height="24" decoding="async" /></a>
				<span class="foot-soc-ico" role="img" aria-label="Instagram"><svg viewBox="0 0 24 24" aria-hidden="true"><defs><radialGradient id="aiFootIg" cx="28%" cy="108%" r="120%"><stop offset="0" stop-color="#f9ce34"/><stop offset=".4" stop-color="#ee2a7b"/><stop offset="1" stop-color="#6228d7"/></radialGradient></defs><rect x="3.2" y="3.2" width="17.6" height="17.6" rx="5" fill="url(#aiFootIg)"/><circle cx="12" cy="12" r="4.15" fill="none" stroke="#fff" stroke-width="1.7"/><circle cx="17.15" cy="6.85" r="1.05" fill="#fff"/></svg></span>
				<span class="foot-soc-ico" role="img" aria-label="Дзен"><img class="foot-soc-img" src="<?php echo ai_img( 'soc/dzen.png' ); ?>" alt="" width="20" height="20" decoding="async" /></span>
			</div>
		</aside>
	</div>

	<div class="wrap foot-bottom">
		<span>© <?php echo esc_html( gmdate( 'Y' ) ); ?> АвтоИгнат — личный байер</span>
		<a href="<?php echo esc_url( home_url( '/politika-konfidencialnosti/' ) ); ?>">Политика конфиденциальности</a>
	</div>
</footer>

<div class="mobile-cta">
	<button class="btn" data-open-modal type="button">Заказать звонок</button>
</div>

<div class="modal" id="modal" role="dialog" aria-modal="true" aria-label="Заявка">
	<form class="sheet" id="lead" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<button type="button" class="modal-close" data-close-modal aria-label="Закрыть">×</button>
		<h3>Заявка</h3>
		<p>Марка, бюджет, город выдачи. Перезвоним в рабочий день.</p>
		<p class="lead-ok" hidden>Заявка отправлена. Спасибо!</p>
		<input type="hidden" name="action" value="ai_lead" />
		<?php wp_nonce_field( 'ai_lead', 'ai_lead_nonce' ); ?>
		<input type="text" name="ai_hp" value="" class="ai-hp" aria-hidden="true" tabindex="-1" autocomplete="off" />
		<input name="name" placeholder="Имя" required />
		<input name="phone" type="tel" placeholder="+7" required />
		<input name="task" placeholder="Crown, до 2.2 млн, Владивосток" />
		<button class="btn" type="submit" style="width:100%;margin-top:8px">Отправить заявку</button>
	</form>
</div>

<div class="lot-zoom" id="lot-zoom" hidden>
	<button type="button" class="lot-zoom-close" aria-label="Закрыть">×</button>
	<button type="button" class="lot-zoom-nav lot-zoom-prev" aria-label="Предыдущее фото" hidden>‹</button>
	<img alt="" />
	<button type="button" class="lot-zoom-nav lot-zoom-next" aria-label="Следующее фото" hidden>›</button>
	<span class="lot-zoom-count" hidden></span>
</div>

<div class="review-modal" id="review-modal" hidden>
	<button type="button" class="review-modal-close" aria-label="Закрыть">×</button>
	<div class="review-modal-inner">
		<video id="review-video" controls playsinline preload="metadata" poster=""></video>
		<div class="review-modal-meta">
			<strong id="review-modal-title"></strong>
			<blockquote id="review-modal-quote"></blockquote>
			<p id="review-modal-desc"></p>
		</div>
	</div>
</div>

<?php wp_footer(); ?>
</body>
</html>
