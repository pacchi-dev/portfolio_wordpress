<?php
/**
 * 実務経験セクション。
 *
 * トップページに一覧を出すため WP_Query でサブループを作る。
 * メインクエリ（この URL に対して WordPress が既に実行済みのクエリ）とは
 * 別の取得なので、必ず wp_reset_postdata() で後始末する。
 * これを忘れるとグローバルの $post が書き換わったままになり、
 * 以降のテンプレートが誤った投稿を表示する。案件で頻出の不具合。
 *
 * @package PacchiPortfolio
 */

defined( 'ABSPATH' ) || exit;

$pp_experience_query = new WP_Query(
	array(
		'post_type'              => 'experience',
		'post_status'            => 'publish',
		'posts_per_page'         => -1,
		'orderby'                => 'menu_order',
		'order'                  => 'ASC',
		// 不要なクエリを省いて負荷を下げる。件数が少なくても付ける癖をつける。
		'no_found_rows'          => true,
		'update_post_term_cache' => true,
	)
);

if ( ! $pp_experience_query->have_posts() ) {
	return;
}

pp_section_open(
	array(
		'id'      => 'experience',
		'eyebrow' => 'experience',
		'title'   => __( '実務経験', 'pacchi-portfolio' ),
		'lead'    => __( '守秘義務のため、企業名・クライアント名は記載せず、担当した内容と技術的な取り組みを中心に記載しています。', 'pacchi-portfolio' ),
		'surface' => true,
		'open'    => true,
	)
);
?>

<div class="exp-list">
	<?php
	while ( $pp_experience_query->have_posts() ) :
		$pp_experience_query->the_post();
		// 第 3 引数で子テンプレートへ値を渡せる（WordPress 5.5 以降）。
		get_template_part(
			'template-parts/card-experience',
			null,
			array( 'is_first' => 0 === $pp_experience_query->current_post )
		);
	endwhile;
	?>
</div>

<?php
pp_section_close();

// サブループの後始末。
wp_reset_postdata();
