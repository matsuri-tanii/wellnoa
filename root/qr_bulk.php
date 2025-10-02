<?php
require_once __DIR__ . '/../secure/env.php';
require_once __DIR__.'/funcs.php'; adopt_incoming_code();

if (!defined('BASE_URL')) {
    define('BASE_URL', (isset($_SERVER['HTTPS'])?'https':'http') . '://' . $_SERVER['HTTP_HOST']);
}

$count = isset($_GET['n']) ? max(1, min(100, (int)$_GET['n'])) : 10; // デフォルト10、最大100

// 配布用：毎回ランダムな“配布gid”を作る（6桁数値・重複は実運用でチェック可）
function make_random_gid(): int {
    return random_int(100000, 999999);
}

$rows = [];
for ($i = 0; $i < $count; $i++) {
    $gid = make_random_gid();

    // （任意）ここで anonymous_users に事前登録したい場合：
    // $pdo = db_conn();
    // $stmt = $pdo->prepare("INSERT IGNORE INTO anonymous_users (anonymous_id, created_at) VALUES (?, NOW())");
    // $stmt->execute([$gid]);

    $target = BASE_URL . '/landing.php?code=' . rawurlencode((string)$gid);
    $qrUrl  = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=' . rawurlencode($target);
    $rows[] = ['gid'=>$gid, 'target'=>$target, 'qr'=>$qrUrl];
}
?>
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<title>配布用QRコード 一括生成</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  body{font-family:system-ui,-apple-system,sans-serif;padding:20px;}
  .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;}
  .card{border:1px solid #ddd;border-radius:8px;padding:12px;}
  .gid{font-weight:600;}
  .print-note{color:#666;margin-bottom:12px;}
  @media print {.controls{display:none;}}
</style>
</head>
<body>
  <h1>配布用QRコード 一括生成</h1>

  <form class="controls" method="get">
    <label>枚数:
      <input type="number" name="n" value="<?php echo (int)$count; ?>" min="1" max="100" style="width:80px">
    </label>
    <button type="submit">再生成</button>
    <button type="button" onclick="window.print()">印刷</button>
  </form>

  <p class="print-note">※ それぞれ異なる匿名ID（gid）が埋め込まれています。配布して読み取ってもらうだけでOKです。</p>

  <div class="grid">
    <?php foreach ($rows as $r): ?>
      <div class="card">
        <div class="gid">gid: <?php echo htmlspecialchars((string)$r['gid'], ENT_QUOTES, 'UTF-8'); ?></div>
        <img src="<?php echo htmlspecialchars($r['qr'], ENT_QUOTES, 'UTF-8'); ?>" alt="QR">
        <div style="font-size:12px;word-break:break-all;margin-top:8px;">
          <a href="<?php echo htmlspecialchars($r['target'], ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($r['target'], ENT_QUOTES, 'UTF-8'); ?>
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</body>
</html>