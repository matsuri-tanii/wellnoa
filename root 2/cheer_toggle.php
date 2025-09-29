<?php
require_once __DIR__ . '/anon_session.php';
require_once __DIR__ . '/funcs.php';

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_POST['log_id']) || $_POST['log_id'] === '') {
  echo json_encode(['ok'=>false, 'error'=>'paramError']); exit;
}

$uid = (int) current_anon_user_id();
$logId = (int) $_POST['log_id'];

$pdo = db_conn();
$pdo->beginTransaction();
try {
  // まず挿入を試す（新規応援）
  $sql = "INSERT INTO cheers (daily_log_id, anonymous_user_id, created_at)
          VALUES (:log_id, :uid, NOW())";
  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':log_id', $logId, PDO::PARAM_INT);
  $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
  $stmt->execute();

  $pdo->commit();
  echo json_encode(['ok'=>true, 'status'=>'added']); // 応援を追加
} catch (PDOException $e) {
  // すでに応援済み（UNIQUE衝突）の場合は取消に切り替える
  if ((int)$e->errorInfo[1] === 1062) { // duplicate
    try {
      $sql = "DELETE FROM cheers WHERE daily_log_id=:log_id AND anonymous_user_id=:uid";
      $stmt = $pdo->prepare($sql);
      $stmt->bindValue(':log_id', $logId, PDO::PARAM_INT);
      $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
      $stmt->execute();
      $pdo->commit();
      echo json_encode(['ok'=>true, 'status'=>'removed']); // 応援を取り消し
    } catch(PDOException $e2) {
      $pdo->rollBack();
      echo json_encode(['ok'=>false, 'error'=>'dbErrorDel']);
    }
  } else {
    $pdo->rollBack();
    echo json_encode(['ok'=>false, 'error'=>'dbErrorAdd']);
  }
}