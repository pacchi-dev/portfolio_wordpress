#!/usr/bin/env bash
#
# 共用レンタルサーバーへ移設するためのファイル一式を書き出す。
#
#   使い方: ./bin/export.sh <公開URL> [テーブル接頭辞]
#
#   例（接頭辞が既定の wp_ のまま）:
#     ./bin/export.sh https://pacchi.net
#
#   例（設置先がランダム接頭辞で構築されている場合）:
#     ./bin/export.sh https://pacchi.net ust2_
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
NEW_PREFIX="${2:-wp_}"

if [ -z "${TARGET_URL}" ]; then
  echo "エラー: 移設先の URL を指定してください" >&2
  echo "  例: ./bin/export.sh https://pacchi.net ust2_" >&2
  exit 1
fi

case "${NEW_PREFIX}" in
  *_) : ;;
  *)  echo "エラー: テーブル接頭辞はアンダースコアで終わらせてください（例: ust2_）" >&2; exit 1 ;;
esac

# 末尾のスラッシュは付けない（DB 内の URL と形式を揃えるため）
TARGET_URL="${TARGET_URL%/}"

if [ ! -f .env ]; then
  echo "エラー: .env がありません" >&2
  exit 1
fi

# shellcheck disable=SC1091
set -a; source .env; set +a

SOURCE_URL="${WP_URL%/}"
OLD_PREFIX="wp_"
CLI_CONTAINER="portfolio_wp_cli"
DIST="bin/dist"

wp() {
  docker exec "${CLI_CONTAINER}" wp --path=/var/www/html "$@"
}

# 開発専用プラグイン。本番の active_plugins に残ると
#   ・ファイルが無いため管理画面にエラーが出る
#   ・Query Monitor は SQL やフックの内訳を表示する調査ツールで、
#     本番に置くこと自体が情報漏洩になる
# ダンプの取得中だけ無効化し、終わったら必ず戻す。
DEV_ONLY_PLUGINS="query-monitor"

restore_dev_plugins() {
  for plugin in ${DEV_ONLY_PLUGINS}; do
    if [ -n "${DEACTIVATED:-}" ]; then
      wp plugin activate "${plugin}" --quiet 2>/dev/null || true
    fi
  done
}
# 途中で失敗してもローカルの状態を戻す
trap restore_dev_plugins EXIT

echo "==> 移設元: ${SOURCE_URL}  (接頭辞 ${OLD_PREFIX})"
echo "==> 移設先: ${TARGET_URL}  (接頭辞 ${NEW_PREFIX})"
echo ""

rm -rf "${DIST}"
mkdir -p "${DIST}/wp-content/themes" "${DIST}/wp-content/plugins" "${DIST}/wp-content/uploads"

# ---------------------------------------------------------------------------
# 1. DB ダンプ（URL 置換済み）
#
#    --all-tables を付けないと wp_ 接頭辞のテーブルしか対象にならない。
#    --export でローカルの DB は書き換えない。
# ---------------------------------------------------------------------------
echo "==> 開発専用プラグインを一時的に無効化します"
DEACTIVATED=""
for plugin in ${DEV_ONLY_PLUGINS}; do
  if wp plugin is-active "${plugin}" 2>/dev/null; then
    wp plugin deactivate "${plugin}" --quiet
    DEACTIVATED="yes"
    echo "    ${plugin} を無効化（書き出し後に戻します）"
  fi
done

# 一時キャッシュを捨ててから書き出す。
#   ・更新チェックの結果（_site_transient_update_plugins など）は
#     ローカルの状態を写したもので、移設先には無意味
#   ・GitHub API のキャッシュも消えるが、本番の初回表示で取り直される
echo "==> 一時キャッシュ（トランジェント）を削除します"
wp transient delete --all --quiet 2>/dev/null || true

echo "==> DB をエクスポートします（URL を置換）"
wp search-replace "${SOURCE_URL}" "${TARGET_URL}" \
  --all-tables --export=/scripts/dist/database.sql --quiet

# ダンプは取り終えたので、ローカルはすぐ元に戻す
restore_dev_plugins
DEACTIVATED=""

# ---------------------------------------------------------------------------
# 2. テーブル接頭辞の書き換え
#
#    レンタルサーバーの自動インストーラは、推測を避けるため
#    ust2_ のようなランダムな接頭辞で WordPress を構築することがある。
#    その場合、wp_ のままのダンプを入れても wp-config.php が
#    自分のテーブルを見つけられず、インストール画面に戻ってしまう。
#
#    注意すべきは、接頭辞がテーブル名だけでなく
#    一部のレコードの「値」にも埋め込まれている点。
#      options  の option_name = 'wp_user_roles'
#      usermeta の meta_key    = 'wp_capabilities' / 'wp_user_level' ほか
#    ここを書き換え忘れると、移設先で管理者権限を失い
#    ログインはできるのに何も操作できない状態になる。
# ---------------------------------------------------------------------------
if [ "${NEW_PREFIX}" != "${OLD_PREFIX}" ]; then
  echo "==> テーブル接頭辞を ${OLD_PREFIX} から ${NEW_PREFIX} へ書き換えます"

  python3 - "${DIST}/database.sql" "${OLD_PREFIX}" "${NEW_PREFIX}" <<'PYEOF'
import re, sys
path, old, new = sys.argv[1], sys.argv[2], sys.argv[3]
sql = open(path, encoding='utf-8', errors='surrogateescape').read()

# (a) テーブル名。SQL 中では必ずバッククォートで囲まれているため、
#     `wp_ を目印にすれば本文中の "wp_head" などを巻き込まない。
sql, n_tables = re.subn(r'`' + re.escape(old), '`' + new, sql)

# (b) 接頭辞が「値」として入っているレコード。
keys = [
    'user_roles', 'capabilities', 'user_level',
    'dashboard_quick_press_last_post_id',
    'user-settings', 'user-settings-time',
]
n_values = 0
for k in keys:
    sql, c = re.subn(r"'" + re.escape(old + k) + r"'", "'" + new + k + "'", sql)
    n_values += c

open(path, 'w', encoding='utf-8', errors='surrogateescape').write(sql)
print(f'    テーブル名 {n_tables} 箇所 / 権限まわりの値 {n_values} 箇所を書き換え')
PYEOF
fi

# 検査対象は active_plugins のみ。
# 更新チェックのキャッシュに名前が載っていても実害はないため、
# 「本番で実際に読み込まれる値」だけを見る。
python3 - "${DIST}/database.sql" ${DEV_ONLY_PLUGINS} <<'PYEOF'
import io, re, sys
sql = io.open(sys.argv[1], encoding='utf-8', errors='surrogateescape').read()
m = re.search(r"active_plugins'\s*,\s*'(.*?)'\s*,", sql, re.S)
if not m:
    print('    警告: active_plugins が見つかりません', file=sys.stderr)
    sys.exit(0)
value = m.group(1)
for plugin in sys.argv[2:]:
    if plugin + '/' in value:
        print(f'    警告: active_plugins に {plugin} が残っています', file=sys.stderr)
plugins = re.findall(r'\\"([^\\"]+\.php)\\"', value)
print('    有効化されるプラグイン: ' + (', '.join(plugins) if plugins else 'なし'))
PYEOF

# 検索エンジンの設定。ローカルは noindex にしてあるため、
# そのまま移すと本番も Google に載らない。移設のたびに見落とすので明示する。
BLOG_PUBLIC=$(grep -oE "'blog_public', *'[^']*'" "${DIST}/database.sql" | head -1 | grep -oE "'[01]'" | tail -1 | tr -d "'")
if [ "${BLOG_PUBLIC:-1}" = "0" ]; then
  echo "    注意: このダンプは noindex（blog_public=0）です。"
  echo "          本番で検索に載せる場合は、移設後に"
  echo "          「設定 > 表示設定 > 検索エンジンがインデックスしないようにする」を外してください。"
else
  echo "    検索エンジン: インデックス許可（blog_public=1）"
fi

# 管理者アカウント。ローカルは admin/admin なので、そのまま移すと
# 公開サーバーが「最も狙われる組み合わせ」で立ち上がることになる。
if [ "${WP_ADMIN_USER:-}" = "admin" ] || [ "${WP_ADMIN_PASSWORD:-}" = "admin" ]; then
  echo ""
  echo "    ！ このダンプにはローカルの管理者アカウントが入っています"
  echo "      ユーザー名 ${WP_ADMIN_USER:-admin} / パスワードは .env の WP_ADMIN_PASSWORD"
  echo "      移設後ただちに、パスワード変更 → 別名の管理者を作成 → admin を削除 を行うこと。"
  echo ""
fi

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
