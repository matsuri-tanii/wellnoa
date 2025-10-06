import API_KEY from './api_config.js';

let currentWeatherData = {
    description: '不明',
    temperature: '不明',
    humidity: '不明'
};

$(document).ready(function() {
    const $weatherInfo = $('#weather-info');
    const $chanceMessage = $('#chance-message');
    const $noChanceMessage = $('#no-chance-message');
    const $recordButton = $('#record-button');
    const $recordsList = $('#records');

    getWeather();

    loadRecordsFromPHP();

    function getWeather() {
        if (!navigator.geolocation) {
            $weatherInfo.text('お使いのブラウザは位置情報に対応していません。');
            return;
        }

        $weatherInfo.text('現在地と天気情報を取得中...');

        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            const weatherApiUrl = `https://api.openweathermap.org/data/2.5/weather?lat=${lat}&lon=${lng}&appid=${API_KEY}&units=metric&lang=ja`;

            axios.get(weatherApiUrl)
                .then(function(response) {
                    const weatherData = response.data;
                    const temp = weatherData.main.temp;
                    const weatherDescription = weatherData.weather[0].description;
                    const humidity = weatherData.main.humidity;

                    currentWeatherData.description = weatherDescription;
                    currentWeatherData.temperature = temp.toFixed(1);
                    currentWeatherData.humidity = humidity;

                    $weatherInfo.html(
                        `📍 現在地の天気は...<br>` +
                        `${weatherDescription}で、<b>気温は ${temp.toFixed(1)}℃、湿度は ${humidity}%</b>`
                    );

                    checkWalkChance(temp, humidity);
                })
                .catch(function(error) {
                    console.error('天気情報の取得に失敗しました:', error);
                    $weatherInfo.text('天気情報の取得に失敗しました。');
                    $chanceMessage.hide();
                    $noChanceMessage.hide();
                });

        }, function(error) {
            console.error('位置情報の取得に失敗しました:', error);
            $weatherInfo.text('位置情報の取得を許可してください。');
            $chanceMessage.hide();
            $noChanceMessage.hide();
        });
    }

    function checkWalkChance(temperature, humidity) {
        if (temperature >= 5 && temperature <= 28 && humidity <= 60) {
            $chanceMessage.show();
            $noChanceMessage.hide();
        } else {
            $chanceMessage.hide();
            $noChanceMessage.show();
        }
    }

    $recordButton.on('click', function() {
        const now = new Date();
        const recordText = `${now.toLocaleDateString('ja-JP')} ${now.toLocaleTimeString('ja-JP', { hour: '2-digit', minute: '2-digit' })}にお散歩しました！` +
                           ` (天気: ${currentWeatherData.description}, 気温: ${currentWeatherData.temperature}℃, 湿度: ${currentWeatherData.humidity}%)`;

        $.post('save_walk_record.php', {
            text: recordText,
            weather: currentWeatherData.description,
            temp: currentWeatherData.temperature,
            humidity: currentWeatherData.humidity
        })
        .done(() => {
            alert("記録を保存しました！");
            loadRecordsFromPHP();
        })
        .fail(() => {
            alert("記録の保存に失敗しました。");
        });
    });

    function loadRecordsFromPHP() {
        $.getJSON('get_walk_records.php', function(records) {
            $recordsList.empty();

            if (!records.length) {
                $recordsList.append('<li>まだ記録がありません。お散歩に行ってみよう</li>');
                return;
            }

            records.forEach(function(rec) {
                const $li = $('<li>').text(rec.text);
                $recordsList.append($li);
            });
        })
        .fail(() => {
            $recordsList.html('<li class="error-message">記録の読み込みに失敗しました。</li>');
        });
    }
});