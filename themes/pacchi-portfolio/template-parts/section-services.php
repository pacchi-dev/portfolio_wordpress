<?php
/**
 * What I Do セクション。
 *
 * ACF 無料版に Repeater が無いため、固定 4 枚を group フィールドで持つ。
 * 空のカードは表示しない。
 *
 * @package PacchiPortfolio
 */

defined( 'ABSPATH' ) || exit;

$mp_page_id = (int) get_option( 'page_on_front' );

$mp_cards = array();

for ( $i = 1; $i <= 4; $i++ ) {
	$card = (array) get_field( "service_{$i}", $mp_page_id );

	if ( '' === trim( (string) ( $card['title'] ?? '' ) ) ) {
		continue;
	}

	$mp_cards[] = $card;
}

if ( empty( $mp_cards ) ) {
	return;
}

mp_section_open(
	array(
		'id'      => 'services',
		'eyebrow' => 'what i do',
		'title'   => (string) get_field( 'services_title', $mp_page_id ),
		'lead'    => (string) get_field( 'services_lead', $mp_page_id ),
		'surface' => true,
		/*
		 * 発注可否を判断するセクションなので、初期状態で開いておく。
		 * details は閉じていると中身が読まれないうえ、
		 * ページ内検索（Ctrl+F）でもヒットしない。
		 */
		'open'    => true,
	)
);
?>

<div class="card-grid">
	<?php foreach ( $mp_cards as $mp_card ) : ?>
		<article class="service-card">
			<div class="service-card__head">
				<span class="service-card__icon" aria-hidden="true">
					<?php mp_service_icon( (string) ( $mp_card['icon'] ?? 'backend' ) ); ?>
				</span>
				<h3 class="service-card__title"><?php echo esc_html( (string) $mp_card['title'] ); ?></h3>
			</div>

			<?php if ( '' !== trim( (string) ( $mp_card['description'] ?? '' ) ) ) : ?>
				<p class="service-card__text text-balance-ja"><?php echo esc_html( (string) $mp_card['description'] ); ?></p>
			<?php endif; ?>

			<?php mp_render_tags( mp_lines_to_array( (string) ( $mp_card['tags'] ?? '' ) ) ); ?>
		</article>
	<?php endforeach; ?>
</div>

<?php
mp_section_close();
