<?php
require_once __DIR__ . '/admin_guard.php';
require_once __DIR__ . '/funcs.php';

$pdo = db_conn();

$id            = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$title         = trim($_POST['title'] ?? '');
$description   = trim($_POST['description'] ?? '');
$url           = trim($_POST['url'] ?? '');
$category      = trim($_POST['category'] ?? '');
$source_name   = trim($_POST['source_name'] ?? '');
$thumbnail_url = trim($_POST['thumbnail_url'] ?? '');
$published_at  = trim($_POST['published_at'] ?? ''); // Y-m-d
$is_published  = isset($_POST['is_published']) ? 1 : 0;

if ($title === '') {
  die('title is required');
}

if ($id > 0) {
  $sql = "UPDATE articles SET
            title=:title, description=:description, url=:url, category=:category,
            source_name=:source_name, thumbnail_url=:thumbnail_url,
            published_at=:published_at, is_published=:is_published,
            updated_at=NOW()
          WHERE id=:id";
  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':id',$id,PDO::PARAM_INT);
} else {
  $sql = "INSERT INTO articles
            (title, description, url, category, source_name, thumbnail_url,
             published_at, is_published, created_at, updated_at)
          VALUES
            (:title, :description, :url, :category, :source_name, :thumbnail_url,
             :published_at, :is_published, NOW(), NOW())";
  $stmt = $pdo->prepare($sql);
}

$stmt->bindValue(':title', $title, PDO::PARAM_STR);
$stmt->bindValue(':description', $description, PDO::PARAM_STR);
$stmt->bindValue(':url', $url, PDO::PARAM_STR);
$stmt->bindValue(':category', $category, PDO::PARAM_STR);
$stmt->bindValue(':source_name', $source_name, PDO::PARAM_STR);
$stmt->bindValue(':thumbnail_url', $thumbnail_url, PDO::PARAM_STR);
$stmt->bindValue(':published_at',
  $published_at !== '' ? $published_at.' 00:00:00' : null,
  $published_at!==''?PDO::PARAM_STR:PDO::PARAM_NULL
);
$stmt->bindValue(':is_published', $is_published, PDO::PARAM_INT);

try {
  $stmt->execute();
  header('Location: admin_articles.php');
  exit;
} catch (PDOException $e) {
  echo json_encode(['sql error'=>$e->getMessage()]);
}