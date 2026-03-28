<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Timestamp;
use Carbon\Carbon;

class AdminStaffController extends Controller
{

    public function index()
    {
        $users = User::orderBy('name')->get();
        return view('admin.staff.list', compact('users'));
    }

    public function staffAttendance(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $monthQuery = $request->query('month');
        try {
            $current = $monthQuery ? Carbon::createFromFormat('Y-m', $monthQuery)->startOfMonth() : Carbon::today()->startOfMonth();
        } catch (\Exception $e) {
            $current = Carbon::today()->startOfMonth();
        }

        $start = $current->copy()->startOfMonth()->toDateString();
        $end = $current->copy()->endOfMonth()->toDateString();

        $attendances = Timestamp::with('breakTime')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$start, $end])
            ->orderBy('work_date', 'desc')
            ->get();

        $attendanceByDate = $attendances->keyBy('work_date');

        // Precompute break/work display strings per date
        $breakDisplayByDate = [];
        $workDisplayByDate = [];
        foreach ($attendanceByDate as $dt => $att) {
            $breakTotal = 0;
            foreach ($att->breakTime as $b) {
                if ($b->breakIn && $b->breakOut) {
                    $breakTotal += \Carbon\Carbon::parse($b->breakIn)->diffInMinutes(\Carbon\Carbon::parse($b->breakOut));
                }
            }
            $breakDisplayByDate[$dt] = $breakTotal > 0 ? (int)($breakTotal/60) . ':' . str_pad($breakTotal%60, 2, '0', STR_PAD_LEFT) : '0:00';
            if ($att->punchIn && $att->punchOut) {
                $workMinutes = \Carbon\Carbon::parse($att->punchIn)->diffInMinutes(\Carbon\Carbon::parse($att->punchOut)) - $breakTotal;
                $workDisplayByDate[$dt] = (int)($workMinutes/60) . ':' . str_pad($workMinutes%60, 2, '0', STR_PAD_LEFT);
            } else {
                $workDisplayByDate[$dt] = '—';
            }
        }

        $numDays = $current->daysInMonth;
        $days = collect();
        for ($i = 0; $i < $numDays; $i++) {
            $days->push($current->copy()->addDays($i));
        }

        $prev = $current->copy()->subMonth();
        $next = $current->copy()->addMonth();

    return view('admin.attendance.staff', compact('user','attendances', 'attendanceByDate', 'days', 'current', 'prev', 'next', 'breakDisplayByDate', 'workDisplayByDate'));
    }

    public function exportCsv(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $monthQuery = $request->query('month');
        try {
            $current = $monthQuery ? Carbon::createFromFormat('Y-m', $monthQuery)->startOfMonth() : Carbon::today()->startOfMonth();
        } catch (\Exception $e) {
            $current = Carbon::today()->startOfMonth();
        }

        $start = $current->copy()->startOfMonth()->toDateString();
        $end = $current->copy()->endOfMonth()->toDateString();

        $attendances = Timestamp::with('breakTime')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$start, $end])
            ->orderBy('work_date', 'asc')
            ->get();

        $filename = sprintf('%s_%s.csv', str_replace(' ', '_', $user->name), $current->format('Y-m'));

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $columns = ['日付', '出勤', '退勤', '休憩合計', '勤務合計', '備考'];

        $callback = function () use ($attendances, $columns) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM for Excel on Windows
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $columns);

            foreach ($attendances as $att) {
                $date = $att->work_date;
                $punchIn = $att->punchIn ? \Carbon\Carbon::parse($att->punchIn)->format('H:i') : '';
                $punchOut = $att->punchOut ? \Carbon\Carbon::parse($att->punchOut)->format('H:i') : '';

                $breakTotal = 0;
                $breakParts = [];
                foreach ($att->breakTime as $b) {
                    if ($b->breakIn && $b->breakOut) {
                        $mins = \Carbon\Carbon::parse($b->breakIn)->diffInMinutes(\Carbon\Carbon::parse($b->breakOut));
                        $breakTotal += $mins;
                        $breakParts[] = sprintf('%s-%s', \Carbon\Carbon::parse($b->breakIn)->format('H:i'), \Carbon\Carbon::parse($b->breakOut)->format('H:i'));
                    }
                }
                $breakStr = $breakParts ? implode(' | ', $breakParts) : '';
                $breakDisplay = $breakTotal > 0 ? (int)($breakTotal/60) . ':' . str_pad($breakTotal%60, 2, '0', STR_PAD_LEFT) : '0:00';

                if ($att->punchIn && $att->punchOut) {
                    $workMinutes = \Carbon\Carbon::parse($att->punchIn)->diffInMinutes(\Carbon\Carbon::parse($att->punchOut)) - $breakTotal;
                    $workDisplay = (int)($workMinutes/60) . ':' . str_pad($workMinutes%60, 2, '0', STR_PAD_LEFT);
                } else {
                    $workDisplay = '';
                }

                $note = $att->note ?? '';

                fputcsv($out, [$date, $punchIn, $punchOut, $breakDisplay, $workDisplay, $note]);
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }
}
