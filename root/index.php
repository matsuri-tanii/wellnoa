<?php
declare(strict_types=1);

$nav_active = 'home';

require_once __DIR__.'/funcs.php';
adopt_incoming_code();

$pdo = db_conn();
$uid = current_anon_user_id();
set_guest_cookie();

// ---------------------- 集計 ----------------------
$sql = "SELECT log_date, AVG(body_condition) AS avg_body, AVG(mental_condition) AS avg_mental
        FROM daily_logs WHERE anonymous_user_id = :uid
        GROUP BY log_date ORDER BY log_date";
$st = $pdo->prepare($sql); $st->execute([':uid'=>$uid]);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

$dates     = array_column($rows, 'log_date');
$avgBody   = array_map('floatval', array_column($rows, 'avg_body'));
$avgMental = array_map('floatval', array_column($rows, 'avg_mental'));

$st = $pdo->prepare("SELECT COUNT(*) FROM article_reads WHERE anonymous_user_id = :uid");
$st->execute([':uid'=>$uid]); $articleCount = (int)$st->fetchColumn();

$sql = "SELECT a.category, COUNT(*) AS cnt
        FROM article_reads ar JOIN articles a ON a.id = ar.article_id
        WHERE ar.anonymous_user_id = :uid GROUP BY a.category";
$st = $pdo->prepare($sql); $st->execute([':uid'=>$uid]);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
$categories     = array_column($rows, 'category');
$categoryCounts = array_map('intval', array_column($rows, 'cnt'));

$cheerCount = (int)$pdo->query("SELECT COUNT(*) FROM cheers WHERE target_type IN('daily','read')")->fetchColumn();

$st = $pdo->prepare("SELECT COUNT(*) FROM daily_logs WHERE anonymous_user_id = :uid");
$st->execute([':uid'=>$uid]); $dailyCount = (int)$st->fetchColumn();

$sql = "SELECT activity_type, COUNT(*) AS cnt
        FROM daily_logs WHERE anonymous_user_id = :uid GROUP BY activity_type";
$st = $pdo->prepare($sql); $st->execute([':uid'=>$uid]);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
$activities     = array_column($rows, 'activity_type');
$activityCounts = array_map('intval', array_column($rows, 'cnt'));

$cnt1 = (int)$pdo->query("SELECT COUNT(*) FROM daily_logs    WHERE anonymous_user_id = ".(int)$uid)->fetchColumn();
$cnt2 = (int)$pdo->query("SELECT COUNT(*) FROM article_reads WHERE anonymous_user_id = ".(int)$uid)->fetchColumn();
$totalUse = $cnt1 + $cnt2;
$showRegisterBanner = ($totalUse >= 5) && empty($_COOKIE['dismiss_reg']);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Wellnoa - あなたの小さな健康習慣</title>
  <link rel="stylesheet" href="css/reset.css">
  <link rel="stylesheet" href="css/variables.css">
  <link rel="stylesheet" href="css/base.css">
  <link rel="stylesheet" href="css/layout.css">
  <link rel="stylesheet" href="css/nav.css">
  <link rel="stylesheet" href="css/components.css">
  <link rel="stylesheet" href="css/forms.css">
  <link rel="stylesheet" href="css/notices.css">
  <link rel="stylesheet" href="css/utilities.css">
  <link rel="stylesheet" href="css/charts.css">
  <link rel="stylesheet" href="css/page-overrides.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <div class="layout">
    <?php require __DIR__.'/inc/header.php'; ?>
    <aside class="side-nav"><?php require __DIR__.'/inc/side_nav.php'; ?></aside>

    <main class="main">
      <?php require __DIR__.'/inc/notices.php'; ?>

      <?php if ($showRegisterBanner): ?>
        <div id="regBanner" class="notice info" style="margin:12px">
          <strong>もっと便利に使うには登録がおすすめです。</strong>
          <a href="register.php" class="btn btn-sm">登録する</a>
          <button type="button"
            onclick="document.getElementById('regBanner').style.display='none'; document.cookie='dismiss_reg=1; path=/; max-age=2592000'">
            閉じる
          </button>
        </div>
      <?php endif; ?>

      <div class="main-inner">

        <!-- ここから2カラム -->
        <div class="dashboard-grid">

          <!-- 左：グラフ群 -->
          <div class="chart-column">
            <div class="card">
              <div class="card-header">体の調子と心の調子の変化</div>
              <div class="card-body">
                <div class="chart-wrap"><canvas id="lineChart"></canvas></div>
              </div>
            </div>

            <div class="card">
              <div class="card-header">記事カテゴリの割合</div>
              <div class="card-body">
                <div class="chart-wrap"><canvas id="categoryChart"></canvas></div>
              </div>
            </div>

            <div class="card">
              <div class="card-header">行動の割合</div>
              <div class="card-body">
                <div class="chart-wrap"><canvas id="activityChart"></canvas></div>
              </div>
            </div>
          </div>

          <!-- 右：数字系（コンパクト） -->
          <div class="stats-column">
            <div class="stat-card">
              <div class="stat-label">読んだ記事</div>
              <div class="stat-value"><?= h((string)$articleCount) ?><span class="stat-unit">本</span></div>
            </div>
            <div class="stat-card">
              <div class="stat-label">応援された数</div>
              <div class="stat-value"><?= h((string)$cheerCount) ?><span class="stat-unit">回</span></div>
            </div>
            <div class="stat-card">
              <div class="stat-label">行動記録数</div>
              <div class="stat-value"><?= h((string)$dailyCount) ?><span class="stat-unit">件</span></div>
            </div>

            <div class="quick-links">
              <a class="btn btn-outline" href="qr.php">自分の匿名ID用QR</a>
              <a class="btn btn-outline" href="qr_bulk.php">配布用QRまとめ</a>
            </div>
          </div>

        </div>
        <!-- 2カラムここまで -->

      </div>
    </main>

    <footer class="app-footer"><?php require __DIR__.'/inc/bottom_nav.php'; ?></footer>
  </div>

  <script>
  // 折れ線
  new Chart(document.getElementById('lineChart'), {
    type: 'line',
    data: {
      labels: <?= json_encode($dates, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,
      datasets: [
        { label: '体の調子', data: <?= json_encode($avgBody) ?>,  tension:.25 },
        { label: '心の調子', data: <?= json_encode($avgMental) ?>, tension:.25 }
      ]
    },
    options: {
      responsive:true, maintainAspectRatio:false,
      layout:{padding:{left:8,right:8,top:8,bottom:8}},
      scales:{ y:{ suggestedMin:0, suggestedMax:100 } }
    }
  });

  // 円
  new Chart(document.getElementById('categoryChart'), {
    type:'pie',
    data:{ labels: <?= json_encode($categories, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,
           datasets:[{ data: <?= json_encode($categoryCounts) ?> }] },
    options:{ responsive:true, maintainAspectRatio:false, layout:{padding:12} }
  });

  // ドーナツ
  new Chart(document.getElementById('activityChart'), {
    type:'doughnut',
    data:{ labels: <?= json_encode($activities, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,
           datasets:[{ data: <?= json_encode($activityCounts) ?> }] },
    options:{ responsive:true, maintainAspectRatio:false, layout:{padding:12}, cutout:'55%' }
  });
  </script>
  <script src="js/ui-nav.js" defer></script>
</body>
</html>