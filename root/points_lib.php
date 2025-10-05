<?php
declare(strict_types=1);
require_once __DIR__.'/funcs.php';

/* テーブル構成に基づくポイント管理 */
function ensure_points_schema(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS anonymous_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        anon_code VARCHAR(64) NOT NULL UNIQUE,
        total_points INT NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/** ポイント加算 */
function add_point_for(string $anonCode, string $eventKey): bool {
    static $cfg = null;
    if ($cfg === null) $cfg = require __DIR__.'/points_config.php';
    $earn = (int)($cfg['earn'][$eventKey] ?? 0);
    if ($earn <= 0) return true;

    $pdo = db_conn();
    ensure_points_schema($pdo);
    $sql = "INSERT INTO anonymous_users (anon_code,total_points)
            VALUES(:c,:p)
            ON DUPLICATE KEY UPDATE total_points = total_points + VALUES(total_points)";
    $st = $pdo->prepare($sql);
    $ok = $st->execute([':c'=>$anonCode,':p'=>$earn]);
    error_log("[points] event=$eventKey code=$anonCode +$earn");
    return $ok;
}

/** ユーザー合計計算（記事・日記・チアー） */
function calc_points_for_user(PDO $pdo, int $uid): array {
    $st = $pdo->prepare("SELECT COUNT(*) FROM article_reads WHERE anonymous_user_id=:uid");
    $st->execute([':uid'=>$uid]);
    $readCount = (int)$st->fetchColumn();

    $st = $pdo->prepare("SELECT COUNT(*) FROM daily_logs WHERE anonymous_user_id=:uid");
    $st->execute([':uid'=>$uid]);
    $logCount = (int)$st->fetchColumn();

    try {
        $st = $pdo->prepare("SELECT COUNT(*) FROM cheers WHERE anonymous_user_id=:uid");
        $st->execute([':uid'=>$uid]);
        $cheerCount = (int)$st->fetchColumn();
    } catch (Throwable $e) {
        $cheerCount = 0;
    }

    $activityPoints = $logCount + $cheerCount;
    $articlePoints = $readCount;

    $anonCode = current_anon_code();
    $total = get_total_points($anonCode);

    // 合計（動的加算のフォールバック）
    $displayTotal = max($total, $activityPoints + $articlePoints);

    $levels = [0=>'芽が出た原っぱ',10=>'小川のほとり',30=>'木漏れ日の林',60=>'見晴らしの丘',100=>'夜空の展望台',160=>'虹の見える峠'];
    ksort($levels);
    $curTh=0;$curLabel=reset($levels);
    foreach($levels as $th=>$label){if($displayTotal>=$th){$curTh=$th;$curLabel=$label;}else break;}
    $next=null;foreach($levels as $th=>$label){if($th>$curTh){$next=$th;break;}}
    $toNext=$next?max(0,$next-$displayTotal):0;
    $ratio=$next?min(1.0,max(0.0,($displayTotal-$curTh)/($next-$curTh))):1.0;

    return [
        'total_points'=>$displayTotal,
        'activity_points'=>$activityPoints,
        'article_points'=>$articlePoints,
        'read_count'=>$readCount,
        'level'=>['label'=>$curLabel],
        'progress'=>[
            'current_threshold'=>$curTh,
            'next_threshold'=>$next,
            'to_next'=>$toNext,
            'ratio'=>$ratio
        ],
    ];
}

function get_total_points(string $anonCode): int {
    $pdo = db_conn();
    $st = $pdo->prepare("SELECT total_points FROM anonymous_users WHERE anon_code=:c");
    $st->execute([':c'=>$anonCode]);
    return (int)($st->fetchColumn() ?? 0);
}