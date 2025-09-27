<?php

namespace App\Http\Controllers;

use App\Models\DailyLog;
use Illuminate\Http\Request;

class DailyLogController extends Controller
{
    public function index(Request $request)
    {
        $anonId = (int) $request->cookie('guest_numeric_id');

        $q = DailyLog::query();
        if ($anonId) {
            $q->where('anonymous_user_id', $anonId);
        }
        $logs = $q->orderByDesc('log_date')->orderByDesc('id')->paginate(20);

        return view('daily_logs.index', compact('logs'));
    }

    public function create()
    {
        $checkItems = ['散歩','ジョギング','筋トレ','ストレッチ','ヨガ','ぼーっとする','ゲーム','手芸','読書','料理'];
        return view('daily_logs.create', compact('checkItems'));
    }

    public function store(Request $request)
    {
        $anonId = (int) $request->cookie('guest_numeric_id');
        if (!$anonId) abort(400, 'guest_numeric_id missing');

        $v = $request->validate([
            'weather'         => ['nullable','string','max:50'],
            'body'            => ['nullable','integer','between:0,100'],
            'mental'          => ['nullable','integer','between:0,100'],
            'activity_type'   => ['nullable','array'],
            'activity_type.*' => ['string','max:50'],
            'memo'            => ['nullable','string'],
        ]);

        // 配列をトリム→空要素除去
        $act = $v['activity_type'] ?? [];
        $act = array_values(array_filter(array_map('trim', $act), fn($s)=>$s!==''));

        // 50文字以内に収まるようにカンマ区切りで詰める
        $buf = [];
        $len = 0;
        foreach ($act as $i => $item) {
            $add = ($i === 0 ? mb_strlen($item) : 1 + mb_strlen($item)); // 先頭はそのまま、以降はカンマ分+1
            if ($len + $add <= 50) { $buf[] = $item; $len += $add; } else { break; }
        }
        $activityJoined = implode(',', $buf);

        DailyLog::create([
            'anonymous_user_id'  => $anonId,
            'log_date'           => now()->toDateString(),
            'log_time'           => now()->format('H:i:s'),
            'weather'            => $v['weather'] ?? null,      // 文字列
            'body_condition'     => $v['body']   ?? null,       // 0–100
            'mental_condition'   => $v['mental'] ?? null,       // 0–100
            'activity_type'      => $activityJoined ?: null,    // varchar(50)
            'memo'               => $v['memo']   ?? null,
        ]);

        return redirect()->route('logs.index')->with('success', '日記を登録しました！');
    }

    public function edit(int $id)
    {
        $log = DailyLog::findOrFail($id);
        $this->authorizeView($log);

        $checkItems = ['散歩','ジョギング','筋トレ','ストレッチ','ヨガ','ぼーっとする','ゲーム','手芸','読書','料理'];
        return view('daily_logs.edit', compact('log','checkItems'));
    }

    public function update(Request $request, int $id)
    {
        $log = DailyLog::findOrFail($id);
        $this->authorizeView($log);

        $v = $request->validate([
            'weather'         => ['nullable','string','max:50'],
            'body'            => ['nullable','integer','between:0,100'],
            'mental'          => ['nullable','integer','between:0,100'],
            'activity_type'   => ['nullable','array'],
            'activity_type.*' => ['string','max:50'],
            'memo'            => ['nullable','string'],
        ]);

        $act = $v['activity_type'] ?? [];
        $act = array_values(array_filter(array_map('trim', $act), fn($s)=>$s!==''));
        $buf = []; $len = 0;
        foreach ($act as $i => $item) {
            $add = ($i === 0 ? mb_strlen($item) : 1 + mb_strlen($item));
            if ($len + $add <= 50) { $buf[] = $item; $len += $add; } else { break; }
        }
        $activityJoined = implode(',', $buf);

        $log->update([
            'weather'            => $v['weather'] ?? null,
            'body_condition'     => $v['body']   ?? null,
            'mental_condition'   => $v['mental'] ?? null,
            'activity_type'      => $activityJoined ?: null,
            'memo'               => $v['memo']   ?? null,
        ]);

        return redirect()->route('logs.index')->with('success', '日記を編集しました！');
    }

    public function destroy(Request $request, int $id)
    {
        $log = DailyLog::findOrFail($id);
        $this->authorizeView($log);
        $log->delete();
        
        return redirect()->route('logs.index')->with('success', '日記を削除しました！');
    }

    private function authorizeView(DailyLog $log): void
    {
        $me = (int) request()->cookie('guest_numeric_id');
        abort_if(!$me || $log->anonymous_user_id !== $me, 403);
    }
}