<?php
require_once __DIR__ . '/admin_guard.php';
require_once __DIR__.'/funcs.php'; adopt_incoming_code();

$pdo = db_conn();
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$to = isset($_POST['to']) ? (int)$_POST['to'] : 0;
if ($id <= 0 || ($to !== 0 && $to !== 1)) die('invalid');

$stmt = $pdo->prepare("UPDATE articles SET is_published=:to, updated_at=NOW() WHERE id=:id");
$stmt->bindValue(':to',$to,PDO::PARAM_INT);
$stmt->bindValue(':id',$id,PDO::PARAM_INT);
try {
  $stmt->execute();
  header('Location: admin_articles.php');
  exit;
} catch (PDOException $e) {
  echo json_encode(['sql error'=>$e->getMessage()]);
}