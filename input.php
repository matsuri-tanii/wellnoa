<?php
session_start();
include('funcs.php');
include('env.php');
check_session_id();


// やりたいことの選択肢
$options = ['ストレッチ','お散歩','筋トレ','ぼーっとする','ゲーム','手芸','読書','料理'];
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>日々のきろく</title>
  <style>
    body {
      background: #f5f5f5;
      padding: 20px;
    }
    .form-row {
      display: flex;
      align-items: center;
      line-height:30px;
    }
    .form-row label{
      width: 120pt;
      margin: 0;
    }
    fieldset {
      max-width: 500px;
      margin: auto;
      background: #fff;
      padding: 20px;
      border: none;
      border-radius: 8px;
      box-shadow: 0 0 5px rgba(0,0,0,0.1);
    }
    legend {
      font-size: 1.2em;
      margin-bottom: 10px;
      font-weight: bold;
    }
    label {
      display: block;
      margin-top: 10px;
      font-weight: bold;
    }
    input[type="text"], select, textarea {
      width: 100%;
      padding: 6px;
      margin-top: 4px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    .range {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      grid-template-rows: repeat(1, 30px);
    }
    .range_bad {
      grid-area: 1/1/2/2;
      place-self: center;
    }
    .range_input {
      grid-area: 1/2/2/7;
    }
    .range_good {
      grid-area: 1/7/2/8;
      place-self: center;
    }
    input[type="range"] {
      width: 100%;
      appearance:none;
      height:20px;
    }
    input[type="range"]::-webkit-slider-runnable-track {
      height:20px;
      background:linear-gradient(90deg,#FFFFCC,#87CEFA);
      border-radius:5px;
    }
    input[type="range"]::-webkit-slider-thumb {
      appearance:none;
      width:20px;
      height:20px;
      border-radius:50%;
      background:#fff;
      border:solid 2px coral;
    }
    .checkbox-group {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: 4px;
    }
    .checkbox-group label {
      font-weight: normal;
    }
    button {
      margin-top: 15px;
      width: 100%;
      padding: 8px;
      background: #007bff;
      color: white;
      border: none;
      border-radius: 4px;
      font-size: 1em;
      cursor: pointer;
    }
    button:hover {
      background: #0056b3;
    }
    a {
      display: inline-block;
      margin-bottom: 10px;
      color: #007bff;
      text-decoration: none;
    }
    a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <form action="create.php" method="POST">
    <fieldset>
      <legend>日々のきろく</legend>
      <a href="read.php">今までのきろくを見る</a>
      <a href="logout.php">ログアウトする</a>

      <input type="hidden" name="weather" id="weather" />

      <div class="form-row">
        <label>記録の種類：</label>
        <select name="record_type">
          <option value="朝">朝のきろく</option>
          <option value="夜">夜のきろく</option>
        </select>
      </div>

      <div class="form-row">
        <label>ニックネーム：</label>
        <input type="text" name="nickname">
      </div>

      <label>体の調子：</label>
      <div class="range">
        <div class="range_bad">悪い</div>
        <div class="range_input"><input type="range" name="body" min="0" max="100"></div>
        <div class="range_good">良い</div>
      </div>

      <label>心の調子：</label>
      <div class="range">
        <div class="range_bad">悪い</div>
        <div class="range_input"><input type="range" name="mental" min="0" max="100"></div>
        <div class="range_good">良い</div>
      </div>

      <label>やりたいこと / やったこと（複数選択可）：</label>
      <div class="checkbox-group">
        <?php foreach($options as $opt): ?>
          <label>
            <input type="checkbox" name="want_to_do[]" value="<?= $opt ?>"> <?= $opt ?>
          </label>
        <?php endforeach; ?>
      </div>

      <label>ひとこと：</label>
      <textarea name="memo"></textarea>

      <button type="submit">記録する</button>
    </fieldset>
  </form>

  <script>
    // 天気をOpenWeather APIで取得してhiddenにセット
    fetch(`https://api.openweathermap.org/data/2.5/weather?q=Tokyo&appid=<?= OPENWEATHER_API_KEY ?>&lang=ja&units=metric`)
    .then(response => response.json())
    .then(data => {
      document.getElementById('weather').value = data.weather[0].description;
    })
    .catch(error => console.error('天気取得エラー:', error));
  </script>
</body>
</html>