<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Timestamp;
use Carbon\Carbon;

class AdminAttendanceController extends Controller
{
    /**
     * Show attendance list for a specific date for admins.
     * Date passed as query 'date' in Y-m-d format; defaults to today.
     */
    public function index(Request $request)
    {
        // only admin middleware will allow access in routes
        $dateQuery = $request->query('date');
        try {
            $current = $dateQuery ? Carbon::createFromFormat('Y-m-d', $dateQuery) : Carbon::today();
        } catch (\Exception $e) {
            $current = Carbon::today();
        }

        $prev = $current->copy()->subDay();
        $next = $current->copy()->addDay();

        $dateStr = $current->format('Y-m-d');

        // load timestamps for the date and only show users who have attendance records
        $timestampsCollection = Timestamp::with(['breakTime'])->where('work_date', $dateStr)->get();
        $timestamps = $timestampsCollection->keyBy('user_id');
        $userIds = $timestampsCollection->pluck('user_id')->unique()->filter()->values();
        $users = User::whereIn('id', $userIds)->orderBy('name')->get();

        // Precompute per-user display values to keep the view simple
        $breakDisplayByUser = [];
        $workDisplayByUser = [];
        $punchInByUser = [];
        $punchOutByUser = [];
        foreach ($timestamps as $uid => $ts) {
            $breakTotal = 0;
            foreach ($ts->breakTime as $b) {
                if ($b->breakIn && $b->breakOut) {
                    $breakTotal += \Carbon\Carbon::parse($b->breakIn)->diffInMinutes(\Carbon\Carbon::parse($b->breakOut));
                }
            }
            $breakDisplayByUser[$uid] = $breakTotal > 0 ? (int)($breakTotal/60) . ':' . str_pad($breakTotal%60, 2, '0', STR_PAD_LEFT) : '0:00';
            if ($ts->punchIn && $ts->punchOut) {
                $workMinutes = \Carbon\Carbon::parse($ts->punchIn)->diffInMinutes(\Carbon\Carbon::parse($ts->punchOut)) - $breakTotal;
                $workDisplayByUser[$uid] = (int)($workMinutes/60) . ':' . str_pad($workMinutes%60, 2, '0', STR_PAD_LEFT);
            } else {
                $workDisplayByUser[$uid] = '—';
            }
            $punchInByUser[$uid] = $ts->punchIn ? \Carbon\Carbon::parse($ts->punchIn)->format('H:i') : null;
            $punchOutByUser[$uid] = $ts->punchOut ? \Carbon\Carbon::parse($ts->punchOut)->format('H:i') : null;
        }

    return view('admin.attendance.list', compact('users', 'timestamps', 'current', 'prev', 'next', 'dateStr', 'breakDisplayByUser', 'workDisplayByUser', 'punchInByUser', 'punchOutByUser'));
    }

    /**
     * Show editable detail for a timestamp (admin can edit directly)
     */
    public function show(Request $request, $id)
    {
        $attendance = Timestamp::with(['user', 'breakTime'])->findOrFail($id);

        // Normalize breaks for the detail view
        $attendance->breaks = $attendance->breakTime->map(function ($b) {
            return [
                'start' => optional($b->breakIn)->format('H:i'),
                'end' => optional($b->breakOut)->format('H:i'),
            ];
        })->toArray();

            // Check for pending approval for this timestamp
            $pending = \App\Models\Approval::where('timestamp_id', $attendance->id)
                ->where('status', 'pending')
                ->first();

            if ($pending) {
                // If there's a pending approval, pass it to the view so it renders read-only
                return view('attendance.detail', ['attendance' => $attendance, 'approval' => $pending]);
            }

            // No pending approval: also fetch latest approval (any status) to populate note
            $latestApproval = \App\Models\Approval::where('timestamp_id', $attendance->id)
                ->orderBy('created_at', 'desc')
                ->first();

            return view('attendance.detail', ['attendance' => $attendance, 'latestApproval' => $latestApproval]);
    }

    /**
     * Admin updates timestamp directly (no approval)
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate(array_merge([
            'punch_in' => 'nullable|date_format:H:i',
            'punch_out' => 'nullable|date_format:H:i',
        ], array_fill_keys(array_map(function($i){ return "breaks.$i.start"; }, range(0,9)), 'nullable|date_format:H:i')));

        $attendance = Timestamp::with('breakTime')->findOrFail($id);

        // If there's a pending approval for this timestamp, block edits
        $existingApproval = \App\Models\Approval::where('timestamp_id', $attendance->id)
            ->where('status', 'pending')
            ->first();
        if ($existingApproval) {
            return redirect('/admin/attendance/'.$id)->with('error', '承認まちのため修正できません');
        }

        $workDate = $attendance->work_date;

        // Update punch times
        $attendance->punchIn = !empty($validated['punch_in'])
            ? Carbon::createFromFormat('Y-m-d H:i', $workDate.' '.$validated['punch_in'])
            : null;
        $attendance->punchOut = !empty($validated['punch_out'])
            ? Carbon::createFromFormat('Y-m-d H:i', $workDate.' '.$validated['punch_out'])
            : null;
        $attendance->save();

        // Update existing break records and append new ones if provided.
        $inputBreaks = $request->input('breaks', []);
        $currentBreaks = $attendance->breakTime()->orderBy('id')->get();
        if (is_array($inputBreaks)) {
            foreach ($inputBreaks as $i => $b) {
                $start = $b['start'] ?? null;
                $end = $b['end'] ?? null;
                if (isset($currentBreaks[$i])) {
                    // update existing
                    $cb = $currentBreaks[$i];
                    $cb->breakIn = $start ? Carbon::createFromFormat('Y-m-d H:i', $workDate.' '.$start) : null;
                    $cb->breakOut = $end ? Carbon::createFromFormat('Y-m-d H:i', $workDate.' '.$end) : null;
                    $cb->save();
                } else {
                    // append if any value provided
                    if ($start || $end) {
                        $attendance->breakTime()->create([
                            'breakIn' => $start ? Carbon::createFromFormat('Y-m-d H:i', $workDate.' '.$start) : null,
                            'breakOut' => $end ? Carbon::createFromFormat('Y-m-d H:i', $workDate.' '.$end) : null,
                        ]);
                    }
                }
            }
        }

            // If admin provided a note, record it into approvals as an approved note entry
            if ($request->filled('note')) {
                $noteText = $request->input('note');
                \App\Models\Approval::create([
                    'user_id' => $attendance->user_id,
                    'timestamp_id' => $attendance->id,
                    'name' => $attendance->user->name ?? null,
                    'target_date' => $attendance->work_date,
                    'status' => 'approved',
                    'reason' => $noteText,
                    'payload' => null,
                    'details_link' => route('attendance.detail', $attendance->id),
                    'approved_by' => auth('admin')->id() ?? null,
                    'approved_at' => now(),
                ]);
            }

        return redirect('/admin/attendance/'.$id)->with('success', '勤怠を更新しました');
    }
}
