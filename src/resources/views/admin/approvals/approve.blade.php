@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail.css') }}">
@endsection

@section('content')
<div class="main-container">
    <div class="container mt-4 attendance-detail">
        <h2>勤怠詳細</h2>

        @php
            // normalize approval payload for reuse of the attendance detail read-only layout
            $ap = $approval;
            $payload = $approval->payload ?? [];
            $punchIn = $payload['punch_in'] ?? null;
            $punchOut = $payload['punch_out'] ?? null;
            $breaks = $payload['breaks'] ?? [];
        @endphp

        {{-- Reuse the pending-approval (read-only) layout from attendance.detail --}}
        <table class="table table-bordered">
            <tbody>
                <tr>
                    <th style="width:160px">名前</th>
                    <td>{{ $approval->name ?? ($approval->user->name ?? '—') }}</td>
                </tr>
                <tr>
                    <th>日付</th>
                    <td>
                        <div class="date-blocks">
                            <div class="block year-block">{{ isset($approval->target_date) ? \Carbon\Carbon::parse($approval->target_date)->format('Y') . '年' : '' }}</div>
                            <div class="block md-block">{{ isset($approval->target_date) ? \Carbon\Carbon::parse($approval->target_date)->format('m') . '月' . \Carbon\Carbon::parse($approval->target_date)->format('d') . '日' : '' }}</div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>出勤・退勤</th>
                    <td>
                        <div class="block">
                            <div class="block-inputs">
                                <div class="block-time"> {{ $punchIn ?? '—' }}</div>
                                <span>〜</span>
                                <div class="block-time"> {{ $punchOut ?? '—' }}</div>
                            </div>
                        </div>
                    </td>
                </tr>
                @foreach($breaks as $i => $b)
                    <tr>
                        <th>{{ $i === 0 ? '休憩' : '休憩'.($i+1) }}</th>
                        <td>
                            <div class="block">
                                <div class="block-inputs">
                                    <div class="block-time">{{ is_array($b) ? ($b['start'] ?? '—') : ($b ?? '—') }}</div>
                                    <span>〜</span>
                                    <div class="block-time">{{ is_array($b) ? ($b['end'] ?? '—') : ($b ?? '—') }}</div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
                <tr>
                    <th>備考</th>
                    <td>{{ $approval->reason ?? '—' }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Approve button area: show actionable controls only to admins. Regular users see read-only labels. --}}
        <div class="text-end">
            @if(isset($approval) && $approval->status === 'pending')
                @if(auth('admin')->check())
                    <form method="POST" action="{{ url('/admin/stamp_correction_request/approve/'.$approval->id) }}" class="correction-button" style="display:inline-block">
                        @csrf
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn btn-primary correction">承認</button>
                    </form>
                @else
                    <div class="alert alert-warning">*承認待ちのため修正はできません。</div>
                @endif
            @elseif(isset($approval) && $approval->status === 'approved')
                <button type="button" class="btn btn-primary correction approved" >承認済み</button>
            @else
                {{-- Non-pending, non-approved (e.g., rejected) — show disabled label --}}
                <button type="button" class="btn btn-secondary" disabled>{{ $approval->status ?? '処理済み' }}</button>
            @endif
        </div>
    </div>
</div>
@endsection
