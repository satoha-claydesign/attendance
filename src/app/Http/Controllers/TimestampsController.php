<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\CorrectRequest;
use Auth;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Timestamp;
use App\Models\Approval;

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

        // Precompute break and work display strings per date to keep view simple
        $breakDisplayByDate = [];
        $workDisplayByDate = [];
        foreach ($attendanceByDate as $date => $att) {
            $breakTotal = 0;
            foreach ($att->breakTime as $b) {
                if ($b->breakIn && $b->breakOut) {
                    $breakTotal += \Carbon\Carbon::parse($b->breakIn)->diffInMinutes(\Carbon\Carbon::parse($b->breakOut));
                }
            }
            $breakDisplayByDate[$date] = $breakTotal > 0 ? (int)($breakTotal/60) . ':' . str_pad($breakTotal%60, 2, '0', STR_PAD_LEFT) : '0:00';
            if ($att->punchIn && $att->punchOut) {
                $workMinutes = \Carbon\Carbon::parse($att->punchIn)->diffInMinutes(\Carbon\Carbon::parse($att->punchOut)) - $breakTotal;
                $workDisplayByDate[$date] = (int)($workMinutes/60) . ':' . str_pad($workMinutes%60, 2, '0', STR_PAD_LEFT);
            } else {
                $workDisplayByDate[$date] = '—';
            }
        }

        // Build list of days in month (ascending: start -> end)
        $numDays = $current->daysInMonth;
        $days = collect();
        for ($i = 0; $i < $numDays; $i++) {
            $days->push($current->copy()->addDays($i));
        }
        // keep ascending order (1日 -> 月末)

        $prev = $current->copy()->subMonth();
        $next = $current->copy()->addMonth();

    return view('attendance.list', compact('attendances', 'attendanceByDate', 'days', 'current', 'prev', 'next', 'breakDisplayByDate', 'workDisplayByDate'));
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

    /**
     * Create an approval request for changes to a timestamp instead of applying immediately.
     * The admin will approve and apply changes later.
     */
    public function update(CorrectRequest $request, $id)
    {
        $user = auth()->user();

        $timestamp = Timestamp::with('breakTime')->where('id', $id)->where('user_id', $user->id)->first();
        if (! $timestamp) {
            return redirect()->back()->with('error', '対象の勤怠が見つかりません。');
        }

        // Use FormRequest validated data
        $validated = $request->validated();

        $workDate = $timestamp->work_date;

        // Build payload representing requested changes
        // collect breaks from input array (supports dynamic count)
        $inputBreaks = $request->input('breaks', []);
        $breaksPayload = [];
        if (is_array($inputBreaks)) {
            foreach ($inputBreaks as $b) {
                $start = $b['start'] ?? null;
                $end = $b['end'] ?? null;
                if ($start || $end) {
                    $breaksPayload[] = ['start' => $start, 'end' => $end];
                }
            }
        }

        $payload = [
            'punch_in' => $validated['punch_in'] ?? null,
            'punch_out' => $validated['punch_out'] ?? null,
            'breaks' => $breaksPayload,
        ];

        // Create approval record (status: pending)
        Approval::create([
            'user_id' => $user->id,
            'timestamp_id' => $timestamp->id,
            'name' => $user->name ?? null,
            'target_date' => $workDate,
            'status' => 'pending',
            'reason' => $validated['note'] ?? null,
            'payload' => $payload,
            'details_link' => route('attendance.detail', $timestamp->id),
        ]);

        return redirect()->route('attendance.detail', $timestamp->id)->with('success', '変更を申請しました。管理者の承認をお待ちください。');
    }
}
