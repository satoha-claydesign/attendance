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

        {{-- Approve button only (no 戻る, no 却下) --}}
        <div class="text-end">
            <form method="POST" action="{{ url('/stamp_correction_request/approve/'.$approval->id) }}" class="correction-button">
                @csrf
                <input type="hidden" name="action" value="approve">
                <button type="submit" class="btn btn-primary correction">承認</button>
            </form>
        </div>
    </div>
</div>
@endsection
