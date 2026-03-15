<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Timestamp;
use App\Models\Approval;

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

        // Normalize breaks into a simple array the view expects
        if ($attendance) {
            $attendance->breaks = $attendance->breakTime->map(function ($b) {
                return [
                    'start' => optional($b->breakIn)->format('H:i'),
                    'end' => optional($b->breakOut)->format('H:i'),
                ];
            })->toArray();
        }

        // Find any pending approval for this timestamp (or by date fallback)
        $approval = null;
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

        return view('attendance.detail', ['attendance' => $attendance, 'approval' => $approval]);
    }
}
