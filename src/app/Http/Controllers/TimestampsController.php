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

    /**
     * Show list of timestamps for authenticated user.
     */
    public function list(\Illuminate\Http\Request $request)
    {
        // month query expected in format YYYY-MM (e.g. 2026-03). Fallback to current month.
        $monthQuery = $request->query('month');
        try {
            $current = $monthQuery ? Carbon::createFromFormat('Y-m', $monthQuery)->startOfMonth() : Carbon::today()->startOfMonth();
        } catch (\Exception $e) {
            $current = Carbon::today()->startOfMonth();
        }

        $start = $current->copy()->startOfMonth()->toDateString();
        $end = $current->copy()->endOfMonth()->toDateString();

        $attendances = Timestamp::with('breakTime')
            ->where('user_id', auth()->id())
            ->whereBetween('work_date', [$start, $end])
            ->orderBy('work_date', 'desc')
            ->get();

        // Key attendances by work_date for quick lookup in view
        $attendanceByDate = $attendances->keyBy('work_date');

        // Build list of days in month (ascending: start -> end)
        $numDays = $current->daysInMonth;
        $days = collect();
        for ($i = 0; $i < $numDays; $i++) {
            $days->push($current->copy()->addDays($i));
        }
        // keep ascending order (1日 -> 月末)

        $prev = $current->copy()->subMonth();
        $next = $current->copy()->addMonth();

        return view('attendance.list', compact('attendances', 'attendanceByDate', 'days', 'current', 'prev', 'next'));
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
