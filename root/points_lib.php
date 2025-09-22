<?php
// points_lib.php
require_once __DIR__ . '/funcs.php';

function calc_points_for_user(PDO $pdo, $uid): array {
  $cfg = require __DIR__ . '/points_config.php';
  $actPts = $cfg['activity_points'];
  $readPt = (int)$cfg['article_read_point'];
  $levels = $cfg['level_thresholds'];

  // 1) 行動ログ（daily_logs） => activity_type を CSV で合計
  $sql = 'SELECT activity_type FROM daily_logs WHERE anonymous_user_id = :uid';
  $st  = $pdo->prepare($sql);
  $st->bindValue(':uid', $uid, PDO::PARAM_INT);
  $st->execute();
  $activityTotal = 0;

  while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
    if (empty($row['activity_type'])) continue;
    // "散歩,ストレッチ" のようなCSVを配列に
    $items = array_map('trim', explode(',', $row['activity_type']));
    foreach ($items as $item) {
      if ($item === '') continue;
      $activityTotal += $actPts[$item] ?? 0; // 未定義は0点
    }
  }

  // 2) 読了数（article_reads）
  $sql = 'SELECT COUNT(*) FROM article_reads WHERE anonymous_user_id = :uid';
  $st = $pdo->prepare($sql);
  $st->bindValue(':uid', $uid, PDO::PARAM_INT);
  $st->execute();
  $readCount = (int)$st->fetchColumn();
  $articleTotal = $readCount * $readPt;

  $total = $activityTotal + $articleTotal;

  // 3) レベル判定（最大しきい値を超えたもの）
  ksort($levels); // 念のため昇順
  $currentLevelKey = 0;
  $currentLevel = $levels[0];
  foreach ($levels as $threshold => $meta) {
    if ($total >= $threshold) {
      $currentLevelKey = $threshold;
      $currentLevel = $meta;
    } else {
      break;
    }
  }

  // 次レベル
  $keys = array_keys($levels);
  sort($keys);
  $nextKey = null;
  foreach ($keys as $k) {
    if ($k > $currentLevelKey) { $nextKey = $k; break; }
  }

  $progress = [
    'current_threshold' => $currentLevelKey,
    'next_threshold'    => $nextKey,
    'in_level_points'   => $total - $currentLevelKey,
    'to_next'           => $nextKey ? ($nextKey - $total) : 0,
    'ratio'             => ($nextKey ? ($total - $currentLevelKey) / ($nextKey - $currentLevelKey) : 1.0)
  ];

  return [
    'total_points'    => $total,
    'activity_points' => $activityTotal,
    'article_points'  => $articleTotal,
    'read_count'      => $readCount,
    'level'           => $currentLevel, // ['code'=>'新緑','label'=>'新緑の景']
    'progress'        => $progress,
  ];
}