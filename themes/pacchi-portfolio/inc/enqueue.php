<?php
/**
 * CSS / JS の読み込み。
 *
 * WordPress では <link> や <script> をテンプレートに直書きせず、
 * wp_enqueue_style() / wp_enqueue_script() で「登録」する。
 * 理由は 3 つ:
 *   1. 依存関係を宣言でき、読み込み順を WordPress が解決する
 *   2. 同じハンドル名なら重複読み込みされない（プラグインとの衝突を防ぐ）
 *   3. 他のプラグインやテーマから wp_dequeue_style() で差し替えできる
 * 実案件でテンプレートへの直書きはレビューで指摘される。
 *
 * @package PacchiPortfolio
 */

defined( 'ABSPATH' ) || exit;

/**
 * アセットのバージョン文字列を返す。
 *
 * ファイルの更新時刻を使うことで、変更時に必ずキャッシュが破棄される。
 * 固定のバージョン番号だと「更新したのに反映されない」という
 * WordPress 案件で頻出のトラブルを招く。
 *
 * @param string $relative_path テーマディレクトリからの相対パス。
 * @return string
 */
function mp_asset_version( string $relative_path ): string {
	$file = MP_THEME_DIR . $relative_path;

	if ( file_exists( $file ) ) {
		return (string) filemtime( $file );
	}

	return MP_THEME_VERSION;
}

/**
 * フロント側のアセットを読み込む。
 * wp_enqueue_scripts はフロント表示用の hook（名前に反して CSS もここで扱う）。
 */
add_action( 'wp_enqueue_scripts', 'mp_enqueue_assets' );
function mp_enqueue_assets(): void {

	/*
	 * Web フォント。
	 * 第 4 引数のバージョンに null を渡すと ?ver= が付かない。
	 * 外部 CDN の URL にクエリを足すとキャッシュ効率が落ちるため。
	 *
	 * TODO(Phase 8): パフォーマンス改善としてセルフホストへ切り替える。
	 *                外部リクエストを 0 にでき、プライバシー面でも望ましい。
	 */
	wp_enqueue_style(
		'mp-fonts',
		'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap',
		array(),
		null
	);

	// メインスタイル。フォントに依存させ、必ず後に読み込ませる。
	wp_enqueue_style(
		'mp-main',
		MP_THEME_URI . '/assets/css/main.css',
		array( 'mp-fonts' ),
		mp_asset_version( '/assets/css/main.css' )
	);

	/*
	 * ナビゲーション用の JS。
	 * 第 5 引数 true で </body> 直前に出力し、描画をブロックしない。
	 */
	wp_enqueue_script(
		'mp-nav',
		MP_THEME_URI . '/assets/js/nav.js',
		array(),
		mp_asset_version( '/assets/js/nav.js' ),
		true
	);
}

/**
 * 外部フォントへの接続を先行させる（filter hook の例）。
 *
 * wp_resource_hints は WordPress が <head> に出力する
 * dns-prefetch / preconnect のリストをフィルタするための hook。
 * add_filter の第 3 引数は優先度、第 4 引数は受け取る引数の数。
 * 引数が 2 つ以上ある filter では第 4 引数の指定を忘れやすい。
 *
 * @param string[] $urls          リソースヒントの URL 配列。
 * @param string   $relation_type 関係の種類。
 * @return string[]
 */
add_filter( 'wp_resource_hints', 'mp_resource_hints', 10, 2 );
function mp_resource_hints( array $urls, string $relation_type ): array {
	if ( 'preconnect' === $relation_type ) {
		$urls[] = array(
			'href' => 'https://fonts.gstatic.com',
			'crossorigin',
		);
	}

	return $urls;
}
