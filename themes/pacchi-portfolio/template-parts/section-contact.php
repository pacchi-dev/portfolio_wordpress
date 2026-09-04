<?php
/**
 * Contact セクション。
 *
 * 未入力の連絡先は表示しない。有効・無効のフラグを別に持たず、
 * 「空欄なら非表示」とすることで管理項目を減らしている。
 *
 * @package PacchiPortfolio
 */

defined( 'ABSPATH' ) || exit;

$mp_page_id = (int) get_option( 'page_on_front' );

$mp_email = (string) get_field( 'contact_email', $mp_page_id );

$mp_links = array(
	array(
		'label' => __( 'Email', 'pacchi-portfolio' ),
		'value' => $mp_email,
		'href'  => '' !== $mp_email ? 'mailto:' . $mp_email : '',
		'ext'   => false,
	),
	array(
		'label' => __( 'GitHub', 'pacchi-portfolio' ),
		'value' => __( 'プロフィール', 'pacchi-portfolio' ),
		'href'  => (string) get_field( 'contact_github', $mp_page_id ),
		'ext'   => true,
	),
	array(
		'label' => __( 'CrowdWorks', 'pacchi-portfolio' ),
		'value' => __( 'プロフィール', 'pacchi-portfolio' ),
		'href'  => (string) get_field( 'contact_crowdworks', $mp_page_id ),
		'ext'   => true,
	),
	array(
		'label' => __( 'X', 'pacchi-portfolio' ),
		'value' => __( 'プロフィール', 'pacchi-portfolio' ),
		'href'  => (string) get_field( 'contact_x', $mp_page_id ),
		'ext'   => true,
	),
);

$mp_links = array_values(
	array_filter( $mp_links, static fn( array $l ): bool => '' !== trim( (string) $l['href'] ) )
);

mp_section_open(
	array(
		'id'      => 'contact',
		'eyebrow' => 'contact',
		'title'   => (string) get_field( 'contact_title', $mp_page_id ),
		'lead'    => (string) get_field( 'contact_lead', $mp_page_id ),
		'open'    => true,
	)
);
?>

<?php if ( ! empty( $mp_links ) ) : ?>
	<ul class="contact-list">
		<?php foreach ( $mp_links as $mp_link ) : ?>
			<li>
				<a class="contact-card"
					href="<?php echo esc_url( (string) $mp_link['href'] ); ?>"
					<?php if ( $mp_link['ext'] ) : ?>target="_blank" rel="noreferrer noopener"<?php endif; ?>>
					<span class="contact-card__label"><?php echo esc_html( (string) $mp_link['label'] ); ?></span>
					<span class="contact-card__value"><?php echo esc_html( (string) $mp_link['value'] ); ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
<?php else : ?>
	<p class="contact-empty"><?php esc_html_e( '連絡先は準備中です。', 'pacchi-portfolio' ); ?></p>
<?php endif; ?>

<?php
mp_section_close();
