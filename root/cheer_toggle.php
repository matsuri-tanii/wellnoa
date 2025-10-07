<?php
declare(strict_types=1);

require_once __DIR__ . '/funcs.php';
adopt_incoming_code(); // QRで来たコードをクッキー採用（存在すれば）

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = db_conn();

    // 匿名ユーザーIDの取得
    $uid = current_anon_user_id();
    if (!$uid) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'anonymous_user_id missing']);
        exit;
    }

    // 入力取得・検証
    $type = isset($_POST['target_type']) ? strtolower(trim((string)$_POST['target_type'])) : '';
    $tid  = isset($_POST['target_id'])   ? (int)$_POST['target_id'] : 0;

    if (!in_array($type, ['daily', 'read'], true) || $tid <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'invalid target_type or target_id']);
        exit;
    }

    // 対象オーナー確認（自分の投稿は応援不可）
    if ($type === 'daily') {
        $ownStmt = $pdo->prepare('SELECT anonymous_user_id FROM daily_logs WHERE id = :id');
    } else { // read
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

    // ====== トグル（同時実行に比較的強い手順） ======
    // 1) まず「取り消し」を試す（既に応援していたら1件消える）
    $pdo->beginTransaction();

    $del = $pdo->prepare('
        DELETE FROM cheers
        WHERE anonymous_user_id = :uid AND target_type = :tt AND target_id = :tid
        LIMIT 1
    ');
    $del->execute([':uid' => $uid, ':tt' => $type, ':tid' => $tid]);

    if ($del->rowCount() > 0) {
        // 取り消し成功
        $cheered = false;
        $pdo->commit();
    } else {
        // 2) 応援を入れてみる（ユニーク衝突は“既に応援中”と扱う）
        $ins = $pdo->prepare('
            INSERT INTO cheers (anonymous_user_id, target_type, target_id, created_at)
            VALUES (:uid, :tt, :tid, NOW())
        ');
        try {
            $ins->execute([':uid' => $uid, ':tt' => $type, ':tid' => $tid]);
            $cheered = true; // 新規に入れられた
            $pdo->commit();
        } catch (PDOException $e) {
            // 1062: duplicate（ユニーク制約）= ほぼ同時に誰か/自分が入れた
            if ($e->getCode() === '23000') {
                // 既に応援状態とみなす（トグル結果は「応援中」）
                $cheered = true;
                $pdo->rollBack(); // ここはINSERT失敗なのでロールバック
            } else {
                $pdo->rollBack();
                throw $e;
            }
        }
    }

    // 最新カウント（この対象に付いた総応援数）
    $countStmt = $pdo->prepare('
        SELECT COUNT(*) FROM cheers WHERE target_type = :tt AND target_id = :tid
    ');
    $countStmt->execute([':tt' => $type, ':tid' => $tid]);
    $count = (int)$countStmt->fetchColumn();

    echo json_encode([
        'ok'      => true,
        'cheered' => $cheered, // true=応援中 / false=取り消し後
        'count'   => $count,   // 対象の総応援数
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}