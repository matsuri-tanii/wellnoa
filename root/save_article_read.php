<?php
declare(strict_types=1);
require_once __DIR__ . '/funcs.php';
require_once __DIR__ . '/points_lib.php';
header('Content-Type: application/json; charset=utf-8');

$uid  = current_anon_user_id();   // int: DBの anonymous_user_id 用
$code = current_anon_code();      // string: anon_code（Cookie管理用）

try {
    $articleId = isset($_POST['article_id']) ? (int)$_POST['article_id'] : 0;
    if ($articleId <= 0) {
        echo json_encode(['ok' => false, 'error' => 'invalid article_id']);
        exit;
    }

    $pdo = db_conn();

    // 既読確認（過去含めて1回でも記録済みなら true）
    $sql = "SELECT 1 FROM article_reads
            WHERE anonymous_user_id = :uid AND article_id = :aid
            LIMIT 1";
    $st  = $pdo->prepare($sql);
    $st->execute([':uid' => $uid, ':aid' => $articleId]);
    $already = (bool)$st->fetch();

    if (!$already) {
        // 初回読了のみ登録
        $ins = $pdo->prepare(
            "INSERT INTO article_reads (anonymous_user_id, article_id, read_date, created_at)
             VALUES (:uid, :aid, CURDATE(), NOW())"
        );
        $ins->execute([':uid' => $uid, ':aid' => $articleId]);

        // ポイント加算（初回のみ）
        // 省略… INSERT article_reads 成功後
        try {
            $ok = add_point_for($code, 'article_read'); // $code は current_anon_code()
            error_log('[points] called from save_article_read ok=' . ($ok?1:0) . ' code=' . $code);
        } catch (Throwable $e) {
            error_log('[points] save_article_read add_point_for error: ' . $e->getMessage());
        }
    }

    echo json_encode(['ok' => true, 'already' => $already]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}