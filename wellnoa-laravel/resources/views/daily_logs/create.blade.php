@extends('layouts.app')

@section('content')
  <h1>日記を追加</h1>

  <form action="{{ route('logs.store') }}" method="POST">
    @csrf

    <div class="mb-3">
      <label class="form-label">天候</label>
      <input type="text" name="weather" class="form-control" value="{{ old('weather') }}">
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
@endsection