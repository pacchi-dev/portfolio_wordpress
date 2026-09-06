<?php
/**
 * AI Development セクション。
 *
 * @package PacchiPortfolio
 */

defined( 'ABSPATH' ) || exit;

$pp_page_id = (int) get_option( 'page_on_front' );
$pp_body    = (string) get_field( 'ai_body', $pp_page_id );
$pp_points  = pp_lines_to_array( (string) get_field( 'ai_points', $pp_page_id ) );
$pp_flow    = pp_lines_to_array( (string) get_field( 'ai_flow', $pp_page_id ) );

$pp_paragraphs = array_values(
	array_filter(
		array_map( 'trim', preg_split( '/\R{2,}/u', $pp_body ) ?: array() ),
		static fn( string $p ): bool => '' !== $p
	)
);

if ( empty( $pp_paragraphs ) && empty( $pp_points ) ) {
	return;
}

pp_section_open(
	array(
		'id'      => 'ai',
		'eyebrow' => 'ai development',
		'title'   => (string) get_field( 'ai_title', $pp_page_id ),
		'lead'    => (string) get_field( 'ai_lead', $pp_page_id ),
		'open'    => false,
	)
);
?>

<div class="ai-layout">
	<div>
		<div class="prose">
			<?php foreach ( $pp_paragraphs as $pp_paragraph ) : ?>
				<p class="text-balance-ja"><?php echo esc_html( $pp_paragraph ); ?></p>
			<?php endforeach; ?>
		</div>

		<?php if ( ! empty( $pp_points ) ) : ?>
			<ul class="bullet-list bullet-list--accent">
				<?php foreach ( $pp_points as $pp_point ) : ?>
					<li class="text-balance-ja"><?php echo esc_html( $pp_point ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>

	<?php if ( ! empty( $pp_flow ) ) : ?>
		<figure class="ai-flow">
			<div class="terminal-card glow-card">
				<div class="terminal-card__bar">
					<span class="terminal-card__dot"></span>
					<span class="terminal-card__dot"></span>
					<span class="terminal-card__dot"></span>
					<span class="terminal-card__name">ai-workflow</span>
				</div>
				<ol class="terminal-card__body" aria-label="<?php esc_attr_e( 'AI を組み込んだ開発フロー', 'pacchi-portfolio' ); ?>">
					<?php
					$pp_last = count( $pp_flow ) - 1;
					foreach ( $pp_flow as $pp_i => $pp_step ) :
						if ( 0 === $pp_i ) {
							$pp_marker = '$';
							$pp_class  = 'is-first';
						} elseif ( $pp_i === $pp_last ) {
							$pp_marker = '✓';
							$pp_class  = 'is-last';
						} else {
							$pp_marker = '→';
							$pp_class  = '';
						}
						?>
						<li class="<?php echo esc_attr( $pp_class ); ?>">
							<span class="terminal-card__marker" aria-hidden="true"><?php echo esc_html( $pp_marker ); ?></span>
							<span><?php echo esc_html( $pp_step ); ?></span>
						</li>
					<?php endforeach; ?>
				</ol>
			</div>
			<figcaption><?php esc_html_e( '// 開発フローへの組み込み例', 'pacchi-portfolio' ); ?></figcaption>
		</figure>
	<?php endif; ?>
</div>

<?php
pp_section_close();
