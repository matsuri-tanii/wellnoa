@extends('layouts.app')

@section('content')
  <p><a href="{{ route('logs.create') }}" class="btn btn-primary">＋ 日記を追加</a></p>

  <h1>日記一覧</h1>
  <p class="meta">最新20件を表示（guest_id ごとに絞り込み済み）</p>

  <table class="table">
    <thead>
      <tr>
        <th>ID</th>
        <th>日付</th>
        <th>天候</th>
        <th>体調</th>
        <th>メンタル</th>
        <th>活動</th>
        <th>操作</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($logs as $log)
        <tr>
          <td>{{ $log->id }}</td>
          <td>{{ $log->log_date ?? '' }}</td>
          <td>{{ $log->weather ?? '' }}</td>
          <td>{{ $log->body_condition ?? '' }}</td>
          <td>{{ $log->mental_condition ?? '' }}</td>
          <td>{{ $log->activity_type ?? '' }}</td>
          <td>
            <a href="{{ route('logs.edit', $log->id) }}" class="btn btn-sm btn-warning">編集</a>

            <form action="{{ route('logs.destroy', $log->id) }}" method="POST" style="display:inline-block" onsubmit="return confirm('本当に削除しますか？');">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-sm btn-danger">削除</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="7">データがありません</td></tr>
      @endforelse
    </tbody>
  </table>

  <div class="pager">
    {{ $logs->links() }}
  </div>
@endsection