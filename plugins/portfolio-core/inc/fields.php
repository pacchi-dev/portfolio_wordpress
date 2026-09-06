<?php
/**
 * ACF フィールドグループの定義。
 *
 * 管理画面でフィールドを作らず PHP で登録している。理由:
 *   - 定義がそのままバージョン管理される（Local JSON の同期漏れが起きない）
 *   - bin/setup.sh だけで環境を完全に再現できる
 *   - 管理画面から誤ってフィールドを壊せない
 *
 * トレードオフとして、管理画面の「フィールドグループ」一覧には
 * 読み取り専用で表示され、UI からの編集はできない。
 * サイト運用者がフィールドを増やす想定がある案件では、
 * 管理画面 + Local JSON の方が適している。
 *
 * @package PortfolioCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * フィールドグループを登録する。
 *
 * acf/init は ACF の初期化完了時に発火する action。
 * ACF が無効でも致命的エラーにならないよう、関数の存在を確認してから呼ぶ。
 * プラグイン依存を持つコードでは必須の防御。
 */
add_action( 'acf/init', 'pc_register_field_groups' );
function pc_register_field_groups(): void {

	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	pc_register_work_fields();
	pc_register_experience_fields();
	pc_register_technology_fields();
}

/**
 * 制作実績（work）のフィールド。
 *
 * 画像は ACF ではなく標準のアイキャッチを使う（docs/decisions.md D-8）。
 * 使用技術は ACF ではなくタクソノミーで持つ（同 D-7）。
 */
function pc_register_work_fields(): void {
	acf_add_local_field_group(
		array(
			'key'                   => 'group_pc_work',
			'title'                 => __( '制作実績の詳細', 'portfolio-core' ),
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'active'                => true,
			'hide_on_screen'        => array( 'comments', 'discussion', 'author' ),
			'location'              => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'work',
					),
				),
			),
			'fields'                => array(
				array(
					'key'          => 'field_pc_work_summary',
					'label'        => __( '概要', 'portfolio-core' ),
					'name'         => 'summary',
					'type'         => 'textarea',
					'instructions' => __( '一覧カードとページ冒頭に表示されます。2〜3 行程度。', 'portfolio-core' ),
					'rows'         => 3,
					'required'     => 1,
				),
				array(
					'key'          => 'field_pc_work_responsibilities',
					'label'        => __( '担当内容', 'portfolio-core' ),
					'name'         => 'responsibilities',
					'type'         => 'textarea',
					'instructions' => __( '1 行につき 1 項目。箇条書きとして表示されます。', 'portfolio-core' ),
					'rows'         => 6,
				),
				array(
					'key'          => 'field_pc_work_period',
					'label'        => __( '開発期間', 'portfolio-core' ),
					'name'         => 'period',
					'type'         => 'text',
					'instructions' => __( '例: 2026.04 - 2026.06', 'portfolio-core' ),
					'placeholder'  => '2026.04 - 2026.06',
				),
				array(
					'key'           => 'field_pc_work_status',
					'label'         => __( 'ステータス', 'portfolio-core' ),
					'name'          => 'status',
					'type'          => 'select',
					'choices'       => array(
						'released'    => __( '公開中', 'portfolio-core' ),
						'development' => __( '開発中', 'portfolio-core' ),
						'archived'    => __( '公開終了', 'portfolio-core' ),
					),
					'default_value' => 'released',
					'return_format' => 'value',
				),
				array(
					'key'          => 'field_pc_work_github_url',
					'label'        => __( 'GitHub URL', 'portfolio-core' ),
					'name'         => 'github_url',
					'type'         => 'url',
					'instructions' => __( '未入力の場合はボタンを表示しません。', 'portfolio-core' ),
				),
				array(
					'key'          => 'field_pc_work_site_url',
					'label'        => __( 'サイト URL', 'portfolio-core' ),
					'name'         => 'site_url',
					'type'         => 'url',
					'instructions' => __( '公開中のサイトがあれば入力します。', 'portfolio-core' ),
				),
				array(
					'key'          => 'field_pc_work_architecture',
					'label'        => __( 'アーキテクチャ図', 'portfolio-core' ),
					'name'         => 'architecture',
					'type'         => 'textarea',
					'instructions' => __(
						'1 行の簡易記法で入力すると SVG の構成図として描画されます。<br>'
						. '例: <code>Client -&gt; Load Balancer -&gt; *Go API (Echo)* -&gt; PostgreSQL, Redis</code><br>'
						. '<code>-&gt;</code> で列を区切り、<code>,</code> で同じ列に並べ、<code>*</code> で囲むと強調表示になります。<br>'
						. '記法に合わない入力（改行を含む図など）は、これまでどおり等幅フォントでそのまま表示されます。',
						'portfolio-core'
					),
					'rows'         => 4,
				),
			),
		)
	);
}

/**
 * 実務経験（experience）のフィールド。
 */
function pc_register_experience_fields(): void {
	acf_add_local_field_group(
		array(
			'key'                   => 'group_pc_experience',
			'title'                 => __( '実務経験の詳細', 'portfolio-core' ),
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'active'                => true,
			'hide_on_screen'        => array( 'comments', 'discussion', 'author', 'excerpt' ),
			'location'              => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'experience',
					),
				),
			),
			'fields'                => array(
				array(
					'key'          => 'field_pc_exp_period',
					'label'        => __( '期間', 'portfolio-core' ),
					'name'         => 'period',
					'type'         => 'text',
					'instructions' => __( '例: 2023.04 - 2026.03 / 進行中は「2026.04 - 現在」', 'portfolio-core' ),
					'required'     => 1,
				),
				array(
					'key'          => 'field_pc_exp_duration',
					'label'        => __( '期間の長さ', 'portfolio-core' ),
					'name'         => 'duration',
					'type'         => 'text',
					'instructions' => __( '例: 3年 / 4ヶ月 / 進行中', 'portfolio-core' ),
				),
				array(
					'key'          => 'field_pc_exp_is_current',
					'label'        => __( '進行中', 'portfolio-core' ),
					'name'         => 'is_current',
					'type'         => 'true_false',
					'instructions' => __( 'オンにすると CURRENT バッジを表示します。', 'portfolio-core' ),
					'ui'           => 1,
				),
				array(
					'key'          => 'field_pc_exp_industry',
					'label'        => __( '領域・業界', 'portfolio-core' ),
					'name'         => 'industry',
					'type'         => 'text',
					'instructions' => __( '守秘義務のため企業名は書かず、業界レベルまで一般化すること。例: 大手通信キャリア向け / アドテク', 'portfolio-core' ),
					'required'     => 1,
				),
				array(
					'key'      => 'field_pc_exp_role',
					'label'    => __( '役割', 'portfolio-core' ),
					'name'     => 'role',
					'type'     => 'text',
					'required' => 1,
				),
				array(
					'key'          => 'field_pc_exp_team_size',
					'label'        => __( 'チーム規模', 'portfolio-core' ),
					'name'         => 'team_size',
					'type'         => 'text',
					'instructions' => __( '例: 13〜22名（全体 約25〜30名）', 'portfolio-core' ),
				),
				array(
					'key'      => 'field_pc_exp_summary',
					'label'    => __( '概要', 'portfolio-core' ),
					'name'     => 'summary',
					'type'     => 'textarea',
					'rows'     => 3,
					'required' => 1,
				),
				array(
					'key'          => 'field_pc_exp_responsibilities',
					'label'        => __( '担当内容', 'portfolio-core' ),
					'name'         => 'responsibilities',
					'type'         => 'textarea',
					'instructions' => __( '1 行につき 1 項目。', 'portfolio-core' ),
					'rows'         => 8,
				),
				array(
					'key'          => 'field_pc_exp_showcase',
					'label'        => __( 'アピールしたい能力', 'portfolio-core' ),
					'name'         => 'showcase',
					'type'         => 'textarea',
					'instructions' => __( '1 行につき 1 項目。強調タグとして表示されます。例: Go 実務3年', 'portfolio-core' ),
					'rows'         => 6,
				),
				array(
					'key'          => 'field_pc_exp_note',
					'label'        => __( '注記', 'portfolio-core' ),
					'name'         => 'note',
					'type'         => 'textarea',
					'instructions' => __( '誤解を避けるための補足。入力すると末尾に小さく表示されます。', 'portfolio-core' ),
					'rows'         => 3,
				),
			),
		)
	);
}

/**
 * 使用技術（technology ターム）のフィールド。
 *
 * タクソノミーのタームにもフィールドを付けられる（ターム メタ）。
 * これにより「技術ごとの習熟度・カテゴリ」を管理画面から設定でき、
 * Skills セクションを実績データと同じソースから描画できる。
 */
function pc_register_technology_fields(): void {
	acf_add_local_field_group(
		array(
			'key'      => 'group_pc_technology',
			'title'    => __( '技術の分類', 'portfolio-core' ),
			'active'   => true,
			'location' => array(
				array(
					array(
						'param'    => 'taxonomy',
						'operator' => '==',
						'value'    => 'technology',
					),
				),
			),
			'fields'   => array(
				array(
					'key'           => 'field_pc_tech_level',
					'label'         => __( '習熟度', 'portfolio-core' ),
					'name'          => 'level',
					'type'          => 'select',
					'instructions'  => __( '「★★★★★」形式の自己評価は使わず、実務での関わり方で分類する。', 'portfolio-core' ),
					'choices'       => array(
						'main'        => __( 'Main — 実務で継続的に使用し、設計から対応できる', 'portfolio-core' ),
						'experienced' => __( 'Experienced — 実務での開発経験がある', 'portfolio-core' ),
						'familiar'    => __( 'Familiar — 業務で触れた経験がある', 'portfolio-core' ),
					),
					'default_value' => 'experienced',
					'return_format' => 'value',
				),
				array(
					'key'           => 'field_pc_tech_category',
					'label'         => __( 'カテゴリ', 'portfolio-core' ),
					'name'          => 'category',
					'type'          => 'select',
					'instructions'  => __( 'Skills セクションでの表示グループ。', 'portfolio-core' ),
					'choices'       => array(
						'backend'  => __( 'Backend', 'portfolio-core' ),
						'database' => __( 'Database / Cache', 'portfolio-core' ),
						'cloud'    => __( 'Cloud / Infrastructure', 'portfolio-core' ),
						'devops'   => __( 'Development / Operation', 'portfolio-core' ),
						'frontend' => __( 'Frontend', 'portfolio-core' ),
						'ai'       => __( 'AI', 'portfolio-core' ),
					),
					'default_value' => 'backend',
					'return_format' => 'value',
				),
				array(
					'key'           => 'field_pc_tech_order',
					'label'         => __( '表示順', 'portfolio-core' ),
					'name'          => 'display_order',
					'type'          => 'number',
					'instructions'  => __( '小さいほど先に表示されます。', 'portfolio-core' ),
					'default_value' => 100,
					'min'           => 0,
				),
			),
		)
	);
}
