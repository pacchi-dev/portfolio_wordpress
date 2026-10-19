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

/*
 * ACF が無効・更新失敗の状態でも致命的エラーにせず、
 * このセクションを出力しないで済ませる。
 * README が謳う「ACF が無効でも致命的エラーにならない」の実装。
 */
if ( ! pp_acf_active() ) {
	return;
}

$pp_page_id = (int) get_option( 'page_on_front' );

$pp_cards = array();

for ( $i = 1; $i <= 4; $i++ ) {
	$card = (array) get_field( "service_{$i}", $pp_page_id );

	if ( '' === trim( (string) ( $card['title'] ?? '' ) ) ) {
		continue;
	}

	$pp_cards[] = $card;
}

if ( empty( $pp_cards ) ) {
	return;
}

pp_section_open(
	array(
		'id'      => 'services',
		'eyebrow' => 'what i do',
		'title'   => (string) get_field( 'services_title', $pp_page_id ),
		'lead'    => (string) get_field( 'services_lead', $pp_page_id ),
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
	<?php foreach ( $pp_cards as $pp_card ) : ?>
		<article class="service-card">
			<div class="service-card__head">
				<span class="service-card__icon" aria-hidden="true">
					<?php pp_service_icon( (string) ( $pp_card['icon'] ?? 'backend' ) ); ?>
				</span>
				<h3 class="service-card__title"><?php echo esc_html( (string) $pp_card['title'] ); ?></h3>
			</div>

			<?php if ( '' !== trim( (string) ( $pp_card['description'] ?? '' ) ) ) : ?>
				<p class="service-card__text text-balance-ja"><?php echo esc_html( (string) $pp_card['description'] ); ?></p>
			<?php endif; ?>

			<?php pp_render_tags( pp_lines_to_array( (string) ( $pp_card['tags'] ?? '' ) ) ); ?>
		</article>
	<?php endforeach; ?>
</div>

<?php
pp_section_close();
