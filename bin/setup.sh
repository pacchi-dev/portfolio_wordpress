#!/usr/bin/env bash
#
# WordPress の初期セットアップを WP-CLI で自動化する。
# 何度実行しても同じ結果になるよう冪等に書いてある。
#
#   使い方: ./bin/setup.sh
#
# 注意:
#   コンテナ内でのコマンド実行に `docker compose exec` ではなく `docker exec` を使う。
#   Compose の一部バージョン（2.0.0-beta.1 で確認）では `exec -T` が標準出力を
#   返さず、バージョン取得などの結果が空になるため。
#   `docker exec` はバージョン差の影響を受けにくい。
#
set -euo pipefail

cd "$(dirname "$0")/.."

if [ ! -f .env ]; then
  echo "==> .env が無いので .env.example からコピーします"
  cp .env.example .env
fi

# shellcheck disable=SC1091
set -a; source .env; set +a

WP_CONTAINER="portfolio_wp"
CLI_CONTAINER="portfolio_wp_cli"
DB_CONTAINER="portfolio_wp_db"

# WP-CLI をコンテナ内で実行するためのラッパ
wp() {
  docker exec "${CLI_CONTAINER}" wp --path=/var/www/html "$@"
}

echo "==> コンテナを起動します"
docker compose up -d

echo "==> データベースの起動を待ちます"
for i in $(seq 1 60); do
  if docker exec "${DB_CONTAINER}" mysqladmin ping -h localhost --silent >/dev/null 2>&1; then
    echo "    データベース応答あり"
    break
  fi
  if [ "$i" -eq 60 ]; then
    echo "    データベースが起動しませんでした" >&2
    exit 1
  fi
  sleep 2
done

echo "==> WordPress 本体の展開を待ちます"
for i in $(seq 1 60); do
  if docker exec "${CLI_CONTAINER}" test -f /var/www/html/wp-settings.php >/dev/null 2>&1; then
    echo "    本体を確認"
    break
  fi
  if [ "$i" -eq 60 ]; then
    echo "    WordPress 本体が展開されませんでした" >&2
    exit 1
  fi
  sleep 2
done

# ---------------------------------------------------------------------------
# WordPress インストール
#   wp core install はテーブル作成と初期ユーザー作成をまとめて行う。
#   ブラウザでの「5分間インストール」と同じ処理を CLI で実行している。
# ---------------------------------------------------------------------------
if wp core is-installed >/dev/null 2>&1; then
  echo "==> WordPress はインストール済みです（スキップ）"
else
  echo "==> WordPress をインストールします"
  wp core install \
    --url="${WP_URL}" \
    --title="${WP_TITLE}" \
    --admin_user="${WP_ADMIN_USER}" \
    --admin_password="${WP_ADMIN_PASSWORD}" \
    --admin_email="${WP_ADMIN_EMAIL}" \
    --skip-email
fi

# ---------------------------------------------------------------------------
# wp-config.php の定数
#   WORDPRESS_CONFIG_EXTRA 環境変数はこのイメージでは反映されなかったため、
#   WP-CLI の `wp config set` で直接書き込む。
#   --raw は値を PHP リテラルとして扱う指定（true が文字列 "true" にならない）。
# ---------------------------------------------------------------------------
echo "==> wp-config.php の定数を設定します"
wp config set WP_DEBUG true --raw --type=constant >/dev/null
wp config set WP_DEBUG_LOG true --raw --type=constant >/dev/null
wp config set WP_DEBUG_DISPLAY false --raw --type=constant >/dev/null
wp config set DISALLOW_FILE_EDIT true --raw --type=constant >/dev/null
wp config set WP_ENVIRONMENT_TYPE local --type=constant >/dev/null

echo "==> 基本設定を適用します"

# パーマリンク構造。
#   デフォルトは ?p=123 形式で、この状態ではカスタム投稿タイプの
#   /works/{slug}/ のような URL が機能しない。
#   CPT を扱う案件では最初に必ず確認する項目。
#   --hard を付けると WP-CLI が .htaccess の再生成を試みるが、CLI からは
#   Apache 環境を判定できず警告が出る。実際の .htaccess は WordPress 本体が
#   書き出しており、パーマリンクは正しく機能する（動作確認済み）。
wp rewrite structure '/%postname%/' >/dev/null
wp rewrite flush >/dev/null

# 日本語環境
wp language core install ja --activate >/dev/null 2>&1 || true
wp option update timezone_string 'Asia/Tokyo'
wp option update date_format 'Y-m-d'
wp option update time_format 'H:i'
wp option update start_of_week 1

# 検索エンジンの表示設定。ローカルでは常にブロックしておく。
wp option update blog_public 0

# コメント機能はこのサイトでは使わない
wp option update default_comment_status closed
wp option update default_ping_status closed

echo "==> 初期状態の不要コンテンツを削除します"
# 「Hello world!」「サンプルページ」「プライバシーポリシー」を削除
sample_post="$(wp post list --post_type=post --posts_per_page=1 --format=ids 2>/dev/null || true)"
if [ -n "${sample_post}" ]; then
  wp post delete "${sample_post}" --force >/dev/null 2>&1 || true
fi
for slug in sample-page privacy-policy; do
  id="$(wp post list --post_type=page --name="${slug}" --format=ids 2>/dev/null || true)"
  if [ -n "${id}" ]; then
    wp post delete "${id}" --force >/dev/null 2>&1 || true
  fi
done

echo "==> 不要な同梱プラグインを削除します"
# WordPress に同梱される Akismet / Hello Dolly は使わないため削除する。
# 「不要なプラグイン依存を避ける」という本プロジェクトの方針に沿う。
wp plugin delete akismet hello >/dev/null 2>&1 || true

echo ""
echo "======================================================"
echo " セットアップ完了"
echo ""
echo "  サイト        : ${WP_URL}"
echo "  管理画面      : ${WP_URL}/wp-admin/"
echo "  ユーザー      : ${WP_ADMIN_USER}"
echo "  パスワード    : ${WP_ADMIN_PASSWORD}"
echo "  メール確認    : http://localhost:${MAILPIT_PORT}"
echo ""
echo "  WordPress     : $(wp core version)"
echo "  PHP           : $(docker exec "${WP_CONTAINER}" php -r 'echo PHP_VERSION;')"
echo "  MySQL         : $(docker exec "${DB_CONTAINER}" mysql --version | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1)"
echo "======================================================"
