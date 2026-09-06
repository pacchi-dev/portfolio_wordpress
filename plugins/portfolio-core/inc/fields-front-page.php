<?php
/**
 * トップページ用の ACF フィールド。
 *
 * ACF 無料版には Options Page（サイト全体設定）が無いため、
 * 固定ページ（フロントページ）にフィールドを付けて代替する。
 * 無料版での定番の回避策で、実案件でも頻繁に使う。
 *
 * Repeater も Pro 機能のため、代わりに以下で構成している。
 *   - 件数が固定のもの（実績指標 3 件、稼働条件 4 項目）→ group フィールド
 *   - 件数が可変のもの（箇条書き）→ textarea に 1 行 1 項目
 *
 * @package PortfolioCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * トップページのフィールドグループを登録する。
 */
add_action( 'acf/init', 'pc_register_front_page_fields' );
function pc_register_front_page_fields(): void {

	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		array(
			'key'            => 'group_pc_front_page',
			'title'          => __( 'トップページの内容', 'portfolio-core' ),
			'menu_order'     => 0,
			'position'       => 'normal',
			'style'          => 'default',
			'active'         => true,
			'hide_on_screen' => array( 'the_content', 'comments', 'discussion', 'author', 'excerpt' ),
			/*
			 * location の page_type == front_page は
			 * 「設定 > 表示設定」でフロントページに指定された固定ページにだけ
			 * このフィールドを出す、という条件。
			 * 特定のページ ID を直接指定すると環境ごとに ID が変わって壊れる。
			 */
			'location'       => array(
				array(
					array(
						'param'    => 'page_type',
						'operator' => '==',
						'value'    => 'front_page',
					),
				),
			),
			'fields'         => array(

				// ---------------------------------------------------------------
				array(
					'key'   => 'field_pc_tab_hero',
					'label' => __( 'Hero', 'portfolio-core' ),
					'type'  => 'tab',
				),
				array(
					'key'           => 'field_pc_hero_role_1',
					'label'         => __( '肩書き（1行目）', 'portfolio-core' ),
					'name'          => 'hero_role_1',
					'type'          => 'text',
					'default_value' => 'Backend',
					'wrapper'       => array( 'width' => '50' ),
				),
				array(
					'key'           => 'field_pc_hero_role_2',
					'label'         => __( '肩書き（2行目）', 'portfolio-core' ),
					'name'          => 'hero_role_2',
					'type'          => 'text',
					'instructions'  => __( 'この行にアクセントカラーのグラデーションがかかります。', 'portfolio-core' ),
					'default_value' => 'Engineer',
					'wrapper'       => array( 'width' => '50' ),
				),
				array(
					'key'   => 'field_pc_hero_name',
					'label' => __( '氏名', 'portfolio-core' ),
					'name'  => 'hero_name',
					'type'  => 'text',
				),
				array(
					/*
					 * 固定ページのアイキャッチではなく専用フィールドにしている。
					 * アイキャッチは inc/seo.php の mp_og_image_url() が
					 * og:image に使うため、ここに顔写真を入れると
					 * SNS 共有時のカード画像が丸いアイコンに置き換わってしまう。
					 */
					'key'           => 'field_pc_hero_avatar',
					'label'         => __( 'プロフィール画像', 'portfolio-core' ),
					'name'          => 'hero_avatar',
					'type'          => 'image',
					'return_format' => 'id',
					'preview_size'  => 'thumbnail',
					'instructions'  => __( '氏名の左に円形で表示します。正方形の画像を推奨。未設定なら表示されません。', 'portfolio-core' ),
				),
				array(
					'key'          => 'field_pc_hero_stack',
					'label'        => __( '主要技術', 'portfolio-core' ),
					'name'         => 'hero_stack',
					'type'         => 'textarea',
					'instructions' => __( '1 行につき 1 つ。3〜4 つが目安。増やしすぎるとヒーローが窮屈になります。', 'portfolio-core' ),
					'rows'         => 4,
				),
				array(
					'key'          => 'field_pc_hero_lead',
					'label'        => __( 'リード文', 'portfolio-core' ),
					'name'         => 'hero_lead',
					'type'         => 'textarea',
					'instructions' => __( '数秒で専門領域が伝わる 1〜2 文。', 'portfolio-core' ),
					'rows'         => 3,
				),
				array(
					'key'          => 'field_pc_hero_highlights',
					'label'        => __( '実績指標', 'portfolio-core' ),
					'name'         => 'hero_highlights',
					'type'         => 'group',
					'instructions' => __( 'Hero 下部に 3 つ並べる指標。空欄の項目は表示されません。', 'portfolio-core' ),
					'layout'       => 'block',
					'sub_fields'   => array(
						pc_highlight_subfield( 1 ),
						pc_highlight_subfield( 2 ),
						pc_highlight_subfield( 3 ),
					),
				),

				// ---------------------------------------------------------------
				array(
					'key'   => 'field_pc_tab_about',
					'label' => __( 'About', 'portfolio-core' ),
					'type'  => 'tab',
				),
				array(
					'key'           => 'field_pc_about_title',
					'label'         => __( '見出し', 'portfolio-core' ),
					'name'          => 'about_title',
					'type'          => 'text',
					'default_value' => 'バックエンドを軸に、設計から運用まで',
				),
				array(
					'key'          => 'field_pc_about_body',
					'label'        => __( '本文', 'portfolio-core' ),
					'name'         => 'about_body',
					'type'         => 'textarea',
					'instructions' => __( '空行で区切ると段落に分かれます。', 'portfolio-core' ),
					'rows'         => 12,
				),

				// ---------------------------------------------------------------
				array(
					'key'   => 'field_pc_tab_services',
					'label' => __( 'What I Do', 'portfolio-core' ),
					'type'  => 'tab',
				),
				array(
					'key'           => 'field_pc_services_title',
					'label'         => __( '見出し', 'portfolio-core' ),
					'name'          => 'services_title',
					'type'          => 'text',
					'default_value' => '対応できる領域',
				),
				array(
					'key'   => 'field_pc_services_lead',
					'label' => __( 'リード文', 'portfolio-core' ),
					'name'  => 'services_lead',
					'type'  => 'textarea',
					'rows'  => 2,
				),
				pc_service_group( 1 ),
				pc_service_group( 2 ),
				pc_service_group( 3 ),
				pc_service_group( 4 ),

				// ---------------------------------------------------------------
				array(
					'key'   => 'field_pc_tab_ai',
					'label' => __( 'AI Development', 'portfolio-core' ),
					'type'  => 'tab',
				),
				array(
					'key'           => 'field_pc_ai_title',
					'label'         => __( '見出し', 'portfolio-core' ),
					'name'          => 'ai_title',
					'type'          => 'text',
					'default_value' => '生成AIを開発プロセスへ組み込む',
				),
				array(
					'key'   => 'field_pc_ai_lead',
					'label' => __( 'リード文', 'portfolio-core' ),
					'name'  => 'ai_lead',
					'type'  => 'textarea',
					'rows'  => 2,
				),
				array(
					'key'          => 'field_pc_ai_body',
					'label'        => __( '本文', 'portfolio-core' ),
					'name'         => 'ai_body',
					'type'         => 'textarea',
					'instructions' => __( '空行で区切ると段落に分かれます。', 'portfolio-core' ),
					'rows'         => 6,
				),
				array(
					'key'          => 'field_pc_ai_points',
					'label'        => __( '取り組み', 'portfolio-core' ),
					'name'         => 'ai_points',
					'type'         => 'textarea',
					'instructions' => __( '1 行につき 1 項目。', 'portfolio-core' ),
					'rows'         => 6,
				),
				array(
					'key'          => 'field_pc_ai_flow',
					'label'        => __( '開発フロー図', 'portfolio-core' ),
					'name'         => 'ai_flow',
					'type'         => 'textarea',
					'instructions' => __( '1 行につき 1 ステップ。ターミナル風のカードとして表示されます。', 'portfolio-core' ),
					'rows'         => 8,
				),

				// ---------------------------------------------------------------
				array(
					'key'   => 'field_pc_tab_work',
					'label' => __( 'Work With Me', 'portfolio-core' ),
					'type'  => 'tab',
				),
				array(
					'key'           => 'field_pc_work_title',
					'label'         => __( '見出し', 'portfolio-core' ),
					'name'          => 'work_title',
					'type'          => 'text',
					'default_value' => 'ご依頼いただけること',
				),
				array(
					'key'   => 'field_pc_work_lead',
					'label' => __( 'リード文', 'portfolio-core' ),
					'name'  => 'work_lead',
					'type'  => 'textarea',
					'rows'  => 2,
				),
				array(
					'key'          => 'field_pc_work_services',
					'label'        => __( '対応可能な業務', 'portfolio-core' ),
					'name'         => 'work_services',
					'type'         => 'textarea',
					'instructions' => __( '1 行につき 1 項目。', 'portfolio-core' ),
					'rows'         => 12,
				),
				array(
					'key'        => 'field_pc_work_conditions',
					'label'      => __( '稼働条件', 'portfolio-core' ),
					'name'       => 'work_conditions',
					'type'       => 'group',
					'layout'     => 'block',
					'sub_fields' => array(
						array(
							'key'           => 'field_pc_cond_remote',
							'label'         => __( 'Remote', 'portfolio-core' ),
							'name'          => 'remote',
							'type'          => 'text',
							'default_value' => 'フルリモート',
							'wrapper'       => array( 'width' => '50' ),
						),
						array(
							'key'           => 'field_pc_cond_availability',
							'label'         => __( 'Availability', 'portfolio-core' ),
							'name'          => 'availability',
							'type'          => 'text',
							'default_value' => '平日夜 / 土日',
							'wrapper'       => array( 'width' => '50' ),
						),
						array(
							'key'           => 'field_pc_cond_hours',
							'label'         => __( 'Hours', 'portfolio-core' ),
							'name'          => 'hours',
							'type'          => 'text',
							'default_value' => '週 10〜15 時間程度',
							'wrapper'       => array( 'width' => '50' ),
						),
						array(
							'key'           => 'field_pc_cond_contract',
							'label'         => __( 'Contract', 'portfolio-core' ),
							'name'          => 'contract',
							'type'          => 'text',
							'default_value' => '業務委託（準委任）',
							'wrapper'       => array( 'width' => '50' ),
						),
					),
				),
				array(
					'key'   => 'field_pc_work_note',
					'label' => __( '補足', 'portfolio-core' ),
					'name'  => 'work_note',
					'type'  => 'textarea',
					'rows'  => 3,
				),

				// ---------------------------------------------------------------
				array(
					'key'   => 'field_pc_tab_contact',
					'label' => __( 'Contact', 'portfolio-core' ),
					'type'  => 'tab',
				),
				array(
					'key'          => 'field_pc_contact_message',
					'label'        => '',
					'type'         => 'message',
					'message'      => __( '空欄にした項目は表示されません。掲載したいものだけ入力してください。', 'portfolio-core' ),
					'new_lines'    => 'wpautop',
				),
				array(
					'key'           => 'field_pc_contact_title',
					'label'         => __( '見出し', 'portfolio-core' ),
					'name'          => 'contact_title',
					'type'          => 'text',
					'default_value' => 'お問い合わせ',
				),
				array(
					'key'   => 'field_pc_contact_lead',
					'label' => __( 'リード文', 'portfolio-core' ),
					'name'  => 'contact_lead',
					'type'  => 'textarea',
					'rows'  => 2,
				),
				array(
					'key'   => 'field_pc_contact_email',
					'label' => __( 'メールアドレス', 'portfolio-core' ),
					'name'  => 'contact_email',
					'type'  => 'email',
				),
				array(
					'key'         => 'field_pc_contact_github',
					'label'       => __( 'GitHub URL', 'portfolio-core' ),
					'name'        => 'contact_github',
					'type'        => 'url',
					'placeholder' => 'https://github.com/username',
				),
				array(
					'key'         => 'field_pc_contact_crowdworks',
					'label'       => __( 'CrowdWorks URL', 'portfolio-core' ),
					'name'        => 'contact_crowdworks',
					'type'        => 'url',
				),
				array(
					'key'         => 'field_pc_contact_x',
					'label'       => __( 'X (Twitter) URL', 'portfolio-core' ),
					'name'        => 'contact_x',
					'type'        => 'url',
				),
			),
		)
	);
}

/**
 * 実績指標 1 件分のサブフィールドを組み立てる。
 *
 * Repeater が使えないため、固定数の group で代替している。
 *
 * @param int $index 1 始まりの番号。
 * @return array<string, mixed>
 */
function pc_highlight_subfield( int $index ): array {
	return array(
		'key'        => "field_pc_highlight_{$index}",
		'label'      => sprintf( /* translators: %d: 指標の番号 */ __( '指標 %d', 'portfolio-core' ), $index ),
		'name'       => "item_{$index}",
		'type'       => 'group',
		'layout'     => 'table',
		'sub_fields' => array(
			array(
				'key'   => "field_pc_highlight_{$index}_label",
				'label' => __( 'ラベル', 'portfolio-core' ),
				'name'  => 'label',
				'type'  => 'text',
			),
			array(
				'key'   => "field_pc_highlight_{$index}_value",
				'label' => __( '値', 'portfolio-core' ),
				'name'  => 'value',
				'type'  => 'text',
			),
		),
	);
}

/**
 * What I Do のカード 1 枚分のフィールドを組み立てる。
 *
 * @param int $index 1 始まりの番号。
 * @return array<string, mixed>
 */
function pc_service_group( int $index ): array {
	return array(
		'key'        => "field_pc_service_{$index}",
		'label'      => sprintf( /* translators: %d: カードの番号 */ __( 'カード %d', 'portfolio-core' ), $index ),
		'name'       => "service_{$index}",
		'type'       => 'group',
		'layout'     => 'block',
		'sub_fields' => array(
			array(
				'key'     => "field_pc_service_{$index}_title",
				'label'   => __( 'タイトル', 'portfolio-core' ),
				'name'    => 'title',
				'type'    => 'text',
				'wrapper' => array( 'width' => '60' ),
			),
			array(
				'key'           => "field_pc_service_{$index}_icon",
				'label'         => __( 'アイコン', 'portfolio-core' ),
				'name'          => 'icon',
				'type'          => 'select',
				'choices'       => array(
					'site'    => __( 'サイト制作', 'portfolio-core' ),
					'backend' => __( 'サーバー', 'portfolio-core' ),
					'cloud'   => __( 'クラウド', 'portfolio-core' ),
					'design'  => __( '設計', 'portfolio-core' ),
					'ai'      => __( 'AI', 'portfolio-core' ),
				),
				'default_value' => 'backend',
				'wrapper'       => array( 'width' => '40' ),
			),
			array(
				'key'   => "field_pc_service_{$index}_description",
				'label' => __( '説明', 'portfolio-core' ),
				'name'  => 'description',
				'type'  => 'textarea',
				'rows'  => 3,
			),
			array(
				'key'          => "field_pc_service_{$index}_tags",
				'label'        => __( 'タグ', 'portfolio-core' ),
				'name'         => 'tags',
				'type'         => 'textarea',
				'instructions' => __( '1 行につき 1 つ。', 'portfolio-core' ),
				'rows'         => 6,
			),
		),
	);
}
