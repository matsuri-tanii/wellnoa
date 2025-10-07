<?php
require_once __DIR__ . '/../secure/env.php';
require_once __DIR__.'/funcs.php'; adopt_incoming_code();

$gid = isset($_GET['gid']) ? (string)$_GET['gid'] : current_anon_user_id();
if (!defined('BASE_URL')) {
    define('BASE_URL', (isset($_SERVER['HTTPS'])?'https':'http') . '://' . $_SERVER['HTTP_HOST']);
}

$target = BASE_URL . '/landing.php?code=' . rawurlencode((string)$gid);

// 無料のQR生成サービス（png直リンク）
$qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . rawurlencode($target);
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>Wellnoa - 自分用QRコード</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="css/reset.css">
  <link rel="stylesheet" href="css/variables.css">
  <link rel="stylesheet" href="css/base.css">
  <link rel="stylesheet" href="css/layout.css">
  <link rel="stylesheet" href="css/nav.css">
  <link rel="stylesheet" href="css/components.css">
  <link rel="stylesheet" href="css/forms.css">
  <link rel="stylesheet" href="css/notices.css">
  <link rel="stylesheet" href="css/utilities.css">
  <link rel="stylesheet" href="css/page-overrides.css">
</head>
<body>
  <h1>自分用QRコード</h1>
  <p>このQRを別端末で読み取ると、あなたの匿名ID（gid）付きで入力ページが開きます。</p>

  <p><img src="<?php echo htmlspecialchars($qrUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="QR"></p>

  <p>リンク：<a href="<?php echo htmlspecialchars($target, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($target, ENT_QUOTES, 'UTF-8'); ?></a></p>
</body>
</html>