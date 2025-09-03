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
    #news {
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
        text-align: center;
    }

    #news p {
        font-size: 1rem;
        margin-bottom: 60px;
    }

    .news_box_container {
        display: flex;
        flex-wrap: wrap;
        gap: 60px 0;
        justify-content: space-between;
        padding: 0 120px;
    }

    .news_box {
        display: flex;
        flex-direction: column;
        width: 300px;
    }

    .news_box:hover {
        box-shadow: 0px 12px 24px #0b5dae63;
        transform: translateY(-4px);
    }

    .news_date {
        padding: 30px 0 24px;
    }

    .news_text {
        line-height: 200%;
    }

    .news_box_more {
        position: relative;
    }

    .news_box_more label {
        position: absolute;
        display: table;
        left: 50%;
        bottom: 0;
        padding: 16px 0;
        margin: 60px 0;
        width: 300px;
        font-size: 1.125rem;
        color: white;
        border:5px solid #a7d7c5;
        text-align: center;
        background-color: #a7d7c5;
        transform: translateX(-50%);
        cursor: pointer;
        z-index: 1;
        box-sizing: border-box;
    }

    .news_box_more label:hover {
        border:5px solid #a7d7c5;
        background-color: white;
        color: #a7d7c5;
        cursor: pointer;
    }
    .news_box_more label::before{
        content: 'More';
    }

    .news_box_more input[type="checkbox"]:checked ~ label::before {
        content: '元に戻す';
    }

    .news_box_more input[type="checkbox"]{
        display: none;
    }
    
    .news_box_more_contents {
        position: relative;
        height: 550px;
        overflow: hidden;
    }

    .news_box_more input[type="checkbox"]:checked ~ .news_box_more_contents {
        height: 1000px;
    }

    .news_box_more_contents::before {
        position: absolute;
        display: block;
        content: "";
        bottom: 0;
        left: 0;
        width: 100%;
        height: 200px;
        background: linear-gradient( rgba(255,255,255,0) 0%, rgba(255,255,255,0.8) 50%, #fff 100%);
    }
    
    .news_box_more input[type="checkbox"]:checked ~ .news_box_more_contents::before {
        display: none;
    }
  </style>
</head>
<body>

<header>
  <h1>Wellnoa</h1>
  <p class="tagline">あなたの健康へ、ちいさな一歩を。</p>
</header>

<div id="news">
    <h2>articles</h2>
    <h3>健康記事を読む</h3>
  </div>
  <div class="news_box_more">
    <input id="news_box_more" type="checkbox">
    <label for="news_box_more"></label>
    <div class="news_box_more_contents">
      <div class="news_box_container">
        <div class="news_box">
          <div>
            <img src="./images/26890499_m.jpg" alt="ウォーキングする男性の画像" width="300px">
          </div>
          <div>
            <p class="news_date">2025/7/31</p>
            <p class="news_text">ウォーキングについて
            </p>
          </div>
        </div>
        <div class="news_box">
          <div>
            <img src="./images/tooth_ha_pikapika.png" alt="歯がピカピカのイラスト" width="300px">
          </div>
          <div>
            <p class="news_date">2025/7/29</p>
            <p class="news_text">お口の健康について
            </p>
          </div>
        </div>
        <div class="news_box">
          <div>
            <img src="./images/heart_tofu_mental.png" alt="豆腐メンタルのイラスト" width="300px">
          </div>
          <div>
            <p class="news_date">2025/7/28</p>
            <p class="news_text">心の健康について
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

<div class="container">
  <a href="index.php">メインページに戻る</a>
</div>


</body>
</html>