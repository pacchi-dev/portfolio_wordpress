<?php
/**
 * GitHub Activity セクション。
 *
 * 外部 API の結果を表示する際は、必ず以下を守る。
 *   1. is_wp_error() で失敗を判定し、画面を壊さない
 *   2. API 由来の文字列は必ずエスケープする（外部入力として扱う）
 *   3. 取得件数が 0 でも成立する表示にする
 *
 * @package PacchiPortfolio
 */

defined( 'ABSPATH' ) || exit;

// プラグインが無効なら何も出さない（テーマがプラグインに依存しすぎない）。
if ( ! function_exists( 'pc_github_get_repositories' ) ) {
	return;
}

$mp_repos = pc_github_get_repositories();
$mp_error = is_wp_error( $mp_repos ) ? $mp_repos : null;
$mp_items = $mp_error ? array() : array_slice( (array) $mp_repos, 0, 6 );
$mp_langs = $mp_error ? array() : pc_github_language_stats( (array) $mp_repos );
$mp_user  = pc_github_username();

mp_section_open(
	array(
		'id'      => 'github',
		'eyebrow' => 'github activity',
		'title'   => __( '公開リポジトリ', 'pacchi-portfolio' ),
		'lead'    => __( 'GitHub API から取得した公開リポジトリです。1 時間キャッシュし、取得に失敗した場合は直前の内容を表示します。', 'pacchi-portfolio' ),
		// 外部 API 連携が実際に動いていることを示すセクションなので開いておく。
		'open'    => true,
	)
);
?>

<?php if ( $mp_error ) : ?>
	<p class="notice-inline">
		<?php esc_html_e( 'GitHub の情報を取得できませんでした。時間をおいて再度お試しください。', 'pacchi-portfolio' ); ?>
	</p>

	<?php if ( current_user_can( 'manage_options' ) ) : ?>
		<?php /* 管理者にだけ原因を見せる。一般の閲覧者に内部エラーを出さない。 */ ?>
		<p class="notice-inline notice-inline--debug">
			<?php echo esc_html( $mp_error->get_error_message() ); ?>
		</p>
	<?php endif; ?>

<?php elseif ( empty( $mp_items ) ) : ?>
	<p class="notice-inline"><?php esc_html_e( '公開リポジトリはまだありません。', 'pacchi-portfolio' ); ?></p>

<?php else : ?>

	<?php if ( ! empty( $mp_langs ) ) : ?>
		<div class="gh-langs">
			<span class="gh-langs__label"><?php esc_html_e( '使用言語', 'pacchi-portfolio' ); ?></span>
			<ul class="tag-list">
				<?php foreach ( array_slice( $mp_langs, 0, 8, true ) as $mp_lang => $mp_count ) : ?>
					<li>
						<span class="tag tag--muted">
							<?php echo esc_html( $mp_lang ); ?>
							<span class="gh-langs__count"><?php echo esc_html( (string) $mp_count ); ?></span>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<ul class="gh-list">
		<?php foreach ( $mp_items as $mp_repo ) : ?>
			<li>
				<a class="gh-card" href="<?php echo esc_url( (string) $mp_repo['url'] ); ?>" target="_blank" rel="noreferrer noopener">
					<span class="gh-card__head">
						<span class="gh-card__name"><?php echo esc_html( (string) $mp_repo['name'] ); ?></span>
						<?php if ( (int) $mp_repo['stars'] > 0 ) : ?>
							<span class="gh-card__stars">★ <?php echo esc_html( (string) (int) $mp_repo['stars'] ); ?></span>
						<?php endif; ?>
					</span>

					<?php if ( '' !== (string) $mp_repo['description'] ) : ?>
						<span class="gh-card__desc text-balance-ja"><?php echo esc_html( (string) $mp_repo['description'] ); ?></span>
					<?php endif; ?>

					<span class="gh-card__meta">
						<?php if ( '' !== (string) $mp_repo['language'] ) : ?>
							<span class="gh-card__lang"><?php echo esc_html( (string) $mp_repo['language'] ); ?></span>
						<?php endif; ?>
						<?php if ( '' !== (string) $mp_repo['updated_at'] ) : ?>
							<span class="gh-card__date">
								<?php
								// API の日時は UTC の ISO8601。サイトのタイムゾーンへ変換して表示する。
								echo esc_html(
									wp_date(
										(string) get_option( 'date_format' ),
										(int) strtotime( (string) $mp_repo['updated_at'] )
									)
								);
								?>
							</span>
						<?php endif; ?>
					</span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php if ( '' !== $mp_user ) : ?>
		<p class="gh-more">
			<a class="button button--secondary" href="<?php echo esc_url( 'https://github.com/' . $mp_user ); ?>" target="_blank" rel="noreferrer noopener">
				<?php esc_html_e( 'View on GitHub', 'pacchi-portfolio' ); ?>
			</a>
		</p>
	<?php endif; ?>

<?php endif; ?>

<?php
mp_section_close();
