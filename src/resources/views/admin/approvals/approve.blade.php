@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail.css') }}">
@endsection

@section('content')
<div class="main-container">
    <div class="container mt-4 attendance-detail">
        <h2>修正申請承認</h2>

        @php
            $ap = $approval;
            $payload = $approval->payload ?? [];
            $punchIn = $payload['punch_in'] ?? null;
            $punchOut = $payload['punch_out'] ?? null;
            $breaks = $payload['breaks'] ?? [];
            $b1 = $breaks[0] ?? null;
            $b2 = $breaks[1] ?? null;
        @endphp

        <table class="table table-bordered">
            <tbody>
                <tr>
                    <th style="width:160px">申請者</th>
                    <td>{{ $approval->name ?? ($approval->user->name ?? '—') }}</td>
                </tr>
                <tr>
                    <th>申請日時</th>
                    <td>{{ optional($approval->created_at)->format('Y-m-d H:i') }}</td>
                </tr>
                <tr>
                    <th>状態</th>
                    <td>{{ $approval->status }}</td>
                </tr>
                <tr>
                    <th>対象日</th>
                    <td>{{ $approval->target_date }}</td>
                </tr>
                <tr>
                    <th>申請理由</th>
                    <td>{{ $approval->reason ?? '—' }}</td>
                </tr>
                <tr>
                    <th>申請された出勤・退勤</th>
                    <td>出勤: {{ $punchIn ?? '—' }} &nbsp;&nbsp; 退勤: {{ $punchOut ?? '—' }}</td>
                </tr>
                <tr>
                    <th>申請された休憩</th>
                    <td>
                        休憩1: {{ is_array($b1) ? ($b1['start'] ?? '—') . ' - ' . ($b1['end'] ?? '—') : ($b1 ?? '—') }}<br>
                        休憩2: {{ is_array($b2) ? ($b2['start'] ?? '—') . ' - ' . ($b2['end'] ?? '—') : ($b2 ?? '—') }}
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="text-end">
                        <a href="{{ url('/stamp_correction_request/list') }}" class="btn btn-secondary">戻る</a>
                        <form method="POST" action="{{ url('/stamp_correction_request/approve/'.$approval->id) }}" style="display:inline-block">
                            @csrf
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-primary">承認する</button>
                        </form>
                        <form method="POST" action="{{ url('/stamp_correction_request/approve/'.$approval->id) }}" style="display:inline-block">
                            @csrf
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="btn btn-danger">却下する</button>
                        </form>
                    </td>
                </tr>
            </tbody>
        </table>

    </div>
</div>
@endsection
