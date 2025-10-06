<?php
declare(strict_types=1);
session_start();
require_once __DIR__.'/funcs.php';

if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf_fp'] ?? '')) {
  http_response_code(400);
  exit('invalid csrf');
}

$email = trim((string)($_POST['email'] ?? ''));
$normalized = mb_strtolower($email);

$pdo = db_conn();

// ユーザー（存在しなくても応答は統一）
$st = $pdo->prepare("SELECT id, email FROM users WHERE LOWER(email) = :email LIMIT 1");
$st->execute([':email' => $normalized]);
$user = $st->fetch(PDO::FETCH_ASSOC);

// トークン生成（常に生成はするが、保存と送信は存在時のみ）
$token = bin2hex(random_bytes(32));    // 生トークン（URL用）
$hash  = hash('sha256', $token);       // 保存はハッシュ
$exp   = date('Y-m-d H:i:s', strtotime('+1 hour'));

if ($user) {
  $upd = $pdo->prepare("
    UPDATE users
      SET reset_token_hash = :h, reset_expires = :e
    WHERE id = :id
  ");
  $upd->execute([':h'=>$hash, ':e'=>$exp, ':id'=>(int)$user['id']]);

  // メール送信
  $base = defined('BASE_URL') ? BASE_URL : '';
  $link = rtrim($base, '/').'/reset_password.php?token='.urlencode($token);

  $subject = '【Wellnoa】パスワード再設定のご案内';
  $body = "以下のリンクから1時間以内にパスワードを再設定してください。\n\n{$link}\n\n"
        . "※このメールに心当たりがない場合は破棄してください。";
  $headers = "Content-Type: text/plain; charset=UTF-8\r\n"
           . "From: Wellnoa <no-reply@wellnoa.example>\r\n"; // ←適切なFromドメインに

  if (function_exists('mb_send_mail')) {
    @mb_language("Japanese");
    @mb_internal_encoding("UTF-8");
    @mb_send_mail($user['email'], $subject, $body, $headers);
  } else {
    @mail($user['email'], $subject, $body, $headers);
  }
}

// 統一メッセージ（存在有無を漏らさない）
if (function_exists('push_flash')) {
  pop_flash('ご入力のメールアドレス宛に再設定用のリンクを送信しました。メールをご確認ください。', 'success');
}

// ★ここでHTMLを出さない（notices.phpも読み込まない）
header('Location: forgot_password.php');
exit;