/**
 * 支払いリセット計画 LP ｜ フォーム受信用 Google Apps Script
 * リードをGoogleスプレッドシートに自動保存し、必要なら通知メールも送る。
 *
 * ─── セットアップ手順 ───────────────────────────────
 * 1. Googleスプレッドシートを新規作成（名前は任意）
 * 2. 上部メニュー「拡張機能」→「Apps Script」を開く
 * 3. エディタの中身を全部消して、この内容を丸ごと貼り付けて保存
 * 4. 通知メールが欲しい場合、下の NOTIFY_EMAIL に送信先アドレスを入れる（不要なら空のまま）
 * 5. 右上「デプロイ」→「新しいデプロイ」→ 種類「ウェブアプリ」
 *      ・説明：任意
 *      ・次のユーザーとして実行：自分
 *      ・アクセスできるユーザー：「全員」
 *    →「デプロイ」を押し、表示される「ウェブアプリのURL」をコピー
 * 6. index.html の JS にある  var GAS_ENDPOINT = '';  にそのURLを貼る
 *      例： var GAS_ENDPOINT = 'https://script.google.com/macros/s/AKfy.../exec';
 * 7. LPのフォームからテスト送信 → スプレッドシートに1行増えればOK
 * ──────────────────────────────────────────────
 */

var NOTIFY_EMAIL = ''; // 例: 'sato@urakata.biz'（空なら通知メールなし）

function doPost(e) {
  var lock = LockService.getScriptLock();
  lock.tryLock(10000);
  try {
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var sheet = ss.getSheetByName('リード') || ss.insertSheet('リード');

    if (sheet.getLastRow() === 0) {
      sheet.appendRow(['受付日時', 'お名前', '電話番号', 'メール', '借入残高', '過去の返済状況', '相談方法', '同意']);
    }

    var p = (e && e.parameter) ? e.parameter : {};
    sheet.appendRow([
      new Date(),
      p.name || '',
      p.tel || '',
      p.email || '',
      p.balance || '',
      p.credit || '',
      p.method || '',
      p.agree ? '同意' : ''
    ]);

    if (NOTIFY_EMAIL) {
      MailApp.sendEmail(
        NOTIFY_EMAIL,
        '【支払いリセット計画】新規の無料相談申込',
        'お名前: ' + (p.name || '') + '\n' +
        '電話: ' + (p.tel || '') + '\n' +
        'メール: ' + (p.email || '') + '\n' +
        '借入残高: ' + (p.balance || '') + '\n' +
        '過去の返済状況: ' + (p.credit || '') + '\n' +
        '相談方法: ' + (p.method || '')
      );
    }

    return ContentService
      .createTextOutput(JSON.stringify({ result: 'ok' }))
      .setMimeType(ContentService.MimeType.JSON);
  } finally {
    lock.releaseLock();
  }
}
