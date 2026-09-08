# 公開手順（タダサーバー / pacchi.net）

方針の背景は `docs/decisions.md` の D-9 / D-12 を参照。

## 完成形

1 つのサーバー・1 つのデータベースに、2 つの WordPress を同居させる。

```
https://pacchi.net/              ポートフォリオ本体    接頭辞 wp_
https://pacchi.net/koumuten/     工務店サイト（架空）  接頭辞 km_
                                 ↑ 同じ MySQL を共有

contact@pacchi.net               メール（Gmail へ転送）
```

無料プランは **MySQL 1 個・独自ドメイン 1 個・サブドメイン不可**。
サブディレクトリと `$table_prefix` を使えば、この制約のまま 2 サイト置ける。

| 費用 | |
|---|---|
| サーバー | 0 円 |
| メール 1 アカウント | 0 円 |
| 独自SSL | 0 円 |
| **ドメイン pacchi.net** | **約 1,920 円/年** |

**タダサーバーは 3 ヶ月ごとに利用期限の更新が必要。**
カラフルボックスの会員画面（cPanel ではない）から手続きする。
更新を忘れるとサイトが停止する。**カレンダーに登録しておくこと。**

---

## 注意

**管理画面のメニュー名は cPanel の一般的な名称で書いている。**
タダサーバー（ColorfulBox）は cPanel を採用しているが、表記が多少異なる場合がある。
見つからないときは近い名前のものを探すか、サポートに確認する。

**最後まで通すと 2〜3 時間かかる。** ステップ 1〜8 でポートフォリオ本体が公開されるので、
そこで一度区切ってよい。工務店サイト（ステップ 9〜12）は後日でも問題ない。

---

# 第 1 部: ポートフォリオ本体を公開する

## 1. 申し込む

https://www.colorfulbox.jp/tadaserver/ から申し込む。

- プランは **タダサーバー（無料）**
- **ドメインを同時に取得する**。`pacchi.net` を指定
- 独自ドメインが必須（無料サブドメインの提供はない）

完了メールに cPanel のログイン情報が届く。**保存しておく。**

---

## 2. WordPress を入れる（ルート）

cPanel の「**WordPress 快速セットアップ**」（または WordPress インストーラ）を使う。

- インストール先: `pacchi.net`（**サブディレクトリではなくルート**）
- **管理者のユーザー名を `admin` にしない**
- パスワードは推測されないものにする

この時点で `https://pacchi.net/` に初期状態の WordPress が出れば OK。

### PHP のバージョンを上げる（必須）

タダサーバーの初期値は **PHP 7.4**。このままではテーマが動かない。

```
cPanel →「ソフトウェア」→「MultiPHP マネージャー」
→ 対象ドメインにチェック → PHP バージョンで 8.3 を選択 → 適用
```

**なぜ 8.3 か**

- ローカルの開発環境が `wordpress:php8.3-apache`。**本番と揃えれば環境差分による事故が起きない**
- 8.1 はセキュリティ更新が 2025-12-31 に終了済み。`Requires PHP: 8.1` は最低ラインの宣言であって推奨値ではない
- 8.3 のセキュリティ更新は 2027-12-31 まで

無い場合は 8.2 → 8.4 の順に選ぶ。**8.1 以下は選ばない。**

**PHP 7.4 のままだと何が起きるか**

テーマとプラグインは PHP 8.0 で追加された `str_contains` / `str_starts_with` を使っている。
7.4 にこの関数は無く、`Fatal error: Call to undefined function` で白画面になる。

| 箇所 | 壊れるもの |
|---|---|
| `themes/pacchi-portfolio/inc/security.php` | REST API 全体 |
| `themes/pacchi-portfolio/inc/template-tags.php` | 構成図（実績詳細） |
| `plugins/portfolio-core/inc/contact-form.php` | お問い合わせの送信 |
| `plugins/portfolio-core/inc/github-client.php` | リポジトリ一覧 |
| `koumuten-wp/.../inc/floor-plan.php` | 工務店サイトの間取り図 |

`.htaccess` の `AddHandler ... ea-php74` の行は手で書き換えない。
MultiPHP マネージャーが自動で更新する。手で触ると二重定義になる。

---

## 3. SSL を有効にする

**先に SSL を有効にしておくこと。** 次のステップで `https://` の URL を DB に焼き込むため。

### 3-1. 証明書を発行する

cPanel（**`http://ドメイン名/cpanel`**。会員画面とは別物で、ログイン情報も別）を開く。

```
「セキュリティ」→「SSL / TLS ステータス」→ 対象ドメインを選択 →「AutoSSLを実行する」
```

約 10 分で Let's Encrypt の証明書が入る。ドメイン追加直後は失敗することがあり、
公式は最大 24 時間としている。確認は次のコマンドで行う。

```bash
echo | openssl s_client -connect ドメイン名:443 -servername ドメイン名 2>/dev/null \
  | openssl x509 -noout -subject -issuer -dates
```

`issuer` が `CN=ドメイン名`（= subject と同じ）なら**自己署名のままで未発行**。
`Let's Encrypt` になっていれば成功。

### 3-2. HTTPS へ転送する

cPanel の「ドメイン」画面に **「HTTPSリダイレクトを強制する」** というトグルがあるが、
**タダサーバー（無料プラン）ではトグルもチェックボックスも操作できない。** 機能が
無効化されているため、`.htaccess` で設定する。

「ファイル マネージャ」で `public_html/.htaccess` を開き、**先頭**に追記する。
（`.htaccess` が見えないときは、右上の「設定」で「隠しファイルを表示する」を有効にする）

```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]
```

**`# BEGIN WordPress` 〜 `# END WordPress` の内側に書かないこと。**
このブロックは WordPress が自動生成し、パーマリンク保存のたびに上書きされる。

なお DB を入れ替えると `siteurl` が `https://` になるため、WordPress 自身も
`http://` を転送するようになる。上記はその手前の層で確実に転送するための設定。

---

## 4. ACF を入れる（順番が重要）

管理画面 → プラグイン → 新規追加 → 「**Advanced Custom Fields**」をインストールして有効化。

このあと入れる DB ダンプは **ACF が有効な状態**で保存されている。
プラグインのファイルが無いまま DB を入れ替えると、管理画面がエラーで開かなくなる。

---

## 5. 書き出す（ローカルで実行）

```bash
cd /Users/admin/prc-project/portfolio/portfolio_wordpress
./bin/export.sh https://pacchi.net
```

`bin/dist/` に出力される。

```
database.sql      URL を置換済みの DB ダンプ
wp-content/       テーマ・自作プラグイン・アップロード画像
README.txt        手順の要約
```

URL の置換は `wp search-replace` で行う。`sed` や SQL の `REPLACE` を使うと
シリアライズされた値の長さが合わなくなり、設定が壊れる。

---

## 6. ファイルを上げる（FTP）

cPanel の「FTP アカウント」で接続情報を確認し、FileZilla や Cyberduck で接続。

`bin/dist/wp-content/` の中身を、サーバーの `public_html/wp-content/` へアップロードする。

```
themes/pacchi-portfolio/   → wp-content/themes/pacchi-portfolio/
plugins/portfolio-core/    → wp-content/plugins/portfolio-core/
uploads/                   → wp-content/uploads/
```

**既存の `themes/` や `plugins/` を消さない。** 中に追加する。

cPanel の「ファイルマネージャ」で zip をアップロードして展開してもよい。そのほうが速い。

---

## 7. DB を入れ替える（phpMyAdmin）

cPanel の「phpMyAdmin」を開く。

1. WordPress が使っているデータベースを選ぶ
2. 既存のテーブルを**すべて選択して削除**
   （インポートは上書きではないので、消さないと重複でエラーになる）
3. 「インポート」タブ → `bin/dist/database.sql` を選択 → 実行

---

## 8. パーマリンクを保存する（最重要）

管理画面 → **設定 → パーマリンク** → 何も変えずに「**変更を保存**」。

カスタム投稿タイプ `/works/` のリライトルールが再生成される。
**飛ばすと制作実績が全部 404 になる。**
「CPT を作ったのに個別ページが 404」は WordPress 案件で最も多いトラブルで、
原因の大半がこれ。

### ここで動作確認

```
https://pacchi.net/                          トップ
https://pacchi.net/works/                    制作実績の一覧
https://pacchi.net/works/soranowa-koumuten/  実績の詳細
https://pacchi.net/tech/php/                 技術タクソノミー
https://pacchi.net/存在しないURL              404 ページ
```

`/works/` が 404 なら、このステップをやり直す。

---

## 9. 管理者アカウントを安全にする

DB を入れ替えた結果、**ローカルの `admin` / `admin` が復活している。**
公開サーバーで `admin` は即座に狙われる。

1. ユーザー → 新規追加で、推測されないユーザー名の管理者を作る
2. そのアカウントでログインし直す
3. 元の `admin` を削除する（投稿は新しいユーザーへ移す）

テーマ側でユーザー列挙（REST API / 著者アーカイブ）は塞いであるが、
`admin` は最初に試される名前なので使わない。

**ここまででポートフォリオ本体は公開完了。** 一度区切ってよい。

---

# 第 2 部: 工務店サイトを追加する

同じ DB に 2 つ目の WordPress を入れる。**接頭辞を変えるのが肝。**

## 10. WordPress 本体を置く

1. https://ja.wordpress.org/download/ から WordPress をダウンロード
2. 展開して `public_html/koumuten/` へアップロード
   （`wp-admin` `wp-includes` `wp-content` `index.php` などが `koumuten/` 直下に来る形）

**cPanel の WordPress インストーラは使わない。** 自動インストールは新しい DB を
作ろうとするが、無料プランは MySQL 1 個までなので作れない。手動で置く。

## 11. wp-config.php を作る

`koumuten/wp-config-sample.php` を `wp-config.php` にコピーし、以下を設定する。

```php
// 1 つ目と「同じ」DB 情報を書く（DB は共有する）
define( 'DB_NAME',     'ルートで使っているDB名' );
define( 'DB_USER',     '同じユーザー' );
define( 'DB_PASSWORD', '同じパスワード' );
define( 'DB_HOST',     'localhost' );

// ここが最重要。接頭辞を km_ にする
$table_prefix = 'km_';
```

DB 情報はルート側の `public_html/wp-config.php` からコピーすればよい。

認証用ソルトは https://api.wordpress.org/secret-key/1.1/salt/ で生成して貼る
（ルート側と同じ値にしない）。

**`$table_prefix` を `wp_` のままにすると、ルート側のサイトを上書きしてしまう。**
ここだけは必ず確認する。

## 12. プラグインを入れて DB を流し込む

`https://pacchi.net/koumuten/` を開くとインストール画面が出る。
**そのままインストールを進めてよい**（サイト名などは仮でよい。あとで DB ごと置き換わる）。

インストール後、

1. プラグイン → **Advanced Custom Fields** と **Contact Form 7** をインストールして有効化
2. ローカルで書き出す

```bash
cd /Users/admin/prc-project/portfolio/koumuten-wp
./bin/export.sh https://pacchi.net/koumuten km_
```

3. `bin/dist/wp-content/` を `public_html/koumuten/wp-content/` へアップロード
4. phpMyAdmin で **`km_` で始まるテーブルだけ削除**し、`database.sql` をインポート
   （**`wp_` のテーブルは絶対に消さない。** ポートフォリオ本体が消える）
5. `https://pacchi.net/koumuten/wp-admin/` → 設定 → パーマリンク → 変更を保存

### 動作確認

```
https://pacchi.net/koumuten/          トップ
https://pacchi.net/koumuten/works/    施工実績の一覧
https://pacchi.net/koumuten/contact/  お問い合わせ
```

---

# 第 3 部: メールと仕上げ

## 13. メールアドレスを作る

cPanel の「メールアカウント」で `contact@pacchi.net` を作成する（無料プランは 1 個まで）。

そのうえで **`contact@pacchi.net` への転送**を設定する。
これで受信は今まで通り Gmail で行える。

## 14. サイトの連絡先を差し替える

`contact@pacchi.net` が使える状態になってから実施する。
`bin/seed/front-page.json` の連絡先を差し替え、シードを流し直す。

## 15. 制作実績を「公開中」にする

`bin/seed/works.json` の以下を更新してシードを流し直す。

- `wordpress-portfolio` に `siteUrl: https://pacchi.net`、`status: released`
- `soranowa-koumuten` に `siteUrl: https://pacchi.net/koumuten`、`status: released`

---

## つまずきやすい箇所

| 症状 | 原因 |
|---|---|
| `/works/` が 404 | パーマリンクを保存していない（ステップ 8 / 12-5） |
| 管理画面が真っ白・エラー | ACF を入れる前に DB を入れ替えた（ステップ 4 の順番） |
| **ポートフォリオ本体が消えた** | **`koumuten/wp-config.php` の `$table_prefix` が `wp_` のまま**（ステップ 11） |
| 工務店サイトがインストール画面に戻る | `$table_prefix` が `km_` になっていない |
| 画像が出ない | `uploads/` のアップロード漏れ |
| 一部の設定が消えた | `sed` や SQL の `REPLACE` で URL を置換した（`export.sh` を使えば起きない） |
| フォームが届かない | サーバーのメール制限。メールアカウント作成後に再確認 |
| `https://` に繋がらない | AutoSSL が未実行、または自己署名のまま（ステップ 3-1） |
| `http://` のまま表示される | 無料プランは強制リダイレクトのトグルが使えない。`.htaccess` で対応（ステップ 3-2） |
| ある日サイトが消えた | **3 ヶ月ごとの利用期限更新を忘れた**（冒頭の注意を参照） |
| 白画面 / `undefined function str_contains` | PHP が 7.4 のまま（ステップ 2 の PHP バージョン） |

---

## 進捗

| | 状態 |
|---|---|
| 1. 申し込み・ドメイン取得 | 完了（`pacchi.net`) |
| 2. WordPress 設置（ルート） | 完了 |
| 3-1. SSL 証明書 | 完了（Let's Encrypt / 期限 2026-12-06・自動更新） |
| 2-2. PHP を 8.3 へ | **未**（初期値は 7.4。最優先） |
| 3-2. HTTPS への転送 | 作業中（`.htaccess` で対応） |
| 4. ACF のインストール | **未** |
| 5〜9. 書き出し〜公開 | 未 |
| 10〜12. 工務店サイト | 未 |
| 13〜15. メールと仕上げ | 未 |

**ステップ 5 の書き出しはステップ 4 の完了後に実行する。**
