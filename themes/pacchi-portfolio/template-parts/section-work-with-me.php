<?php
/**
 * Work With Me セクション。
 *
 * @package PacchiPortfolio
 */

defined( 'ABSPATH' ) || exit;

$mp_page_id   = (int) get_option( 'page_on_front' );
$mp_services  = mp_lines_to_array( (string) get_field( 'work_services', $mp_page_id ) );
$mp_note      = (string) get_field( 'work_note', $mp_page_id );
$mp_condition = (array) get_field( 'work_conditions', $mp_page_id );

if ( empty( $mp_services ) ) {
	return;
}

// 表示ラベルと ACF のサブフィールド名の対応。
$mp_condition_labels = array(
	'remote'       => 'Remote',
	'availability' => 'Availability',
	'hours'        => 'Hours',
	'contract'     => 'Contract',
);

mp_section_open(
	array(
		'id'      => 'work',
		'eyebrow' => 'work with me',
		'title'   => (string) get_field( 'work_title', $mp_page_id ),
		'lead'    => (string) get_field( 'work_lead', $mp_page_id ),
		'surface' => true,
		'open'    => true,
	)
);
?>

<div class="work-layout">
	<ul class="work-services">
		<?php foreach ( $mp_services as $mp_service ) : ?>
			<li class="text-balance-ja">
				<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<path d="M2.5 7.5l3 3 6-7" />
				</svg>
				<?php echo esc_html( $mp_service ); ?>
			</li>
		<?php endforeach; ?>
	</ul>

	<div class="availability-card">
		<h3 class="availability-card__title"><?php esc_html_e( 'Availability', 'pacchi-portfolio' ); ?></h3>
		<dl class="availability-card__list">
			<?php foreach ( $mp_condition_labels as $mp_key => $mp_label ) : ?>
				<?php $mp_value = (string) ( $mp_condition[ $mp_key ] ?? '' ); ?>
				<?php if ( '' === trim( $mp_value ) ) { continue; } ?>
				<div>
					<dt><?php echo esc_html( $mp_label ); ?></dt>
					<dd><?php echo esc_html( $mp_value ); ?></dd>
				</div>
			<?php endforeach; ?>
		</dl>
		<?php if ( '' !== trim( $mp_note ) ) : ?>
			<p class="availability-card__note text-balance-ja"><?php echo esc_html( $mp_note ); ?></p>
		<?php endif; ?>
	</div>
</div>

<?php
mp_section_close();
