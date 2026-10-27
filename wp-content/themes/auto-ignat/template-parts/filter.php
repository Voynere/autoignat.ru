<?php
/**
 * Фильтр подбора автомобиля (главная / каталог / страницы доставки).
 * GET → страница каталога.
 *
 * @package auto-ignat
 */

$args            = ( isset( $args ) && is_array( $args ) ) ? $args : array();
$ai_catalog_url  = $args['action'] ?? home_url( '/catalog/' );
$ai_hide_country = ! empty( $args['hide_country'] );
$ai_has_filter   = (bool) array_intersect( array( 'make', 'model', 'engine', 'body', 'grade', 'drive', 'wheel', 'year_from', 'country' ), array_keys( $_GET ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$ai_collapsed    = ! empty( $args['collapsible'] ) && ! $ai_has_filter;
$ai_compact      = ! empty( $args['compact'] );
$ai_picker       = ! empty( $args['picker'] );
$ai_has_extra    = (bool) array_intersect( array( 'engine', 'body', 'grade', 'drive', 'wheel', 'hp_from', 'volume_from' ), array_keys( $_GET ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$ai_q            = static function ( string $key, string $default = '' ) {
	return isset( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : $default; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
};

$ai_chips = static function ( string $name, array $options, string $active ) {
	echo '<div class="chips" data-group="' . esc_attr( $name ) . '">';
	foreach ( $options as $ai_value => $ai_label ) {
		$ai_html = $ai_label;
		if ( is_array( $ai_label ) ) {
			$ai_html = '';
			if ( ! empty( $ai_label['flag'] ) ) {
				$ai_html .= '<img class="flag" src="' . ai_img( $ai_label['flag'] ) . '" alt="" width="16" height="16" />';
			}
			$ai_html .= esc_html( $ai_label['text'] );
		} else {
			$ai_html = esc_html( $ai_label );
		}
		printf(
			'<button type="button" class="chip%1$s" data-value="%2$s">%3$s</button>',
			(string) $ai_value === $active ? ' is-on' : '',
			esc_attr( (string) $ai_value ),
			$ai_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}
	echo '</div>';
	printf( '<input type="hidden" name="%s" value="%s" />', esc_attr( $name ), esc_attr( $active ) );
};

$ai_num = static function ( float $n ): string {
	$s = rtrim( rtrim( number_format( $n, 4, '.', '' ), '0' ), '.' );
	return '' === $s ? '0' : $s;
};
$ai_stepper = static function ( string $label, string $name_from, string $val_from, string $name_to, string $val_to, string $suffix = '', float $step = 1, float $min = 0, ?float $max = null ) use ( $ai_num ) {
	$ai_fmt = static function ( string $raw ) use ( $step, $min, $max, $ai_num ): string {
		$raw = str_replace( ',', '.', trim( $raw ) );
		$n   = is_numeric( $raw ) ? (float) $raw : $min;
		if ( $n < $min ) {
			$n = $min;
		}
		if ( null !== $max && $n > $max ) {
			$n = $max;
		}
		if ( $step > 0 && $step < 1 ) {
			$n = round( ( $n - $min ) / $step ) * $step + $min;
		}
		return $ai_num( $n );
	};
	$ai_arr = static function ( string $dir ): string {
		return sprintf( '<button type="button" class="arr" data-dir="%s" aria-label="%s">%s</button>', esc_attr( $dir ), $dir === 'up' ? 'Больше' : 'Меньше', $dir === 'up' ? '▲' : '▼' );
	};
	$ai_bounds = ' min="' . esc_attr( $ai_num( $min ) ) . '" step="' . esc_attr( $ai_num( $step ) ) . '"';
	if ( null !== $max ) {
		$ai_bounds .= ' max="' . esc_attr( $ai_num( $max ) ) . '"';
	}
	$ai_pair = '<div class="stepper-pair">';
	foreach ( array(
		array( 'от', $name_from, $val_from ),
		array( 'до', $name_to, $val_to ),
	) as $ai_f ) {
		$ai_pair .= sprintf(
			'<span class="stepper"><span class="prefix">%1$s</span><input type="number" name="%2$s" value="%3$s" inputmode="decimal"%4$s /><span class="arrs">%5$s</span>%6$s</span>',
			esc_html( $ai_f[0] ),
			esc_attr( $ai_f[1] ),
			esc_attr( $ai_fmt( $ai_f[2] ) ),
			$ai_bounds, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$ai_arr( 'up' ) . $ai_arr( 'down' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$suffix ? '<span class="suffix">' . esc_html( $suffix ) . '</span>' : ''
		);
	}
	$ai_pair .= '</div>';
	if ( '' === $label ) {
		echo $ai_pair; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return;
	}
	echo '<div class="filter-field"><span class="field-label">' . esc_html( $label ) . '</span>';
	echo $ai_pair; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</div>';
};

$ai_country   = strtolower( $ai_q( 'country', $args['country'] ?? '' ) );
$ai_table     = function_exists( 'ai_auc_market_table' ) ? ai_auc_market_table( $ai_country ) : 'main';
$ai_make_now  = $ai_q( 'make' );
$ai_model_now = $ai_q( 'model' );
$ai_vendors   = function_exists( 'ai_auc_vendors' ) ? ai_auc_vendors( $ai_table ) : array();
if ( ! $ai_vendors ) {
	$ai_vendors = array(
		array( 'id' => 'TOYOTA', 'label' => 'Toyota' ),
		array( 'id' => 'HONDA', 'label' => 'Honda' ),
		array( 'id' => 'SUBARU', 'label' => 'Subaru' ),
		array( 'id' => 'MAZDA', 'label' => 'Mazda' ),
		array( 'id' => 'LEXUS', 'label' => 'Lexus' ),
		array( 'id' => 'BMW', 'label' => 'BMW' ),
		array( 'id' => 'BENZ', 'label' => 'Mercedes-Benz' ),
		array( 'id' => 'HYUNDAI', 'label' => 'Hyundai' ),
		array( 'id' => 'KIA', 'label' => 'Kia' ),
		array( 'id' => 'BYD', 'label' => 'BYD' ),
		array( 'id' => 'LI', 'label' => 'Li Auto' ),
		array( 'id' => 'ZEEKR', 'label' => 'Zeekr' ),
	);
}
$ai_models = ( $ai_make_now && function_exists( 'ai_auc_models' ) ) ? ai_auc_models( $ai_make_now, $ai_table ) : array();
$ai_same   = static function ( string $a, string $b ): bool {
	return '' !== $a && '' !== $b && strcasecmp( $a, $b ) === 0;
};
$ai_opt_label = static function ( array $row ): string {
	$label = (string) ( $row['label'] ?? $row['id'] ?? '' );
	$count = (int) ( $row['count'] ?? 0 );
	if ( $count > 0 && function_exists( 'ai_auc_num' ) ) {
		$label .= ' (' . ai_auc_num( $count ) . ')';
	}
	return $label;
};
?>
<?php if ( $ai_picker ) : ?>
<?php
$ai_wheel_q = $ai_q( 'wheel' );
if ( in_array( $ai_wheel_q, array( 'все', 'любое' ), true ) ) {
	$ai_wheel_q = '';
}
$ai_quick = 'all';
if ( '1' === $ai_q( 'euro' ) ) {
	$ai_quick = 'euro';
} elseif ( '1' === $ai_q( 'sanction' ) ) {
	$ai_quick = 'sanction-1';
} elseif ( '0' === $ai_q( 'sanction' ) ) {
	$ai_quick = 'sanction-0';
} elseif ( '' !== $ai_q( 'body' ) && 'любой' !== $ai_q( 'body' ) ) {
	$ai_quick = 'body-' . $ai_q( 'body' );
} elseif ( 'гибрид' === $ai_q( 'engine' ) ) {
	$ai_quick = 'engine';
} elseif ( '160' === $ai_q( 'hp_to' ) ) {
	$ai_quick = 'hp';
}
$ai_quick_chips = array(
	array( 'key' => 'all', 'quick' => 'all', 'label' => 'Все' ),
	array( 'key' => 'hp', 'quick' => 'hp', 'value' => '160', 'label' => 'До 160 л.с.' ),
	array( 'key' => 'engine', 'quick' => 'engine', 'value' => 'гибрид', 'label' => 'Гибриды' ),
	array( 'key' => 'body-кроссовер', 'quick' => 'body', 'value' => 'кроссовер', 'label' => 'Кроссоверы' ),
	array( 'key' => 'body-минивен', 'quick' => 'body', 'value' => 'минивен', 'label' => 'Минивэны' ),
	array( 'key' => 'body-седан', 'quick' => 'body', 'value' => 'седан', 'label' => 'Седаны' ),
	array( 'key' => 'body-хэтчбек', 'quick' => 'body', 'value' => 'хэтчбек', 'label' => 'Хэтчбеки' ),
	array( 'key' => 'euro', 'quick' => 'euro', 'value' => '1', 'label' => 'Европейские авто' ),
	array( 'key' => 'sanction-1', 'quick' => 'sanction', 'value' => '1', 'label' => 'Санкции Японии' ),
	array( 'key' => 'sanction-0', 'quick' => 'sanction', 'value' => '0', 'label' => 'Вне санкций' ),
	array( 'key' => 'body-универсал', 'quick' => 'body', 'value' => 'универсал', 'label' => 'Универсалы' ),
	array( 'key' => 'body-открытый', 'quick' => 'body', 'value' => 'открытый', 'label' => 'Кабриолеты' ),
	array( 'key' => 'body-купе', 'quick' => 'body', 'value' => 'купе', 'label' => 'Купе' ),
	array( 'key' => 'body-пикап', 'quick' => 'body', 'value' => 'пикап', 'label' => 'Пикапы' ),
	array( 'key' => 'body-лифтбек', 'quick' => 'body', 'value' => 'лифтбек', 'label' => 'Лифтбеки' ),
	array( 'key' => 'body-кейкар', 'quick' => 'body', 'value' => 'кейкар', 'label' => 'Кей-кары' ),
);
?>
<form class="filter-quick filter-panel" method="get" action="<?php echo esc_url( $ai_catalog_url ); ?>">
	<div class="filter-quick-card">
		<div class="qrow">
			<span class="qpill-label">
				<svg class="qico" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M3.5 14.2 5.4 9.6A2 2 0 0 1 7.2 8.2h9.6a2 2 0 0 1 1.8 1.4l1.9 4.6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M3.2 14.2h17.6v2.2a1 1 0 0 1-1 1H16a1.7 1.7 0 0 0-3.2 0H11a1.7 1.7 0 0 0-3.2 0H4.2a1 1 0 0 1-1-1v-2.2Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><circle cx="7.8" cy="17.2" r="1.15" fill="currentColor"/><circle cx="16.2" cy="17.2" r="1.15" fill="currentColor"/></svg>
				Выберите авто
			</span>
			<label class="qpill qpill-make">
				<select name="make" data-filter-make data-with-count aria-label="Марка">
					<option value="">Марка</option>
					<?php
					foreach ( $ai_vendors as $ai_v ) {
						$ai_vid = (string) ( $ai_v['id'] ?? '' );
						$ai_on  = $ai_same( $ai_make_now, $ai_vid ) || $ai_same( $ai_make_now, (string) ( $ai_v['label'] ?? '' ) );
						printf(
							'<option value="%s"%s>%s</option>',
							esc_attr( $ai_vid ),
							$ai_on ? ' selected' : '',
							esc_html( $ai_opt_label( $ai_v ) )
						);
					}
					?>
				</select>
			</label>
			<label class="qpill qpill-model">
				<select name="model" data-filter-model data-with-count data-placeholder="Выберите модель" aria-label="Модель"<?php echo $ai_make_now ? '' : ' disabled'; ?>>
					<option value="">Выберите модель</option>
					<?php
					foreach ( $ai_models as $ai_m ) {
						$ai_mid = (string) ( $ai_m['id'] ?? '' );
						$ai_on  = $ai_same( $ai_model_now, $ai_mid ) || $ai_same( $ai_model_now, (string) ( $ai_m['label'] ?? '' ) );
						printf(
							'<option value="%s"%s>%s</option>',
							esc_attr( $ai_mid ),
							$ai_on ? ' selected' : '',
							esc_html( $ai_opt_label( $ai_m ) )
						);
					}
					?>
				</select>
			</label>
			<label class="qpill qpill-wheel">
				<select name="wheel" aria-label="Руль">
					<option value="">Руль</option>
					<option value="левый"<?php selected( $ai_wheel_q, 'левый' ); ?>>Левый</option>
					<option value="правый"<?php selected( $ai_wheel_q, 'правый' ); ?>>Правый</option>
				</select>
			</label>
			<button class="qsubmit" type="submit">
				<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.2" fill="none" stroke="currentColor" stroke-width="2"/><path d="M15.2 15.2 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
				Показать авто
			</button>
		</div>
		<div class="qchip-band">
		<div class="qchips-stack" data-quick-chips>
			<?php
			$ai_chip_rows = array(
				array_slice( $ai_quick_chips, 0, 9 ),
				array_slice( $ai_quick_chips, 9 ),
			);
			foreach ( $ai_chip_rows as $ai_chip_row ) {
				echo '<div class="qchips">';
				foreach ( $ai_chip_row as $ai_chip ) {
					printf(
						'<button type="button" class="qchip%s" data-quick="%s"%s>%s</button>',
						$ai_chip['key'] === $ai_quick ? ' is-on' : '',
						esc_attr( $ai_chip['quick'] ),
						isset( $ai_chip['value'] ) ? ' data-value="' . esc_attr( $ai_chip['value'] ) . '"' : '',
						esc_html( $ai_chip['label'] )
					);
				}
				echo '</div>';
			}
			?>
		</div>
		<div class="qbudget" data-budget-row>
			<span class="field-label">Цена под ключ</span>
			<div class="qchips qbudget-row">
				<?php
				$ai_bf = $ai_q( 'budget_from' );
				$ai_bt = $ai_q( 'budget_to' );
				$ai_prices = array(
					array( '', '', 'Любая' ),
					array( '', '1', 'до 1 млн' ),
					array( '1', '2', '1–2 млн' ),
					array( '2', '3', '2–3 млн' ),
					array( '3', '5', '3–5 млн' ),
					array( '5', '', 'от 5 млн' ),
				);
				foreach ( $ai_prices as $ai_p ) {
					$ai_on = ( (string) $ai_p[0] === $ai_bf && (string) $ai_p[1] === $ai_bt );
					printf(
						'<button type="button" class="qchip budget-chip%1$s" data-from="%2$s" data-to="%3$s">%4$s</button>',
						$ai_on ? ' is-on' : '',
						esc_attr( $ai_p[0] ),
						esc_attr( $ai_p[1] ),
						esc_html( $ai_p[2] )
					);
				}
				?>
			</div>
		</div>
		<input type="hidden" name="budget_from" value="<?php echo esc_attr( $ai_bf ); ?>" data-budget-from />
		<input type="hidden" name="budget_to" value="<?php echo esc_attr( $ai_bt ); ?>" data-budget-to />
		</div>
		<input type="hidden" name="hp_to" value="<?php echo esc_attr( $ai_q( 'hp_to' ) ); ?>" data-qfield="hp" />
		<input type="hidden" name="engine" value="<?php echo esc_attr( $ai_q( 'engine' ) ); ?>" data-qfield="engine" />
		<input type="hidden" name="body" value="<?php echo esc_attr( $ai_q( 'body' ) ); ?>" data-qfield="body" />
		<input type="hidden" name="sanction" value="<?php echo esc_attr( $ai_q( 'sanction' ) ); ?>" data-qfield="sanction" />
		<input type="hidden" name="euro" value="<?php echo esc_attr( $ai_q( 'euro' ) ); ?>" data-qfield="euro" />
	</div>
</form>
<?php endif; ?>
<div class="filter-wrap<?php echo $ai_collapsed ? ' is-collapsed' : ''; ?><?php echo $ai_compact ? ' is-compact' : ''; ?><?php echo ( $ai_compact && $ai_has_extra ) ? ' is-open' : ''; ?><?php echo $ai_picker ? ' filter-after-quick' : ''; ?>">
<?php if ( ! empty( $args['collapsible'] ) ) : ?>
	<button class="filter-toggle" type="button" data-filter-toggle aria-expanded="<?php echo $ai_collapsed ? 'false' : 'true'; ?>">
		<?php echo $ai_collapsed ? 'Показать фильтры' : 'Скрыть фильтры'; ?>
	</button>
<?php endif; ?>
<form class="filter-panel" method="get" action="<?php echo esc_url( $ai_catalog_url ); ?>">
	<div class="filter-grid">
		<?php if ( ! $ai_hide_country ) : ?>
		<div class="filter-field">
			<span class="field-label">Страна</span>
			<?php
			$ai_chips(
				'country',
				array(
					''   => 'Все',
					'jp' => array( 'flag' => 'flag-jp.svg', 'text' => 'Япония' ),
					'kr' => array( 'flag' => 'flag-kr.svg', 'text' => 'Корея' ),
					'cn' => array( 'flag' => 'flag-cn.svg', 'text' => 'Китай' ),
				),
				$ai_q( 'country', $args['country'] ?? '' )
			);
			?>
		</div>
		<?php elseif ( ! empty( $args['country'] ) ) : ?>
			<input type="hidden" name="country" value="<?php echo esc_attr( $args['country'] ); ?>" />
		<?php endif; ?>
		<div class="filter-field">
			<span class="field-label">Марка и модель</span>
			<div class="make-row">
				<select class="select-box" name="make" data-filter-make aria-label="Марка">
					<option value="">Марка</option>
					<?php
					foreach ( $ai_vendors as $ai_v ) {
						$ai_vid = (string) ( $ai_v['id'] ?? '' );
						$ai_vl  = (string) ( $ai_v['label'] ?? $ai_vid );
						$ai_on  = $ai_same( $ai_make_now, $ai_vid ) || $ai_same( $ai_make_now, $ai_vl );
						printf(
							'<option value="%s"%s>%s</option>',
							esc_attr( $ai_vid ),
							$ai_on ? ' selected' : '',
							esc_html( $ai_vl )
						);
					}
					?>
				</select>
				<select class="select-box" name="model" data-filter-model aria-label="Модель"<?php echo $ai_make_now ? '' : ' disabled'; ?>>
					<option value=""><?php echo $ai_make_now ? 'Модель' : 'Сначала марка'; ?></option>
					<?php
					foreach ( $ai_models as $ai_m ) {
						$ai_mid = (string) ( $ai_m['id'] ?? '' );
						$ai_ml  = (string) ( $ai_m['label'] ?? $ai_mid );
						$ai_on  = $ai_same( $ai_model_now, $ai_mid ) || $ai_same( $ai_model_now, $ai_ml );
						printf(
							'<option value="%s"%s>%s</option>',
							esc_attr( $ai_mid ),
							$ai_on ? ' selected' : '',
							esc_html( $ai_ml )
						);
					}
					?>
				</select>
			</div>
		</div>
		<?php $ai_stepper( 'Год', 'year_from', $ai_q( 'year_from', '2022' ), 'year_to', $ai_q( 'year_to', '2023' ), '', 1, 1980, 2100 ); ?>
		<div class="filter-field filter-extra">
			<span class="field-label">Двигатель</span>
			<?php $ai_chips( 'engine', array( 'любой' => 'Любой', 'бензин' => 'Бензин', 'электро' => 'Электро', 'дизель' => 'Дизель', 'гибрид' => 'Гибрид' ), $ai_q( 'engine', 'любой' ) ); ?>
		</div>
		<div class="filter-field filter-extra">
			<span class="field-label">Кузов</span>
			<?php $ai_chips( 'body', array( 'любой' => 'Любой', 'седан' => 'Седан', 'кроссовер' => 'Кроссовер', 'кейкар' => 'Кейкар', 'пикап' => 'Пикап', 'купе' => 'Купе', 'универсал' => 'Универсал', 'минивен' => 'Минивен', 'открытый' => 'Открытый', 'джип' => 'Джип', 'хэтчбек' => 'Хэтчбек' ), $ai_q( 'body', 'любой' ) ); ?>
		</div>
		<div class="filter-field filter-extra">
			<span class="field-label">Расположение руля</span>
			<?php
			$ai_wheel = $ai_q( 'wheel', 'любое' );
			if ( 'все' === $ai_wheel ) {
				$ai_wheel = 'любое';
			}
			$ai_chips( 'wheel', array( 'любое' => 'Любое', 'левый' => 'Левый', 'правый' => 'Правый' ), $ai_wheel );
			?>
		</div>
		<div class="filter-field filter-extra">
			<span class="field-label">Лошадиные силы, объём ДВС</span>
			<div class="power-block">
				<div class="power-line">
					<?php $ai_stepper( '', 'hp_from', $ai_q( 'hp_from', '50' ), 'hp_to', $ai_q( 'hp_to', '160' ), '', 1, 0 ); ?>
					<span class="power-hint">лошадиные силы</span>
				</div>
				<div class="power-line">
					<?php $ai_stepper( '', 'volume_from', $ai_q( 'volume_from', '1' ), 'volume_to', $ai_q( 'volume_to', '3.5' ), '', 0.1, 0 ); ?>
					<span class="power-hint">объем в см3</span>
				</div>
			</div>
		</div>
		<div class="filter-field filter-extra">
			<span class="field-label">Оценка</span>
			<?php $ai_chips( 'grade', array( '3' => '3', '3.5' => '3.5', 'R' => 'R', 'RA' => 'RA', '4' => '4', '4.5' => '4.5', '5' => '5', 'S' => 'S' ), $ai_q( 'grade' ) ); ?>
		</div>
		<div class="filter-field filter-extra">
			<span class="field-label">Привод</span>
			<?php $ai_chips( 'drive', array( 'любой' => 'Любой', 'полный' => 'Полный', 'передний' => 'Передний', 'задний' => 'Задний' ), $ai_q( 'drive', 'любой' ) ); ?>
		</div>
	</div>
	<?php if ( $ai_compact ) : ?>
		<button class="filter-more" type="button" data-filter-more aria-expanded="<?php echo $ai_has_extra ? 'true' : 'false'; ?>">
			<?php echo $ai_has_extra ? 'Скрыть параметры' : 'Ещё параметры'; ?>
		</button>
	<?php endif; ?>
	<div class="filter-foot">
		<?php $ai_stepper( 'Бюджет под ключ', 'budget_from', $ai_q( 'budget_from', '1.5' ), 'budget_to', $ai_q( 'budget_to', '3.5' ), 'млн', 0.1, 0 ); ?>
		<button class="btn filter-submit" type="submit">Подобрать авто</button>
	</div>
</form>
</div>
