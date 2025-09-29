<?php
require_once __DIR__.'/funcs.php'; adopt_incoming_code();

if (
  !isset($_GET['id']) || $_GET['id'] === ''
) {
  exit('paramError');
}

$uid = current_anon_user_id();

$id = $_GET['id'];

// DB接続
$pdo = db_conn();

$sql = 'DELETE FROM daily_logs WHERE id=:id AND anonymous_user_id = :uid';

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->bindValue(':uid', $uid, PDO::PARAM_INT);

try {
  $status = $stmt->execute();
} catch (PDOException $e) {
  echo json_encode(["sql error" => "{$e->getMessage()}"]);
  exit();
}

set_flash('記録を削除しました');
header("Location:read.php");
exit();

