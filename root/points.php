<?php
declare(strict_types=1);

require_once __DIR__.'/funcs.php';
adopt_incoming_code();
require_once __DIR__.'/points_lib.php';

$pdo = db_conn();
[$uid, $code] = ensure_anon_identity($pdo);
if (!$uid) { exit('匿名ユーザーIDがありません'); }

$info = calc_points_for_user($pdo, $uid);

$total = (int)$info['total_points'];

function scene_key(int $pt): string {
  if ($pt >= 160) return 'rainbow';
  if ($pt >= 100) return 'observatory';
  if ($pt >=  60) return 'hill';
  if ($pt >=  30) return 'forest';
  if ($pt >=  10) return 'brook';
  return 'field';
}
$scene = scene_key($total);

$level = $info['level'];
$prog  = $info['progress'];
$ratioPercent = (int)floor($prog['ratio'] * 100);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>Wellnoa - 成長を見る</title>
  <link rel="stylesheet" href="css/reset.css">
  <link rel="stylesheet" href="css/variables.css">
  <link rel="stylesheet" href="css/base.css">
  <link rel="stylesheet" href="css/layout.css">
  <link rel="stylesheet" href="css/nav.css">
  <link rel="stylesheet" href="css/components.css">
  <link rel="stylesheet" href="css/forms.css">
  <link rel="stylesheet" href="css/notices.css">
  <link rel="stylesheet" href="css/utilities.css">
  <link rel="stylesheet" href="css/scenes.css">
  <link rel="stylesheet" href="css/page-overrides.css">
</head>
<body>
  <div class="layout">
    <!-- 1) 常時表示ヘッダー 共通お知らせ（未登録警告・フラッシュ・登録誘導） -->
    <?php require __DIR__.'/inc/header.php'; ?>
    <!-- 2) サイドナビ（PC/タブ横のみCSSで表示） -->
    <aside class="side-nav">
      <?php require __DIR__.'/inc/side_nav.php'; ?>
    </aside>
    <!-- 3) メイン -->
    <main class="main">
      <?php require __DIR__.'/inc/notices.php'; ?>
    
      <h1>成長の記録</h1>
      <div>あなたの景色：<strong><?= h($level['label']) ?></strong></div>
    
    <!-- ========== Growth Scene (花鳥風月モチーフ) ========== -->
      <div class="scene-wrap" data-scene="<?= h($scene) ?>">
        <svg class="scene" viewBox="0 0 600 260" role="img" aria-label="成長の情景">
          <!-- 空・地面 -->
          <defs>
            <linearGradient id="skyGrad" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stop-color="#e9f5ff"/>
              <stop offset="100%" stop-color="#f7fcff"/>
            </linearGradient>
            <linearGradient id="duskGrad" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stop-color="#2b2e4a"/>
              <stop offset="100%" stop-color="#1b1e34"/>
            </linearGradient>
            <linearGradient id="mint" x1="0" y1="0" x2="1" y2="0">
              <stop offset="0%" stop-color="#76c7b0"/>
              <stop offset="100%" stop-color="#99dbc7"/>
            </linearGradient>
          </defs>

          <!-- 空／夜空（observatory以上で夜空） -->
          <rect class="sky" x="0" y="0" width="600" height="200" fill="url(#skyGrad)"/>
          <rect class="sky-night" x="0" y="0" width="600" height="200" fill="url(#duskGrad)" opacity="0"/>

          <!-- 地面 -->
          <rect x="0" y="200" width="600" height="60" fill="#cfeee4"/>

          <!-- 小川（brook以上） -->
          <path class="brook" d="M0,210 C120,205 180,220 300,215 C420,210 480,225 600,220 L600,260 L0,260 Z"
                fill="#bde7ff" opacity="0"/>

          <!-- 木漏れ日（forest以上）：やわらかい楕円の光 -->
          <g class="komorebi" opacity="0">
            <ellipse cx="180" cy="120" rx="110" ry="40" fill="#fff7b3" opacity="0.35"/>
            <ellipse cx="260" cy="90"  rx="70"  ry="26" fill="#fff2a6" opacity="0.35"/>
            <ellipse cx="340" cy="130" rx="90"  ry="30" fill="#fff7b3" opacity="0.25"/>
          </g>

          <!-- 見晴らしの丘（hill以上）：遠景の丘 -->
          <g class="hills" opacity="0">
            <path d="M0,170 C80,140 140,160 220,150 C300,140 360,160 440,150 C520,140 560,160 600,150 L600,200 L0,200 Z"
                  fill="#dff6ef"/>
          </g>

          <!-- 夜空の星（observatory以上） -->
          <g class="stars" opacity="0">
            <circle cx="100" cy="60" r="1.8" fill="#fff"/>
            <circle cx="160" cy="40" r="1.2" fill="#fff"/>
            <circle cx="220" cy="70" r="1.5" fill="#fff"/>
            <circle cx="300" cy="50" r="2.0" fill="#fff"/>
            <circle cx="380" cy="80" r="1.3" fill="#fff"/>
            <circle cx="460" cy="35" r="1.6" fill="#fff"/>
            <circle cx="520" cy="60" r="1.1" fill="#fff"/>
          </g>

          <!-- 虹（rainbow以上） -->
          <g class="rainbow" opacity="0">
            <path d="M100 200 A150 150 0 0 1 500 200" stroke="#ff6b6b" stroke-width="10" fill="none"/>
            <path d="M120 200 A130 130 0 0 1 480 200" stroke="#ffd166" stroke-width="10" fill="none"/>
            <path d="M140 200 A110 110 0 0 1 460 200" stroke="#06d6a0" stroke-width="10" fill="none"/>
            <path d="M160 200 A90  90  0 0 1 440 200" stroke="#118ab2" stroke-width="10" fill="none"/>
            <path d="M180 200 A70  70  0 0 1 420 200" stroke="#9b5de5" stroke-width="10" fill="none"/>
          </g>

          <!-- 芽（field〜）：にょきっと出る -->
          <g class="sprout">
            <line x1="300" y1="200" x2="300" y2="170" stroke="url(#mint)" stroke-width="4" stroke-linecap="round"/>
            <ellipse cx="292" cy="168" rx="10" ry="6" fill="#76c7b0" transform="rotate(-20 292 168)"/>
            <ellipse cx="308" cy="165" rx="11" ry="7" fill="#76c7b0" transform="rotate(20 308 165)"/>
          </g>

          <!-- 蔓（forest〜）：左へ伸びる -->
          <path class="vine" d="M300,170 C260,150 240,140 220,130" stroke="#76c7b0" stroke-width="3" fill="none"
                stroke-linecap="round" stroke-dasharray="120" stroke-dashoffset="120"/>

          <!-- 花（hill〜）：咲く -->
          <g class="flower" transform="translate(220 130)" opacity="0">
            <circle r="3" fill="#ffd166"/>
            <g fill="#e9a8ff">
              <ellipse cx="0" cy="-9" rx="4" ry="8"/>
              <ellipse cx="8" cy="-3" rx="4" ry="8" transform="rotate(60)"/>
              <ellipse cx="8" cy="3"  rx="4" ry="8" transform="rotate(120)"/>
              <ellipse cx="0" cy="9"  rx="4" ry="8" transform="rotate(180)"/>
              <ellipse cx="-8" cy="3" rx="4" ry="8" transform="rotate(240)"/>
              <ellipse cx="-8" cy="-3" rx="4" ry="8" transform="rotate(300)"/>
            </g>
          </g>
        </svg>
      </div>

      <div class="card">
        <div class="level">
          累積ポイント：<span class="big"><?= number_format((int)$info['total_points']) ?></span> pt
        </div>

        <div class="bar">
          <div class="fill" style="width: <?= $ratioPercent ?>%;"></div>
        </div>

        <div class="meta">
          <div>現在の段階: <?= (int)$prog['current_threshold'] ?> pt 〜</div>
          <div>
            <?php if (!empty($prog['next_threshold'])): ?>
              次の段階まで：<?= max(0, (int)$prog['to_next']) ?> pt
            <?php else: ?>
              最高段階に到達しています 🎉
            <?php endif; ?>
          </div>
        </div>

        <div class="nums">
          <div class="box">
            <div>行動ポイント</div>
            <div class="big"><?= number_format((int)$info['activity_points']) ?></div>
          </div>
          <div class="box">
            <div>記事ポイント（読了 <?= (int)$info['read_count'] ?> 件）</div>
            <div class="big"><?= number_format((int)$info['article_points']) ?></div>
          </div>
        </div>

        <div class="footer">
          <a class="btn" href="record_hub.php">記録を追加する</a>
          <a class="btn" href="articles.php">記事を読む</a>
          <a class="btn" href="index.php">ホームへ</a>
        </div>
      </div>
    </main>

    <!-- 4) ボトムナビ（スマホ/タブ縦） -->
    <footer class="app-footer">
      <?php require __DIR__.'/inc/bottom_nav.php'; ?>
    </footer>
  </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const wrap = document.querySelector('.scene-wrap');
  if (!wrap || !('IntersectionObserver' in window)) return;
  const obs = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        wrap.classList.add('inview'); // 必要ならこのクラスで開始制御もできる
        obs.disconnect();
      }
    });
  }, { threshold: 0.4 });
  obs.observe(wrap);
});
</script>

</body>
</html>