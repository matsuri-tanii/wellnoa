<?php
require_once __DIR__ . '/admin_guard.php';
require_once __DIR__ . '/funcs.php';

$pdo = db_conn();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) die('invalid id');

$stmt = $pdo->prepare("DELETE FROM articles WHERE id=:id");
$stmt->bindValue(':id',$id,PDO::PARAM_INT);
try {
  $stmt->execute();
  header('Location: admin_articles.php');
  exit;
} catch (PDOException $e) {
  echo json_encode(['sql error'=>$e->getMessage()]);
}