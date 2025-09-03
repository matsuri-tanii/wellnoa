<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Wellnoa - あなたの小さな健康習慣</title>
  <link rel="stylesheet" href="style.css"> <!-- 外部CSSがあれば -->
  <style>
    body {
      font-family: 'Helvetica', sans-serif;
      background: #f0f9f7;
      color: #333;
      margin: 0;
      padding: 0;
    }
    header {
      background: #a7d7c5;
      padding: 1.5em;
      text-align: center;
    }
    h1 {
      margin: 0;
      font-size: 2em;
    }
    p.tagline {
      font-size: 1.2em;
      color: #555;
    }
    .container {
      padding: 2em;
      text-align: center;
    }
    .btn {
      display: inline-block;
      margin: 1em;
      padding: 1em 2em;
      font-size: 1em;
      background: #76c7b0;
      color: white;
      text-decoration: none;
      border-radius: 8px;
      transition: 0.3s;
    }
    .btn:hover {
      background: #5db9a1;
    }
  </style>
</head>
<body>

<header>
  <h1>Wellnoa</h1>
  <p class="tagline">あなたの健康へ、ちいさな一歩を。</p>
</header>

<div class="container">
  <a href="login.php" class="btn">日々のきろく</a>
  <a href="osanpo.php" class="btn">お散歩チャンス</a>
  <a href="janken.php" class="btn">脳トレじゃんけん</a>
  <a href="articles.php" class="btn">[未実装]健康記事を読む</a>
  <a href="points.php" class="btn">[未実装]成長を見る</a>
</div>

</body>
</html>