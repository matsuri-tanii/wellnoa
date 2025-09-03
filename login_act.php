<?php
include("funcs.php");

// データ受け取り
$username = $_POST["username"];
$password = $_POST["password"];

// DB接続
$pdo = db_conn();

// SQL文（手打ちで確認）
$sql = 'SELECT * FROM users_table WHERE username = :username AND password = :password AND deleted_at IS NULL';
$stmt = $pdo->prepare($sql);

// プレースホルダーにバインド
$stmt->bindValue(':username', $username, PDO::PARAM_STR);
$stmt->bindValue(':password', $password, PDO::PARAM_STR);

try {
    $stmt->execute();
} catch (PDOException $e) {
    echo json_encode([
        "sql error" => $e->getMessage(),
        "username" => $username,
        "password" => $password,
        "sql" => $sql
    ]);
    exit();
}

// 結果取得・処理
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    echo "<p>ログインに失敗しました。</p>";
    echo '<a href="login.php">戻る</a>';
} else {
    session_start();
    $_SESSION["session_id"] = session_id();
    $_SESSION["username"] = $user["username"];
    $_SESSION["is_admin"] = $user["is_admin"];
    $_SESSION["user_id"] = $user["id"];

    header("Location: read.php");
    exit();
}