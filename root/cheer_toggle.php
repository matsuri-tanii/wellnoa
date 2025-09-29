<?php
require_once __DIR__.'/funcs.php'; adopt_incoming_code();
require_once __DIR__.'/points_lib.php';

header('Content-Type: application/json; charset=utf-8');

$me = current_anon_code();
if ($me === '') {
    http_response_code(400);
    echo json_encode(['ok'=>false, 'error'=>'anon_code missing']);
    exit;
}

$article_id = isset($_POST['article_id']) ? (int)$_POST['article_id'] : 0;
if ($article_id <= 0) {
    http_response_code(400);
    echo json_encode(['ok'=>false, 'error'=>'invalid article_id']);
    exit;
}

try {
    $pdo = db_conn();
    // 既に押しているかを確認
    $stmt = $pdo->prepare("SELECT id FROM cheers WHERE anon_code=:c AND article_id=:a");
    $stmt->execute([':c'=>$me, ':a'=>$article_id]);
    $row = $stmt->fetch();

    if ($row) {
        $pdo->prepare("DELETE FROM cheers WHERE id=:id")->execute([':id'=>$row['id']]);
        echo json_encode(['ok'=>true, 'cheered'=>false]);
    } else {
        $pdo->prepare("INSERT INTO cheers(anon_code, article_id, created_at) VALUES(:c,:a, NOW())")
            ->execute([':c'=>$me, ':a'=>$article_id]);
        // ポイント加算
        add_point_for($me, 'cheer_send');
        echo json_encode(['ok'=>true, 'cheered'=>true]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
}
