@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/adminstaff.css') }}">
@endsection

@section('content')
<div class="main-container">
    <div class="container mt-4 attendance-list-container">
            <h2 class="attendance-list-title">{{ $user->name }}の勤怠</h2>

                <div class="month-nav">
                    <a class="month-link prev" href="{{ url('/admin/attendance/staff/'.$user->id.'?month='.$prev->format('Y-m')) }}">前月</a>
                    <a class="month-current" href="{{ url('/admin/attendance/staff/'.$user->id.'?month='.$current->format('Y-m')) }}">{{ $current->format('Y/m') }}</a>
                    <a class="month-link next" href="{{ url('/admin/attendance/staff/'.$user->id.'?month='.$next->format('Y-m')) }}">次月</a>
                </div>

            <table class="attendance-table">
            <thead>
                <tr>
                    <th>日付</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
                <tbody>
                    @foreach($days as $day)
                        @php
                            $dateStr = $day->format('Y-m-d');
                            $attendance = $attendanceByDate[$dateStr] ?? null;
                        @endphp
                        @if($attendance)
                            @php
                                $breakTotal = 0;
                                foreach($attendance->breakTime as $b) {
                                    if($b->breakIn && $b->breakOut) {
                                        $breakTotal += \Carbon\Carbon::parse($b->breakIn)->diffInMinutes(\Carbon\Carbon::parse($b->breakOut));
                                    }
                                }
                                $breakDisplay = $breakTotal > 0 ? (int)($breakTotal/60) . ':' . str_pad($breakTotal%60, 2, '0', STR_PAD_LEFT)  : '0:00';
                                if($attendance->punchIn && $attendance->punchOut) {
                                    $workMinutes = \Carbon\Carbon::parse($attendance->punchIn)->diffInMinutes(\Carbon\Carbon::parse($attendance->punchOut)) - $breakTotal;
                                    $workDisplay = (int)($workMinutes/60) . ':' . str_pad($workMinutes%60, 2, '0', STR_PAD_LEFT);
                                } else {
                                    $workDisplay = '—';
                                }
                            @endphp
                            <tr>
                                <td data-label="日付">{{ $day->locale('ja')->isoFormat('MM/DD (ddd)') }}</td>
                                <td data-label="出勤">{{ $attendance->punchIn ? \Carbon\Carbon::parse($attendance->punchIn)->format('H:i') : '—' }}</td>
                                <td data-label="退勤">{{ $attendance->punchOut ? \Carbon\Carbon::parse($attendance->punchOut)->format('H:i') : '—' }}</td>
                                <td data-label="休憩">{{ $breakDisplay }}</td>
                                <td data-label="合計">{{ $workDisplay }}</td>
                                <td data-label="詳細"><a class="attendance-link" href="{{ url('/admin/attendance/'.$attendance->id) }}">詳細</a></td>
                            </tr>
                        @else
                            <tr>
                                <td data-label="日付">{{ $day->locale('ja')->isoFormat('MM/DD (ddd)') }}</td>
                                <td data-label="出勤"> </td>
                                <td data-label="退勤"> </td>
                                <td data-label="休憩"> </td>
                                <td data-label="合計"> </td>
                                <td data-label="詳細"><a class="attendance-link" href="{{ route('attendance.detail') }}?date={{ $dateStr }}">詳細</a></td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
        </table>
    </div>
</div>
@endsection
