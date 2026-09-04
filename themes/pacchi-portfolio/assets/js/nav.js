/**
 * ページ内リンクまわりの挙動。
 *
 * Next.js 版の NavBehavior.tsx を素の JavaScript へ移植したもの。
 *   1. 折りたたまれたセクションへ移動したとき、そのセクションを開く
 *   2. モバイルメニュー内のリンクを押したときにメニューを閉じる
 *
 * 開閉そのものは details/summary のネイティブ機能で動くため、
 * この JS が読み込まれなくてもサイトは壊れない（Progressive Enhancement）。
 */
(function () {
  'use strict';

  /**
   * URL のハッシュが指すセクションが閉じていれば開く。
   */
  function openTargetSection() {
    var id = decodeURIComponent(window.location.hash.slice(1));
    if (!id) {
      return;
    }

    var target = document.getElementById(id);
    if (!target) {
      return;
    }

    var details = target.querySelector('details[data-section]');
    if (details && !details.open) {
      details.open = true;
    }
  }

  /**
   * モバイルメニュー内のリンクが押されたらメニューを閉じる。
   * イベント委譲でドキュメント全体を監視する。
   */
  function closeMobileNav(event) {
    var link = event.target.closest('a');
    if (!link) {
      return;
    }

    var menu = link.closest('details[data-mobile-nav]');
    if (menu) {
      menu.open = false;
    }
  }

  openTargetSection();
  window.addEventListener('hashchange', openTargetSection);
  document.addEventListener('click', closeMobileNav);
})();
