<?php
/**
 * Карточка лота.
 *
 * @package auto-ignat
 */

get_header();

$ai_demo_n = isset( $_GET['demo'] ) ? sanitize_text_field( wp_unslash( $_GET['demo'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$ai_lot    = is_array( $GLOBALS['ai_auc_lot_data'] ?? null ) ? $GLOBALS['ai_auc_lot_data'] : null;
$ai_is_wp  = is_singular( 'ai_lot' );

if ( $ai_lot ) {
	$ai_lot['content'] = $ai_lot['content'] ?? '';
} elseif ( $ai_is_wp ) {
	$ai_post = get_post();
	$ai_lot  = array(
		'title'        => get_the_title( $ai_post ),
		'num'          => get_post_meta( $ai_post->ID, 'ai_lot_number', true ),
		'market'       => strtoupper( get_post_meta( $ai_post->ID, 'ai_lot_market', true ) ?: 'JP' ),
		'source'       => get_post_meta( $ai_post->ID, 'ai_lot_source', true ),
		'grade'        => get_post_meta( $ai_post->ID, 'ai_lot_grade', true ),
		'year'         => get_post_meta( $ai_post->ID, 'ai_lot_year', true ),
		'km'           => get_post_meta( $ai_post->ID, 'ai_lot_mileage', true ),
		'price'        => get_post_meta( $ai_post->ID, 'ai_lot_price', true ),
		'origin_price' => get_post_meta( $ai_post->ID, 'ai_lot_origin_price', true ),
		'fuel'         => get_post_meta( $ai_post->ID, 'ai_lot_fuel', true ),
		'gearbox'      => get_post_meta( $ai_post->ID, 'ai_lot_gearbox', true ),
		'drive'        => get_post_meta( $ai_post->ID, 'ai_lot_drive', true ),
		'body'         => get_post_meta( $ai_post->ID, 'ai_lot_body', true ),
		'color'        => get_post_meta( $ai_post->ID, 'ai_lot_color', true ),
		'volume'       => get_post_meta( $ai_post->ID, 'ai_lot_volume', true ),
		'hp'           => get_post_meta( $ai_post->ID, 'ai_lot_hp', true ),
		'place'        => get_post_meta( $ai_post->ID, 'ai_lot_place', true ) ?: 'Владивосток',
		'img'          => has_post_thumbnail( $ai_post ) ? get_the_post_thumbnail_url( $ai_post, 'ai-lot' ) : ai_img( 'lot-featured.png' ),
		'content'      => apply_filters( 'the_content', $ai_post->post_content ),
	);
} else {
	foreach ( ai_demo_lots() as $ai_d ) {
		if ( (string) $ai_d['num'] === (string) $ai_demo_n || '' === $ai_demo_n ) {
			$ai_lot = $ai_d;
			break;
		}
	}
	$ai_lot['img']     = ai_img( $ai_lot['img'] );
	$ai_lot['content'] = '';
	$ai_lot['source']  = $ai_lot['src'];
	$ai_lot['grade']   = $ai_lot['meta'];
}

$ai_country = ai_market_label( (string) $ai_lot['market'] );
$ai_from    = array(
	'JP' => 'Японии',
	'KR' => 'Кореи',
	'CN' => 'Китая',
)[ $ai_lot['market'] ] ?? $ai_country;
$ai_grade = trim( (string) ( $ai_lot['grade'] ?? $ai_lot['rate'] ?? '' ) );
if ( $ai_grade && preg_match( '/GRADE\s+(.+)/i', $ai_grade, $ai_gm ) ) {
	$ai_grade = $ai_gm[1];
}
$ai_price = (string) ( $ai_lot['price'] ?? '' );
if ( $ai_price && ! str_starts_with( $ai_price, 'от ' ) ) {
	$ai_price = 'от ' . $ai_price;
}
$ai_price_reg = (string) ( $ai_lot['price_reg'] ?? '' );
if ( $ai_price_reg && ! str_starts_with( $ai_price_reg, 'от ' ) ) {
	$ai_price_reg = 'от ' . $ai_price_reg;
}
$ai_calc = is_array( $ai_lot['price_calc'] ?? null ) ? $ai_lot['price_calc'] : null;
$ai_market_code = (string) ( $ai_lot['market'] ?? '' );
if ( ( ! $ai_calc || ! empty( $ai_calc['incomplete'] ) ) && 'JP' === $ai_market_code && function_exists( 'ai_price_jp_from_lot' ) ) {
	$ai_calc_full = ai_price_jp_from_lot( $ai_lot, true );
	if ( $ai_calc_full ) {
		$ai_calc      = $ai_calc_full;
		$ai_price     = 'от ' . $ai_calc['total_label'];
		$ai_price_reg = 'от ' . $ai_calc['total_reg_label'];
	}
}
if ( ( ! $ai_calc || ! empty( $ai_calc['incomplete'] ) ) && in_array( $ai_market_code, array( 'CN', 'KR' ), true ) && function_exists( 'ai_price_cnkr_from_lot' ) ) {
	$ai_calc_full = ai_price_cnkr_from_lot( $ai_lot, true );
	if ( $ai_calc_full ) {
		$ai_calc      = $ai_calc_full;
		$ai_price     = 'от ' . $ai_calc['total_label'];
		$ai_price_reg = 'от ' . $ai_calc['total_reg_label'];
	}
}
$ai_origin = $ai_lot['origin_price'] ?? '';
$ai_origin_line = $ai_origin
	? (
		array(
			'CN' => 'цена в Китае ',
			'KR' => 'цена в Корее ',
		)[ $ai_lot['market'] ] ?? 'цена на площадке '
	) . $ai_origin
	: '';
if ( ! empty( $ai_lot['price_basis'] ) && 'start' === $ai_lot['price_basis'] ) {
	$ai_origin_line = trim(
		$ai_origin_line . ( $ai_origin_line ? ' · ' : '' ) . (
			in_array( $ai_market_code, array( 'CN', 'KR' ), true )
				? 'FINISH нет — стартовая'
				: 'средней нет — стартовая'
		)
	);
}

$ai_make  = $ai_lot['make'] ?? '';
$ai_model = $ai_lot['model'] ?? '';
if ( ! $ai_make || ! $ai_model ) {
	$ai_parts = preg_split( '/\s+/', trim( (string) $ai_lot['title'] ) );
	$ai_model = $ai_model ?: ( count( $ai_parts ) > 1 ? implode( ' ', array_slice( $ai_parts, 1 ) ) : ( $ai_parts[0] ?? '' ) );
	$ai_make  = $ai_make ?: ( $ai_parts[0] ?? $ai_lot['title'] );
}

$ai_city = array(
	'JP' => 'Япония / Владивосток',
	'KR' => 'Сеул / Владивосток',
	'CN' => 'Нанкин / Владивосток',
)[ $ai_lot['market'] ] ?? ( $ai_lot['place'] ?? 'Владивосток' );

if ( function_exists( 'ai_auc_year' ) && ! empty( $ai_lot['year'] ) ) {
	$ai_lot['year'] = ai_auc_year( $ai_lot['year'] );
}

$ai_vin = trim( (string) ( $ai_lot['serial'] ?? '' ) ) ?: '—';

$ai_rows_left  = array(
	'Год'      => $ai_lot['year'],
	'Пробег'   => $ai_lot['km'],
	'Мощность' => $ai_lot['hp'] ?? '',
	'Топливо'  => $ai_lot['fuel'] ?? '',
	'КПП'      => $ai_lot['gearbox'] ?? '',
);
$ai_rows_right = array(
	'Привод' => $ai_lot['drive'] ?? '',
	'Кузов'  => $ai_lot['body'] ?? '',
	'Цвет'   => $ai_lot['color'] ?? '',
	'Объём'  => $ai_lot['volume'] ?? '',
	'Лот'    => $ai_lot['num'],
);

$ai_table = array(
	'Кузов / шасси'             => $ai_lot['chassis'] ?? $ai_lot['body'] ?? '—',
	'КПП'                       => $ai_lot['gearbox'] ?? '—',
	'Цвет'                      => $ai_lot['color'] ?? '—',
	'Кузов'                     => $ai_lot['body'] ?? '—',
	'Марка'                     => $ai_make,
	'Модель'                    => $ai_model,
	'Привод'                    => $ai_lot['drive'] ?? '—',
	'Пробег'                    => $ai_lot['km'],
	'Мощность'                  => $ai_lot['hp'] ?? '—',
	'Оценка'                    => $ai_grade ?: '—',
	'Год выпуска'               => $ai_lot['year'],
	'Город / адрес'             => $ai_city,
	'Площадка'                  => $ai_lot['source'] ?? $ai_lot['src'] ?? '—',
	'Дата аукциона'             => ! empty( $ai_lot['auction_date'] ) ? preg_replace( '/:\d\d$/', '', (string) $ai_lot['auction_date'] ) : '—',
	'Предварительная цена в РФ' => $ai_lot['price'] ?: '—',
	'Цена на площадке'          => $ai_lot['origin_price'] ?? '—',
	'Объём двигателя, см3'      => $ai_lot['volume'] ?? '—',
	'Номер кузова'              => $ai_vin,
);
if ( ! empty( $ai_lot['equip'] ) ) {
	$ai_table['Комплектация'] = $ai_lot['equip'];
}

$ai_gallery = $ai_lot['gallery'] ?? array();
$ai_thumbs  = array();
if ( $ai_gallery ) {
	foreach ( $ai_gallery as $ai_g ) {
		$ai_thumb = $ai_g['medium'] ?: $ai_g['small'];
		if ( ! $ai_thumb ) {
			continue;
		}
		$ai_thumbs[] = array(
			'thumb'  => $ai_thumb,
			'medium' => $ai_g['medium'] ?: $ai_g['big'],
			'big'    => $ai_g['big'] ?: $ai_g['medium'],
			'sheet'  => ! empty( $ai_g['sheet'] ),
		);
	}
}
if ( ! $ai_thumbs ) {
	$ai_thumbs[] = array(
		'thumb'  => $ai_lot['img'],
		'medium' => $ai_lot['img'],
		'big'    => $ai_lot['big'] ?? $ai_lot['img'],
		'sheet'  => false,
	);
}
$ai_main = $ai_thumbs[0];
?>
<div class="page-head">
	<div class="wrap">
		<div class="crumbs">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Главная</a>
			<?php echo ai_crumb_chev(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme SVG ?>
			<a href="<?php echo esc_url( home_url( '/catalog/' ) ); ?>">Каталог</a>
			<?php echo ai_crumb_chev(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme SVG ?>
			<span><?php echo esc_html( $ai_lot['title'] ); ?></span>
		</div>
	</div>
</div>

<div class="wrap page-body lot-detail">
	<div class="lot-hero-card">
		<div class="lot-gallery">
			<div class="lot-main-photo<?php echo ! empty( $ai_main['sheet'] ) ? ' is-sheet' : ''; ?>" data-zoom-open>
				<img id="lot-main-img" src="<?php echo esc_url( $ai_main['medium'] ); ?>" data-zoom="<?php echo esc_url( $ai_main['big'] ); ?>" alt="<?php echo esc_attr( $ai_lot['title'] ); ?>" decoding="async" />
				<span class="lot-badge"><?php echo esc_html( $ai_country ); ?></span>
				<?php if ( $ai_grade ) : ?>
					<span class="lot-badge lot-badge-2">оценка <?php echo esc_html( $ai_grade ); ?></span>
				<?php endif; ?>
				<span class="lot-badge lot-badge-auc">Аукцион</span>
				<button class="lot-zoom-btn" type="button">Увеличить</button>
			</div>
			<?php if ( count( $ai_thumbs ) > 1 ) : ?>
			<div class="lot-thumbs">
				<?php foreach ( $ai_thumbs as $ai_i => $ai_src ) : ?>
					<button type="button" class="lot-thumb<?php echo 0 === $ai_i ? ' is-on' : ''; ?><?php echo ! empty( $ai_src['sheet'] ) ? ' lot-thumb-sheet' : ''; ?>" data-src="<?php echo esc_url( $ai_src['medium'] ); ?>" data-zoom="<?php echo esc_url( $ai_src['big'] ); ?>" data-fit="<?php echo ! empty( $ai_src['sheet'] ) ? 'contain' : 'cover'; ?>">
						<img src="<?php echo esc_url( $ai_src['thumb'] ); ?>" alt="<?php echo ! empty( $ai_src['sheet'] ) ? 'Аукционный лист' : ''; ?>"<?php echo $ai_i > 3 ? ' loading="lazy"' : ''; ?> />
						<?php if ( ! empty( $ai_src['sheet'] ) ) : ?>
							<span class="lot-thumb-label">Лист</span>
						<?php endif; ?>
					</button>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>
		<div class="lot-hero-info">
			<div>
				<div class="lot-hero-top">
					<b><?php echo esc_html( $ai_lot['title'] . ( $ai_lot['year'] ? ', ' . $ai_lot['year'] . ' год' : '' ) ); ?></b>
					<span><?php echo esc_html( $ai_lot['source'] ?? $ai_lot['src'] ?? $ai_country ); ?></span>
				</div>
				<?php if ( $ai_origin_line ) : ?>
					<p class="lot-origin"><?php echo esc_html( $ai_origin_line ); ?></p>
				<?php endif; ?>
				<div class="lot-spec-grid">
					<div>
						<?php foreach ( $ai_rows_left as $ai_k => $ai_v ) : ?>
							<?php if ( $ai_v ) : ?>
								<div class="lot-spec"><span><?php echo esc_html( $ai_k ); ?></span><b><?php echo esc_html( $ai_v ); ?></b></div>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
					<div>
						<?php foreach ( $ai_rows_right as $ai_k => $ai_v ) : ?>
							<?php if ( $ai_v ) : ?>
								<div class="lot-spec"><span><?php echo esc_html( $ai_k ); ?></span><b><?php echo esc_html( $ai_v ); ?></b></div>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				</div>
				<p class="lot-price-lg"><?php echo esc_html( $ai_price ); ?></p>
				<?php if ( $ai_price_reg && $ai_price_reg !== $ai_price ) : ?>
					<p class="lot-price-sub">с временной пропиской (не ДВФО): <?php echo esc_html( $ai_price_reg ); ?></p>
				<?php endif; ?>
				<?php if ( ! empty( $ai_calc['incomplete'] ) ) : ?>
					<p class="lot-price-note">Пошлина и утильсбор уточняются — напишите нам</p>
				<?php endif; ?>
			</div>
			<button class="btn" data-open-modal type="button">Узнать цену под ключ</button>
		</div>
	</div>

	<div class="lot-tabs" data-tabs>
		<div class="lot-tab-nav" role="tablist">
			<button type="button" class="is-on" data-tab="desc">Описание</button>
			<button type="button" data-tab="spec">Комплектация</button>
			<button type="button" data-tab="calc">Предварительный расчёт</button>
			<button type="button" data-tab="faq">Вопрос-ответ</button>
			<button type="button" data-tab="rev">Отзывы и обзор</button>
		</div>
		<div class="lot-tab-panel is-on" data-panel="desc">
			<table class="lot-table">
				<?php
				foreach ( $ai_table as $ai_k => $ai_v ) {
					$ai_v = trim( (string) $ai_v );
					if ( '' === $ai_v || '—' === $ai_v ) {
						continue;
					}
					printf( '<tr><th>%s</th><td>%s</td></tr>', esc_html( $ai_k ), esc_html( $ai_v ) );
				}
				?>
			</table>
		</div>
		<div class="lot-tab-panel" data-panel="spec" hidden>
			<table class="lot-table">
				<?php
				$ai_spec_rows = array(
					'Топливо'       => $ai_lot['fuel'] ?? '',
					'КПП'           => $ai_lot['gearbox'] ?? '',
					'Привод'        => $ai_lot['drive'] ?? '',
					'Объём'         => $ai_lot['volume'] ?? '',
					'Мощность'      => $ai_lot['hp'] ?? '',
					'Оценка'        => $ai_grade,
					'Комплектация'  => $ai_lot['equip'] ?? '',
					'Дополнительно' => $ai_lot['info'] ?? '',
				);
				foreach ( $ai_spec_rows as $ai_k => $ai_v ) {
					$ai_v = trim( (string) $ai_v );
					if ( '' === $ai_v || '—' === $ai_v ) {
						continue;
					}
					printf( '<tr><th>%s</th><td>%s</td></tr>', esc_html( $ai_k ), esc_html( $ai_v ) );
				}
				?>
			</table>
		</div>
		<div class="lot-tab-panel" data-panel="calc" hidden>
			<?php if ( $ai_calc && ! empty( $ai_calc['lines'] ) ) : ?>
				<p>Предварительный расчёт до Владивостока. Курс ЦБ +5%. Скрытые комиссии в разбивку не входят.</p>
				<table class="lot-table lot-calc-table">
					<?php foreach ( $ai_calc['lines'] as $ai_line ) : ?>
						<tr>
							<th>
								<?php echo esc_html( $ai_line['label'] ); ?>
								<?php if ( ! empty( $ai_line['note'] ) ) : ?>
									<span class="lot-calc-note"><?php echo esc_html( $ai_line['note'] ); ?></span>
								<?php endif; ?>
							</th>
							<td>
								<?php
								if ( null === $ai_line['amount_rub'] ) {
									echo '—';
								} else {
									echo esc_html( ai_auc_num( (int) $ai_line['amount_rub'] ) . ' ₽' );
								}
								?>
							</td>
						</tr>
					<?php endforeach; ?>
					<tr class="lot-calc-total">
						<th>Итого (прописка ДВФО)</th>
						<td><?php echo esc_html( $ai_calc['total_label'] ); ?></td>
					</tr>
					<tr class="lot-calc-total">
						<th>Итого с временной пропиской</th>
						<td><?php echo esc_html( $ai_calc['total_reg_label'] ); ?></td>
					</tr>
				</table>
				<?php if ( ! empty( $ai_calc['incomplete'] ) ) : ?>
					<p class="lot-price-note">Пошлина и утильсбор временно не получены из Calculator-API.</p>
				<?php endif; ?>
			<?php else : ?>
				<p>В цену под ключ входят лот, фрахт, пошлина, СБКТС, ЭПТС и доставка до Владивостока. Фиксируем до ставки.</p>
			<?php endif; ?>
		</div>
		<div class="lot-tab-panel" data-panel="faq" hidden>
			<p>Срок 21–45 дней. Предоплата — депозит на торги. Остаток — после выкупа и до прихода в порт.</p>
		</div>
		<div class="lot-tab-panel" data-panel="rev" hidden>
			<p>Видео-осмотр и отзывы по похожим выдачам — по запросу в мессенджер.</p>
		</div>
	</div>

	<div class="lot-story">
		<figure>
			<img src="<?php echo esc_url( $ai_lot['img'] ); ?>" alt="<?php echo esc_attr( $ai_lot['title'] ); ?>" />
		</figure>
		<div class="prose">
			<?php if ( $ai_lot['content'] ) : ?>
				<?php echo $ai_lot['content']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php else : ?>
				<p><b><?php echo esc_html( $ai_lot['title'] ); ?> из <?php echo esc_html( $ai_from ); ?></b> — лот с площадки <?php echo esc_html( $ai_lot['source'] ?? $ai_lot['src'] ); ?>. Перед ставкой присылаем аукционный лист, фото и полный расчёт до порта выдачи.</p>
				<?php
				$ai_facts = array();
				if ( ! empty( $ai_lot['fuel'] ) ) {
					$ai_facts[] = 'Топливо — ' . $ai_lot['fuel'];
				}
				if ( ! empty( $ai_lot['volume'] ) && '—' !== $ai_lot['volume'] ) {
					$ai_facts[] = 'объём ' . $ai_lot['volume'];
				}
				if ( ! empty( $ai_lot['hp'] ) ) {
					$ai_facts[] = 'мощность ' . $ai_lot['hp'];
				}
				if ( ! empty( $ai_lot['gearbox'] ) && '—' !== $ai_lot['gearbox'] ) {
					$ai_facts[] = 'КПП — ' . $ai_lot['gearbox'];
				}
				if ( ! empty( $ai_lot['drive'] ) && '—' !== $ai_lot['drive'] ) {
					$ai_facts[] = 'привод — ' . $ai_lot['drive'];
				}
				if ( ! empty( $ai_lot['year'] ) ) {
					$ai_facts[] = 'год выпуска — ' . $ai_lot['year'];
				}
				if ( ! empty( $ai_lot['km'] ) ) {
					$ai_facts[] = 'пробег — ' . $ai_lot['km'];
				}
				if ( $ai_facts ) :
					?>
				<p><?php echo esc_html( ucfirst( implode( ', ', $ai_facts ) ) ); ?>.</p>
				<?php endif; ?>
				<p>По данному лоту предварительная итоговая цена с учётом доставки и таможенного оформления — <?php echo esc_html( $ai_price ); ?>. В итоговую стоимость включены доставка, таможенные пошлины, утильсбор и сопутствующие расходы. Для уточнения цифры и сроков воспользуйтесь формой или напишите в мессенджер.</p>
			<?php endif; ?>
		</div>
	</div>
</div>

<?php
get_footer();
