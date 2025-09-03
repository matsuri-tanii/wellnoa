<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>脳トレじゃんけん</title>
  <link href="https://fonts.googleapis.com/css?family=Noto+Sans+JP" rel="stylesheet" />
  <style>
    @media (max-width: 400px) {
      .button_box, .janken_box {
        flex-direction: column;
        align-items: center;
      }
      .my_hands button {
        margin-bottom: 10px;
      }
    }
    * {
      margin: 0;
      box-sizing: border-box;
    }

    button {
      width: 100px;
      height: 100px;
      background-color: #97ddff;
      border: none;
      font-family: YDW bananaSlip plus;
    }

    button img {
      width: 85px;
      padding: 3px 0 0 0;
    }

    main {
      max-width: 480px;
      height: 100vh;
      margin: 0 auto;
      color: #384B70;
      font-family: YDW bananaSlip plus;
    }

    section {
      height: calc(100vh/5);
      display: flex;
      justify-content: center;
      align-items: center;
    }

    h1 {
      margin: 0 auto;
      padding: 0 auto;
      font-size: 40px;
    }

    .button_box {
      display: flex;
      justify-content: space-around;
    }

    .start_button_box {
      display: flex;
      justify-content: center;
    }

    .start_button {
      display: block;
      font-size: 18px;
      text-align: center;
      cursor: pointer;
      background-color: white;
      width: 200px;
      height: 50px;
      color: #384B70;
      border: 5px solid #97ddff;
      margin: 0 auto;
      box-sizing: border-box;
    }

    .start_button:hover {
      border:5px solid #88cb11;
      background-color: white;
      color: #384b70;
    }

    .reset_button_box {
      justify-content: center;
    }

    .reset_button {
      display: block;
      font-size: 18px;
      text-align: center;
      cursor: pointer;
      background-color: white;
      width: 200px;
      height: 50px;
      color: #384B70;
      border: 5px solid #97ddff;
      margin: 0 auto;
      box-sizing: border-box;
    }

    .reset_button:hover {
      border:5px solid #88cb11;
      background-color: white;
      color: #384b70;
    }

    #result {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 80px;
      margin: 20px 0;
      font-size: 20px;
    }

    .result_box {
      display: flex;
      justify-content: center;
      margin: 20px;
      font-size: 25px;
    }

    .janken_box {
      display: flex;
      justify-content: space-between;
      align-items: center;

    }

    #pc_hands {
      display: flex;
      justify-content: center;
      align-items: center;
      flex-direction: column;
    }

    #pc_hands p {
      text-align: center;
    }

    .my_hands {
      display: flex;
      justify-content: center;
      align-items: center;
      flex-direction: column;
    }

    #my_handsBox {
      display: flex;
      justify-content: center;
    }

    #gu_btn:hover {
      border:2px solid #88cb11;
      background-color: #88cb11;
      color: #384b70;
      box-sizing: border-box;
    }

    #cho_btn:hover {
      border:2px solid #88cb11;
      background-color: #88cb11;
      color: #384b70;
      box-sizing: border-box;
    }

    #par_btn:hover {
      border:2px solid #88cb11;
      background-color: #88cb11;
      color: #384b70;
      box-sizing: border-box;
    }

    #result_display {
      font-size: 50px;
      color: #ef5e03;
    }

  </style>
</head>

<body>
  <main>
    <section>
      <h1>脳トレじゃんけん</h1>
    </section>
    <div><a href="index.php">メインページに戻る</a></div>
    <div class="button_box">
      <div class="start_button_box">
        <button id="start" class="start_button">スタート</button>
      </div>
      <div class="reset_button_box">
        <button id="reloadPage" class="reset_button">リセット</button>
      </div>
    </div>

    <div id="result">
      <div class="result_comment"><p>コンピューターの出す手と勝敗の指示を見て、</p></div>
      <div class="result_img"><p>合う手を選ぼう</p></div>
    </div>

    <div class="result_box">
      <div>
        <p class="info">
        正解数: <span id="correct">0</span>
        間違えた数: <span id="wrong">0</span>
        </p>
        <div class="container">
          <div id="timer">残り時間 00:00:00</div>
        </div>
      </div>
    </div>
    
    <div class="janken_box">
      <div id="pc_hands">
        <div><p>コンピュータの<br>手は？？</p></div>
        <div class="pc_result"><img src="images/questionmark.png" alt="クエスチョンマーク" width="150px"></div>
      </div>
      <div class="my_hands">
        <div>
          <p>あなたの手は…</p>
        </div>
        <div id="my_handsBox">
          <div>
            <button id="gu_btn" data-value="0"><img src="images/rock.png" alt="グー"></button>
            <button id="cho_btn" data-value="1"><img src="images/scissors.png" alt="チョキ"></button>
            <button id="par_btn" data-value="2"><img src="images/paper.png" alt="パー"></button>
          </div>
        </div>
        <div id="result_display"></div>
      </div>
    </div>
  </main>

  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script>

    // スタートを押すと始まる旨アラート

    const startComment = "スタートボタンを押すと始まるよ"
    alert(startComment);

    //グローバル変数を宣言
    let pcHands; //pcの手(0:グー, 1:チョキ, 2:パー)
    let instruction; //お題(0:あいこ, 1:勝ち, 2:負け)
    let correctCount = 0; //正解数
    let wrongCount = 0; //間違えた数
    let gameActive = false; //ゲーム中かどうか（まだ始まってない）
    let interval;

    //効果音
    const startMusic = new Audio('./audio/start.mp3') //スタート
    const OKMusic = new Audio('./audio/ok5.mp3') //正解
    const NGMusic = new Audio('./audio/ng1.mp3') //不正解
    
    //自分の手のボタンをクリックしたらdata-valueから値を取得してjudge()関数で判定を出す
    $("#gu_btn").on("click",function(){
      const input_button = parseInt($(this).data('value'));
      console.log("自分の出した手：" + input_button );
      judge(input_button);
    });

    $("#cho_btn").on("click",function(){
      const input_button = parseInt($(this).data('value'));
      console.log("自分の出した手：" + input_button);
      judge(input_button);
    });

    $("#par_btn").on("click",function(){
      const input_button = parseInt($(this).data('value'));
      console.log("自分の出した手：" + input_button);
      judge(input_button);
    });

    // スタートボタンを押したら
    // randomNumberで０-2までのランダムな数字をコンソールに表示
    // コンピュータの手
    // 0→グー
    // 1→チョキ
    // 2→パー

    // randomNumber2で０-2までのランダムな数字をコンソールに表示（勝ち、負け、あいこの指示）
    // お題 (randomNumber2)
    // 0→あいこ
    // 1→勝ち
    // 2→負け

    function startGame(){
      const randomNumber = Math.floor(Math.random()*3);
      const randomNumber2 = Math.floor(Math.random()*3);
      console.log("PCの手：" + randomNumber);
      console.log("お題：" + randomNumber2);

      pcHands = randomNumber;
      instruction = randomNumber2;

      //ランダムに出たPCの手と指示に応じて画像を変更し表示
      if ( pcHands === 0 && instruction === 0 ) {
        // グーとあいこ
        $(".pc_result").html('<img src="images/rock_invert.png" alt="グーの画像" width="100px">');
        $(".result_comment").text("コンピューターの出す手と");
        $(".result_img").html('<img src="images/draw_tore.png" alt="あいこにしての画像" width="100px">');
      } else if ( pcHands === 0 && instruction === 1 ) {
        // グーと勝ち
        $(".pc_result").html('<img src="images/rock_invert.png" alt="グーの画像" width="100px">');
        $(".result_comment").text("コンピューターの出す手に");
        $(".result_img").html('<img src="images/win_tore.png" alt="勝っての画像" width="100px">');
      } else if ( pcHands === 0 && instruction === 2 ) {
        // グーと負け
        $(".pc_result").html('<img src="images/rock_invert.png" alt="グーの画像" width="100px">');
        $(".result_comment").text("コンピューターの出す手に");
        $(".result_img").html('<img src="images/lose_tore.png" alt="負けての画像" width="100px">');
      } else if ( pcHands === 1 && instruction === 0 ) {
        // チョキとあいこ
        $(".pc_result").html('<img src="images/scissors_invert.png" alt="チョキの画像" width="100px">');
        $(".result_comment").text("コンピューターの出す手と");
        $(".result_img").html('<img src="images/draw_tore.png" alt="あいこにしての画像" width="100px">');
      } else if ( pcHands === 1 && instruction === 1 ) {
        // チョキと勝ち
        $(".pc_result").html('<img src="images/scissors_invert.png" alt="チョキの画像" width="100px">');
        $(".result_comment").text("コンピューターの出す手に");
        $(".result_img").html('<img src="images/win_tore.png" alt="勝っての画像" width="100px">');
      } else if ( pcHands === 1 && instruction === 2 ) {
        // チョキと負け
        $(".pc_result").html('<img src="images/scissors_invert.png" alt="チョキの画像" width="100px">');
        $(".result_comment").text("コンピューターの出す手に");
        $(".result_img").html('<img src="images/lose_tore.png" alt="負けての画像" width="100px">');
      } else if ( pcHands === 2 && instruction === 0) {
        // パーとあいこ
        $(".pc_result").html('<img src="images/paper_invert.png" alt="パーの画像" width="100px">');
        $(".result_comment").text("コンピューターの出す手と");
        $(".result_img").html('<img src="images/draw_tore.png" alt="あいこにしての画像" width="100px">');
      } else if ( pcHands === 2 && instruction === 1) {
        // パーと勝ち
        $(".pc_result").html('<img src="images/paper_invert.png" alt="パーの画像" width="100px">');
        $(".result_comment").text("コンピューターの出す手に");
        $(".result_img").html('<img src="images/win_tore.png" alt="勝っての画像" width="100px">');
      } else if ( pcHands === 2 && instruction === 2) {
        // パーと負け
        $(".pc_result").html('<img src="images/paper_invert.png" alt="パーの画像" width="100px">');
        $(".result_comment").text("コンピューターの出す手に");
        $(".result_img").html('<img src="images/lose_tore.png" alt="負けての画像" width="100px">');
      }
    }

    $("#start").on("click", function() {
      if (gameActive) return; //すでにゲーム中なら何もしない（ボタン連打を防ぐ）
      gameActive = true;
      correctCount = 0; //リセット
      wrongCount = 0; //リセット
      $("#correct").text(correctCount);
      $("#wrong").text(wrongCount);
      startGame(); //pcの手とお題を表示
      countDownTimer(); //タイマーを開始
      startMusic.currentTime = 0; //効果音の再生位置を0秒からスタートする
      startMusic.play(); //効果音再生
    });

    // ボタンを押したら正解か違うかもを表示
    // グーボタン・チョキボタン・パーボタン３種類で作る
    function judge(input_button) {
      if(!gameActive) return;//ゲーム中じゃなかったら何もしない
      if ( instruction === 0 ) { //指示があいこの時
        if ( pcHands === input_button ) { //pcの手と自分の手が同じなら
          $("#result_display").text("正解！！！"); //正解を表示
          OKMusic.currentTime = 0;
          OKMusic.play();
          correctCount++; //正解数を１増やす
          $("#correct").text(correctCount);
        } else {
          $("#result_display").text("違うかも…");
          NGMusic.currentTime = 0;
          NGMusic.play();
          wrongCount++;
          $("#wrong").text(wrongCount);
        }
      } else if ( instruction === 1 ) {
        if (( pcHands === 0 && input_button === 2 ) ||
          ( pcHands === 1 && input_button === 0 ) ||
          ( pcHands === 2 && input_button === 1 )) {
          $("#result_display").text("正解！！！");
          OKMusic.currentTime = 0;
          OKMusic.play();
          correctCount++;
          $("#correct").text(correctCount);
        } else {
          $("#result_display").text("違うかも…");
          NGMusic.currentTime = 0;
          NGMusic.play();
          wrongCount++;
          $("#wrong").text(wrongCount);
        }
      } else if ( instruction === 2 ) {
        if (( pcHands === 0 && input_button === 1 ) ||
          ( pcHands === 1 && input_button === 2 ) ||
          ( pcHands === 2 && input_button === 0 )) {
          $("#result_display").text("正解！！！");
          OKMusic.currentTime = 0;
          OKMusic.play();
          correctCount++;
          $("#correct").text(correctCount);
        } else {
          $("#result_display").text("違うかも…");
          NGMusic.currentTime = 0;
          NGMusic.play();
          wrongCount++;
          $("#wrong").text(wrongCount);
        }
      }

      setTimeout(function() {
        startGame();
        }, 500); //0.5秒後に次のpcの手とお題を表示
    }

    function countDownTimer(){
      const countDown = document.getElementById('timer');
      const targetTime = new Date().getTime() + 15000; //現在の日付時刻＋15秒後を終了時刻とする

      clearInterval(interval); //前のタイマーがあればクリア

      function updateCountDown(){ //タイマーの表示（現在時刻との差）を更新
        const now = new Date().getTime(); //現在時刻
        const distance = targetTime - now; //残り時間

        //残り時間を時、分、秒に変換(floorで小数点以下切り捨て)
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / (1000));
        const miliseconds = distance < 0 ? 0 :Math.floor(distance % 1000);

        //タイマーの残り時間をHTMLに表示
        //padStartで桁が少ない時は頭に０をつけて表示する
        countDown.textContent = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}.${ String( miliseconds ).padStart( 3, "0" ) }`;

        if(distance <= 5000 && distance >0){ //残り時間が5秒以下の時
          countDown.style.color = 'red'; //文字色を赤に
        } else {
          countDown.style.color = '#384B70';
        }

        if(distance < 0){ //残り時間がマイナスの時
          clearInterval(interval); //タイマーをクリア
          countDown.textContent = '終了しました'; //表示を終了しましたに変更
          countDown.style.color = '#384B70';
          gameActive = false; //ゲームを無効化（ボタン無反応になる）
        
          // ゲーム終了時に結果保存
          saveJankenResult();
        }
      }

      interval = setInterval(updateCountDown, 1); 
      updateCountDown();
    }
    
    $(document).ready(function() {
      $('#reloadPage').on('click', function() {
        location.reload();  // ページを再読み込み
      });
    })
    
    // ゲーム終了後に送信する処理
    function saveJankenResult() {
      const anonymoseUserId = 1; // ← セッションや JS で動的に差し替える

      const playData = {
        correct: correctCount,
        wrong: wrongCount,
        duration: 15,
        anonymose_user_id: anonymoseUserId
      };

      $.ajax({
        type: "POST",
        url: "save_janken_result.php",
        data: playData,
        success: function(response) {
          console.log("記録保存成功:", response);
        },
        error: function() {
          console.log("記録保存失敗");
        }
      });
    }



    


  </script>
</body>

</html>