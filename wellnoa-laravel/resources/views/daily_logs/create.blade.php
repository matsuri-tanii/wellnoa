@extends('layouts.app')

@section('content')
  <h1>日記を追加</h1>

  <form action="{{ route('logs.store') }}" method="POST">
    @csrf

    <div style="margin-bottom:12px;">
      <label>天気</label><br>
      <input type="text" name="weather" id="weather" value="{{ old('weather') }}" placeholder="例: 晴れ" style="width:240px;">
      <button type="button" id="btnFetchWeather" style="margin-left:8px;">現在地から自動入力</button>
      <div id="weatherHint" style="font-size:12px;color:#666;margin-top:4px;"></div>
      @error('weather')<div style="color:#c00;">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
      <label class="form-label">身体の調子</label>
      <input type="range" name="body" min="0" max="100" step="1" value="{{ old('body',50) }}">
    </div>

    <div class="mb-3">
      <label class="form-label">心の調子</label>
      <input type="range" name="mental" min="0" max="100" step="1" value="{{ old('mental',50) }}">
    </div>

    <div class="mb-3">
      <label class="form-label">やったこと</label><br>
      @foreach ($checkItems as $item)
        <label class="me-2">
          <input type="checkbox" name="activity_type[]" value="{{ $item }}"
            {{ in_array($item, old('activity_type', [])) ? 'checked' : '' }}>
          {{ $item }}
        </label>
      @endforeach
    </div>

    <div class="mb-3">
      <label class="form-label">メモ</label>
      <textarea name="memo" class="form-control" rows="3">{{ old('memo') }}</textarea>
    </div>

    <button type="submit" class="btn btn-primary">保存</button>
    <a href="{{ route('logs.index') }}" class="btn btn-secondary">戻る</a>
  </form>
  
  <script>
    document.getElementById('btnFetchWeather')?.addEventListener('click', () => {
    const hint = document.getElementById('weatherHint');
    hint.textContent = '現在地を取得しています…';

    if (!navigator.geolocation) {
        hint.textContent = 'このブラウザは位置情報に対応していません。';
        return;
    }

    navigator.geolocation.getCurrentPosition(async pos => {
        try {
        hint.textContent = '天気を取得しています…';
        const { latitude: lat, longitude: lon } = pos.coords;
        const rsp = await fetch(`/api/weather?lat=${lat}&lon=${lon}`);
        if (!rsp.ok) throw new Error('failed');

        const data = await rsp.json();
        const desc = data.description || '';
        const temp = data.temp;

        const text = desc ? `${desc}` : '';
        document.getElementById('weather').value = text;

        // ヒント表示（温度も添える）
        hint.textContent = desc
            ? (typeof temp === 'number' ? `取得: ${desc}（${temp}℃）` : `取得: ${desc}`)
            : '天気が取得できませんでした。';
        } catch (e) {
        hint.textContent = '天気の取得に失敗しました。時間をおいて再試行してください。';
        }
    }, err => {
        hint.textContent = '位置情報の取得が許可されませんでした。ブラウザの設定をご確認ください。';
    }, { enableHighAccuracy: true, timeout: 10000 });
    });
    </script>
@endsection