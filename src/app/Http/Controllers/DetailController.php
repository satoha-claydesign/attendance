<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Timestamp;

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

        return view('attendance.detail', ['attendance' => $attendance]);
    }
}
