<?php
require_once __DIR__ . '/../secure/anon_bootstrap.php';

$count = isset($_GET['n']) && ctype_digit($_GET['n']) ? min(50, (int)$_GET['n']) : 10;
$list = [];
for ($i=0; $i<$count; $i++) {
    $gid = random_int(100000, 999999);
    $landing = 'https://wellnoa.sakura.ne.jp/tutorial.php?g='.$gid;
    $qr = 'https://chart.googleapis.com/chart?cht=qr&chs=240x240&chl='.rawurlencode($landing);
    $list[] = ['gid'=>$gid,'landing'=>$landing,'qr'=>$qr];
}
?>
<!doctype html><meta charset="utf-8">
<h1>匿名QR（まとめ発行 <?= $count ?>件）</h1>
<p>院内・窓口などで配布者の画面にまとめて表示 → 来訪者に読み取ってもらう想定です。</p>
<div style="display:grid;grid-template-columns:repeat(auto-fill,240px);gap:16px;">
<?php foreach($list as $row): ?>
  <div style="border:1px solid #ddd;padding:8px;border-radius:8px;text-align:center;">
    <img src="<?= $row['qr'] ?>" width="240" height="240" alt="QR">
    <div style="font:12px/1.6 system-ui;margin-top:6px;word-break:break-all;">
      GID: <?= (int)$row['gid'] ?><br>
      <a href="<?= htmlspecialchars($row['landing'], ENT_QUOTES) ?>" target="_blank">着地URL</a>
    </div>
  </div>
<?php endforeach; ?>
</div>