<?php
declare(strict_types=1);

require_once __DIR__ . '/funcs.php';
adopt_incoming_code(); // QRで来たコードをクッキー採用（存在すれば）

// JSONとして返す＆キャッシュさせない
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

try {
    // 1) メソッド＆入力検証
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'method not allowed']); exit;
    }

    $pdo = db_conn();

    // 匿名ユーザーID
    $uid = current_anon_user_id();
    if (!$uid) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'anonymous_user_id missing']); exit;
    }

    // 入力取得
    $type = strtolower(trim((string)($_POST['target_type'] ?? '')));
    $tid  = (int)($_POST['target_id'] ?? 0);

    if (!in_array($type, ['daily', 'read'], true) || $tid <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'invalid target_type or target_id']); exit;
    }

    // 2) 対象レコードのオーナー確認（存在しなければ404 / 自分なら403）
    if ($type === 'daily') {
        $ownStmt = $pdo->prepare('SELECT anonymous_user_id FROM daily_logs WHERE id = :id LIMIT 1');
    } else { // 'read'
        $ownStmt = $pdo->prepare('SELECT anonymous_user_id FROM article_reads WHERE id = :id LIMIT 1');
    }
    $ownStmt->execute([':id' => $tid]);
    $owner = $ownStmt->fetchColumn();

    if ($owner === false) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'target not found']); exit;
    }
    if ((int)$owner === (int)$uid) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'cannot cheer own record']); exit;
    }

    // 3) 応援をトグル（ユニーク制約がある前提: (anonymous_user_id, target_type, target_id)）
    //    先にINSERTを試行し、既にある場合はDELETEで取り消し
    $cheered = null;

    // INSERT 試行
    $ins = $pdo->prepare('
        INSERT INTO cheers (anonymous_user_id, target_type, target_id, created_at)
        VALUES (:uid, :tt, :tid, NOW())
    ');
    try {
        $ins->execute([':uid' => $uid, ':tt' => $type, ':tid' => $tid]);
        // 挿入できた → 応援ON
        $cheered = true;
    } catch (PDOException $e) {
        // ユニーク制約違反（＝既に応援済み）なら削除に切り替え
        // 1062: ER_DUP_ENTRY
        if ((int)$e->errorInfo[1] === 1062) {
            $del = $pdo->prepare('
                DELETE FROM cheers
                WHERE anonymous_user_id = :uid AND target_type = :tt AND target_id = :tid
                LIMIT 1
            ');
            $del->execute([':uid' => $uid, ':tt' => $type, ':tid' => $tid]);
            $cheered = false;
        } else {
            // それ以外のDBエラーは素通し
            throw $e;
        }
    }

    // 4) 最新カウント
    $countStmt = $pdo->prepare('
        SELECT COUNT(*) FROM cheers WHERE target_type = :tt AND target_id = :tid
    ');
    $countStmt->execute([':tt' => $type, ':tid' => $tid]);
    $count = (int)$countStmt->fetchColumn();

    echo json_encode(['ok' => true, 'cheered' => $cheered, 'count' => $count], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}