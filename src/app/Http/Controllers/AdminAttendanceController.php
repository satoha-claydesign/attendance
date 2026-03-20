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

        // load all users and timestamps for the date
        $users = User::orderBy('name')->get();
        $timestamps = Timestamp::with('breakTime')->where('work_date', $dateStr)->get()->keyBy('user_id');

        return view('admin.attendance.list', compact('users', 'timestamps', 'current', 'prev', 'next', 'dateStr'));
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

        // Pass no approval so the detail view renders the editable form
        return view('attendance.detail', ['attendance' => $attendance]);
    }

    /**
     * Admin updates timestamp directly (no approval)
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate(array_merge([
            'punch_in' => 'nullable|date_format:H:i',
            'punch_out' => 'nullable|date_format:H:i',
            'note' => 'nullable|string|max:1000',
        ], array_fill_keys(array_map(function($i){ return "breaks.$i.start"; }, range(0,9)), 'nullable|date_format:H:i')));

        $attendance = Timestamp::with('breakTime')->findOrFail($id);

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

        if (array_key_exists('note', $validated)) {
            $attendance->note = $validated['note'];
            $attendance->save();
        }

        return redirect('/admin/attendance/list')->with('success', '勤怠を更新しました');
    }
}
