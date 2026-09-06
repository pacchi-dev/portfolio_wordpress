# WordPress版ポートフォリオ 設計

## 1. 移植元（Next.js 版）の分析

### 技術構成
Next.js 16 (App Router) / TypeScript strict / Tailwind CSS v4 / 依存ライブラリ追加ゼロ。
全ページ静的生成。クライアント JS は `NavBehavior.tsx` 1 つのみ
（アンカー移動時のセクション自動展開＋モバイルメニューを閉じる処理）。

Lighthouse: Desktop 100/100/100/100, Mobile 95/100/100/100

### 再利用できるもの / 書き換えが必要なもの

| 移植元の要素 | 判断 |
|---|---|
| コンテンツが `src/data/*.ts` に完全分離（9 ファイル・型定義付き） | **そのまま活用**。CPT / ACF の設計図として使える |
| コンポーネントが Section / パーツ単位で分割（13 個） | **1:1 で `template-parts/*.php` に対応** |
| 開閉 UI が `<details>` / `<summary>` のネイティブ実装 | **HTML ごと移植可能**（React 非依存） |
| デザイントークンが `globals.css` の `@theme`（CSS 変数） | **ほぼコピーで移植可能** |
| Tailwind ユーティリティクラス | **要書き換え**。素の CSS へ（最大の作業量） |
| OGP 画像 / favicon を `next/og` で動的生成 | 静的 PNG 書き出し、または PHP 側で対応 |
| sitemap / robots / メタデータ（Next の機能） | WP 標準の sitemap + `wp_head` で代替 |

### 移植対象のコンテンツ（= 管理画面化の対象）

Hero（肩書き・実績 3 指標・受付ステータス）/ About 4 段落 / What I Do 4 カード /
Skills 6 カテゴリ × 3 段階 / 実務経験 4 ケース / AI Development /
Work With Me（業務 11 件 + 稼働条件）/ Contact

---

## 2. サイト構成

1 ページ縦スクロール構成は維持しつつ、CPT 化する部分に個別ページを追加する。

```
/                     front-page.php          現在と同じ 1 ページ構成（各セクションを WP_Query で描画）
/works/               archive-work.php        制作実績一覧（CPT）
/works/{slug}/        single-work.php         実績詳細
/experience/{slug}/   single-experience.php   実務経験の詳細（任意・後回し可）
                      404.php
```

検索機能は不要のため無効化する。Contact は独立ページにせず、フロントページ内のセクションに置く。

### 現行データ → WordPress 上の管理場所

| 移植元ファイル | WordPress 上の管理場所 |
|---|---|
| `profile.ts`（Hero・About） | フロントページ（固定ページ）の ACF フィールド |
| `experience.ts` | **CPT `experience`** + ACF |
| `projects.ts` | **CPT `work`** + ACF |
| `skills.ts` | **カスタムタクソノミー `technology`** + タームメタ |
| `services.ts` / `aiDevelopment.ts` / `workWithMe.ts` | フロントページの ACF フィールド群 |
| `contact.ts` | ACF（連絡先）+ Customizer（受付中 / 停止中トグル） |
| `site.ts`（SEO / OGP） | `wp_head` 出力 + ACF |

---

## 3. テーマ構成

**クラシックテーマをフルスクラッチで作る**（ブロックテーマ / FSE ではない）。

理由: 国内の WordPress 案件（軽微修正・既存テーマ改修・functions.php 修正）は
圧倒的にクラシックテーマが対象であり、テンプレート階層・The Loop・hooks の
理解を示すのに最適なため。既製テーマの子テーマ改修は「テーマ修正案件」の
練習にはなるが、制作力の証明としては弱いので採用しない。

```
themes/pacchi-portfolio/
├── style.css                 # テーマ情報ヘッダ（WP の必須ファイル）
├── functions.php             # inc/ を読み込むだけの薄いエントリ
├── inc/
│   ├── setup.php             # theme_support, メニュー登録
│   ├── enqueue.php           # CSS / JS / フォント読み込み
│   ├── template-tags.php     # エスケープ済み出力ヘルパ
│   └── security.php          # バージョン非表示等のハードニング
├── index.php                 # 階層の最終フォールバック（必須）
├── front-page.php
├── header.php / footer.php
├── archive-work.php / single-work.php
├── single-experience.php
├── 404.php
├── template-parts/           # 移植元の React コンポーネントと 1:1 対応
│   ├── section-hero.php / section-about.php / section-services.php
│   ├── section-skills.php / section-experience.php / section-ai.php
│   ├── section-work-with-me.php / section-contact.php
│   ├── card-experience.php / card-work.php / code-card.php
├── assets/
│   ├── css/main.css          # トークン + セクション別 CSS
│   └── js/nav.js             # NavBehavior を vanilla JS へ移植
└── screenshot.png
```

### プラグインへの分離

**CPT・タクソノミー・GitHub API・お問い合わせ処理はテーマに入れず、自作プラグインに置く。**

「テーマを変えてもコンテンツと機能が生き残る」という WordPress の責務分離の原則。
実案件でも評価されるポイント。

```
plugins/portfolio-core/
├── portfolio-core.php        # プラグインヘッダ + ローダ
├── inc/
│   ├── post-types.php        # CPT / タクソノミー登録
│   ├── github-client.php     # GitHub API（wp_remote_get + Transients + WP_Error）
│   ├── contact-form.php      # 自作フォーム処理（nonce / sanitize / wp_mail）
│   └── rest.php              # 独自 REST エンドポイント
```

---

## 4. 使用プラグイン

**最小限の 2 つ + 開発用 1 つ。** ページビルダー・SEO プラグイン・既製テーマは使わない。

| プラグイン | 用途 | 採用理由 |
|---|---|---|
| **ACF（無料版）** | カスタムフィールド | 案件での圧倒的シェア。フィールド UI を自作するコストとの比較で採用 |
| **Query Monitor**（開発時のみ） | クエリ・hooks・HTTP 通信の可視化 | 「不具合調査」「パフォーマンス改善」案件の実練習ツール |
| Contact Form 7 | — | **採用しない**。README に「実案件では CF7 が標準的だが、今回は nonce / sanitize / wp_mail を自分で書くことを優先して自作した」と使い分けを記載する |

### ACF に関する補足（README 記載事項）

2024 年に ACF の wordpress.org 配布枠が「Secure Custom Fields」にフォークされた経緯があり、
現在は入手元が 2 系統ある。README でこの状況に触れる。

### ACF 無料版の制約を前提にした設計

Repeater・Options Page は有料（Pro）機能。そのため以下の回避策を採る。
**この「無料版でどう設計するか」自体が実案件スキル**なので README の解説対象にする。

- サイト全体設定 → Options Page の代わりに**フロントページ固定ページにフィールドを付ける**
- 箇条書き（担当内容など）→ Repeater の代わりに**改行区切り textarea**

---

## 5. Custom Post Type / ACF 設計

### CPT `work`（制作実績）

`public: true`, `has_archive: true`, `show_in_rest: true`（Gutenberg / REST 対応）

| フィールド | タイプ |
|---|---|
| 概要 | ACF textarea |
| 担当内容 | ACF textarea（改行区切り） |
| GitHub URL / サイト URL | ACF url |
| 開発期間 | ACF text（例 `2026.04 - 2026.06`） |
| ステータス | ACF select（公開中 / 開発中） |
| 画像 | **標準のアイキャッチ**（ACF にしない。理由も README に記載） |
| 使用技術 | **タクソノミー `technology`**（ACF フィールドにしない） |

### CPT `experience`（実務経験）

`has_archive: false`（フロントページのループで表示）

期間 / 役割 / チーム規模 / 業界 / 概要 / 担当内容 / showcase タグ / 注記 /
進行中フラグ（true で CURRENT バッジを表示）

### タクソノミー `technology`（work / experience 共通）

タームメタ（ACF のターム用フィールド）で以下を持たせる。

- `level`: main / experienced / familiar
- `category`: Backend / Cloud / Database / Frontend / DevOps / AI

**Skills セクションはこのタクソノミーから描画する。**
実績に技術を登録すれば技術スタック一覧と連動する単一ソース設計。

### The Loop / WP_Query の使い分け（学習ポイント）

| 場所 | 使うもの |
|---|---|
| `archive-work.php` | **メインループ**。`pre_get_posts` フィルタで並び順を制御（hooks の実練習） |
| `front-page.php` の各セクション | **`WP_Query` のサブループ**。`posts_per_page`, `meta_query` で進行中を先頭に等 |
| `single-work.php` | `the_post()` + `get_field()` + エスケープ出力 |

---

## 6. 外部 API 連携（GitHub API）

`portfolio-core` プラグイン内に実装する。

```
GitHubClient
├── wp_remote_get() で api.github.com/users/{user}/repos を取得
├── 失敗時: WP_Error を返す
│    └── テンプレート側は「取得できませんでした」表示。
│        古いキャッシュがあればそれを表示
├── キャッシュ: Transients API（set_transient, 1 時間）
│    └── 期限切れ時も stale キャッシュを別キーで保持し、API 障害時のフォールバックに使う
└── 表示: フロントページに「GitHub Activity」セクションを新設
     リポジトリ名 / 説明 / 主要言語 / 最終更新日 / スター数
```

### 独自 REST エンドポイント

`GET /wp-json/portfolio/v1/github` を `register_rest_route` で公開し、
`assets/js` から fetch する更新ボタン（nonce 検証付き）を管理バー表示ユーザー向けに用意する。

→ **REST API・nonce・`current_user_can` の 3 点を 1 機能で学べる。**

### レート制限

未認証は 60 req/h。キャッシュで実質問題にならないが、
README にトークン認証への切り替え手順を記載する。

---

## 7. 開発環境（Docker）

```yaml
services:
  wordpress:   # wordpress:6.x-php8.3-apache  → localhost:8080
    volumes:
      - ./themes/pacchi-portfolio:/var/www/html/wp-content/themes/pacchi-portfolio
      - ./plugins/portfolio-core:/var/www/html/wp-content/plugins/portfolio-core
  db:          # mysql:8.0
  wpcli:       # wordpress:cli  → セットアップ自動化
  mailpit:     # 問い合わせフォームのメール確認用 → localhost:8025
```

ポートは 8080（Next.js 版の 3000 と共存可能）。

### セットアップスクリプト `bin/setup.sh`

1. `wp core install`
2. テーマ / プラグイン有効化
3. ACF フィールド登録（Local JSON 同期）
4. **移植元の `data/*.ts` から変換したシードデータを `wp eval-file` で投入**
   → コンテンツ移行の実練習になる

### Git 管理

WordPress 本体・uploads・DB は `.gitignore`。
ACF のフィールド定義は Local JSON 機能でテーマ内に自動保存し Git 管理する
（実案件の定番手法）。

---

## 8. 実装フェーズ

各フェーズの冒頭で「何を・なぜ WP ではこうするか・実案件での使われ方」を説明してから実装する。

| Phase | 内容 | 学ぶ WordPress 固有概念 |
|---|---|---|
| 0 | Docker 環境 + WP-CLI で自動セットアップ | wp-config / WP-CLI / ディレクトリ構造 |
| 1 | テーマ骨格（style.css・functions.php・header/footer・enqueue） | **テンプレート階層** / **hooks** / enqueue 作法 |
| 2 | デザイン移植（トークン CSS 化・フロントページを静的内容で構築） | get_template_part / The Loop の基本 |
| 3 | `portfolio-core` プラグイン: CPT + タクソノミー | **register_post_type** / rewrite / 管理画面カラム |
| 4 | ACF 設計 + テンプレート接続 + シードデータ移行 | **ACF** / get_field / **escaping** |
| 5 | フロントページの ACF 化 + Customizer（受付ステータス） | Options 的設計 / Customizer API |
| 6 | GitHub API 連携 | **wp_remote_get / Transients / WP_Error / REST API** |
| 7 | お問い合わせフォーム自作 | **nonce / sanitization** / wp_mail / スパム対策 |
| 8 | 仕上げ: OGP・パフォーマンス（Query Monitor で計測）・セキュリティ・README 完成 | 案件視点の総仕上げ |
| 9（受注後） | 共用レンタルサーバー（ロリポップ）へデプロイ | サーバー移行 / WP-CLI での URL 移行 |

各 Phase 完了時に動作確認 → コミット。

**Phase 9 の方針は `docs/decisions.md` の D-9 で確定済み。**
当初は AWS Lightsail を想定していたが、CrowdWorks の WordPress 案件 143 件を調べたところ
AWS の名前が出る案件は 0 件で、実案件で使われているのは国内の共用レンタルサーバーだった。
また公開そのものを受注後まで行わないと決めたため、Phase 9 は当面着手しない。
実装前に D-9 を読むこと。

---

## 9. 本制作でカバーされる案件領域

| 狙う案件 | 本制作でカバーされる経験 |
|---|---|
| 軽微修正 / 既存テーマ修正 / PHP 修正 | テンプレート階層・The Loop・template-parts の実装経験そのもの |
| functions.php 修正 | Phase 1（enqueue / hooks）・inc/ 分割 |
| ACF による管理画面化 | Phase 4–5（**案件で最頻出の「ハードコードを ACF 化する」作業を実際に行う**） |
| 不具合調査 | Query Monitor での計測・調査（Phase 8） |
| プラグイン関連 | 自作プラグイン開発（Phase 3–7） |
| 外部 API 連携 | Phase 6 |
| サーバー移行 | Phase 9（共用サーバーへの移設）+ WP-CLI でのデータ移行経験（Phase 4）。調査した 150 件中 11 件（7%）がこの領域 |
| パフォーマンス改善 | Phase 8（Transients・enqueue 最適化・計測） |
| 会員機能 | **スコープ外**。README「今後追加したい機能」に記載する |
