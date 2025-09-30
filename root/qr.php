<?php
require_once __DIR__ . '/../secure/env.php';
require_once __DIR__.'/funcs.php'; adopt_incoming_code();

$gid = isset($_GET['gid']) ? (string)$_GET['gid'] : current_anon_user_id();
if (!defined('BASE_URL')) {
    define('BASE_URL', (isset($_SERVER['HTTPS'])?'https':'http') . '://' . $_SERVER['HTTP_HOST']);
}

// チュートリアルや初回導線に飛ばしたい場合：/input.php?gid=... を基本に
$target = BASE_URL . '/input.php?gid=' . rawurlencode((string)$gid);

// 無料のQR生成サービス（png直リンク）
$qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . rawurlencode($target);
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<title>自分用QRコード</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>body{font-family:system-ui,-apple-system,sans-serif;padding:24px;}img{display:block;}</style>
</head>
<body>
  <h1>自分用QRコード</h1>
  <p>このQRを別端末で読み取ると、あなたの匿名ID（gid）付きで入力ページが開きます。</p>

  <p><img src="<?php echo htmlspecialchars($qrUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="QR"></p>

  <p>リンク：<a href="<?php echo htmlspecialchars($target, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($target, ENT_QUOTES, 'UTF-8'); ?></a></p>
</body>
</html>