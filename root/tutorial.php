<?php
require_once __DIR__.'/funcs.php';

// ?code=xxxx で来たらそのコードを採用してクッキーに上書き
$incoming = isset($_GET['code']) ? preg_replace('/[^a-z0-9]/','', strtolower((string)$_GET['code'])) : '';
if ($incoming !== '') {
    setcookie('anon_code', $incoming, [
        'expires'  => time() + 60*60*24*365,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
    $_COOKIE['anon_code'] = $incoming;
}

$me = current_anon_code();
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>Wellnoa チュートリアル</title>
  <style>body{font-family:system-ui,-apple-system,sans-serif;margin:24px}</style>
</head>
<body>
  <h1>ようこそ 👋</h1>
  <p>あなたの匿名ID: <code><?=h($me)?></code></p>
  <ol>
    <li>まずは今日の記録をつけてみましょう → <a href="create.php">日記を追加</a></li>
    <li>読み物から学んでみましょう → <a href="read_all.php">記事一覧</a></li>
  </ol>
  <p><a href="index.php">トップへ戻る</a></p>
</body>
</html>
