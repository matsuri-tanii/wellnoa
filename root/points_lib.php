<?php
declare(strict_types=1);
require_once __DIR__.'/funcs.php'; adopt_incoming_code();

/* スキーマ自己修復 */
function ensure_points_schema(PDO $pdo): void {
    // テーブル作成（無ければ）
    $pdo->exec("CREATE TABLE IF NOT EXISTS anonymous_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        anon_code VARCHAR(64) NOT NULL,
        total_points INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_anon_code (anon_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 既存テーブルに列が無い・型が違う場合の保険
    $col = $pdo->query("SHOW COLUMNS FROM anonymous_users LIKE 'anon_code'")->fetch();
    if (!$col) { try { $pdo->exec("ALTER TABLE anonymous_users ADD COLUMN anon_code VARCHAR(64) NOT NULL"); } catch (\Throwable $e) {} }
    $col = $pdo->query("SHOW COLUMNS FROM anonymous_users LIKE 'total_points'")->fetch();
    if (!$col) { try { $pdo->exec("ALTER TABLE anonymous_users ADD COLUMN total_points INT NOT NULL DEFAULT 0"); } catch (\Throwable $e) {} }
    // ユニーク制約（無ければ）
    $idx = $pdo->query("SHOW INDEX FROM anonymous_users WHERE Key_name='uniq_anon_code'")->fetch();
    if (!$idx) { try { $pdo->exec("CREATE UNIQUE INDEX uniq_anon_code ON anonymous_users(anon_code)"); } catch (\Throwable $e) {} }
}

/**
 * ポイント加算（anon_code 基準）
 * 失敗理由が分かるようにログ出力も付けます。
 */
function add_point_for(string $anonCode, string $eventKey): bool {
    static $cfg = null;
    if ($cfg === null) $cfg = require __DIR__.'/points_config.php';
    $earn = (int)($cfg['earn'][$eventKey] ?? 0);
    if ($earn <= 0) return true; // 加点0なら成功扱い

    $pdo = db_conn();
    ensure_points_schema($pdo);

    $sql = "INSERT INTO anonymous_users(anon_code,total_points)
            VALUES(:c,:p)
            ON DUPLICATE KEY UPDATE total_points = total_points + VALUES(total_points)";
    $stmt = $pdo->prepare($sql);
    try {
        $ok = $stmt->execute([':c' => $anonCode, ':p' => $earn]);
        error_log('[points] add_point_for ok=' . ($ok?1:0) . ' code=' . $anonCode . ' +=' . $earn . ' rowCount=' . $stmt->rowCount());
        return $ok;
    } catch (\Throwable $e) {
        error_log('[points] add_point_for FAIL code=' . $anonCode . ' err=' . $e->getMessage());
        throw $e;
    }
}

/** 合計ポイント取得 */
function get_total_points(string $anonCode): int {
    $pdo = db_conn();
    ensure_points_schema($pdo);
    $stmt = $pdo->prepare("SELECT total_points FROM anonymous_users WHERE anon_code=:c");
    $stmt->execute([':c'=>$anonCode]);
    $row = $stmt->fetch();
    return (int)($row['total_points'] ?? 0);
}

/** 画面用集計 */
function calc_points_for_user(PDO $pdo, int $uid): array
{
    // 行動系（見た目用ポイント）
    $st = $pdo->prepare("SELECT COUNT(*) FROM article_reads WHERE anonymous_user_id = :uid");
    $st->execute([':uid' => $uid]);
    $readCount = (int)$st->fetchColumn();

    $st = $pdo->prepare("SELECT COUNT(*) FROM daily_logs WHERE anonymous_user_id = :uid");
    $st->execute([':uid' => $uid]);
    $logsCount = (int)$st->fetchColumn();

    try {
        $st = $pdo->prepare("SELECT COUNT(*) FROM cheers WHERE anonymous_user_id = :uid");
        $st->execute([':uid' => $uid]);
        $cheersCount = (int)$st->fetchColumn();
    } catch (\Throwable $e) {
        $cheersCount = 0;
    }

    $activityPoints = $logsCount + $cheersCount;
    $articlePoints  = $readCount;

    // ★ 合計ポイントは cookie の anon_code 基準で取得（ここが超重要）
    $anonCode = current_anon_code();
    $total    = get_total_points($anonCode);

    // 段階（省略：元のままでOK）
    $levels = [0=>'芽が出た原っぱ',10=>'小川のほとり',30=>'木漏れ日の林',60=>'見晴らしの丘',100=>'夜空の展望台',160=>'虹の見える峠'];
    ksort($levels);
    $currentThreshold = 0; $currentLabel = reset($levels);
    foreach ($levels as $th=>$label){ if($total >= $th){ $currentThreshold=$th; $currentLabel=$label; } else break; }
    $nextThreshold = null; foreach ($levels as $th=>$label){ if($th > $currentThreshold){ $nextThreshold=$th; break; } }
    if ($nextThreshold === null){ $ratio=1.0; $toNext=0; }
    else { $range=max(1,$nextThreshold-$currentThreshold); $toNext=max(0,$nextThreshold-$total); $ratio=min(1.0,max(0.0,($total-$currentThreshold)/$range)); }

    return [
        'total_points'    => $total,
        'activity_points' => $activityPoints,
        'article_points'  => $articlePoints,
        'read_count'      => $readCount,
        'level'           => ['label'=>$currentLabel],
        'progress'        => [
            'current_threshold'=>$currentThreshold,
            'next_threshold'=>$nextThreshold,
            'to_next'=>$toNext,
            'ratio'=>$ratio,
        ],
    ];
}