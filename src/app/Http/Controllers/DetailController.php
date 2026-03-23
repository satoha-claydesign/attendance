<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Timestamp;
use App\Models\Approval;
use Carbon\Carbon;

class DetailController extends Controller
{
    /**
     * Display attendance detail.
     * If `id` query param is provided, find by id; otherwise use authenticated user's record for provided `date` (or today).
     */
    public function detail(Request $request, $id = null)
    {
        // Prefer route param $id, fallback to query param 'id'
        $id = $id ?? $request->query('id');

        if ($id) {
            $attendance = Timestamp::with(['user', 'breakTime'])->find($id);
        } else {
            $date = $request->query('date', now()->format('Y-m-d'));
            $attendance = Timestamp::with(['user', 'breakTime'])
                ->where('user_id', auth()->id())
                ->where('work_date', $date)
                ->first();
        }

        // Normalize breaks into a simple array the view expects and prepare formatted values
        $approval = null;
        $breaksForInput = [];
        $punchInFormatted = '';
        $punchOutFormatted = '';
        $dateYear = '';
        $dateMonthDay = '';
        $rows = 2; // default rows for breaks inputs
        $note = '';

        if ($attendance) {
            $attendance->breaks = $attendance->breakTime->map(function ($b) {
                return [
                    'start' => optional($b->breakIn)->format('H:i'),
                    'end' => optional($b->breakOut)->format('H:i'),
                ];
            })->toArray();

            // formatted punch times for inputs
            $punchInFormatted = $attendance->punchIn ? Carbon::parse($attendance->punchIn)->format('H:i') : '';
            $punchOutFormatted = $attendance->punchOut ? Carbon::parse($attendance->punchOut)->format('H:i') : '';

            // existing breaks for inputs
            foreach ($attendance->breakTime as $bk) {
                $breaksForInput[] = [
                    'start' => $bk->breakIn ? Carbon::parse($bk->breakIn)->format('H:i') : null,
                    'end' => $bk->breakOut ? Carbon::parse($bk->breakOut)->format('H:i') : null,
                ];
            }
            $rows = max(1, count($breaksForInput)) + 1;

            // derive date blocks
            $dateVal = $attendance->work_date ?? ($attendance->date ?? ($attendance->created_at ? Carbon::parse($attendance->created_at)->format('Y-m-d') : null));
            if ($dateVal) {
                try {
                    $dobj = Carbon::parse($dateVal);
                    $dateYear = $dobj->format('Y') . '年';
                    $dateMonthDay = $dobj->format('m') . '月' . $dobj->format('d') . '日';
                } catch (\Exception $e) { }
            }

            // find latest approval for note population
            $latestApproval = Approval::where('timestamp_id', $attendance->id)->orderBy('created_at', 'desc')->first();
            $note = $latestApproval->reason ?? $attendance->note ?? $attendance->memo ?? '';
        }

        // Find any pending approval for this timestamp (or by date fallback)
        if ($attendance && $attendance->id) {
            $approval = Approval::where('timestamp_id', $attendance->id)
                ->where('status', 'pending')
                ->latest()
                ->first();
        } else {
            $date = $request->query('date', now()->format('Y-m-d'));
            $approval = Approval::where('user_id', auth()->id())
                ->where('target_date', $date)
                ->where('status', 'pending')
                ->latest()
                ->first();
        }

        // If pending approval exists, prepare its formatted payload for read-only display
        $ap_punch_in = null; $ap_punch_out = null; $ap_breaks = [];
        if ($approval && $approval->payload) {
            $pl = $approval->payload;
            $ap_punch_in = $pl['punch_in'] ?? null;
            $ap_punch_out = $pl['punch_out'] ?? null;
            $ap_breaks = $pl['breaks'] ?? [];
        }

        return view('attendance.detail', [
            'attendance' => $attendance,
            'approval' => $approval,
            'breaksForInput' => $breaksForInput,
            'punchInFormatted' => $punchInFormatted,
            'punchOutFormatted' => $punchOutFormatted,
            'dateYear' => $dateYear,
            'dateMonthDay' => $dateMonthDay,
            'rows' => $rows,
            'note' => $note,
            'ap_punch_in' => $ap_punch_in,
            'ap_punch_out' => $ap_punch_out,
            'ap_breaks' => $ap_breaks,
        ]);
    }
}
