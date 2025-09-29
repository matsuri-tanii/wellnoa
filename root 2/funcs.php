<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

//XSS対応（ echoする場所で使用！それ以外はNG ）
function h($str)
{
    return htmlspecialchars($str, ENT_QUOTES);
}

//DB接続
function db_conn()
{
    $envCandidates = [
      __DIR__ . '/../secure/env.php', // 本番（サーバー）
      __DIR__ . '/env.php',     // ローカル（XAMPP）
    ];

    $loaded = false;
    foreach ($envCandidates as $p) {
      if (is_file($p)) { require_once $p; $loaded = true; break; }
    }
    if (!$loaded) {
      http_response_code(500);
      echo "Config file not found. Looking for:\n" . implode("\n", $envCandidates);
      exit;
    }
    // このコードを実行しているサーバー情報を取得して変数に保存
    $server_info = $_SERVER;

    // 変数の箱だけ先に用意
    $db_name;
    $db_host;
    $db_id;
    $db_pw;

    // env.phpからデータのオブジェクトを取得
    $sakura_db_info = sakura_db_info();


    // サーバー情報の中のサーバの名前がlocalhostだった場合と本番だった場合で処理を分ける
    if ($server_info["SERVER_NAME"] == "localhost") {
        $db_name = 'wellnoa';          // データベース名
        $db_host = 'localhost';         // DBホスト
        $db_id   = 'root';              // アカウント名
        $db_pw   = '';                  // パスワード：XAMPPはパスワード無しに修正してください。
    } else {
        // 連想配列の情報変数に格納
        $db_name =  $sakura_db_info["db_name"];    //データベース名
        $db_host =  $sakura_db_info["db_host"];    //DBホスト
        $db_id =    $sakura_db_info["db_id"];      //アカウント名(登録しているドメイン)
        $db_pw =    $sakura_db_info["db_pw"];      //さくらサーバのパスワード
    }

    try {
        $server_info ='mysql:dbname='.$db_name.';charset=utf8;host='.$db_host;
        $pdo = new PDO($server_info, $db_id, $db_pw);

        return $pdo;
    } catch (PDOException $e) {
        exit('DB Connection Error:' . $e->getMessage());
    }
}


//SQLエラー
function sql_error($stmt)
{
    //execute（SQL実行時にエラーがある場合）
    $error = $stmt->errorInfo();
    exit('SQLError:' . $error[2]);
}

// ログイン状態のチェック関数
function check_session_id()
{
  if (!isset($_SESSION["session_id"]) ||$_SESSION["session_id"] !== session_id()) {
    header('Location:login.php');
    exit();
  } else {
    session_regenerate_id(true);
    $_SESSION["session_id"] = session_id();
  }
}

function check_admin()
{
  //管理者じゃないユーザーは一覧画面に移動
  if (!isset($_SESSION["is_admin"]) ||$_SESSION["is_admin"] !== 1) {
    header('Location:login.php');
    exit();
  }
}

function set_flash(string $message, string $type='success'): void {
  if (session_status() === PHP_SESSION_NONE) { session_start(); }
  $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}
function pop_flash(): ?array {
  if (session_status() === PHP_SESSION_NONE) { session_start(); }
  if (!isset($_SESSION['flash'])) return null;
  $f = $_SESSION['flash'];
  unset($_SESSION['flash']);
  return $f;
}

/** 現在の匿名ID（数値）を取得。なければ null */
function current_guest_id(): ?int {
  if (!isset($_COOKIE['guest_numeric_id'])) return null;
  $v = (int)$_COOKIE['guest_numeric_id'];
  return $v > 0 ? $v : null;
}

/** 匿名IDをCookieにセット（1年） */
function set_guest_cookie(int $gid): void {
  setcookie('guest_numeric_id', (string)$gid, [
    'expires'  => time()+60*60*24*365,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    // 'secure' => true, // 本番が https のとき有効化
  ]);
}

/** 匿名ユーザーをDB上に作成（存在しなければ）してIDを返す */
function ensure_anon_user(PDO $pdo, ?int $pref_gid = null): int {
  if ($pref_gid) {
    // 既存IDが有効か確認
    $st = $pdo->prepare('SELECT id FROM anonymous_users WHERE id = :id');
    $st->execute([':id'=>$pref_gid]);
    if ($st->fetchColumn()) return $pref_gid;
  }
  // 新規作成
  $pdo->exec('INSERT INTO anonymous_users(created_at,updated_at) VALUES (NOW(),NOW())');
  return (int)$pdo->lastInsertId();
}
?>
