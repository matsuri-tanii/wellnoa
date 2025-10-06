<?php
declare(strict_types=1);

$nav_active = 'home';

require_once __DIR__.'/funcs.php';
adopt_incoming_code();

$pdo = db_conn();
$uid = current_anon_user_id();
set_guest_cookie();

/* ---------------------- 折れ線（既存の日別平均） ---------------------- */
$sql = "SELECT log_date, AVG(body_condition) AS avg_body, AVG(mental_condition) AS avg_mental
        FROM daily_logs WHERE anonymous_user_id = :uid
        GROUP BY log_date ORDER BY log_date";
$st = $pdo->prepare($sql); $st->execute([':uid'=>$uid]);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

$dates     = array_column($rows, 'log_date');
$avgBody   = array_map('floatval', array_column($rows, 'avg_body'));
$avgMental = array_map('floatval', array_column($rows, 'avg_mental'));

/* ---------------------- 右カラムの数字 ---------------------- */
$st = $pdo->prepare("SELECT COUNT(*) FROM article_reads WHERE anonymous_user_id = :uid");
$st->execute([':uid'=>$uid]); $articleCount = (int)$st->fetchColumn();

$cheerCount = (int)$pdo->query("SELECT COUNT(*) FROM cheers WHERE target_type IN('daily','read')")->fetchColumn();

$st = $pdo->prepare("SELECT COUNT(*) FROM daily_logs WHERE anonymous_user_id = :uid");
$st->execute([':uid'=>$uid]); $dailyCount = (int)$st->fetchColumn();

/* ---------------------- 円/ドーナツ用の“生データ” ---------------------- */
$st = $pdo->prepare("
  SELECT a.category, ar.read_date
  FROM article_reads ar
  JOIN articles a ON a.id = ar.article_id
  WHERE ar.anonymous_user_id = :uid
");
$st->execute([':uid'=>$uid]);
$readRows = $st->fetchAll(PDO::FETCH_ASSOC);   // [{category, read_date}]

$st = $pdo->prepare("
  SELECT activity_type, log_date
  FROM daily_logs
  WHERE anonymous_user_id = :uid
");
$st->execute([':uid'=>$uid]);
$actRows = $st->fetchAll(PDO::FETCH_ASSOC);    // [{activity_type, log_date}]
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Wellnoa - あなたの小さな健康習慣</title>
  <!-- 共通CSS（順番固定＆page-overridesは最後） -->
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
  <style>
    .range-tabs{display:flex;gap:6px;flex-wrap:wrap;margin:6px 0 10px}
    .range-tabs .tab{
      padding:6px 10px;border:1px solid #ddd;border-radius:8px;background:#fff;cursor:pointer;font-size:13px
    }
    .range-tabs .tab.is-active{background:#eef6f4;border-color:#bfe6db;font-weight:700}
  </style>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <div class="layout">
    <?php require __DIR__.'/inc/header.php'; ?>
    <aside class="side-nav"><?php require __DIR__.'/inc/side_nav.php'; ?></aside>

    <main class="main">
      <?php require __DIR__.'/inc/notices.php'; ?>

      <div class="main-inner">
        <div class="dashboard-grid">
          <!-- 左：グラフ群 -->
          <div class="chart-column">
            <div class="range-tabs" role="tablist" aria-label="期間">
              <button class="tab is-active" data-range="all">全期間</button>
              <button class="tab" data-range="7">1週間</button>
              <button class="tab" data-range="30">1ヶ月</button>
              <button class="tab" data-range="182">半年</button>
              <button class="tab" data-range="365">1年</button>
              <button class="tab" data-range="1095">3年</button>
            </div>

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

          <!-- 右：数字系 -->
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
      </div>
    </main>

    <footer class="app-footer"><?php require __DIR__.'/inc/bottom_nav.php'; ?></footer>
  </div>

  <script>
  /* ====== 1) PHPから生データ受け取り ====== */
  const baseLabels = <?= json_encode($dates, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>; // "YYYY-MM-DD"
  const baseBody   = <?= json_encode($avgBody) ?>;
  const baseMental = <?= json_encode($avgMental) ?>;

  const readRows   = <?= json_encode($readRows, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>; // [{category, read_date}]
  const actRows    = <?= json_encode($actRows,  JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>; // [{activity_type, log_date}]

  // 折れ線用データ(Date化・昇順)
  const basePoints = baseLabels.map((d,i)=>({
    t: new Date(d + 'T00:00:00'),
    body: Number(baseBody[i] ?? 0),
    mental: Number(baseMental[i] ?? 0)
  })).sort((a,b)=>a.t-b.t);

  // 円/ドーナツ用 生データ(Date化)
  const readPoints = readRows.map(r => ({
    t: new Date((r.read_date||'') + 'T00:00:00'),
    category: r.category || '未分類'
  }));
  const actPoints = actRows.map(r => ({
    t: new Date((r.log_date||'') + 'T00:00:00'),
    activity: r.activity_type || '未設定'
  }));

  /* ====== 2) ユーティリティ ====== */
  const fmt = (dt)=> dt.toLocaleDateString('ja-JP', {month:'numeric', day:'numeric'});

  function sliceByRange(points, range){
    if (range === 'all') return points;
    const days = Number(range);
    const cutoff = new Date(); cutoff.setHours(0,0,0,0);
    cutoff.setDate(cutoff.getDate() - days + 1); // 今日含めdays日
    return points.filter(p => p.t >= cutoff);
  }

  function countBy(list, key){
    const map = new Map();
    for (const it of list) {
      const k = it[key] || '未設定';
      map.set(k, (map.get(k) || 0) + 1);
    }
    const labels = Array.from(map.keys());
    const values = labels.map(k => map.get(k));
    return { labels, values };
  }

  /* ====== 3) 初期表示：全期間（タブと一致） ====== */
  let currentRange = 'all';

  // 折れ線
  const lineCtx = document.getElementById('lineChart');
  const initLine = sliceByRange(basePoints, currentRange);
  const lineChart = new Chart(lineCtx, {
    type: 'line',
    data: {
      labels: initLine.map(p=>fmt(p.t)),
      datasets: [
        { label: '体の調子', data: initLine.map(p=>p.body), tension:.25 },
        { label: '心の調子', data: initLine.map(p=>p.mental), tension:.25 }
      ]
    },
    options: {
      responsive:true, maintainAspectRatio:false,
      layout:{padding:{left:8,right:8,top:8,bottom:8}},
      scales:{ y:{ suggestedMin:0, suggestedMax:100 } }
    }
  });

  // 円（カテゴリ別：読了）
  const pieCtx = document.getElementById('categoryChart');
  const initReads = sliceByRange(readPoints, currentRange);
  const catInit = countBy(initReads, 'category');
  const pieChart = new Chart(pieCtx, {
    type:'pie',
    data:{ labels: catInit.labels, datasets:[{ data: catInit.values }] },
    options:{ responsive:true, maintainAspectRatio:false, layout:{padding:12} }
  });

  // ドーナツ（活動別）
  const donutCtx = document.getElementById('activityChart');
  const initActs = sliceByRange(actPoints, currentRange);
  const actInit = countBy(initActs, 'activity');
  const donutChart = new Chart(donutCtx, {
    type:'doughnut',
    data:{ labels: actInit.labels, datasets:[{ data: actInit.values }] },
    options:{ responsive:true, maintainAspectRatio:false, layout:{padding:12}, cutout:'55%' }
  });

  /* ====== 4) タブ切替：3つのグラフをまとめて更新 ====== */
  document.querySelectorAll('.range-tabs .tab').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      if (btn.dataset.range === currentRange) return;
      currentRange = btn.dataset.range;

      // active切替
      document.querySelectorAll('.range-tabs .tab').forEach(b=>b.classList.remove('is-active'));
      btn.classList.add('is-active');

      // 折れ線
      const lp = sliceByRange(basePoints, currentRange);
      lineChart.data.labels = lp.map(p=>fmt(p.t));
      lineChart.data.datasets[0].data = lp.map(p=>p.body);
      lineChart.data.datasets[1].data = lp.map(p=>p.mental);
      lineChart.update();

      // 円（カテゴリ）
      const r = sliceByRange(readPoints, currentRange);
      const cc = countBy(r, 'category');
      pieChart.data.labels = cc.labels;
      pieChart.data.datasets[0].data = cc.values;
      pieChart.update();

      // ドーナツ（活動）
      const a = sliceByRange(actPoints, currentRange);
      const ac = countBy(a, 'activity');
      donutChart.data.labels = ac.labels;
      donutChart.data.datasets[0].data = ac.values;
      donutChart.update();
    });
  });
  </script>

  <script src="js/ui-nav.js" defer></script>
</body>
</html>