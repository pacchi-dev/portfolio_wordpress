#!/usr/bin/env bash
#
# 共用レンタルサーバーへ移設するためのファイル一式を書き出す。
#
#   使い方: ./bin/export.sh https://example.xfree.jp
#
# 出力先: bin/dist/
#   database.sql  URL を移設先へ置換済みの DB ダンプ
#   wp-content/   テーマ・プラグイン・アップロード画像
#   README.txt    アップロード手順
#
# ------------------------------------------------------------------
# なぜ wp search-replace を使うのか
#
# WordPress は DB に「シリアライズされた配列」を保存している
# （カスタマイザーの設定、一部のオプションなど）。
# シリアライズ形式は s:21:"http://localhost:8080" のように
# 文字列の長さを持つため、SQL の REPLACE や sed で URL を置換すると
# 長さが合わなくなり、値が丸ごと読めなくなる。
#
# wp search-replace はシリアライズを展開してから置換し、
# length を再計算して詰め直す。「サーバー移行したら設定が飛んだ」の
# 典型的な原因がこれで、実案件で最も見落とされる点。
#
# --export を付けると、ローカルの DB を書き換えずに
# 置換後の内容だけをファイルへ出力できる。
# ------------------------------------------------------------------
set -euo pipefail

cd "$(dirname "$0")/.."

TARGET_URL="${1:-}"

if [ -z "${TARGET_URL}" ]; then
  echo "エラー: 移設先の URL を指定してください" >&2
  echo "  例: ./bin/export.sh https://example.xfree.jp" >&2
  exit 1
fi

# 末尾のスラッシュは付けない（DB 内の URL と形式を揃えるため）
TARGET_URL="${TARGET_URL%/}"

if [ ! -f .env ]; then
  echo "エラー: .env がありません" >&2
  exit 1
fi

# shellcheck disable=SC1091
set -a; source .env; set +a

SOURCE_URL="${WP_URL%/}"
CLI_CONTAINER="portfolio_wp_cli"
DIST="bin/dist"

wp() {
  docker exec "${CLI_CONTAINER}" wp --path=/var/www/html "$@"
}

echo "==> 移設元: ${SOURCE_URL}"
echo "==> 移設先: ${TARGET_URL}"
echo ""

rm -rf "${DIST}"
mkdir -p "${DIST}/wp-content/themes" "${DIST}/wp-content/plugins" "${DIST}/wp-content/uploads"

# ---------------------------------------------------------------------------
# 1. DB ダンプ（URL 置換済み）
#
#    --all-tables を付けないと wp_ 接頭辞のテーブルしか対象にならない。
#    --export でローカルの DB は書き換えない。
# ---------------------------------------------------------------------------
echo "==> DB をエクスポートします（URL を置換）"
wp search-replace "${SOURCE_URL}" "${TARGET_URL}" \
  --all-tables --export=/scripts/dist/database.sql --quiet

REPLACED=$(grep -c "${TARGET_URL}" "${DIST}/database.sql" || true)
echo "    database.sql に移設先 URL が ${REPLACED} 箇所"

if grep -q "${SOURCE_URL}" "${DIST}/database.sql"; then
  echo "    警告: 移設元 URL が残っています。内容を確認してください" >&2
fi

# ---------------------------------------------------------------------------
# 2. wp-content（テーマ・プラグイン・アップロード）
#
#    WordPress 本体は移設先の簡単インストールで用意されるため含めない。
#    上書きするのは wp-content 配下だけにする。
# ---------------------------------------------------------------------------
echo "==> wp-content を収集します"
cp -R themes/pacchi-portfolio "${DIST}/wp-content/themes/"
cp -R plugins/portfolio-core "${DIST}/wp-content/plugins/"

# uploads はメディアの実体。DB の添付ファイル情報と対で意味を持つ。
if [ -d uploads ] && [ -n "$(ls -A uploads 2>/dev/null)" ]; then
  cp -R uploads/. "${DIST}/wp-content/uploads/"
fi

# mu-plugins（ローカル SMTP）は移設しない。
# wp_get_environment_type() で守ってはいるが、本番に開発用の
# コードを置かないほうが事故が起きない。
echo "    mu-plugins は移設対象外（ローカル専用のため）"

# ACF 本体は同梱しない（GPL だが配布物に含めず、移設先で入れる）
echo "    ACF は移設先で管理画面からインストールする"

# ---------------------------------------------------------------------------
# 3. 手順書
# ---------------------------------------------------------------------------
cat > "${DIST}/README.txt" <<EOS
移設手順（${TARGET_URL}）

前提: 移設先に WordPress がインストール済みであること。

1. プラグイン Advanced Custom Fields（無料版）をインストールして有効化する。
   DB ダンプ側で有効化済みの状態になっているため、ファイルが無いと
   管理画面でエラーになる。先に入れておく。

2. FTP で wp-content/ の中身をアップロードする。
     wp-content/themes/pacchi-portfolio/
     wp-content/plugins/portfolio-core/
     wp-content/uploads/

3. phpMyAdmin で database.sql をインポートする。
   既存のテーブルがある場合は先に削除する（インポートで上書きされない）。

4. 管理画面にログインし、「設定 > パーマリンク」を開いて保存する。
   カスタム投稿タイプ /works/ のリライトルールを再生成するために必要。
   ボタンを押すだけでよい（設定は変えない）。

5. 動作確認
     ${TARGET_URL}/           トップ
     ${TARGET_URL}/works/     制作実績の一覧
     ${TARGET_URL}/tech/php/  技術タクソノミー
   404 が出る場合は 4 をやり直す。

6. お問い合わせフォームの送信を試す。
   届かない場合は「外観 > カスタマイズ > サイト設定」で
   「お問い合わせフォームを表示する」をオフにし、
   メールアドレスの案内のみにする。

注意
- 管理者のパスワードはローカルと同じものが入っている。
  ログイン後すぐに変更すること。
- mu-plugins（ローカル SMTP）は含めていない。
EOS

echo ""
echo "==> 完了"
du -sh "${DIST}" | sed 's/^/    /'
find "${DIST}" -maxdepth 2 -mindepth 1 | sed "s|${DIST}/|    |"
echo ""
echo "    手順は ${DIST}/README.txt を参照してください。"
