<?php
session_start();
include('funcs.php');
check_session_id();

if (
  !isset($_GET['id']) || $_GET['id'] === ''
) {
  exit('paramError');
}

$id = $_GET['id'];

// DB接続
$pdo = db_conn();

$sql = 'UPDATE records SET deleted_at=now() WHERE id=:id AND user_id=:user_id';

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);

try {
  $status = $stmt->execute();
} catch (PDOException $e) {
  echo json_encode(["sql error" => "{$e->getMessage()}"]);
  exit();
}

header("Location:read.php");
exit();

