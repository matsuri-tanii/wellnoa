import { initializeApp } from "https://www.gstatic.com/firebasejs/11.9.1/firebase-app.js";
import { getFirestore, collection, addDoc, query, orderBy, onSnapshot } from "https://www.gstatic.com/firebasejs/11.9.1/firebase-firestore.js";

import firebaseConfig from './firebaseConfig.js';
import API_KEY from './api_config.js';

const app = initializeApp(firebaseConfig);
const db = getFirestore(app);

let currentWeatherData = {
    description: '不明',
    temperature: '不明',
    humidity: '不明'
};

$(document).ready(function() {
    //天気情報表示用のHTML要素を取得
    const $weatherInfo = $('#weather-info');
    const $chanceMessage = $('#chance-message');
    const $noChanceMessage = $('#no-chance-message');
    const $recordButton = $('#record-button');
    const $recordsList = $('#records');

    //現在の天気情報を取得
    getWeather();
    //過去の記録をFirebaseから読み込む
    loadRecordsFromFirebase();

    //天気情報を取得する関数
    function getWeather() {
        if (!navigator.geolocation) { //取得できなかったら以下を表示
            $weatherInfo.text('お使いのブラウザは位置情報に対応していません。');
            return;
        }

        $weatherInfo.text('現在地と天気情報を取得中...');

        //現在地情報の取得
        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude; 
            const lng = position.coords.longitude; 

            const weatherApiUrl = `https://api.openweathermap.org/data/2.5/weather?lat=${lat}&lon=${lng}&appid=${API_KEY}&units=metric&lang=ja`;
            axios
                .get(weatherApiUrl)
                .then(function(response) {
                    const weatherData = response.data;

                    const temp = weatherData.main.temp; //今の気温
                    const weatherDescription = weatherData.weather[0].description; // 今の天気（くもりとか晴れとか）
                    const humidity = weatherData.main.humidity; // 湿度

                    currentWeatherData.description = weatherDescription;
                    currentWeatherData.temperature = temp.toFixed(1);
                    currentWeatherData.humidity = humidity;

                    $weatherInfo.html(
                        `📍 現在地の天気は...<br>` +
                        `${weatherDescription}で、<b>気温は ${temp.toFixed(1)}℃、湿度は ${humidity}%</b>`
                    );

                    //お散歩チャンスを判定する関数を呼ぶ
                    checkWalkChance(temp, humidity);

                })
                .catch(function(error) {
                    console.error('天気情報の取得に失敗しました:', error);
                    $weatherInfo.text('天気情報の取得に失敗しました。APIキーを確認してください。');
                    // メッセージを一旦隠す
                    $chanceMessage.hide(); 
                    $noChanceMessage.hide(); 
                });

        }, function(error) {
            console.error('位置情報の取得に失敗しました:', error);
            let errorMessage = '位置情報の取得を許可してください。';
            if (error.code === error.PERMISSION_DENIED) {
                errorMessage = '位置情報サービスへのアクセスが拒否されました。設定を確認してください。';
            }
            $weatherInfo.text(errorMessage);
            // メッセージを一旦隠す
            $chanceMessage.hide(); 
            $noChanceMessage.hide(); 
        });
    }

    //お散歩チャンスを判定する関数
    function checkWalkChance(temperature, humidity) {
        // 気温が「5℃以上28℃以下かつ湿度60%以下」の時にお散歩チャンスとする
        if (temperature >= 5 && temperature <= 28 && humidity <= 60) {
            $chanceMessage.show();
            $noChanceMessage.hide();
        } else {
            $chanceMessage.hide();
            $noChanceMessage.show();
        }
    }

    //お散歩記録をFirebaseに保存する関数
    $recordButton.on('click', function() {
        const now = new Date();
        const recordText = `${now.toLocaleDateString('ja-JP')} ${now.toLocaleTimeString('ja-JP', { hour: '2-digit', minute: '2-digit' })}にお散歩しました！` +
                           ` (天気: ${currentWeatherData.description}, 気温: ${currentWeatherData.temperature}℃, 湿度: ${currentWeatherData.humidity}%)`;

        const recordData = {
            timestamp: now,
            text: recordText,
            weatherDescription: currentWeatherData.description,
            temperature: currentWeatherData.temperature,
            humidity: currentWeatherData.humidity
        };

        addDoc(collection(db, 'osanpo'), recordData)
            .then(function() {
                console.log("記録が正常にFirebaseに追加されました！");
            })
            .catch(function(error) {
                console.error("記録の追加中にエラーが発生しました: ", error);
                alert("記録の保存に失敗しました。");
            });
    });

    //Firebaseから過去の記録を読込・表示する関数
    function loadRecordsFromFirebase() {
        const q = query(collection(db, 'osanpo'), orderBy('timestamp', 'desc'));
        onSnapshot(q, function(snapshot) {
            $recordsList.empty();

            if (snapshot.empty) {
                $recordsList.append('<li>まだ記録がありません。お散歩に行ってみよう</li>');
                return;
            }

            snapshot.forEach(function(doc) {
                const record = doc.data();
                const $listItem = $('<li>').text(record.text);
                $recordsList.append($listItem);
            });
            console.log("お散歩記録がFirebaseから更新されました。");
        }, function(error) {
            console.error("お散歩記録の取得中にエラーが発生しました:", error);
            $recordsList.html('<li class="error-message">記録の読み込みに失敗しました。</li>');
        });
    }
});