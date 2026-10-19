# portfolio_wordpress — プロジェクトコンテキスト

WordPress で再構築するエンジニアポートフォリオサイト。

**このファイルは新しいセッションで最初に読まれる前提で書いている。**
経緯・決定事項・設計方針はすべてここと `docs/` に集約する。会話履歴に依存しない。

## このプロジェクトの目的（3つ、すべて満たす）

1. Web エンジニアとしてのポートフォリオ
2. **WordPress 制作・カスタマイズの実績**（副業案件応募時の提出物）
3. CrowdWorks 等での案件応募に使える制作物

単なる Next.js からの移植ではない。**WordPress 案件で実際に求められる技術を意図的に取り入れ、
「WordPress の仕組みを理解して実装した」と読み取れる構成にすること**が主眼。
既製テーマを入れてページを作るだけの構成にはしない。

## 前提となる人物像

本業で Go / PHP・Laravel / TypeScript / AWS / API 開発の実務経験があるエンジニア。
**PHP・プログラミング一般の初歩的な説明は不要。WordPress 固有の仕組みの説明が必要。**

Laravel との対比で説明すると理解が早い（例: hooks ≒ イベント/ミドルウェア、
テンプレート階層 ≒ 暗黙のルーティング規約）。

## 進め方の原則（重要）

**本人が WordPress を学習しながら実装することが目的。全部自動実装して終わらせない。**

WordPress 特有の実装に入る前に、毎回以下を簡潔に説明してから進める。

1. 今から何を実装するのか
2. WordPress ではなぜこの方法を使うのか
3. 実案件ではどのような場面で使われるのか

特に説明が必要な概念:
テンプレート階層 / functions.php / hooks (actions・filters) / The Loop / WP_Query /
Custom Post Type / ACF / REST API / nonce / sanitization・escaping

## 関連リポジトリ

| リポジトリ | 内容 |
|---|---|
| `pacchi-dev/portfolio` | **Next.js 版**（移植元）。ブランチ `nextjs-version`、タグ `v1.0-nextjs` に完成状態を保存済み |
| このリポジトリ | WordPress 版 |

移植元のローカルパス: このリポジトリと同じ親ディレクトリにある `portfolio-site/`
コンテンツは同リポジトリの `src/data/*.ts` にすべて入っている（シードデータの原本）。

## 設計ドキュメント

| ファイル | 内容 |
|---|---|
| `docs/design.md` | サイト構成・テーマ構成・CPT/ACF 設計・API 連携・Docker 構成・フェーズ分割 |
| `docs/decisions.md` | 決定事項と、その理由（なぜ他の選択肢を採らなかったか） |

**実装前に必ず `docs/design.md` を読むこと。**

## コード品質の要件

実案件で提出できる品質を維持する。学習用だからと雑に書かない。

- WordPress Coding Standards に従う
- 出力は必ずエスケープする（`esc_html()` / `esc_attr()` / `esc_url()` / `wp_kses_post()`）
- 入力は必ずサニタイズする（`sanitize_text_field()` 等）
- フォーム・Ajax には nonce を必須とする
- DB アクセスは `WP_Query` / `$wpdb->prepare()` を使う。生 SQL の文字列連結は禁止
- テーマとプラグインの責務を分離する（表示 = テーマ / データ構造・機能 = プラグイン）
- 不要なプラグイン依存を避ける
- ハードコードを減らし、管理画面から編集できるようにする

## Git 管理の方針

**WordPress 本体・uploads・DB は Git 管理しない。** 管理するのは以下のみ。

- 自作テーマ `themes/pacchi-portfolio/`
- 自作プラグイン `plugins/portfolio-core/`
- `docker-compose.yml` / セットアップスクリプト
- ドキュメント

ACF のフィールド定義は Local JSON 機能でテーマ内に保存し、Git 管理する。

## 完了の定義

各フェーズ完了時に以下を確認してからコミットする。

- Docker 環境で表示・動作すること
- PHP エラー・WordPress の警告が出ていないこと
- PC（1440px）とモバイル（390px）でレイアウトが崩れないこと
- エスケープ・サニタイズ・nonce の漏れがないこと
