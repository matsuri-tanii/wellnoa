<?php
declare(strict_types=1);

require_once __DIR__.'/funcs.php';
adopt_incoming_code();              // ?code=.. で来たらクッキーに採用

$pdo = db_conn();
$uid = current_anon_user_id();      // 数値の匿名ユーザーID
set_guest_cookie();                 // anon_code クッキーも同時に保証

// ---------------------- データ集計 ----------------------

// 体調・心の調子（折れ線グラフ用）
$sql = "SELECT log_date,
               AVG(body_condition)   AS avg_body,
               AVG(mental_condition) AS avg_mental
        FROM daily_logs
        WHERE anonymous_user_id = :uid
        GROUP BY log_date
        ORDER BY log_date";
$st = $pdo->prepare($sql);
$st->execute([':uid'=>$uid]);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

$dates     = array_column($rows, 'log_date');
$avgBody   = array_map('floatval', array_column($rows, 'avg_body'));
$avgMental = array_map('floatval', array_column($rows, 'avg_mental'));

// 読了記事数
$st = $pdo->prepare("SELECT COUNT(*) FROM article_reads WHERE anonymous_user_id = :uid");
$st->execute([':uid'=>$uid]);
$articleCount = (int)$st->fetchColumn();

// 読んだ記事カテゴリ割合
$sql = "SELECT a.category, COUNT(*) AS cnt
        FROM article_reads ar
        JOIN articles a ON a.id = ar.article_id
        WHERE ar.anonymous_user_id = :uid
        GROUP BY a.category";
$st = $pdo->prepare($sql);
$st->execute([':uid'=>$uid]);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
$categories      = array_column($rows, 'category');
$categoryCounts  = array_map('intval', array_column($rows, 'cnt'));

// 応援された総数（全体）
$cheerCount = (int)$pdo->query("SELECT COUNT(*) FROM cheers WHERE target_type IN('daily','read')")->fetchColumn();

// 行動記録数
$st = $pdo->prepare("SELECT COUNT(*) FROM daily_logs WHERE anonymous_user_id = :uid");
$st->execute([':uid'=>$uid]);
$dailyCount = (int)$st->fetchColumn();

// 行動割合
$sql = "SELECT activity_type, COUNT(*) AS cnt
        FROM daily_logs
        WHERE anonymous_user_id = :uid
        GROUP BY activity_type";
$st = $pdo->prepare($sql);
$st->execute([':uid'=>$uid]);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
$activities     = array_column($rows, 'activity_type');
$activityCounts = array_map('intval', array_column($rows, 'cnt'));

// ざっくり利用回数（例：日記+記事閲覧）
$cnt1 = (int)$pdo->query("SELECT COUNT(*) FROM daily_logs     WHERE anonymous_user_id = ".(int)$uid)->fetchColumn();
$cnt2 = (int)$pdo->query("SELECT COUNT(*) FROM article_reads  WHERE anonymous_user_id = ".(int)$uid)->fetchColumn();
$totalUse = $cnt1 + $cnt2;

// 5回以上で登録バナーを出す（×30日抑止クッキー）
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
  <div class="layout">
    <!-- 1) 常時表示ヘッダー 共通お知らせ（未登録警告・フラッシュ・登録誘導） -->
    <header class="site-header">
      <?php require __DIR__.'/inc/header.php'; ?>
    </header>
    <!-- 2) サイドナビ（PC/タブ横のみCSSで表示） -->
    <aside class="side-nav">
      <nav>
        <ul>
          <li><a href="index.php"><img src="images/home.png" alt="ホーム">ホーム画面</a></li>
          <li><a href="input.php"><img src="images/memo.png" alt="">記録する</a></li>
          <li><a href="read.php"><img src="images/calender.png" alt="">記録を見る</a></li>
          <li><a href="articles.php"><img src="images/book.png" alt="">記事を読む</a></li>
          <li><a href="points.php"><img src="images/plants.png" alt="">成長を見る</a></li>
          <li><a href="read_all.php"><img src="images/ouen.png" alt="">応援する</a></li>
        </ul>
      </nav>
    </aside>

    <!-- 3) メイン -->
    <main class="main">
      <?php if ($showRegisterBanner): ?>
        <div id="regBanner" style="background:#fffbe6;border:1px solid #ffe58f;padding:10px 12px;margin:12px;border-radius:8px;">
          <strong>もっと便利に使うには登録がおすすめです。</strong>
          <a href="register.php" class="btn">登録する</a>
          <button
            type="button"
            onclick="document.getElementById('regBanner').style.display='none'; document.cookie='dismiss_reg=1; path=/; max-age=2592000'">
            閉じる
          </button>
        </div>
      <?php endif; ?>

      <div class="main-inner">

        <div class="card">
          <div class="card-header">体の調子と心の調子の変化</div>
          <div class="card-body">
            <canvas id="lineChart"></canvas>
          </div>
        </div>

        <div class="card">
          <div class="card-header">今まで読んだ記事の数</div>
          <div class="card-body">
            <?= h((string)$articleCount) ?> 本
          </div>
        </div>

        <div class="card">
          <div class="card-header">記事カテゴリの割合</div>
          <div class="card-body">
            <canvas id="categoryChart"></canvas>
          </div>
        </div>

        <div class="card">
          <div class="card-header">応援された総数（全体）</div>
          <div class="card-body">
            <?= h((string)$cheerCount) ?> 回
          </div>
        </div>

        <div class="card">
          <div class="card-header">行動記録数</div>
          <div class="card-body">
            <?= h((string)$dailyCount) ?> 件
          </div>
        </div>

        <div class="card">
          <div class="card-header">行動の割合</div>
          <div class="card-body">
            <canvas id="activityChart"></canvas>
          </div>
        </div>

        <p style="margin:12px 0;">
          <a href="qr.php">自分の匿名ID用QRを表示</a> /
          <a href="qr_bulk.php">配布用QRをまとめて作る</a>
        </p>

      </div>
      <div class="page-bottom-spacer"></div>
    </div>
  </main>

  <!-- 4) ボトムナビ（スマホ/タブ縦） -->
  <footer class="app-footer">
    <a href="index.php" class="btn"><img src="images/home.png" alt="ホーム" width="48"></a>
    <a href="input.php" class="btn"><img src="images/memo.png" alt="入力" width="48"></a>
    <a href="articles.php" class="btn"><img src="images/book.png" alt="記事" width="48"></a>
    <a href="points.php" class="btn"><img src="images/plants.png" alt="成長" width="48"></a>
    <a href="read_all.php" class="btn"><img src="images/ouen.png" alt="みんな" width="48"></a>
    <a href="read.php" class="btn"><img src="images/calender.png" alt="記録一覧" width="48"></a>
  </footer>
</div>

  <script>
  /* 折れ線 */
  new Chart(document.getElementById('lineChart'), {
    type: 'line',
    data: {
      labels: <?= json_encode($dates, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,
      datasets: [
        { label: '体の調子', data: <?= json_encode($avgBody) ?>,  tension: .25 },
        { label: '心の調子', data: <?= json_encode($avgMental) ?>, tension: .25 }
      ]
    },
    options: { responsive:true, maintainAspectRatio:false, aspectRatio:2 }
  });

  /* 円グラフ */
  new Chart(document.getElementById('categoryChart'), {
    type: 'pie',
    data: {
      labels: <?= json_encode($categories, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,
      datasets: [{ data: <?= json_encode($categoryCounts) ?> }]
    },
    options: { responsive:true }
  });

  /* ドーナツ */
  new Chart(document.getElementById('activityChart'), {
    type: 'doughnut',
    data: {
      labels: <?= json_encode($activities, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,
      datasets: [{ data: <?= json_encode($activityCounts) ?> }]
    },
    options: { responsive:true }
  });
  </script>

</body>
</html>