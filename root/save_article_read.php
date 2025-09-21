<?php
require_once 'anon_session.php';
include_once 'funcs.php';

if (!isset($_POST['article_id']) || $_POST['article_id'] === '') {
  exit('paramError');
}

$article_id = (int)$_POST['article_id'];
$uid = current_anon_user_id(); // セッションから取得

$pdo = db_conn();

// すでに登録済みか確認
$sql = "SELECT COUNT(*) FROM article_reads WHERE anonymous_user_id=:uid AND article_id=:aid";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
$stmt->bindValue(':aid', $article_id, PDO::PARAM_INT);
$stmt->execute();
$exists = $stmt->fetchColumn();

if ($exists == 0) {
  $sql = "INSERT INTO article_reads (anonymous_user_id, article_id, read_date, created_at) 
          VALUES (:uid, :aid, CURDATE(), NOW())";
  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
  $stmt->bindValue(':aid', $article_id, PDO::PARAM_INT);
  try {
    $stmt->execute();
    echo "OK";
  } catch (PDOException $e) {
    echo json_encode(["sql error" => $e->getMessage()]);
  }
} else {
  echo "already exists";
}