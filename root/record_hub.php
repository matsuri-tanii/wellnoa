<?php
// record_hub.php — 記録専用ハブ（履歴は read.php に集約）
session_start();
require_once __DIR__.'/funcs.php'; adopt_incoming_code();

$pdo = db_conn();
$uid = current_anon_user_id();

$options = [
  '散歩','ジョギング','筋トレ','ストレッチ','ヨガ','ぼーっとする','ゲーム','手芸','読書','料理'
];
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>記録ハブ</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="css/reset.css">
  <link rel="stylesheet" href="css/variables.css">
  <link rel="stylesheet" href="css/base.css">
  <link rel="stylesheet" href="css/layout.css">
  <link rel="stylesheet" href="css/nav.css">
  <link rel="stylesheet" href="css/components.css">
  <link rel="stylesheet" href="css/forms.css">
  <link rel="stylesheet" href="css/notices.css">
  <link rel="stylesheet" href="css/utilities.css">
  <link rel="stylesheet" href="css/page-overrides.css">
  <style>
    .hub { max-width: 960px; margin: 0 auto; }
    .top-actions{ display:flex; gap:8px; align-items:center; justify-content:flex-end; padding:10px 12px; }
    .help { color:#666; font-size:.9rem; margin:6px 0 12px; }
  </style>
</head>
<body>
  <div class="layout">
    <?php require __DIR__.'/inc/header.php'; ?>

    <aside class="side-nav">
      <?php require __DIR__.'/inc/side_nav.php'; ?>
    </aside>

    <main class="main">
      <?php require __DIR__.'/inc/notices.php'; ?>

      <div class="main-inner hub">
        <div class="top-actions">
          <a href="read.php" class="btn btn-outline">すべての履歴へ</a>
        </div>

        <div id="weather-info" class="notice info" style="margin: 8px 0; font-size: 0.9rem;">
          現在地の天気を取得中…
        </div>

        <form class="form-card" action="create.php" method="POST" id="recordForm">
          <fieldset>
            <legend class="visually-hidden">記録する</legend>

            <input type="hidden" name="weather" id="weather">

            <label>体の調子：</label>
            <div class="range">
              <div class="range_bad">悪い</div>
              <div class="range_input">
                <input type="range" name="body" min="0" max="100" aria-label="体の調子">
              </div>
              <div class="range_good">良い</div>
            </div>

            <label>心の調子：</label>
            <div class="range">
              <div class="range_bad">悪い</div>
              <div class="range_input">
                <input type="range" name="mental" min="0" max="100" aria-label="心の調子">
              </div>
              <div class="range_good">良い</div>
            </div>

            <label>やったこと（複数選択可）：</label>
            <div class="checkbox-group">
              <?php foreach($options as $opt): ?>
                <label><input type="checkbox" name="activity_type[]" value="<?= h($opt) ?>"> <?= h($opt) ?></label>
              <?php endforeach; ?>
            </div>

            <label>ひとこと：</label>
            <textarea name="memo" rows="3" placeholder="メモ（任意）"></textarea>

            <button type="submit" class="btn">記録する</button>
          </fieldset>
        </form>
      </div>
    </main>

    <footer class="app-footer">
      <?php require __DIR__.'/inc/bottom_nav.php'; ?>
    </footer>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const infoEl = document.getElementById('weather-info');
      const hiddenInput = document.getElementById('weather');
      const apiKey = "<?= h(OPENWEATHER_API_KEY) ?>"; // ← env.phpから直接取得

      // 初期表示
      infoEl.textContent = '現在地の天気を取得中…';

      // 位置情報が使えない場合
      if (!navigator.geolocation) {
        infoEl.textContent = 'お使いのブラウザは位置情報に対応していません。';
        hiddenInput.value = '未取得';
        return;
      }

      // 現在地を取得
      navigator.geolocation.getCurrentPosition(success, error, {
        enableHighAccuracy: true,
        timeout: 8000
      });

      function success(pos) {
        const lat = pos.coords.latitude;
        const lon = pos.coords.longitude;
        const url = `https://api.openweathermap.org/data/2.5/weather?lat=${lat}&lon=${lon}&appid=${apiKey}&units=metric&lang=ja`;

        axios.get(url)
          .then(res => {
            const data = res.data;
            const desc = data.weather?.[0]?.description ?? '不明';
            const temp = data.main?.temp?.toFixed(1) ?? '—';
            const hum  = data.main?.humidity ?? '—';
            const text = `📍 現在地：${desc}（${temp}℃／湿度${hum}%）`;
            infoEl.textContent = text;
            hiddenInput.value = text;
          })
          .catch(() => {
            infoEl.textContent = '天気情報の取得に失敗しました（未取得のままでも記録可能）';
            hiddenInput.value = '取得失敗';
          });
      }

      function error(e) {
        infoEl.textContent = '位置情報の取得を許可してください（未取得のままでも記録可能）';
        hiddenInput.value = '未取得';
      }
    });
  </script>

</body>
</html>