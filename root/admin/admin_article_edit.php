<?php
require_once __DIR__ . '/admin_guard.php';
require_once __DIR__.'/funcs.php'; adopt_incoming_code();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_same_origin_check() || !admin_csrf_check($_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Bad Request (CSRF)');
    }
}

$pdo = db_conn();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$data = [
  'title'=>'','description'=>'','url'=>'','category'=>'',
  'source_name'=>'','thumbnail_url'=>'','published_at'=>'',
  'is_published'=>1, // ← 追加: 初期値は公開
];

if ($id > 0) {
  $stmt = $pdo->prepare("SELECT * FROM articles WHERE id=:id");
  $stmt->bindValue(':id',$id,PDO::PARAM_INT);
  $stmt->execute();
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($row) $data = $row;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title><?= $id? '記事編集':'記事追加' ?></title>
<link rel="stylesheet" href="css/reset.css">
<link rel="stylesheet" href="css/variables.css">
<link rel="stylesheet" href="css/base.css">
<link rel="stylesheet" href="css/layout.css">
<link rel="stylesheet" href="css/nav.css">
<link rel="stylesheet" href="css/components.css">
<link rel="stylesheet" href="css/forms.css">
<link rel="stylesheet" href="css/notices.css">
<link rel="stylesheet" href="css/utilities.css">
<link rel="stylesheet" href="css/admin.css">
</head>
<body>
<header class="app-header">
  <img class="app-logo" src="images/title_logo.png" alt="" width="240">
  <p class="tagline"><?= $id? '記事編集':'記事追加' ?></p>
</header>

<form class="form-card" action="admin_article_save.php" method="post">
  <fieldset>
    <input type="hidden" name="id" value="<?= (int)$id ?>">
    <div class="form-row"><label>タイトル</label>
      <input type="text" name="title" value="<?= h($data['title']) ?>" required></div>

    <div class="form-row"><label>説明文</label>
      <textarea name="description" rows="6"><?= h($data['description']) ?></textarea></div>

    <div class="form-row"><label>元記事URL</label>
      <input type="text" name="url" value="<?= h($data['url']) ?>"></div>

    <div class="form-row"><label>カテゴリ</label>
      <input type="text" name="category" value="<?= h($data['category']) ?>"></div>

    <div class="form-row"><label>出典（サイト名）</label>
      <input type="text" name="source_name" value="<?= h($data['source_name']) ?>"></div>

    <div class="form-row"><label>サムネURL</label>
      <input type="text" name="thumbnail_url" value="<?= h($data['thumbnail_url']) ?>"></div>

    <div class="form-row"><label>公開日</label>
      <input type="date" name="published_at" value="<?= h(substr((string)$data['published_at'],0,10)) ?>">
    </div>
    <div class="form-row">
        <label>公開状態</label>
        <input type="checkbox" name="is_published" value="1"
            <?= !empty($data['is_published']) ? 'checked' : '' ?>>
        <span style="margin-left:.5rem;color:var(--muted)">チェックで公開 / 外すと非公開</span>
    </div>


    <div class="mt-2">
      <button class="btn" type="submit"><?= $id? '更新する':'追加する' ?></button>
      <a class="btn btn-outline" href="admin_articles.php">一覧へ</a>
    </div>
  </fieldset>
</form>
</body>
</html>