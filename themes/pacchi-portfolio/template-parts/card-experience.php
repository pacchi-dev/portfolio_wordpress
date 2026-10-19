<?php
/**
 * 実務経験 1 件分のカード。
 *
 * get_template_part() から読み込まれる。ループ内で呼ばれる前提で、
 * 現在の投稿（グローバルの $post）を参照する。
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

$post_id = get_the_ID();

// 進行中の案件を最初から開いておく。$args は get_template_part の第 3 引数。
$is_open = ! empty( $args['is_first'] );

$period     = (string) get_field( 'period', $post_id );
$duration   = (string) get_field( 'duration', $post_id );
$is_current = (bool) get_field( 'is_current', $post_id );
$industry   = (string) get_field( 'industry', $post_id );
$role       = (string) get_field( 'role', $post_id );
$team_size  = (string) get_field( 'team_size', $post_id );
$summary    = (string) get_field( 'summary', $post_id );
$note       = (string) get_field( 'note', $post_id );

$responsibilities = pp_lines_to_array( (string) get_field( 'responsibilities', $post_id ) );
$showcase         = pp_lines_to_array( (string) get_field( 'showcase', $post_id ) );

// 使用技術はタクソノミーから取得する。
$technologies = get_the_terms( $post_id, 'technology' );
$tech_names   = ( is_array( $technologies ) ) ? wp_list_pluck( $technologies, 'name' ) : array();
?>

<details class="exp-card"<?php echo $is_open ? ' open' : ''; ?>>
	<span class="exp-card__dot" aria-hidden="true"></span>

	<summary class="exp-card__summary">
		<span class="exp-card__head">
			<span class="exp-card__meta">
				<span class="exp-card__period"><?php echo esc_html( $period ); ?></span>
				<?php if ( '' !== $duration ) : ?>
					<span class="exp-card__sep" aria-hidden="true">·</span>
					<span class="exp-card__duration"><?php echo esc_html( $duration ); ?></span>
				<?php endif; ?>
				<?php if ( $is_current ) : ?>
					<span class="badge badge--current"><?php esc_html_e( 'Current', 'pacchi-portfolio' ); ?></span>
				<?php endif; ?>
			</span>
			<?php pp_icon_chevron( 'exp-card__chevron' ); ?>
		</span>

		<span class="exp-card__title text-balance-ja"><?php the_title(); ?></span>

		<span class="exp-card__industry">
			<?php echo esc_html( $industry ); ?>
			<?php if ( '' !== $role ) : ?>
				<span class="exp-card__sep" aria-hidden="true">·</span>
				<?php echo esc_html( $role ); ?>
			<?php endif; ?>
		</span>
	</summary>

	<div class="exp-card__body">
		<?php if ( '' !== $team_size ) : ?>
			<dl class="exp-card__facts">
				<div>
					<dt><?php esc_html_e( 'Team', 'pacchi-portfolio' ); ?></dt>
					<dd><?php echo esc_html( $team_size ); ?></dd>
				</div>
			</dl>
		<?php endif; ?>

		<?php if ( '' !== $summary ) : ?>
			<p class="exp-card__summary-text text-balance-ja"><?php echo esc_html( $summary ); ?></p>
		<?php endif; ?>

		<?php if ( ! empty( $responsibilities ) ) : ?>
			<ul class="bullet-list">
				<?php foreach ( $responsibilities as $item ) : ?>
					<li class="text-balance-ja"><?php echo esc_html( $item ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php
		if ( ! empty( $showcase ) ) {
			pp_render_tags( $showcase, 'accent' );
		}
		?>

		<?php if ( ! empty( $tech_names ) ) : ?>
			<div class="exp-card__tech">
				<?php pp_render_tags( $tech_names, 'muted' ); ?>
			</div>
		<?php endif; ?>

		<?php if ( '' !== $note ) : ?>
			<p class="exp-card__note text-balance-ja">※ <?php echo esc_html( $note ); ?></p>
		<?php endif; ?>
	</div>
</details>
