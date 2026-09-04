<?php
/**
 * AI Development セクション。
 *
 * @package PacchiPortfolio
 */

defined( 'ABSPATH' ) || exit;

$mp_page_id = (int) get_option( 'page_on_front' );
$mp_body    = (string) get_field( 'ai_body', $mp_page_id );
$mp_points  = mp_lines_to_array( (string) get_field( 'ai_points', $mp_page_id ) );
$mp_flow    = mp_lines_to_array( (string) get_field( 'ai_flow', $mp_page_id ) );

$mp_paragraphs = array_values(
	array_filter(
		array_map( 'trim', preg_split( '/\R{2,}/u', $mp_body ) ?: array() ),
		static fn( string $p ): bool => '' !== $p
	)
);

if ( empty( $mp_paragraphs ) && empty( $mp_points ) ) {
	return;
}

mp_section_open(
	array(
		'id'      => 'ai',
		'eyebrow' => 'ai development',
		'title'   => (string) get_field( 'ai_title', $mp_page_id ),
		'lead'    => (string) get_field( 'ai_lead', $mp_page_id ),
		'open'    => false,
	)
);
?>

<div class="ai-layout">
	<div>
		<div class="prose">
			<?php foreach ( $mp_paragraphs as $mp_paragraph ) : ?>
				<p class="text-balance-ja"><?php echo esc_html( $mp_paragraph ); ?></p>
			<?php endforeach; ?>
		</div>

		<?php if ( ! empty( $mp_points ) ) : ?>
			<ul class="bullet-list bullet-list--accent">
				<?php foreach ( $mp_points as $mp_point ) : ?>
					<li class="text-balance-ja"><?php echo esc_html( $mp_point ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>

	<?php if ( ! empty( $mp_flow ) ) : ?>
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
					$mp_last = count( $mp_flow ) - 1;
					foreach ( $mp_flow as $mp_i => $mp_step ) :
						if ( 0 === $mp_i ) {
							$mp_marker = '$';
							$mp_class  = 'is-first';
						} elseif ( $mp_i === $mp_last ) {
							$mp_marker = '✓';
							$mp_class  = 'is-last';
						} else {
							$mp_marker = '→';
							$mp_class  = '';
						}
						?>
						<li class="<?php echo esc_attr( $mp_class ); ?>">
							<span class="terminal-card__marker" aria-hidden="true"><?php echo esc_html( $mp_marker ); ?></span>
							<span><?php echo esc_html( $mp_step ); ?></span>
						</li>
					<?php endforeach; ?>
				</ol>
			</div>
			<figcaption><?php esc_html_e( '// 開発フローへの組み込み例', 'pacchi-portfolio' ); ?></figcaption>
		</figure>
	<?php endif; ?>
</div>

<?php
mp_section_close();
