<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Timestamp;

class TimestampsController extends Controller
{
    public function index() {
    $timestamp = Timestamp::where('user_id', Auth::id())
                ->where('work_date', Carbon::today())
                ->first();

    // ここで view に渡す
    return view('attendance.index', compact('timestamp'));
    }

    public function punchIn()
    {
        $user = Auth::user();
        $today = Carbon::today();

        // 1日1レコードの作成または取得（無ければ作成）
        $timestamp = Timestamp::firstOrCreate(
            ['user_id' => $user->id, 'work_date' => $today],
            ['punchIn' => Carbon::now()]
        );

        return redirect()->back()->with('timestamp', $timestamp);
    }

    // 退勤処理
    public function punchOut()
    {
        $user = Auth::user();
        $today = Carbon::today();

        // 今日のレコードを更新
        $timestamp = Timestamp::where('user_id', $user->id)
            ->where('work_date', $today)
            ->first();

        if ($timestamp && !$timestamp->punchOut) {
            $timestamp->update(['punchOut' => Carbon::now()]);
            return redirect()->back()->with('timestamp', $timestamp);
        }

        return redirect()->back();
    }

    // 休憩開始
    public function breakIn(Request $request)
    {
        $timestamp = Timestamp::where('user_id', auth()->id())
                                ->where('work_date', now()->format('Y-m-d'))->first();
        if ($timestamp) {
            $timestamp->breakTime()->create([
                'breakIn' => now(),
            ]);
        }
        return redirect()->back();
    }

    // 休憩終了
    public function breakOut(Request $request)
    {
        $timestamp = Timestamp::where('user_id', auth()->id())
                                ->where('work_date', now()->format('Y-m-d'))->first();

        if ($timestamp) {
            // まだ休憩終了していない（break_outがnullの）レコードを探して更新
            $currentBreak = $timestamp->breakTime()->whereNull('breakOut')->first();
            if ($currentBreak) {
                $currentBreak->update(['breakOut' => now()]);
            }
        }
        return redirect()->back();
    }
}
