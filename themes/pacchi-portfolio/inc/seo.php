<?php
/**
 * SEO / OGP メタタグの出力。
 *
 * Yoast SEO 等のプラグインを入れず、必要な分だけ自前で出力する。
 * このサイトは 1 ページ + CPT の小規模構成で、
 * SEO プラグインが持つ多機能（リダイレクト管理、パンくず、
 * スキーマ生成、サイトマップ）の大半を必要としないため。
 *
 * wp_head は <head> 内に出力を差し込むための action hook。
 * テンプレートに直接書かず hook 経由にすることで、
 * 出力順の制御や他プラグインからの差し替えが可能になる。
 *
 * @package PacchiPortfolio
 */

defined( 'ABSPATH' ) || exit;

/**
 * meta description と OGP を出力する。
 *
 * 優先度 5 を指定して、canonical（既定 10）より前に出す。
 */
add_action( 'wp_head', 'pp_output_meta_tags', 5 );
function pp_output_meta_tags(): void {

	$description = pp_meta_description();
	$title       = pp_meta_title();
	$url         = pp_current_url();
	$image       = pp_og_image_url();
	$type        = ( is_singular() && ! is_front_page() ) ? 'article' : 'website';

	if ( '' !== $description ) {
		printf(
			'<meta name="description" content="%s">' . "\n",
			esc_attr( $description )
		);
	}

	printf( '<meta property="og:type" content="%s">' . "\n", esc_attr( $type ) );
	printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );

	if ( '' !== $description ) {
		printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $description ) );
	}

	printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $url ) );
	printf(
		'<meta property="og:site_name" content="%s">' . "\n",
		esc_attr( wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES ) )
	);
	printf( '<meta property="og:locale" content="%s">' . "\n", esc_attr( (string) get_locale() ) );

	if ( '' !== $image ) {
		printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image ) );
		printf( '<meta name="twitter:card" content="%s">' . "\n", 'summary_large_image' );
	} else {
		printf( '<meta name="twitter:card" content="%s">' . "\n", 'summary' );
	}

	printf( '<meta name="twitter:title" content="%s">' . "\n", esc_attr( $title ) );

	if ( '' !== $description ) {
		printf( '<meta name="twitter:description" content="%s">' . "\n", esc_attr( $description ) );
	}
}

/**
 * ページに応じた description を返す。
 *
 * @return string
 */
function pp_meta_description(): string {
	if ( is_front_page() ) {
		$page_id = (int) get_option( 'page_on_front' );
		$lead    = $page_id > 0 && function_exists( 'get_field' )
			? (string) get_field( 'hero_lead', $page_id )
			: '';

		if ( '' !== $lead ) {
			return pp_trim_text( $lead, 120 );
		}

		return pp_trim_text( (string) get_bloginfo( 'description' ), 120 );
	}

	if ( is_singular() ) {
		$summary = (string) get_field( 'summary', get_the_ID() );

		if ( '' !== $summary ) {
			return pp_trim_text( $summary, 120 );
		}

		$excerpt = (string) get_the_excerpt();

		if ( '' !== $excerpt ) {
			return pp_trim_text( $excerpt, 120 );
		}
	}

	if ( is_tax( 'technology' ) ) {
		$term = get_queried_object();

		if ( $term instanceof WP_Term ) {
			return sprintf(
				/* translators: %s: 技術名 */
				__( '%s を使用した実務経験・制作実績の一覧。', 'pacchi-portfolio' ),
				$term->name
			);
		}
	}

	if ( is_post_type_archive( 'work' ) ) {
		return __( '実運用を想定して設計・実装した制作物の一覧。', 'pacchi-portfolio' );
	}

	return pp_trim_text( (string) get_bloginfo( 'description' ), 120 );
}

/**
 * OGP に使うタイトルを返す。
 *
 * @return string
 */
function pp_meta_title(): string {
	if ( is_front_page() ) {
		return wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES );
	}

	// wp_get_document_title() は title-tag の出力と同じ文字列を返す。
	return wp_specialchars_decode( wp_get_document_title(), ENT_QUOTES );
}

/**
 * 現在の URL を返す。
 *
 * $_SERVER['REQUEST_URI'] を直接使うと改ざん可能な値が混入するため、
 * WordPress が解決したパーマリンクを優先する。
 *
 * @return string
 */
function pp_current_url(): string {
	if ( is_front_page() ) {
		return home_url( '/' );
	}

	if ( is_singular() ) {
		return (string) get_permalink();
	}

	if ( is_tax() || is_category() || is_tag() ) {
		$term = get_queried_object();

		if ( $term instanceof WP_Term ) {
			$link = get_term_link( $term );

			if ( ! is_wp_error( $link ) ) {
				return (string) $link;
			}
		}
	}

	if ( is_post_type_archive() ) {
		return (string) get_post_type_archive_link( (string) get_post_type() );
	}

	return home_url( '/' );
}

/**
 * OGP 画像の URL を返す。
 *
 * 個別ページはアイキャッチ、それ以外はテーマ同梱の既定画像を使う。
 *
 * @return string
 */
function pp_og_image_url(): string {
	if ( is_singular() && has_post_thumbnail() ) {
		$url = get_the_post_thumbnail_url( get_the_ID(), 'full' );

		if ( is_string( $url ) ) {
			return $url;
		}
	}

	$default = PP_THEME_DIR . '/assets/img/og-default.png';

	if ( file_exists( $default ) ) {
		return PP_THEME_URI . '/assets/img/og-default.png';
	}

	return '';
}

/**
 * 文字数を丸める。
 *
 * mb_strimwidth ではなく mb_substr を使うのは、
 * 日本語の全角を 2 文字換算せず「見た目の文字数」で切るため。
 *
 * @param string $text   対象。
 * @param int    $length 最大文字数。
 * @return string
 */
function pp_trim_text( string $text, int $length ): string {
	$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $text ) ) ?? '' );

	if ( mb_strlen( $text ) <= $length ) {
		return $text;
	}

	return mb_substr( $text, 0, $length ) . '…';
}

/**
 * 1 ページ構成のため、不要なアーカイブを 404 にする。
 *
 * 日付アーカイブや投稿（post）のアーカイブは使っておらず、
 * 空のページがインデックスされると評価を下げるため塞ぐ。
 */
add_action( 'template_redirect', 'pp_disable_unused_archives' );
function pp_disable_unused_archives(): void {
	if ( is_date() || is_search() ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
}
