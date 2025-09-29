<?php
require_once __DIR__ . '/../secure/anon_bootstrap.php';

$gid = $CURRENT_GID; // 配布者自身のgidでもOK、または毎回 new でもOK
// 「受け取り側がこのURLを読めば cookie に g= が保存される」着地点を作る
$landing = 'https://wellnoa.sakura.ne.jp/tutorial.php?g='.$gid;

// Google Chart API でQR生成
$qr = 'https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl='.rawurlencode($landing);
?>
<!doctype html><meta charset="utf-8">
<h1>匿名QR（1枚）</h1>
<p>このQRを配布すると、読み取った端末に匿名IDが保存されます。</p>
<p><img src="<?= $qr ?>" alt="QR" width="300" height="300"></p>
<p><a href="<?= htmlspecialchars($landing, ENT_QUOTES) ?>" target="_blank">着地URLをテストで開く</a></p>