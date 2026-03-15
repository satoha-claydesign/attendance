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
        $validated = $request->validate([
            'punch_in' => 'nullable|date_format:H:i',
            'punch_out' => 'nullable|date_format:H:i',
            'break1_start' => 'nullable|date_format:H:i',
            'break1_end' => 'nullable|date_format:H:i',
            'break2_start' => 'nullable|date_format:H:i',
            'break2_end' => 'nullable|date_format:H:i',
            'note' => 'nullable|string|max:1000',
        ]);

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

        // Replace break records
        $attendance->breakTime()->delete();
        $breaks = [
            ['start' => $validated['break1_start'] ?? null, 'end' => $validated['break1_end'] ?? null],
            ['start' => $validated['break2_start'] ?? null, 'end' => $validated['break2_end'] ?? null],
        ];
        foreach ($breaks as $b) {
            if (!empty($b['start']) || !empty($b['end'])) {
                $attendance->breakTime()->create([
                    'breakIn' => !empty($b['start']) ? Carbon::createFromFormat('Y-m-d H:i', $workDate.' '.$b['start']) : null,
                    'breakOut' => !empty($b['end']) ? Carbon::createFromFormat('Y-m-d H:i', $workDate.' '.$b['end']) : null,
                ]);
            }
        }

        if (array_key_exists('note', $validated)) {
            $attendance->note = $validated['note'];
            $attendance->save();
        }

        return redirect('/admin/attendance/list')->with('success', '勤怠を更新しました');
    }
}
