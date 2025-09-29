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
    $sql = "INSERT INTO article_reads(anon_code, article_id, read_at) VALUES(:c,:a, NOW())";
    $stmt= $pdo->prepare($sql);
    $stmt->execute([':c'=>$me, ':a'=>$article_id]);

    // ポイント加算
    add_point_for($me, 'article_read');

    echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
}
