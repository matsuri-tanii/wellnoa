<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ログイン画面</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f4f8f9;
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    form {
      background-color: #ffffff;
      padding: 30px 40px;
      border-radius: 16px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
      width: 100%;
      max-width: 400px;
    }

    fieldset {
      border: none;
      padding: 0;
    }

    legend {
      font-size: 1.4rem;
      font-weight: bold;
      color: #2c3e50;
      margin-bottom: 20px;
      text-align: center;
    }

    div {
      margin-bottom: 16px;
    }

    label {
      display: block;
      font-weight: 500;
      margin-bottom: 6px;
      color: #34495e;
    }

    input[type="text"],
    input[type="password"] {
      width: 100%;
      padding: 10px 14px;
      border: 1px solid #ccd6dd;
      border-radius: 8px;
      background-color: #fefefe;
      box-sizing: border-box;
      font-size: 1rem;
    }

    .btn {
      background-color: #4aa8b4;
      color: white;
      padding: 12px;
      border-radius: 8px;
      text-align: center;
      font-weight: bold;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    .btn:hover {
      background-color: #3c919e;
    }

    a {
      display: block;
      text-align: center;
      margin-top: 20px;
      color: #4aa8b4;
      text-decoration: none;
      font-size: 0.95rem;
    }

    a:hover {
      text-decoration: underline;
    }
  </style>
</head>

<body>
  <form action="login_act.php" method="POST">
    <fieldset>
      <legend>ログイン画面</legend>
      <div>
        <label for="username">ユーザネーム</label>
        <input type="text" name="username" id="username" required />
      </div>
      <div>
        <label for="password">パスワード</label>
        <input type="password" name="password" id="password" required />
      </div>
      <div>
        <button class="btn" type="submit">ログイン</button>
      </div>
      <a href="register.php">ユーザ登録画面へ</a>
      <a href="index.php">メインページに戻る</a>
    </fieldset>
  </form>
</body>

</html>