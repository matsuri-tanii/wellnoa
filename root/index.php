<?php
include 'anon_session.php';
include 'funcs.php';

$pdo = db_conn();
$uid = current_anon_user_id();

// ---------------------- データ集計 ----------------------

// 体調・心の調子（折れ線グラフ用）
$sql = "SELECT log_date, 
               AVG(body_condition) AS avg_body, 
               AVG(mental_condition) AS avg_mental
        FROM daily_logs
        WHERE anonymous_user_id = :uid
        GROUP BY log_date
        ORDER BY log_date";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$dates = array_column($rows, 'log_date');
$avgBody = array_column($rows, 'avg_body');
$avgMental = array_column($rows, 'avg_mental');

// 読了記事数
$sql = "SELECT COUNT(*) FROM article_reads WHERE anonymous_user_id = :uid";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
$stmt->execute();
$articleCount = $stmt->fetchColumn();

// 読んだ記事カテゴリ割合
$sql = "SELECT a.category, COUNT(*) AS cnt
        FROM article_reads ar
        JOIN articles a ON a.id = ar.article_id
        WHERE ar.anonymous_user_id = :uid
        GROUP BY a.category";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$categories = array_column($rows, 'category');
$categoryCounts = array_column($rows, 'cnt');

// 応援された総数
$sql = "SELECT COUNT(*) FROM cheers WHERE target_type IN('daily','read')";
$cheerCount = $pdo->query($sql)->fetchColumn();

// 行動記録数
$sql = "SELECT COUNT(*) FROM daily_logs WHERE anonymous_user_id = :uid";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
$stmt->execute();
$dailyCount = $stmt->fetchColumn();

// 行動割合
$sql = "SELECT activity_type, COUNT(*) AS cnt
        FROM daily_logs
        WHERE anonymous_user_id = :uid
        GROUP BY activity_type";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$activities = array_column($rows, 'activity_type');
$activityCounts = array_column($rows, 'cnt');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Wellnoa - あなたの小さな健康習慣</title>
  <link rel="stylesheet" href="css/reset.css">
  <link rel="stylesheet" href="css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<header>
  <img src="images/title_logo.png" alt="アプリロゴ画像" width="380">
  <p class="tagline">あなたの健康へ、ちいさな一歩を。a</p>
</header>

<div class="container">

  <div class="card">
    <div class="card-header">体の調子と心の調子の変化</div>
    <div class="card-body">
      <canvas id="lineChart"></canvas>
    </div>
  </div>

  <div class="card">
    <div class="card-header">今まで読んだ記事の数</div>
    <div class="card-body">
      <?= h($articleCount) ?> 本
    </div>
  </div>

  <div class="card">
    <div class="card-header">記事カテゴリの割合</div>
    <div class="card-body">
      <canvas id="categoryChart"></canvas>
    </div>
  </div>

  <div class="card">
    <div class="card-header">応援された総数</div>
    <div class="card-body">
      <?= h($cheerCount) ?> 回
    </div>
  </div>

  <div class="card">
    <div class="card-header">行動記録数</div>
    <div class="card-body">
      <?= h($dailyCount) ?> 件
    </div>
  </div>

  <div class="card">
    <div class="card-header">行動の割合</div>
    <div class="card-body">
      <canvas id="activityChart"></canvas>
    </div>
  </div>

</div>

<footer>
  <div class="footerMenuList">
    <a href="input.php" class="btn"><img src="images/memo.png" alt="入力" width="60"></a>
    <a href="articles.php" class="btn"><img src="images/book.png" alt="記事" width="60"></a>
    <a href="points.php" class="btn"><img src="images/plants.png" alt="成長" width="60"></a>
    <a href="read_all.php" class="btn"><img src="images/ouen.png" alt="応援" width="60"></a>
    <a href="read.php" class="btn"><img src="images/calender.png" alt="カレンダー" width="60"></a>
  </div>
</footer>

<script>
new Chart(document.getElementById('lineChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode($dates) ?>,
    datasets: [
      { label: '体の調子', data: <?= json_encode($avgBody) ?>, borderColor: 'blue' },
      { label: '心の調子', data: <?= json_encode($avgMental) ?>, borderColor: 'red' }
    ]
  }
});

new Chart(document.getElementById('categoryChart'), {
  type: 'pie',
  data: {
    labels: <?= json_encode($categories) ?>,
    datasets: [{ data: <?= json_encode($categoryCounts) ?> }]
  }
});

new Chart(document.getElementById('activityChart'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode($activities) ?>,
    datasets: [{ data: <?= json_encode($activityCounts) ?> }]
  }
});
</script>

</body>
</html>