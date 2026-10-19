<?php
/**
 * Work With Me セクション。
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

$pp_page_id   = (int) get_option( 'page_on_front' );
$pp_services  = pp_lines_to_array( (string) get_field( 'work_services', $pp_page_id ) );
$pp_note      = (string) get_field( 'work_note', $pp_page_id );
$pp_condition = (array) get_field( 'work_conditions', $pp_page_id );

if ( empty( $pp_services ) ) {
	return;
}

// 表示ラベルと ACF のサブフィールド名の対応。
$pp_condition_labels = array(
	'remote'       => 'Remote',
	'availability' => 'Availability',
	'hours'        => 'Hours',
	'contract'     => 'Contract',
);

pp_section_open(
	array(
		'id'      => 'work',
		'eyebrow' => 'work with me',
		'title'   => (string) get_field( 'work_title', $pp_page_id ),
		'lead'    => (string) get_field( 'work_lead', $pp_page_id ),
		'surface' => true,
		'open'    => true,
	)
);
?>

<div class="work-layout">
	<ul class="work-services">
		<?php foreach ( $pp_services as $pp_service ) : ?>
			<li class="text-balance-ja">
				<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<path d="M2.5 7.5l3 3 6-7" />
				</svg>
				<?php echo esc_html( $pp_service ); ?>
			</li>
		<?php endforeach; ?>
	</ul>

	<div class="availability-card">
		<h3 class="availability-card__title"><?php esc_html_e( 'Availability', 'pacchi-portfolio' ); ?></h3>
		<dl class="availability-card__list">
			<?php foreach ( $pp_condition_labels as $pp_key => $pp_label ) : ?>
				<?php $pp_value = (string) ( $pp_condition[ $pp_key ] ?? '' ); ?>
				<?php if ( '' === trim( $pp_value ) ) { continue; } ?>
				<div>
					<dt><?php echo esc_html( $pp_label ); ?></dt>
					<dd><?php echo esc_html( $pp_value ); ?></dd>
				</div>
			<?php endforeach; ?>
		</dl>
		<?php if ( '' !== trim( $pp_note ) ) : ?>
			<p class="availability-card__note text-balance-ja"><?php echo esc_html( $pp_note ); ?></p>
		<?php endif; ?>
	</div>
</div>

<?php
pp_section_close();
