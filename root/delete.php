<?php
require_once __DIR__.'/funcs.php';
adopt_incoming_code();
session_start();

$uid = current_anon_user_id();
if (empty($uid)) {
  exit('匿名ユーザーIDが取得できません。');
}

if (empty($_GET['id']) || !ctype_digit($_GET['id'])) {
  set_flash('削除対象が正しく指定されていません。', 'error');
  header('Location: read.php');
  exit();
}

$id = (int)$_GET['id'];

// DB接続
$pdo = db_conn();

$sql = 'DELETE FROM daily_logs WHERE id = :id AND anonymous_user_id = :uid';
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->bindValue(':uid', $uid, PDO::PARAM_INT);

try {
  $stmt->execute();
  if ($stmt->rowCount() > 0) {
    set_flash('記録を削除しました', 'success');
  } else {
    // 他人のデータや存在しないIDの削除防止
    set_flash('指定された記録が見つかりませんでした。', 'error');
  }
} catch (PDOException $e) {
  set_flash('削除に失敗しました：' . $e->getMessage(), 'error');
}

// 一覧へ戻る
header('Location: read.php');
exit();