<?php
/**
 * 支払いリセット計画 LP ｜ フォーム受信（メール送信）
 * 送信先: sato@urakata.biz / お名前.com RS（mb_send_mail・SMTP設定不要）
 * 注意: .htaccess に PHPハンドラ指定は書かない（ソース露出事故防止）。
 */
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
mb_language('Japanese');
mb_internal_encoding('UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => '不正なアクセスです'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ハニーポット：botが company を埋めたら、成功を偽装して静かに破棄
if (!empty($_POST['company'])) {
    echo json_encode(['status' => 'ok'], JSON_UNESCAPED_UNICODE);
    exit;
}

function v($k) { return isset($_POST[$k]) ? trim((string)$_POST[$k]) : ''; }

$name    = v('name');
$tel     = v('tel');
$email   = v('email');
$balance = v('balance');
$lenders = v('lenders');
$credit  = v('credit');
$source  = v('source');
$method  = v('method');
$agree   = v('agree');

// 必須チェック
$miss = [];
if ($name === '')    $miss[] = 'お名前';
if ($tel === '')     $miss[] = '電話番号';
if ($balance === '') $miss[] = '借入残高';
if ($credit === '')  $miss[] = '過去の返済状況';
if ($agree === '')   $miss[] = '個人情報の同意';
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $miss[] = 'メールアドレスの形式';

if ($miss) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => '入力に不備があります：' . implode('、', $miss)], JSON_UNESCAPED_UNICODE);
    exit;
}

$to      = 'sato@urakata.biz';
$subject = '【支払いリセット計画】無料相談の新規申込';

$body  = "支払いリセット計画 LP から新しい申込がありました。\n\n";
$body .= "■ お名前　　　：{$name}\n";
$body .= "■ 電話番号　　：{$tel}\n";
$body .= "■ メール　　　：{$email}\n";
$body .= "■ 借入残高　　：{$balance}\n";
$body .= "■ 借入社数　　：{$lenders}\n";
$body .= "■ 過去の返済　：{$credit}\n";
$body .= "■ 相談方法　　：{$method}\n";
$body .= "■ きっかけ　　：{$source}\n";
$body .= "■ 個人情報同意：" . ($agree !== '' ? 'あり' : 'なし') . "\n";
$body .= "\n";
$body .= "受付日時：" . date('Y-m-d H:i:s') . "\n";
$body .= "IPアドレス：" . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '') . "\n";

// From は実在アカウントを指定（SPF通過のため）。ASCIIのみでヘッダー安全。
$headers = 'From: sato@urakata.biz';
if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $headers .= "\r\nReply-To: " . $email; // 申込者へ返信しやすく
}

$ok = mb_send_mail($to, $subject, $body, $headers);

if ($ok) {
    echo json_encode(['status' => 'ok'], JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => '送信に失敗しました。時間をおいて再度お試しください。'], JSON_UNESCAPED_UNICODE);
}
