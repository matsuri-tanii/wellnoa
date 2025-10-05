<?php
require_once __DIR__.'/admin_guard.php';
require_once __DIR__.'/funcs.php'; adopt_incoming_code(); // db_conn(), h()
$pdo = db_conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_same_origin_check() || !admin_csrf_check($_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Bad Request (CSRF)');
    }
}

/* ------------ アクション処理（POST） ------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $op = $_POST['op'] ?? '';

  if ($op === 'toggle') {
    // 公開/非公開の切替
    $id  = (int)($_POST['id'] ?? 0);
    $val = (int)($_POST['val'] ?? 0);
    $sql = "UPDATE articles SET is_published=:val, updated_at=NOW() WHERE id=:id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':val', $val, PDO::PARAM_INT);
    $stmt->bindValue(':id',  $id,  PDO::PARAM_INT);
    $stmt->execute();
    header('Location: admin_articles.php'); exit;
  }

  if ($op === 'delete') {
    // 削除
    $id = (int)($_POST['id'] ?? 0);
    $sql = "DELETE FROM articles WHERE id=:id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    header('Location: admin_articles.php'); exit;
  }

  if ($op === 'create') {
    // 新規追加
    $title         = trim($_POST['title'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $url           = trim($_POST['url'] ?? '');
    $source_name   = trim($_POST['source_name'] ?? '');
    $category      = trim($_POST['category'] ?? '');
    $thumbnail_url = trim($_POST['thumbnail_url'] ?? '');
    $is_published  = isset($_POST['is_published']) ? 1 : 0;

    // 空なら NULL 扱い
    $published_at_input = trim($_POST['published_at'] ?? '');
    $published_at = $published_at_input !== '' ? $published_at_input : null;

    $sql = "
      INSERT INTO articles
        (title, description, url, source_name, category, thumbnail_url,
         published_at, is_published, created_at, updated_at)
      VALUES
        (:title, :description, :url, :source_name, :category, :thumbnail_url,
         :published_at, :is_published, NOW(), NOW())
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':title',         $title,         PDO::PARAM_STR);
    $stmt->bindValue(':description',   $description,   PDO::PARAM_STR);
    $stmt->bindValue(':url',           $url,           PDO::PARAM_STR);
    $stmt->bindValue(':source_name',   $source_name,   PDO::PARAM_STR);
    $stmt->bindValue(':category',      $category,      PDO::PARAM_STR);
    $stmt->bindValue(':thumbnail_url', $thumbnail_url, PDO::PARAM_STR);
    if ($published_at === null) {
      $stmt->bindValue(':published_at', null, PDO::PARAM_NULL);
    } else {
      $stmt->bindValue(':published_at', $published_at, PDO::PARAM_STR);
    }
    $stmt->bindValue(':is_published',  $is_published,  PDO::PARAM_INT);
    $stmt->execute();

    header('Location: admin_articles.php'); exit;
  }
}

/* ------------ 一覧取得（ここで必ず $sql を定義） ------------ */
$sql = "
  SELECT id, title, source_name, category, is_published, published_at
  FROM articles
  ORDER BY
    CASE WHEN published_at IS NULL THEN 1 ELSE 0 END,
    published_at DESC,
    id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>記事管理</title>
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
  <img class="app-logo" src="images/title_logo.png" alt="Wellnoa" width="320">
  <p class="tagline">記事管理</p>
  <a href="admin_logout.php" class="btn btn-outline">ログアウト</a>
</header>

<main class="wrap">
  <section class="card">
    <div class="card-header">新規記事の追加</div>
    <div class="card-body">
      <form class="new-form" method="post">
        <input type="hidden" name="op" value="create">
        <input type="hidden" name="csrf" value="<?= h(admin_csrf_token()) ?>">
        <div class="row"><label>タイトル</label><input type="text" name="title" required></div>
        <div class="row"><label>説明文</label><textarea name="description" rows="3"></textarea></div>
        <div class="row"><label>URL</label><input type="text" name="url"></div>
        <div class="row"><label>出典（サイト名）</label><input type="text" name="source_name"></div>
        <div class="row"><label>カテゴリ</label><input type="text" name="category"></div>
        <div class="row"><label>サムネURL</label><input type="text" name="thumbnail_url"></div>
        <div class="row"><label>公開日</label><input type="datetime-local" name="published_at"></div>
        <div class="row"><label>公開状態</label><label><input type="checkbox" name="is_published" value="1"> 公開する</label></div>
        <button class="btn" type="submit">追加</button>
      </form>
    </div>
  </section>

  <section class="card" style="margin-top:16px;">
    <div class="card-header">記事一覧</div>
    <div class="card-body">
      <table>
        <thead>
          <tr>
            <th>ID</th><th>タイトル</th><th>出典</th><th>カテゴリ</th><th>公開日</th><th>公開</th><th>操作</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= (int)$r['id'] ?></td>
              <td><?= h($r['title']) ?></td>
              <td><?= h($r['source_name'] ?? '') ?></td>
              <td><?= h($r['category'] ?? '') ?></td>
              <td><?= h($r['published_at'] ?? '') ?></td>
              <td><?= ((int)$r['is_published'] === 1) ? '公開' : '非公開' ?></td>
              <td class="actions">
                <!-- 公開/非公開トグル -->
                <form class="inline" method="post">
                  <input type="hidden" name="op" value="toggle">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <input type="hidden" name="val" value="<?= ((int)$r['is_published'] === 1) ? 0 : 1 ?>">
                  <input type="hidden" name="csrf" value="<?= h(admin_csrf_token()) ?>">
                  <button class="btn btn-outline" type="submit">
                    <?= ((int)$r['is_published'] === 1) ? '非公開にする' : '公開にする' ?>
                  </button>
                </form>
                <!-- 削除 -->
                <form class="inline" method="post" onsubmit="return confirm('削除してよろしいですか？');">
                  <input type="hidden" name="op" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <input type="hidden" name="csrf" value="<?= h(admin_csrf_token()) ?>">
                  <button class="btn" type="submit">削除</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($rows)): ?>
            <tr><td colspan="7">記事がありません。</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>

<footer class="app-footer">
  <a href="index.php" class="btn"><img src="images/home.png" alt="ホーム" width="48"></a>
  <a href="articles.php" class="btn"><img src="images/book.png" alt="記事" width="48"></a>
</footer>
</body>
</html>