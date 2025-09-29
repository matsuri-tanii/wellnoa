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

        try {
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
        } catch (\Throwable $e) {
            // 何か例外が起きたら
            return back()->withInput()->with('error', '登録に失敗しました。もう一度お試しください。');
            // ↑ back() で同じフォームに戻る。入力値も保持（withInput）
        }
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

        // activity_type の 50文字制限ロジック（省略：あなたの最新版のままでOK）
        $act = $v['activity_type'] ?? [];
        $act = array_values(array_filter(array_map('trim', $act), fn($s)=>$s!==''));
        $buf = []; $len = 0;
        foreach ($act as $i => $item) {
            $add = ($i === 0 ? mb_strlen($item) : 1 + mb_strlen($item));
            if ($len + $add <= 50) { $buf[] = $item; $len += $add; } else { break; }
        }
        $activityJoined = implode(',', $buf);

        // 変更を適用（fill→isClean で「変更なし」を検出）
        $log->fill([
            'weather'            => $v['weather'] ?? null,
            'body_condition'     => $v['body']   ?? null,
            'mental_condition'   => $v['mental'] ?? null,
            'activity_type'      => $activityJoined ?: null,
            'memo'               => $v['memo']   ?? null,
        ]);

        if ($log->isClean()) {
            // 値が一つも変わっていない → 情報メッセージ
            return redirect()->route('logs.index')->with('info', '変更はありませんでした。');
        }

        $log->save();

        return redirect()->route('logs.index')->with('success', '日記を編集しました！');
    }

    public function destroy(Request $request, int $id)
    {
        $log = DailyLog::find($id);

        if (!$log) {
            // レコード自体が見つからない → エラー
            return redirect()->route('logs.index')->with('error', '対象データが見つかりませんでした。');
        }

        $this->authorizeView($log);

        try {
            $ok = $log->delete(); // bool が返る
            if ($ok) {
                return redirect()->route('logs.index')->with('success', '日記を削除しました！');
            } else {
                return redirect()->route('logs.index')->with('error', '削除に失敗しました…');
            }
        } catch (\Throwable $e) {
            // 例外発生時（外部キー制約など）
            return redirect()->route('logs.index')->with('error', '削除時にエラーが発生しました。');
        }
    }

    private function authorizeView(DailyLog $log): void
    {
        $me = (int) request()->cookie('guest_numeric_id');
        abort_if(!$me || $log->anonymous_user_id !== $me, 403);
    }
}