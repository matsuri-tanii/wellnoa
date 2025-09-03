<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>今日のお散歩チャンス判断＆記録アプリ</title>
    <link rel="stylesheet" href="./CSS/style.css">
</head>

<body>
    <div class="container">
        <h1>お散歩チャンス</h1>
        <div><a href="index.php">メインページに戻る</a></div>

        <div id="weather-info">
            天気情報を取得中...
        </div>

        <div id="chance-message" style="display: none;">
            ✨ 今がお散歩チャンスだよ！ ✨
        </div>
        <div id="no-chance-message" style="display: none;">
            今じゃないかな...また後でチェックしてね！
        </div>

        <button id="record-button">お散歩に行った！</button>

        <div id="record-list">
            <h2>🚶‍♀️ これまでのお散歩記録</h2>
            <ul id="records">
                <li>まだ記録がありません。</li>
            </ul>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script type="module" src="./JS/main.js"></script>
</body>
</html>