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
	 * Web フォント（セルフホスト）。
	 *
	 * Google Fonts の CDN から読み込んでいたが、外部ドメインへの接続と
	 * CSS 取得が描画を 816ms ブロックしていた（Lighthouse 実測）。
	 * woff2 を同一オリジンに置くことで外部リクエストを 0 にしている。
	 * 個人情報を外部へ送らずに済むため、プライバシー面でも望ましい。
	 */
	wp_enqueue_style(
		'mp-fonts',
		MP_THEME_URI . '/assets/css/fonts.css',
		array(),
		mp_asset_version( '/assets/css/fonts.css' )
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
	 *
	 * 第 5 引数に配列を渡せるのは WordPress 6.3 以降。
	 * in_footer だけでは Lighthouse に描画ブロックと判定されるため
	 * strategy => 'defer' を指定して HTML の解析を止めないようにする。
	 * boolean を渡す旧来の書き方でも動くが、defer は指定できない。
	 */
	wp_enqueue_script(
		'mp-nav',
		MP_THEME_URI . '/assets/js/nav.js',
		array(),
		mp_asset_version( '/assets/js/nav.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}

/**
 * フォントファイル自体を preload する。
 *
 * CSS の解析を待たずにダウンロードを開始でき、
 * テキストの再描画（FOUT）が起きる時間を短くできる。
 *
 * 登録はファイルのトップレベルで行う。wp_enqueue_scripts の
 * コールバック内から wp_head の優先度 1 に登録しても、
 * その時点では既に通過済みで実行されない。
 */
add_action( 'wp_head', 'mp_preload_fonts', 1 );

/**
 * フォントファイルを preload する。
 *
 * crossorigin の指定は必須。フォントは CORS モードで取得されるため、
 * 指定が無いと preload したものが再利用されず二重にダウンロードされる。
 * 実装ミスとして非常に多い箇所。
 */
function mp_preload_fonts(): void {
	$fonts = array(
		'/assets/fonts/inter-latin-var.woff2',
		'/assets/fonts/jetbrains-mono-latin-var.woff2',
	);

	foreach ( $fonts as $font ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( MP_THEME_URI . $font )
		);
	}
}
