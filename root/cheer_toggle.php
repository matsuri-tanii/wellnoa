<?php
declare(strict_types=1);

require_once __DIR__ . '/funcs.php';
adopt_incoming_code();

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = db_conn();
    $uid = current_anon_user_id();
    if (!$uid) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'anonymous_user_id missing']);
        exit;
    }

    $type = isset($_POST['target_type']) ? strtolower((string)$_POST['target_type']) : '';
    $tid  = isset($_POST['target_id'])   ? (int)$_POST['target_id'] : 0;

    if (!in_array($type, ['daily', 'read'], true) || $tid <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'invalid target_type or target_id']);
        exit;
    }

    // オーナー確認（自分の投稿は応援不可）
    if ($type === 'daily') {
        $ownStmt = $pdo->prepare('SELECT anonymous_user_id FROM daily_logs WHERE id = :id');
    } else {
        $ownStmt = $pdo->prepare('SELECT anonymous_user_id FROM article_reads WHERE id = :id');
    }
    $ownStmt->execute([':id' => $tid]);
    $owner = $ownStmt->fetchColumn();

    if ($owner === false) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'target not found']);
        exit;
    }
    if ((int)$owner === (int)$uid) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'cannot cheer own record']);
        exit;
    }

    // 既に応援済みか？
    $stmt = $pdo->prepare('
        SELECT id FROM cheers
        WHERE anonymous_user_id = :uid AND target_type = :tt AND target_id = :tid
        LIMIT 1
    ');
    $stmt->execute([':uid' => $uid, ':tt' => $type, ':tid' => $tid]);
    $row = $stmt->fetch();

    if ($row) {
        // 取り消し
        $del = $pdo->prepare('DELETE FROM cheers WHERE id = :id');
        $del->execute([':id' => $row['id']]);
        $cheered = false;
    } else {
        // 追加
        $ins = $pdo->prepare('
            INSERT INTO cheers(anonymous_user_id, target_type, target_id, created_at)
            VALUES(:uid, :tt, :tid, NOW())
        ');
        $ins->execute([':uid' => $uid, ':tt' => $type, ':tid' => $tid]);
        $cheered = true;
    }

    // 最新カウント（対象に付いた総応援数）
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM cheers WHERE target_type = :tt AND target_id = :tid');
    $countStmt->execute([':tt' => $type, ':tid' => $tid]);
    $count = (int)$countStmt->fetchColumn();

    echo json_encode(['ok' => true, 'cheered' => $cheered, 'count' => $count]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}