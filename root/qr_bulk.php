<?php
// qr_bulk.php — 完成版
require_once __DIR__ . '/../secure/env.php';
require_once __DIR__ . '/funcs.php'; adopt_incoming_code();

if (!defined('BASE_URL')) {
  define('BASE_URL', (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']);
}

// -------- パラメータ --------
// デフォルト12、最大100
$count = isset($_GET['n']) ? max(1, min(100, (int)$_GET['n'])) : 12;
// 情報の表示ON/OFF（必要に応じて切替）
$show_gid = isset($_GET['show_gid']) ? (bool)(int)$_GET['show_gid'] : true;
$show_url = isset($_GET['show_url']) ? (bool)(int)$_GET['show_url'] : false;

// 7桁の配布gidを生成（重複チェックは運用で調整）
function make_random_gid(): int { return random_int(1_000_000, 9_999_999); }

// -------- データ生成 --------
$rows = [];
for ($i = 0; $i < $count; $i++) {
  $gid    = make_random_gid();
  $target = BASE_URL . '/landing.php?code=' . rawurlencode((string)$gid);
  // SVG + 高めの誤り訂正 + 余白ゼロ（小さく印刷しても潰れにくい）
  $qrUrl  = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&format=svg&ecc=Q&margin=0&data='
          . rawurlencode($target);
  $rows[] = ['gid' => $gid, 'target' => $target, 'qr' => $qrUrl];
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>Wellnoa QR配布カード（印刷用）</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <style>
    /* 画面（プレビュー＆操作） */
    :root { color-scheme: light; }
    body {
      font-family: "Helvetica Neue", Arial, "Noto Sans JP", sans-serif;
      background:#f7f7f7; padding: 20px; color:#333;
    }
    .controls {
      display:flex; gap:10px; align-items:center; flex-wrap:wrap;
      margin-bottom: 16px; background:#fff; padding:10px 12px; border:1px solid #eee; border-radius:10px;
    }
    .controls label { display:inline-flex; align-items:center; gap:6px; }
    .preview-note { color:#666; margin: 8px 0 14px; }

    /* 印刷レイアウト（A4縦固定） */
    @media print {
      @page { size: A4 portrait; margin: 8mm; }
      body { padding: 0; background: #fff; }

      /* 画面用のUIだけ消す */
      .controls, .preview-note { display: none !important; }

      /* 念のため明示的に表示 */
      .screen-preview { display: block !important; }

      html {
        -webkit-print-color-adjust: exact; /* Safari/Chrome系 */
        print-color-adjust: exact;         /* 仕様の標準プロパティ */
      }

      .sheet { break-after: page; }           /* 旧ブラウザ/UA対策に以下も併記可 */
      .sheet:last-child { break-after: auto; } /* 最終ページでの余計な白紙を防ぐ */
      /* 互換指定（必要なら） */
      .sheet { page-break-after: always; }
      .sheet:last-child { page-break-after: auto; }
    }

    /* ページ（12枚/ページ） */
    .sheet {
      /* A4内寸：幅 210-16=194mm / 高さ 297-16=281mm */
      /* 3列×4行、gap 4mm →  カラム幅 = (194 - 8) / 3 = 62mm / 行高 = (281 - 12) / 4 ≒ 67.25mm */
      display: grid;
      grid-template-columns: repeat(3, 62mm);
      grid-auto-rows: 66mm; /* 余裕を持たせて 66mm（隙間 281 - (66*4) - 12 = 11mm 程度） */
      gap: 4mm;
      margin-bottom: 8mm; /* プレビュー時にページ間の余白感 */
    }

    /* カード（1枚） */
    .card {
      border: 1px solid #666;                 /* モノクロ印刷でも線が出る */
      border-radius: 4px;
      background: #fff;
      padding: 4mm;
      box-sizing: border-box;
      display: grid;
      grid-template-rows: auto auto 1fr auto; /* title / desc / QR / note */
      align-items: start;
      text-align: center;
      position: relative;
      break-inside: avoid;
    }

    .title { font-weight: 800; font-size: 11pt; color:#000; margin: 0 0 1mm; }
    .desc  { font-size: 8.5pt; color:#111; margin: 0 0 2mm; line-height: 1.35; }

    .card img {
      width: 32mm; height: 32mm;  /* 物理サイズで指定 */
      object-fit: contain; margin: 0 auto;
      display: block;
    }

    .note {
      font-size: 8pt; color:#111; margin-top: 1mm; line-height: 1.35;
      overflow-wrap: anywhere;
    }

    /* 右下の小さなgid（必要ならON） */
    .gid {
      position: absolute; right: 3mm; bottom: 2mm;
      font-size: 7pt; color:#111; opacity: .75;
    }

    /* 画面プレビュー用（任意） */
    .screen-preview .sheet { background: #fafafa; }
  </style>
</head>
<body>

  <!-- 画面操作 -->
  <form class="controls" method="get">
    <label>枚数:
      <input type="number" name="n" value="<?= (int)$count ?>" min="1" max="100" style="width:90px">
    </label>
    <label><input type="checkbox" name="show_gid" value="1" <?= $show_gid ? 'checked' : '' ?>> gidを表示</label>
    <label><input type="checkbox" name="show_url" value="1" <?= $show_url ? 'checked' : '' ?>> URLを表示</label>
    <button type="submit">再生成</button>
    <button type="button" onclick="window.print()">印刷</button>
  </form>

  <p class="preview-note">A4縦／1ページ12枚（3×4）。印刷時はこの操作パネルや背景は出ません。</p>

  <!-- 12枚ごとに自動で改ページ -->
  <div class="screen-preview">
    <?php foreach (array_chunk($rows, 12) as $page): ?>
      <div class="sheet">
        <?php foreach ($page as $r): ?>
          <div class="card">
            <div class="title">Wellnoa</div>
            <div class="desc">匿名で気軽に健康習慣を記録<br>登録なしですぐ始められます</div>
            <img src="<?= htmlspecialchars($r['qr'], ENT_QUOTES, 'UTF-8') ?>" alt="QRコード">
            <div class="note">
              QRを読み取ってスタート
              <?php if ($show_url): ?><br><small><?= htmlspecialchars($r['target'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
            </div>
            <?php if ($show_gid): ?>
              <div class="gid">gid: <?= htmlspecialchars((string)$r['gid'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>

</body>
</html>