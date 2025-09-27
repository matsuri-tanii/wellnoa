@extends('layouts.app')

@section('content')
  <h1>日記を編集</h1>

  <form action="{{ route('logs.update', $log->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="mb-3">
      <label class="form-label">天候</label>
      <input type="text" name="weather" class="form-control" value="{{ old('weather',$log->weather) }}">
    </div>

    <div class="mb-3">
      <label class="form-label">身体の調子</label>
      <input type="range" name="body" min="0" max="100" step="1" value="{{ old('body',$log->body_condition) }}">
    </div>

    <div class="mb-3">
      <label class="form-label">心の調子</label>
      <input type="range" name="mental" min="0" max="100" step="1" value="{{ old('mental',$log->mental_condition) }}">
    </div>

    <div class="mb-3">
      <label class="form-label">やったこと</label><br>
      @php
        $checked = explode(',', $log->activity_type ?? '');
      @endphp
      @foreach ($checkItems as $item)
        <label class="me-2">
          <input type="checkbox" name="activity_type[]" value="{{ $item }}"
            {{ in_array($item, old('activity_type',$checked)) ? 'checked' : '' }}>
          {{ $item }}
        </label>
      @endforeach
    </div>

    <div class="mb-3">
      <label class="form-label">メモ</label>
      <textarea name="memo" class="form-control" rows="3">{{ old('memo',$log->memo) }}</textarea>
    </div>

    <button type="submit" class="btn btn-primary">更新</button>
    <a href="{{ route('logs.index') }}" class="btn btn-secondary">戻る</a>
  </form>
@endsection