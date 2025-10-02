<?php
require_once __DIR__ . '/funcs.php';
adopt_incoming_code(); // QRで来たコードをクッキー採用（存在すれば）

header('Content-Type: application/json; charset=utf-8');

try {
    // ログイン相当（匿名ユーザーID）
    $uid = current_anon_user_id();
    if (!$uid) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'anonymous_user_id missing']);
        exit;
    }

    // 入力取得（フロントは target_type / target_id を送っている）
    $type = isset($_POST['target_type']) ? (string)$_POST['target_type'] : '';
    $tid  = isset($_POST['target_id'])   ? (int)$_POST['target_id']   : 0;

    // バリデーション
    $type = strtolower($type);
    if (!in_array($type, ['daily', 'read'], true) || $tid <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'invalid target_type or target_id']);
        exit;
    }

    $pdo = db_conn();

    // 既に自分が応援しているか？
    $sql = "SELECT id FROM cheers WHERE anonymous_user_id = :uid AND target_type = :tt AND target_id = :tid LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':uid' => $uid, ':tt' => $type, ':tid' => $tid]);
    $row = $stmt->fetch();

    if ($row) {
        // 取り消し
        $pdo->prepare("DELETE FROM cheers WHERE id = :id")->execute([':id' => $row['id']]);
        $cheered = false;
    } else {
        // 追加
        $pdo->prepare("
            INSERT INTO cheers(anonymous_user_id, target_type, target_id, created_at)
            VALUES (:uid, :tt, :tid, NOW())
        ")->execute([':uid' => $uid, ':tt' => $type, ':tid' => $tid]);

        // ※ポイント連動をしたい場合は points_lib.php の呼び出しをここで。
        // require_once __DIR__.'/points_lib.php';
        // add_point_for((string)$uid, 'cheer_send');

        $cheered = true;
    }

    // 最新の合計カウントを返す
    $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM cheers WHERE target_type = :tt AND target_id = :tid");
    $stmt->execute([':tt' => $type, ':tid' => $tid]);
    $count = (int)($stmt->fetchColumn() ?: 0);

    echo json_encode(['ok' => true, 'cheered' => $cheered, 'count' => $count]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}