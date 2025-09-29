<?php
$envCandidates = [
  __DIR__ . '/../secure/env.php', // 本番（サーバー）
  __DIR__ . '/env.php',     // ローカル（XAMPP）
];
require_once __DIR__ . '/funcs.php';

$loaded = false;
foreach ($envCandidates as $p) {
  if (is_file($p)) { require_once $p; $loaded = true; break; }
}
if (!$loaded) {
  http_response_code(500);
  echo "Config file not found. Looking for:\n" . implode("\n", $envCandidates);
  exit;
}

$pdo = db_conn();
$uid = current_anon_user_id();

$gid = current_guest_id();
if (!$gid) {
  $gid = ensure_anon_user($pdo, null);
  set_guest_cookie($gid);
}


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

// ざっくり利用回数（例：日記+記事閲覧）
$cnt1 = (int)$pdo->query("SELECT COUNT(*) FROM daily_logs WHERE anonymous_user_id = ".(int)$gid)->fetchColumn();
$cnt2 = (int)$pdo->query("SELECT COUNT(*) FROM article_reads WHERE anonymous_user_id = ".(int)$gid)->fetchColumn();
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
  <link rel="stylesheet" href="css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<header>
  <img src="images/title_logo.png" alt="アプリロゴ画像" width="380">
  <p class="tagline">あなたの健康へ、ちいさな一歩をー。</p>
  <nav>
      <ul>
        <li><a href="input.php"><img src="images/memo.png" alt="入力"> 記録する</a></li>
        <li><a href="read.php"><img src="images/calender.png" alt="記録"> 記録を見る</a></li>
        <li><a href="articles.php"><img src="images/book.png" alt="記事"> 記事を読む</a></li>
        <li><a href="points.php"><img src="images/plants.png" alt="成長"> 成長を見る</a></li>
        <li><a href="read_all.php"><img src="images/ouen.png" alt="応援">応援する</a></li>
      </ul>
    </nav>
</header>

<?php if ($showRegisterBanner): ?>
<div id="regBanner" style="background:#fffbe6;border:1px solid #ffe58f;padding:10px 12px;margin:12px 0;border-radius:8px;">
  <strong>もっと便利に使うには登録がおすすめです。</strong>
  <a href="register.php" class="btn">登録する</a>
  <button onclick="document.getElementById('regBanner').style.display='none'; document.cookie='dismiss_reg=1; path=/; max-age=2592000'">閉じる</button>
</div>
<?php endif; ?>

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
<div class="page-bottom-spacer"></div>

<p style="margin:12px 0;">
  <a href="qr.php">📱 自分の匿名ID用QRを表示</a> /
  <a href="qr_bulk.php">🧾 配布用QRをまとめて作る</a>
</p>

<footer>
  <div class="footerMenuList">
    <a href="input.php" class="btn"><img src="images/memo.png" alt="入力" width="60"></a>
    <a href="articles.php" class="btn"><img src="images/book.png" alt="記事" width="60"></a>
    <a href="points.php" class="btn"><img src="images/plants.png" alt="成長" width="60"></a>
    <a href="read_all.php" class="btn"><img src="images/ouen.png" alt="応援" width="60"></a>
    <a href="read.php" class="btn"><img src="images/calender.png" alt="カレンダー" width="60"></a>
  </div>
  <footer>
    <p>&copy; 2025 Wellnoa</p>
  </footer>
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
