<?php

//XSS対応（ echoする場所で使用！それ以外はNG ）
function h($str)
{
    return htmlspecialchars($str, ENT_QUOTES);
}

//DB接続
function db_conn()
{
    require_once "env.php";
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

?>
