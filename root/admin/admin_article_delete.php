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