<?php
declare(strict_types=1);
require_once __DIR__.'/funcs.php'; adopt_incoming_code();

/**
 * 匿名利用者の累計行動を anonymous_users テーブルに記録するユーティリティ。
 * もしテーブルが無い/使わない場合は呼ばなくてもOK。
 */
function add_point_for(string $anonCode, string $eventKey): void {
    static $cfg = null;
    if ($cfg === null) $cfg = require __DIR__.'/points_config.php';

    $earn = $cfg['earn'][$eventKey] ?? 0;
    if ($earn <= 0) return;

    $pdo = db_conn();
    // anonymous_users に anon_code が無ければ作成、あれば update
    $pdo->exec("CREATE TABLE IF NOT EXISTS anonymous_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        anon_code VARCHAR(32) UNIQUE,
        total_points INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $sql = "INSERT INTO anonymous_users(anon_code,total_points) VALUES(:c,:p)
            ON DUPLICATE KEY UPDATE total_points = total_points + VALUES(total_points)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':c'=>$anonCode, ':p'=>$earn]);
}

function get_total_points(string $anonCode): int {
    $pdo = db_conn();
    $stmt = $pdo->prepare("SELECT total_points FROM anonymous_users WHERE anon_code=:c");
    $stmt->execute([':c'=>$anonCode]);
    $row = $stmt->fetch();
    return (int)($row['total_points'] ?? 0);
}
