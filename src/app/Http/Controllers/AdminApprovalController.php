<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Approval;
use App\Models\Timestamp;
use App\Models\BreakTime;
use Carbon\Carbon;

class AdminApprovalController extends Controller
{
    /**
     * List pending approvals
     */
    public function index()
    {
        // tab can be 'pending' or 'approved' (approved includes 'approved' and 'rejected'?)
        $tab = request()->query('tab', 'pending');
        $statusFilter = $tab === 'approved' ? ['approved','rejected'] : ['pending'];

        if (auth('admin')->check()) {
            $approvals = Approval::with('user', 'timestamp')
                ->whereIn('status', $statusFilter)
                ->orderBy('created_at', 'desc')
                ->get();
        } elseif (auth()->check()) {
            $approvals = Approval::with('timestamp')
                ->where('user_id', auth()->id())
                ->whereIn('status', $statusFilter)
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            return redirect()->route('login');
        }

            // Add presentation helpers to each approval to reduce view logic
            $approvals->each(function($ap){
                $label = $ap->status;
                if ($ap->status === 'pending') $label = '承認待ち';
                elseif ($ap->status === 'approved') $label = '承認済み';
                elseif ($ap->status === 'rejected') $label = '却下';
                $ap->status_label = $label;
                $ap->target_date_label = $ap->target_date ? \Carbon\Carbon::parse($ap->target_date)->format('Y/m/d') : '—';
                $ap->created_at_label = optional($ap->created_at)->format('Y/m/d');
            });

        return view('admin.approvals.index', compact('approvals', 'tab'));
    }

    /**
     * Show approval detail (read-only) — same look as user detail
     */
    public function show($id)
    {
        $approval = Approval::with('user', 'timestamp')->findOrFail($id);

        // Build a fake attendance shape to reuse the detail view UI
        $attendance = null;
        if ($approval->timestamp) {
            $attendance = $approval->timestamp->load('breakTime');
            $attendance->breaks = $attendance->breakTime->map(function ($b) {
                return [
                    'start' => optional($b->breakIn)->format('H:i'),
                    'end' => optional($b->breakOut)->format('H:i'),
                ];
            })->toArray();
        }

        return view('admin.approvals.approve', ['approval' => $approval, 'attendance' => $attendance]);
    }

    /**
     * Approve or reject the request. POST action.
     */
    public function approve(Request $request, $id)
    {
        $approval = Approval::findOrFail($id);

        if ($approval->status !== 'pending') {
            return redirect()->back()->with('error', 'この申請は既に処理されています。');
        }

        $action = $request->input('action', 'approve');

        if ($action === 'approve') {
            // Apply payload to timestamps/breaks
            $payload = $approval->payload ?? [];
            $userId = $approval->user_id;
            $targetDate = $approval->target_date;

            // find or create timestamp
            $timestamp = null;
            if ($approval->timestamp_id) {
                $timestamp = Timestamp::find($approval->timestamp_id);
            }
            if (! $timestamp) {
                $timestamp = Timestamp::firstOrCreate([
                    'user_id' => $userId,
                    'work_date' => $targetDate,
                ]);
            }

            // Update punch times
            if (isset($payload['punch_in'])) {
                $timestamp->punchIn = $payload['punch_in'] ? Carbon::createFromFormat('Y-m-d H:i', $targetDate.' '.$payload['punch_in']) : null;
            }
            if (isset($payload['punch_out'])) {
                $timestamp->punchOut = $payload['punch_out'] ? Carbon::createFromFormat('Y-m-d H:i', $targetDate.' '.$payload['punch_out']) : null;
            }
            $timestamp->save();

            // Append payload breaks to existing break records (do not delete existing)
            $breaks = $payload['breaks'] ?? [];
            foreach ($breaks as $b) {
                if ((!empty($b['start'])) || (!empty($b['end']))) {
                    $timestamp->breakTime()->create([
                        'breakIn' => !empty($b['start']) ? Carbon::createFromFormat('Y-m-d H:i', $targetDate.' '.$b['start']) : null,
                        'breakOut' => !empty($b['end']) ? Carbon::createFromFormat('Y-m-d H:i', $targetDate.' '.$b['end']) : null,
                    ]);
                }
            }

            $approval->status = 'approved';
            $approval->approved_by = auth('admin')->id() ?? auth()->id();
            $approval->approved_at = now();
            $approval->save();

            return redirect('/stamp_correction_request/approve/'.$approval->id)->with('success', '申請を承認し、勤怠を更新しました。');
        }

        // reject
    $approval->status = 'rejected';
    $approval->approved_by = auth('admin')->id() ?? auth()->id();
    $approval->approved_at = now();
    $approval->save();

    return redirect('/stamp_correction_request/approve/'.$approval->id)->with('success', '申請を却下しました。');
    }
}
