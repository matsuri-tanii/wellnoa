<?php
require_once __DIR__.'/funcs.php'; adopt_incoming_code();

$options = ['散歩','ジョギング','筋トレ','ストレッチ','ヨガ','ぼーっとする','ゲーム','手芸','読書','料理'];

$pdo = db_conn();
$uid = current_anon_user_id();

$id = $_GET['id'];

$sql = 'SELECT * FROM daily_logs WHERE id = :id AND anonymous_user_id = :uid';

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->bindValue(':uid', $uid, PDO::PARAM_INT);

try {
  $status = $stmt->execute();
} catch (PDOException $e) {
  echo json_encode(["sql error" => "{$e->getMessage()}"]);
  exit();
}

$record = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$record) {
  exit('該当の記録が見つかりませんでした。IDまたはユーザーが一致していません。');
}
// 追加：保存されている値を配列に変換
$checked_options = explode(',', $record['activity_type']);
?>

<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>今までのきろく（編集）</title>
  <link rel="stylesheet" href="css/reset.css">
  <link rel="stylesheet" href="css/variables.css">
  <link rel="stylesheet" href="css/base.css">
  <link rel="stylesheet" href="css/layout.css">
  <link rel="stylesheet" href="css/nav.css">
  <link rel="stylesheet" href="css/components.css">
  <link rel="stylesheet" href="css/forms.css">
  <link rel="stylesheet" href="css/notices.css">
  <link rel="stylesheet" href="css/utilities.css">
</head>

<body class="page-has-footer">
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

      <form action="update.php" method="POST">
        <fieldset>
          <legend>今までの記録（編集）</legend>

          <div class="form-row">
            <label>記録日時：</label>
            <input type="date" name="log_date" value="<?= htmlspecialchars($record['log_date']) ?>">
            <input type="time" name="log_time" value="<?= htmlspecialchars(substr($record['log_time'],0,5)) ?>"> 
          </div>
          
          <div class="form-row">
            <label>天気：</label>
            <input type="text" name="weather" value="<?= $record['weather'] ?>">
          </div>

          <label>体の調子：</label>
          <div class="range">
            <div class="range_bad">悪い</div>
            <div class="range_input"><input type="range" name="body_condition" min="0" max="100" value="<?= $record['body_condition'] ?>"></div>
            <div class="range_good">良い</div>
          </div>
          
          <label>心の調子：</label>
          <div class="range">
            <div class="range_bad">悪い</div>
            <div class="range_input"><input type="range" name="mental_condition" min="0" max="100" value="<?= $record['mental_condition'] ?>"></div>
            <div class="range_good">良い</div>
          </div>

          <label>やったこと：</label>
          <div class="checkbox-group">
            <?php foreach($options as $opt): ?>
              <label>
                <input 
                  type="checkbox" 
                  name="activity_type[]" 
                  value="<?= $opt ?>" 
                  <?= in_array($opt, $checked_options) ? 'checked' : '' ?>
                  > <?= $opt ?>
              </label>
            <?php endforeach; ?>
          </div>

          <label>ひとこと：</label>
          <textarea name="memo"><?= htmlspecialchars($record['memo']) ?></textarea>

          <button type="submit">記録する</button>
        
          <input type="hidden" name="id" value="<?= $record['id']?>">
        </fieldset>
      </form>
    </main>

      <!-- 4) ボトムナビ（スマホ/タブ縦） -->
    <footer class="app-footer">
      <?php require __DIR__.'/inc/bottom_nav.php'; ?>
    </footer>
  </div>

  <script src="js/main.js"></script>
</body>

</html>