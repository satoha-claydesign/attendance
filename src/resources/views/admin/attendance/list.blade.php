@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendancelist.css') }}">
@endsection

@section('content')
<div class="main-container">
    <div class="container mt-4 attendance-list-container">
        <h2 class="attendance-list-title">{{ $current->isoFormat('YYYY年MM月DD日') }}の勤怠</h2>

        <div class="month-nav">
            <a class="month-link prev" href="{{ url('/admin/attendance/list?date='.$prev->format('Y-m-d')) }}">前日</a>
            <span class="month-current">{{ $current->format('Y/m/d') }}</span>
            <a class="month-link next" href="{{ url('/admin/attendance/list?date='.$next->format('Y-m-d')) }}">翌日</a>
        </div>

        <table class="attendance-table">
            <thead>
                <tr>
                    <th>名前</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    @php
                        $ts = $timestamps[$user->id] ?? null;
                        $breakDisplay = '0:00';
                        $workDisplay = '—';
                        $punchIn = null;
                        $punchOut = null;
                        if ($ts) {
                            $punchIn = $ts->punchIn;
                            $punchOut = $ts->punchOut;
                            $breakTotal = 0;
                            foreach($ts->breakTime as $b) {
                                if($b->breakIn && $b->breakOut) {
                                    $breakTotal += \Carbon\Carbon::parse($b->breakIn)->diffInMinutes(\Carbon\Carbon::parse($b->breakOut));
                                }
                            }
                            $breakDisplay = $breakTotal > 0 ? (int)($breakTotal/60) . ':' . str_pad($breakTotal%60, 2, '0', STR_PAD_LEFT)  : '0:00';
                            if($ts->punchIn && $ts->punchOut) {
                                $workMinutes = \Carbon\Carbon::parse($ts->punchIn)->diffInMinutes(\Carbon\Carbon::parse($ts->punchOut)) - $breakTotal;
                                $workDisplay = (int)($workMinutes/60) . ':' . str_pad($workMinutes%60, 2, '0', STR_PAD_LEFT);
                            }
                        }
                    @endphp
                    <tr>
                        <td data-label="名前">{{ $user->name }}</td>
                        <td data-label="出勤">{{ $punchIn ? \Carbon\Carbon::parse($punchIn)->format('H:i') : '—' }}</td>
                        <td data-label="退勤">{{ $punchOut ? \Carbon\Carbon::parse($punchOut)->format('H:i') : '—' }}</td>
                        <td data-label="休憩">{{ $breakDisplay }}</td>
                        <td data-label="合計">{{ $workDisplay }}</td>
                        <td data-label="詳細">
                            @if($ts)
                                <a class="attendance-link" href="{{ url('/admin/attendance/'.$ts->id) }}">詳細</a>
                            @else
                                <a class="attendance-link" href="{{ route('attendance.detail') }}?date={{ $dateStr }}">詳細</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
