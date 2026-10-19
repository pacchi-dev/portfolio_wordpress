<?php
/**
 * About セクション。
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
$pp_title   = (string) get_field( 'about_title', $pp_page_id );
$pp_body    = (string) get_field( 'about_body', $pp_page_id );

// 空行で段落に分割する。/u はマルチバイト文字を壊さないために必須。
$pp_paragraphs = array_values(
	array_filter(
		array_map( 'trim', preg_split( '/\R{2,}/u', $pp_body ) ?: array() ),
		static fn( string $p ): bool => '' !== $p
	)
);

if ( '' === $pp_title && empty( $pp_paragraphs ) ) {
	return;
}

pp_section_open(
	array(
		'id'      => 'about',
		'eyebrow' => 'about',
		'title'   => $pp_title,
		'open'    => true,
	)
);
?>

<div class="prose">
	<?php foreach ( $pp_paragraphs as $pp_paragraph ) : ?>
		<p class="text-balance-ja"><?php echo esc_html( $pp_paragraph ); ?></p>
	<?php endforeach; ?>
</div>

<?php
pp_section_close();
