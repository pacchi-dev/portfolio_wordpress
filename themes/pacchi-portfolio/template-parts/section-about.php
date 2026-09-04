<?php
/**
 * About セクション。
 *
 * @package PacchiPortfolio
 */

defined( 'ABSPATH' ) || exit;

$mp_page_id = (int) get_option( 'page_on_front' );
$mp_title   = (string) get_field( 'about_title', $mp_page_id );
$mp_body    = (string) get_field( 'about_body', $mp_page_id );

// 空行で段落に分割する。/u はマルチバイト文字を壊さないために必須。
$mp_paragraphs = array_values(
	array_filter(
		array_map( 'trim', preg_split( '/\R{2,}/u', $mp_body ) ?: array() ),
		static fn( string $p ): bool => '' !== $p
	)
);

if ( '' === $mp_title && empty( $mp_paragraphs ) ) {
	return;
}

mp_section_open(
	array(
		'id'      => 'about',
		'eyebrow' => 'about',
		'title'   => $mp_title,
		'open'    => true,
	)
);
?>

<div class="prose">
	<?php foreach ( $mp_paragraphs as $mp_paragraph ) : ?>
		<p class="text-balance-ja"><?php echo esc_html( $mp_paragraph ); ?></p>
	<?php endforeach; ?>
</div>

<?php
mp_section_close();
