<?php
/**
 * save_article_read.php
 * 記事の「読了」を記録（初回のみ）し、ポイントを加算するエンドポイント。
 * 返り値は JSON。
 */
declare(strict_types=1);

require_once __DIR__ . '/funcs.php';
adopt_incoming_code(); // GET/POSTからanon_code採用する実装（あなたの環境）

require_once __DIR__ . '/points_lib.php';
header('Content-Type: application/json; charset=utf-8');

/* -----------------------------------------------------------
   どこでも必ず見えるファイルログ（php.ini非依存）
----------------------------------------------------------- */
if (!function_exists('app_log')) {
  function app_log(string $msg): void {
    $dir = __DIR__ . '/_logs';
    if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
    @file_put_contents($dir . '/app_trace.log', '[' . date('c') . '] ' . $msg . "\n", FILE_APPEND);
  }
}

/* -----------------------------------------------------------
   入力バリデーション
----------------------------------------------------------- */
try {
  $articleId = isset($_POST['article_id']) ? (int)$_POST['article_id'] : 0;
  if ($articleId <= 0) {
    app_log("[save_article_read] invalid article_id: " . json_encode($_POST, JSON_UNESCAPED_UNICODE));
    echo json_encode(['ok' => false, 'error' => 'invalid article_id']);
    exit;
  }

  $uid  = current_anon_user_id(); // anonymous_users.id 相当の匿名ユーザーID（数値）
  $code = current_anon_code();    // anon_code（文字列）

  if (!$uid) {
    app_log("[save_article_read][ERR] missing uid");
    echo json_encode(['ok' => false, 'error' => 'anonymous_user_id missing']);
    exit;
  }
  if (!$code) {
    // 念のため自動採番（環境の実装によっては不要）
    $code = bin2hex(random_bytes(16));
    setcookie('anon_code', $code, time() + 3600 * 24 * 365, '/');
    app_log("[save_article_read][WARN] anon_code was empty -> generated: $code");
  }

  $pdo = db_conn();

  // どのDBに刺さっているか確認ログ（デバッグ用）
  try {
    $who = $pdo->query("SELECT CURRENT_USER() user, DATABASE() db")->fetch();
    app_log("[save_article_read] DB user={$who['user']} db={$who['db']} uid=$uid aid=$articleId code=$code");
  } catch (\Throwable $e) {
    app_log("[save_article_read][WARN] whois DB failed: " . $e->getMessage());
  }

  /* -----------------------------------------------------------
     既読チェック（初回のみ登録する仕様）
  ----------------------------------------------------------- */
  $chk = $pdo->prepare("
    SELECT 1
    FROM article_reads
    WHERE anonymous_user_id = :uid AND article_id = :aid
    LIMIT 1
  ");
  $chk->execute([':uid' => $uid, ':aid' => $articleId]);
  $already = (bool)$chk->fetchColumn();

  if ($already) {
    app_log("[save_article_read] already recorded uid=$uid aid=$articleId");
    echo json_encode(['ok' => true, 'already' => true, 'affected' => 0]);
    exit;
  }

  /* -----------------------------------------------------------
     初回読了のINSERT ＋ ポイント加算
  ----------------------------------------------------------- */
  $pdo->beginTransaction();
  try {
    $ins = $pdo->prepare("
      INSERT INTO article_reads (anonymous_user_id, article_id, read_date, created_at)
      VALUES (:uid, :aid, CURDATE(), NOW())
    ");
    $okIns = $ins->execute([':uid' => $uid, ':aid' => $articleId]);
    app_log("[save_article_read] insert article_reads ok=" . ($okIns ? 1 : 0) . " rc=" . $ins->rowCount());

    // ポイント加算（points_config.php の 'earn' に 'article_read' がある前提）
    $okPt = false;
    try {
      ensure_points_schema($pdo); // 念のため（points_lib.php）
      $okPt = add_point_for($code, 'article_read');
      app_log("[save_article_read] add_point_for ok=" . ($okPt ? 1 : 0) . " code=$code");
    } catch (\Throwable $e) {
      // ポイント加算失敗しても記事読了は記録済み。ロールバックはしない方針。
      app_log("[save_article_read][POINTS][EX] " . $e->getMessage());
    }

    $pdo->commit();

    // 画面更新用に現状ポイントを返しておくと便利
    $total = 0;
    try {
      $total = get_total_points($code);
    } catch (\Throwable $e) {
      app_log("[save_article_read][WARN] get_total_points failed: " . $e->getMessage());
    }

    echo json_encode([
      'ok'       => true,
      'already'  => false,
      'affected' => (int)$ins->rowCount(),
      'points'   => [
        'added'       => $okPt ? 1 : 0, // 実際の加点値は points_config.php の設定次第
        'total'       => (int)$total,
        'event'       => 'article_read',
        'anon_code'   => $code,
      ],
    ]);
    exit;

  } catch (\Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    app_log("[save_article_read][TX][EX] " . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    exit;
  }

} catch (\Throwable $e) {
  app_log("[save_article_read][FATAL] " . $e->getMessage());
  echo json_encode(['ok' => false, 'error' => 'unexpected error']);
  exit;
}