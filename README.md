# WordPress ポートフォリオサイト

バックエンドエンジニアのポートフォリオを、WordPress のオリジナルテーマと
自作プラグインで構築したものです。

既製テーマやページビルダーを使わず、テンプレート階層・The Loop・hooks・
カスタム投稿タイプ・ACF・外部 API 連携・自作フォームを実装しています。

| | |
|---|---|
| Lighthouse (Desktop) | Performance 100 / Accessibility 100 / Best Practices 100 / SEO 100 |
| Lighthouse (Mobile) | Performance 100 / Accessibility 100 / Best Practices 100 / SEO 100 |
| 使用プラグイン | ACF（無料版）のみ ※Query Monitor は開発時のみ |

---

## 目次

- [なぜ WordPress で作り直したのか](#なぜ-wordpress-で作り直したのか)
- [使用技術](#使用技術)
- [アーキテクチャ](#アーキテクチャ)
- [ローカル環境構築](#ローカル環境構築)
- [テーマ構成](#テーマ構成)
- [カスタム投稿タイプ / タクソノミー](#カスタム投稿タイプ--タクソノミー)
- [ACF](#acf)
- [外部 API 連携（GitHub API）](#外部-api-連携github-api)
- [お問い合わせフォーム](#お問い合わせフォーム)
- [セキュリティ上の考慮](#セキュリティ上の考慮)
- [パフォーマンス](#パフォーマンス)
- [工夫したポイント](#工夫したポイント)
- [開発中に遭遇した不具合と対処](#開発中に遭遇した不具合と対処)
- [今後追加したい機能](#今後追加したい機能)

---

## なぜ WordPress で作り直したのか

このサイトには [Next.js 版](https://github.com/pacchi-dev/portfolio) が
先に存在します。それを WordPress へ「移植」したのではなく、
**WordPress 案件で実際に求められる技術を意図的に取り入れて再構築**しました。

目的は 3 つです。

1. Web エンジニアとしてのポートフォリオ
2. **WordPress 制作・カスタマイズの実績**
3. 副業案件への応募時に提示できる制作物

WordPress 案件で最も依頼が多い作業のひとつが
「ハードコードされたサイトを管理画面から編集できるようにする」ことです。
本リポジトリは、静的に書かれていたコンテンツを
カスタム投稿タイプと ACF で管理画面化する作業そのものを含んでいます。

---

## 使用技術

| 領域 | 技術 |
|---|---|
| CMS | WordPress 7.1（クラシックテーマ / FSE 不使用） |
| 言語 | PHP 8.3, JavaScript (ES5 相当・ビルド無し), CSS |
| データベース | MySQL 8.0 |
| Web サーバー | Apache 2.4 (mod_rewrite / mod_expires / mod_headers) |
| 開発環境 | Docker Compose, WP-CLI, Mailpit |
| プラグイン | Advanced Custom Fields 6.8（無料版） |
| 開発ツール | Query Monitor |

**フレームワーク・ビルドツールを使っていません。**
CSS は素の CSS（カスタムプロパティ）、JavaScript はバニラです。
npm も Composer も不要で、`docker compose up` だけで動きます。

---

## アーキテクチャ

### 責務の分離

```
┌─────────────────────────────────────────────────┐
│ themes/pacchi-portfolio    表示（見た目）      │
│   テンプレート階層 / The Loop / CSS / JS         │
├─────────────────────────────────────────────────┤
│ plugins/portfolio-core       データ構造と機能    │
│   CPT / タクソノミー / ACF 定義                  │
│   GitHub API / REST / お問い合わせ処理           │
├─────────────────────────────────────────────────┤
│ mu-plugins/                  環境固有の設定      │
│   ローカル SMTP（本番では発動しない）            │
└─────────────────────────────────────────────────┘
```

**カスタム投稿タイプをテーマの `functions.php` に書いていません。**
テーマを変更した瞬間に投稿タイプが未登録になり、
データベースに残った記事へ管理画面から到達できなくなるためです。
WordPress では「表示 = テーマ / データ構造・機能 = プラグイン」で
責務を分けるのが原則です。

### コンテナ構成

```
┌──────────────┐   ┌──────────────┐   ┌──────────────┐
│  wordpress   │   │    wpcli     │   │   mailpit    │
│ PHP 8.3      │   │  WP-CLI      │   │ SMTP 受信箱   │
│ Apache       │   │  （常駐）     │   │ :8025        │
│ :8080        │   │              │   │              │
└──────┬───────┘   └──────┬───────┘   └──────────────┘
       │                  │
       └────────┬─────────┘
                ▼
         ┌──────────────┐
         │      db      │
         │  MySQL 8.0   │
         └──────────────┘
```

WordPress 本体は名前付きボリュームに置き、
**自作テーマ・自作プラグイン・mu-plugins だけをバインドマウント**しています。
「本体は更新で置き換わる / ユーザー領域は `wp-content` のみ」という
WordPress の構造を開発環境にも反映させています。

---

## ローカル環境構築

### 必要なもの

- Docker / Docker Compose

### 手順

```bash
git clone https://github.com/pacchi-dev/portfolio_wordpress.git
cd portfolio_wordpress
./bin/setup.sh
```

`bin/setup.sh` が以下を自動実行します（**冪等**なので何度でも流せます）。

1. コンテナ起動、DB と WordPress 本体の起動待ち
2. `wp core install`
3. `wp config set` による定数設定（デバッグログ、ファイル編集禁止など）
4. パーマリンク構造の設定（**CPT の URL を機能させるために必須**）
5. 日本語化・タイムゾーン設定
6. 初期状態の不要コンテンツ・同梱プラグイン（Akismet / Hello Dolly）の削除

### アクセス先

| | URL |
|---|---|
| サイト | http://localhost:8080 |
| 管理画面 | http://localhost:8080/wp-admin/ （`admin` / `admin`） |
| メール確認 | http://localhost:8025 |

### サンプルデータの投入

```bash
docker exec portfolio_wp_cli wp --path=/var/www/html eval-file /scripts/seed.php
docker exec portfolio_wp_cli wp --path=/var/www/html eval-file /scripts/seed-front-page.php
docker exec portfolio_wp_cli wp --path=/var/www/html eval-file /scripts/seed-works.php
```

いずれのスクリプトも冪等です。何度実行しても投稿・画像は重複しません。

画像は `bin/seed/images/` に置いた元ファイルをメディアライブラリへ取り込みます
（`seed-front-page.php` はプロフィール画像、`seed-works.php` は制作実績のアイキャッチ）。
取り込み処理は両方から使うため `bin/seed-lib.php` に切り出しています。

`uploads/` は Git 管理外のため、環境を作り直しても画像を再現できるよう
元画像をリポジトリ側に置いています。

### 共用レンタルサーバーへの移設

```bash
./bin/export.sh https://example.xfree.jp
```

`bin/dist/` に、移設先の URL へ置換済みの DB ダンプと `wp-content/` 一式、
アップロード手順（`README.txt`）が出力されます。

**URL の置換に `wp search-replace` を使っています。**
WordPress は DB にシリアライズされた配列を保存しており
（`s:21:"http://localhost:8080"` のように文字列長を持つ）、
SQL の `REPLACE` や `sed` で URL を置換すると長さが合わなくなり、
値が丸ごと読めなくなります。「サーバー移行したら設定が飛んだ」の典型的な原因です。

`--export` を付けているため、ローカルの DB は書き換わりません。

移設先に WP-CLI が無い前提のため、置換はローカルで済ませてから
ダンプを持っていく方式にしています。

公開までの具体的な手順は **[docs/deploy.md](docs/deploy.md)** にまとめています。

### Git 管理の方針

WordPress 本体・`uploads`・データベース・`.env` は **Git 管理していません**。
管理対象は自作テーマ、自作プラグイン、環境定義、ドキュメントのみです。

---

## テーマ構成

```
themes/pacchi-portfolio/
├── style.css              テーマ情報ヘッダ（WordPress の必須ファイル）
├── functions.php          inc/ を読み込むだけの薄いエントリ
├── inc/
│   ├── setup.php          add_theme_support / register_nav_menus
│   ├── enqueue.php        CSS・JS の読み込み、フォントの preload
│   ├── template-tags.php  エスケープ済み出力ヘルパ
│   ├── customizer.php     カスタマイザー設定
│   ├── seo.php            meta description / OGP の出力
│   └── security.php       不要出力の抑制、ユーザー列挙の遮断
├── index.php              テンプレート階層の最終フォールバック（必須）
├── front-page.php         トップページ
├── archive-work.php       制作実績の一覧
├── single-work.php        制作実績の詳細
├── taxonomy-technology.php 技術ごとの一覧
├── 404.php
├── header.php / footer.php
├── template-parts/        セクション単位の部品
└── assets/
    ├── css/               main.css / fonts.css
    ├── js/nav.js
    ├── fonts/             セルフホストした woff2
    └── img/               OGP 画像、サイトアイコン
```

### テンプレート階層

WordPress は URL の種類に応じて、決められた優先順位でファイルを探します。
ルーティングを定義するのではなく、**正しい名前のファイルを置く**方式です。

```
/                → front-page.php
/works/          → archive-work.php → archive.php → index.php
/works/{slug}/   → single-work.php  → single.php  → singular.php → index.php
/tech/{slug}/    → taxonomy-technology-{slug}.php → taxonomy-technology.php
                   → taxonomy.php → archive.php → index.php
存在しない URL   → 404.php
```

### `functions.php` を分割している理由

`functions.php` は有効なテーマで毎リクエスト自動読み込みされ、
何でも書けてしまうため実案件では容易に数千行へ肥大化します。
本テーマでは処理を書かず、`inc/` に責務ごとへ分割して読み込むだけにしています。

---

## カスタム投稿タイプ / タクソノミー

### CPT `work`（制作実績）

`public: true` / `has_archive: true` / `show_in_rest: true`

| フィールド | 種別 |
|---|---|
| 概要 / 担当内容 / 開発期間 / ステータス | ACF |
| GitHub URL / サイト URL / アーキテクチャ図 | ACF |
| サムネイル | **標準のアイキャッチ** |
| 使用技術 | **タクソノミー `technology`** |

### CPT `experience`（実務経験）

`has_archive: false`（トップページのサブループで一覧表示）

期間 / 期間の長さ / 進行中フラグ / 領域・業界 / 役割 / チーム規模 /
概要 / 担当内容 / アピールしたい能力 / 注記

### タクソノミー `technology`（使用技術）

`work` と `experience` で共通。ターム側に ACF で
**習熟度**（Main / Experienced / Familiar）と**カテゴリ**を持たせています。

**技術スタックのセクションはこのタクソノミーから描画**しており、
実績に技術を登録すれば一覧にも反映される単一ソース構成です。

### `flush_rewrite_rules()` について

CPT の登録直後はリライトルールが未生成のため、
`/works/{slug}/` のような URL が 404 になります。
`register_activation_hook` で有効化時に一度だけ再生成しています。

**「CPT を作ったのに個別ページが 404」は WordPress 案件で最も多いトラブルのひとつ**で、
原因の大半がこのフラッシュ漏れです。
なお `flush_rewrite_rules()` は重い処理のため、`init` などで毎回呼んではいけません。

---

## ACF

### なぜ ACF を使うのか

WordPress 標準にもカスタムフィールドがあり、`wp_postmeta` へ
任意のキー・値を保存できます。しかし**管理画面の UI が
「キー名とテキスト値の生入力」だけで、型も検証もありません**。
非エンジニアが運用できる状態ではありません。

ACF はその上に型付きフィールド定義・入力 UI・`get_field()` による
型変換を提供します。画像フィールドなら添付 ID ではなく
URL や配列を返してくれます。

### 標準機能との使い分け

| 項目 | 選択 | 理由 |
|---|---|---|
| サムネイル画像 | **標準のアイキャッチ** | `has_post_thumbnail()` / `the_post_thumbnail()` / REST API / OGP 生成と連携する。ACF の画像フィールドにするとこれらを自前で再実装することになる |
| 使用技術 | **タクソノミー** | ACF のテキストだと表記ゆれが起き、「この技術を使った実績一覧」も作れない。タクソノミーならアーカイブが自動生成される |
| 概要・担当内容など | **ACF** | 投稿本文（`post_content`）に混ぜると構造化されず、部分的な取得・更新ができない |

**「標準機能で足りるものは標準機能を使う」**方針です。

### フィールドを PHP で定義している理由

管理画面ではなく `acf_add_local_field_group()` で登録しています。

| | 管理画面で作成 → Local JSON | **PHP で登録（採用）** |
|---|---|---|
| バージョン管理 | JSON の同期が必要 | コードそのもの |
| 環境の再現 | 初回に手動操作が要る | `setup.sh` だけで完結 |
| 事故耐性 | 管理画面から壊せる | 編集不可 |
| 実案件での頻度 | こちらが多数派 | 上級者・チーム開発向け |

**実案件では Local JSON 方式が多数派**です。
今回はセットアップの完全自動化を優先しました。
サイト運用者自身がフィールドを追加する想定がある案件では、
管理画面 + Local JSON の方が適しています。

### 無料版の制約と回避策

`Repeater` と `Options Page` は Pro 機能です。以下で代替しています。

| 必要なもの | Pro なら | 本プロジェクトの対処 |
|---|---|---|
| サイト全体設定 | Options Page | **フロントページの固定ページ**にフィールドを付ける（`page_type == front_page`） |
| 件数固定の繰り返し | Repeater | **`group` フィールド**（無料で使える） |
| 件数可変の箇条書き | Repeater | **改行区切りの textarea** |
| 編集画面の整理 | — | **`tab` フィールド**（無料） |

`location` に固定ページの ID を直接指定すると環境ごとに ID が変わって壊れるため、
`page_type == front_page` を使っています。

### ACF の入手元について

2024 年に ACF の wordpress.org 配布枠を巡る経緯があり、
「Secure Custom Fields」というフォークが存在します。
本プロジェクトで `wp plugin install advanced-custom-fields` を実行したところ、
**WP Engine 提供の本家 ACF 6.8.9 がインストールされました**（実測）。

### ACF とカスタマイザーの使い分け

| | 用途 | 取得方法 |
|---|---|---|
| **ACF** | そのページのコンテンツ | `get_field()` |
| **カスタマイザー** | サイト全体に効く表示設定 | `get_theme_mod()` |

「副業の受付状況」は Hero 以外にも影響しうるサイト全体の状態のため、
カスタマイザーに置いています。

---

## 外部 API 連携（GitHub API）

`plugins/portfolio-core/inc/github-client.php`

### `wp_remote_get()` を使う理由

cURL や Guzzle を直接使わず、WordPress 内蔵の HTTP API を使っています。

1. **環境差を吸収する** — cURL が使えないサーバーでは自動で `fsockopen` にフォールバックする（共用サーバーで実際に起こる）
2. **フィルタで介入できる** — `pre_http_request` / `http_request_args` でテストや他プラグインから通信を差し替えられる
3. **プロキシ設定を尊重する** — `wp-config.php` のプロキシ定数が効く

Guzzle を `composer require` すると、これらすべてを失ったうえで依存も増えます。

### 見落としやすい罠

**`wp_remote_get()` は HTTP 4xx / 5xx を「成功」として返します。**
`WP_Error` になるのは DNS 解決失敗やタイムアウトなど通信自体の失敗だけです。
ステータスコードを別途確認しないと、
**エラーページの HTML をデータとして処理してしまいます**。

### キャッシュ戦略

```
通常キャッシュ (1時間)  ── あれば即返す（API を叩かない）
      │ 期限切れ
      ▼
   GitHub API ─────── 成功 → 両方のキャッシュを更新
      │ 失敗
      ▼
stale キャッシュ (1週間) ── あれば返す（表示を維持）
      │ これも無い
      ▼
   WP_Error を返す
```

Transients API を使っています。
オブジェクトキャッシュ（Redis 等）が有効ならそちらへ、
無ければ `wp_options` テーブルへ保存されるため、
**コードを変えずにインフラだけ差し替えられます**。

期限切れ後も古い値を別キーで保持することで、
**GitHub 側の障害中もサイトが空欄になりません**。

### 独自 REST エンドポイント

| メソッド | パス | 権限 |
|---|---|---|
| GET | `/wp-json/portfolio/v1/github` | 公開（`__return_true` を明示） |
| POST | `/wp-json/portfolio/v1/github/refresh` | `manage_options` |

`permission_callback` は**省略できません**。
省略すると WordPress が `_doing_it_wrong` 警告を出し、
実質「誰でも叩ける」エンドポイントになります。
公開してよいものは明示的に `__return_true` と書きます。

Cookie 認証の場合、WordPress が `X-WP-Nonce` ヘッダの検証も
自動で行うため、これが CSRF 対策になっています。

### 異常系のテスト方法

`pre_http_request` フィルタで通信を強制的に失敗させて検証しています。
**外部 API を実際に落とさずに異常系を確認できる**、実案件でも使う手法です。

---

## お問い合わせフォーム

`plugins/portfolio-core/inc/contact-form.php`

### Contact Form 7 を使わなかった理由

`nonce` / サニタイズ / `wp_mail()` を自分で実装する経験を優先しました。
これらは WordPress のセキュリティ実装の中核で、案件でも問われます。

**実案件では CF7 が標準的に使われます。**
運用者がフォーム項目を自分で編集したい案件では CF7 の方が適切です。
今回は学習目的での判断です。

### 処理の流れ（POST-Redirect-Get）

```
1. admin-post.php が POST を受ける
2. nonce 検証 → ハニーポット判定 → サニタイズ → バリデーション
3. wp_mail() で送信
4. 結果をクエリパラメータに載せてリダイレクト
```

4 が重要です。POST の結果をそのまま出力すると、
ブラウザの再読み込みで二重送信が起きます。

### 実装上のポイント

**`admin_post_` と `admin_post_nopriv_` の両方を登録する**
片方だけだと、未ログインユーザーからの送信が 400 になります。よくある実装漏れです。

**`wp_unslash()` をサニタイズより先に適用する**
WordPress は歴史的経緯から `$_POST` にスラッシュを付与しています。
順序を誤ると `It\'s` のような値が残ります。

**From に送信者のアドレスを入れない**
SPF / DKIM の検証に失敗して迷惑メール判定されます。
送信者は `Reply-To` に入れるのが正解で、
**「問い合わせメールが届かない」案件の原因の多くがこれ**です。

**`wp_safe_redirect()` を使う**
`wp_redirect()` だと外部サイトへ飛ばせてしまい、
オープンリダイレクトの脆弱性になります。

**表示メッセージは allowlist 方式**
`?contact=` の値をそのまま表示せず、既知のコードにのみ対応するメッセージを返します。

### スパム対策

CSS で画面外に飛ばした入力欄（ハニーポット）を用意しています。
入力があれば bot と判断し、**成功したように見せて破棄**します。
bot に検知させないためです。

### 開発時のメール確認

`mu-plugins/local-smtp.php` が `phpmailer_init` をフックし、
**ローカル環境でのみ** Mailpit へ送信します。
本番では SES や SendGrid に向けることになりますが、
アプリケーション側は `wp_mail()` のままで変わりません。

---

## セキュリティ上の考慮

### 出力・入力の扱い

| | タイミング | 使用関数 |
|---|---|---|
| **サニタイズ** | 入力を受け取った直後 | `sanitize_text_field()` / `sanitize_email()` / `sanitize_textarea_field()` / `sanitize_key()` |
| **エスケープ** | 出力する直前 | `esc_html()` / `esc_attr()` / `esc_url()` / `wp_kses()` |

「保存時にエスケープ済みだから出力時は不要」は**誤り**です。
保存先が変わったり別経路で値が入る可能性があるため、
**出力直前に必ずエスケープ**します（Late Escaping）。

外部 API 由来の文字列（リポジトリ名・説明文）も
**外部入力として扱い**、すべてエスケープしています。

### CSRF 対策

`wp_nonce_field()` / `wp_verify_nonce()` を使用。

WordPress の nonce は Laravel の CSRF トークンと**中身が異なります**。
セッションに保存されたランダム値ではなく、
「アクション名 + ユーザー ID + セッショントークン + 時刻」から算出するハッシュです。
サーバー側に保存されないためロードバランサ配下でも同期不要ですが、
**有効期限（既定 24 時間）があります**。

### SQL インジェクション対策

生 SQL を書いていません。すべて `WP_Query` / `get_posts()` /
`get_terms()` 経由で、プレースホルダ処理は WordPress が行います。

### ユーザー列挙の遮断

`inc/security.php`

- REST API の `/wp/v2/users` を未ログインで叩けないようにする
- 著者アーカイブ（`/?author=1`）を 404 にする
- ログイン失敗メッセージを共通化する（ユーザー名の存在有無を推測させない）

### その他のハードニング

- `generator` メタタグの削除（WordPress のバージョンを露出させない）
- XML-RPC の無効化（総当たり攻撃の増幅に悪用される）
- `DISALLOW_FILE_EDIT`（管理画面からのテーマ・プラグイン編集を禁止）
- RSD / wlwmanifest リンクの削除

### 注意点

`the_content()` は `wp_kses` を通しません。
投稿本文は編集権限を持つユーザーしか書けない前提のため、
WordPress の設計として意図的にそうなっています。
**権限管理が甘いサイトでは XSS の経路になります。**

---

## パフォーマンス

### 計測結果

| | Performance | Accessibility | Best Practices | SEO |
|---|---|---|---|---|
| Desktop | **100** | **100** | **100** | **100** |
| Mobile | **100** | **100** | **100** | **100** |

### 改善の内容

Query Monitor と Lighthouse で計測し、以下を実施しました。

| 施策 | 効果 |
|---|---|
| **Web フォントのセルフホスト** | Google Fonts の CDN が描画を **816ms ブロック**していた。woff2 を同一オリジンに置いて解消 |
| **フォントの preload** | CSS の解析を待たずにダウンロードを開始 |
| **JS に `defer` を指定** | `in_footer` だけでは描画ブロックと判定されるため、`strategy => 'defer'` を指定 |
| **静的アセットのキャッシュ** | `mod_expires` / `mod_headers` で 1 年。URL に `filemtime` 由来の `?ver=` が付くため更新は即反映される |
| **絵文字スクリプトの削除** | 全ページで読み込まれるが実用上ほぼ不要 |

**改善前 → 改善後**

```
Performance             92 →  98 → 100
First Contentful Paint  2.7s → 1.1s
Largest Contentful Paint 2.7s → 1.6s
```

### `filemtime` をアセットのバージョンに使う

```php
wp_enqueue_style( 'mp-main', $url, array(), mp_asset_version( '/assets/css/main.css' ) );
```

固定のバージョン番号だと「更新したのに反映されない」という
**WordPress 案件で頻出のトラブル**を招きます。

### フォントの preload における注意

`crossorigin` の指定は必須です。フォントは CORS モードで取得されるため、
指定が無いと preload したものが再利用されず**二重にダウンロードされます**。

---

## 工夫したポイント

### 1. 単一ソースからの描画

使用技術をタクソノミーにしたことで、以下が同じデータから生成されます。

- 技術スタックのセクション（習熟度別に色分け）
- 各実績・経験に紐づく技術タグ
- `/tech/{slug}/` の一覧ページ（**register_taxonomy だけで自動生成**）

### 2. JavaScript を最小限に保つ

セクションと実務経験の開閉は `<details>` / `<summary>` の
**ネイティブ機能**で動作します。
JavaScript は「アンカー移動時に対象セクションを開く」
「モバイルメニューを閉じる」の 2 つだけで、
**読み込まれなくてもサイトは壊れません**（Progressive Enhancement）。

### 3. 外部ライブラリを一切追加していない

アイコン・図・シンタックスハイライトはすべてインライン SVG と CSS です。
npm も Composer も使わず、`docker compose up` だけで動きます。

### 4. 障害時にサイトが壊れない設計

- GitHub API 障害時は stale キャッシュで表示を維持
- ACF が無効でも致命的エラーにならないよう `function_exists()` で確認
- プラグインが無効ならセクションごと出力しない
- エラー詳細は管理者にのみ表示

### 5. 環境の完全な再現性

`./bin/setup.sh` だけで、WordPress のインストールから
ACF フィールド定義、初期設定まで再現できます。
**冪等**なので何度でも流し直せます。

### 6. アクセシビリティ

- 配色は全背景トークン上で **コントラスト比 4.5:1（WCAG AA）** を満たすよう計算して決定
- スキップリンク、`aria-labelledby`、`role="status"`
- `prefers-reduced-motion` 対応
- Lighthouse Accessibility 100

---

## 開発中に遭遇した不具合と対処

実際に踏んだものと、その原因・対処です。

### 1. 日本語が正規表現で分割されデータが壊れた

`preg_split( '/\R/', $text )` に `/u` 修飾子が無く、PCRE がバイト単位で動作していました。
`\R` は改行のほかに `\x85`（NEL）にもマッチしますが、
**日本語 UTF-8 の継続バイトに `0x85` が現れる**ため、
マルチバイト文字の途中で分割されていました（5 項目が 10 項目に分裂）。

→ `/u` 修飾子を追加。**PHP で日本語を正規表現処理する際の典型的な落とし穴**です。

### 2. `WORDPRESS_CONFIG_EXTRA` が反映されず、デバッグログが機能していなかった

docker-compose の `WORDPRESS_CONFIG_EXTRA` が `wp-config.php` へ反映されず、
`WP_DEBUG_LOG` も `WP_ENVIRONMENT_TYPE` も未定義でした。
`debug.log` が生成されないため、エラー確認が実質無効になっていました。

→ WP-CLI の `wp config set` で確実に書き込む方式へ変更。

### 3. ローカルでメールが送信できなかった

PHPMailer が `wordpress@localhost` を無効な From として拒否していました。
`localhost` は TLD を持たないためです。

→ RFC 6761 でテスト用に予約された `.test` を補う実装に変更。
実案件でも「ローカルではメールが送れない」の原因になりがちな箇所です。

### 4. `preload` が出力されなかった

`wp_enqueue_scripts` のコールバック内から `wp_head` の優先度 1 に
`add_action` していましたが、その時点では既に通過済みで実行されませんでした。

→ ファイルのトップレベルで登録するよう修正。

### 5. CPT の個別ページが 404 になる

CPT 登録後にリライトルールが再生成されていませんでした。

→ `register_activation_hook` で `flush_rewrite_rules()` を実行。
**WordPress 案件で最も多いトラブルのひとつ**です。

---

## 今後追加したい機能

- **会員機能** — 実績の一部を限定公開する。`add_role()` / `current_user_can()` / カスタムログインフローの実装
- **多言語対応** — 英語版の追加。翻訳ファイル（`.pot` / `.mo`）は生成済みの構造にしてある
- **管理画面からのキャッシュ操作 UI** — 現在は REST エンドポイントのみ。管理バーへのボタン追加
- **制作実績の絞り込み** — 技術タグによるフィルタ UI
- **共用レンタルサーバーへのデプロイ** — `wp search-replace` を用いた URL 移行を含む移設
- **PHPUnit / WP-CLI によるテスト** — 特に GitHub API クライアントのモックテスト
- **WordPress Coding Standards の CI** — PHPCS を GitHub Actions で自動実行

---

## ライセンス

GPL-2.0-or-later（WordPress テーマ・プラグインの標準ライセンス）
