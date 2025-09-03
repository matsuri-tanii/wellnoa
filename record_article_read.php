<?php
include('funcs.php');
$pdo = db_conn();

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['article_id'])) {
  echo json_encode(['error' => 'No article ID']);
  exit;
}

// 匿名ユーザーIDはセッションから取得 or 生成
session_start();
if (!isset($_SESSION['anonymose_user_id'])) {
  $_SESSION['anonymose_user_id'] = uniqid('user_');
}
$anonymose_user_id = $_SESSION['anonymose_user_id'];

$sql = 'INSERT INTO article_reads (anonymose_user_id, article_id, read_date, created_at)
        VALUES (:anonymose_user_id, :article_id, NOW(), NOW())';
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':anonymose_user_id', $anonymose_user_id, PDO::PARAM_STR);
$stmt->bindValue(':article_id', $data['article_id'], PDO::PARAM_INT);

$status = $stmt->execute();

if ($status === false) {
  echo json_encode(['error' => 'DB error']);
} else {
  echo json_encode(['success' => true]);
}