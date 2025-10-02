<?php
declare(strict_types=1);

require_once __DIR__ . '/funcs.php';
require_once __DIR__ . '/points_lib.php';

header('Content-Type: application/json; charset=utf-8');

$uid  = current_anon_user_id();   // int: DBの anonymous_user_id 用
$code = current_anon_code();      // string: ポイント用 anon_code

try {
    $articleId = isset($_POST['article_id']) ? (int)$_POST['article_id'] : 0;
    if ($articleId <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'invalid article_id']);
        exit;
    }

    $pdo = db_conn();

    // きょう既に記録済みか？
    $sql = "SELECT id FROM article_reads
            WHERE anonymous_user_id = :uid AND article_id = :aid AND read_date = CURDATE()
            LIMIT 1";
    $st  = $pdo->prepare($sql);
    $st->execute([':uid' => $uid, ':aid' => $articleId]);
    $already = (bool)$st->fetch();

    if ($already) {
        echo json_encode(['ok' => true, 'already' => true]);
        exit;
    }

    // 新規読了を登録
    $ins = $pdo->prepare(
        "INSERT INTO article_reads (anonymous_user_id, article_id, read_date)
         VALUES (:uid, :aid, CURDATE())"
    );
    $ins->execute([':uid' => $uid, ':aid' => $articleId]);

    // ポイント加算（← ここは anon_code を渡す）
    add_point_for($code, 'article_read');

    echo json_encode(['ok' => true, 'already' => false]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}