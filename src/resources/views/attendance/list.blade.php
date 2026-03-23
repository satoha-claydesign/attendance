@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendancelist.css') }}">
@endsection

@section('content')
<div class="main-container">
    <div class="container mt-4 attendance-list-container">
            <h2 class="attendance-list-title">勤怠一覧</h2>

                <div class="month-nav">
                    <a class="month-link prev" href="{{ route('attendance.list', ['month' => $prev->format('Y-m')]) }}">前月</a>
                    <a class="month-current" href="{{ route('attendance.list', ['month' => $current->format('Y-m')]) }}">{{ $current->format('Y/m') }}</a>
                    <a class="month-link next" href="{{ route('attendance.list', ['month' => $next->format('Y-m')]) }}">次月</a>
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
                            @php $dateKey = $dateStr; @endphp
                            <tr>
                                <td data-label="日付">{{ $day->locale('ja')->isoFormat('MM/DD (ddd)') }}</td>
                                <td data-label="出勤">{{ $attendance->punchIn ? \Carbon\Carbon::parse($attendance->punchIn)->format('H:i') : '—' }}</td>
                                <td data-label="退勤">{{ $attendance->punchOut ? \Carbon\Carbon::parse($attendance->punchOut)->format('H:i') : '—' }}</td>
                                <td data-label="休憩">{{ $breakDisplayByDate[$dateKey] ?? '0:00' }}</td>
                                <td data-label="合計">{{ $workDisplayByDate[$dateKey] ?? '—' }}</td>
                                <td data-label="詳細"><a class="attendance-link" href="{{ route('attendance.detail', $attendance->id) }}">詳細</a></td>
                            </tr>
                        @else
                            <tr>
                                                <td data-label="日付">{{ $day->locale('ja')->isoFormat('MM/DD (ddd)') }}</td>
                                <td data-label="出勤"> </td>
                                <td data-label="退勤"> </td>
                                <td data-label="休憩"> </td>
                                <td data-label="合計"> </td>
                                <td data-label="詳細"><span class="attendance-link inactive">詳細</span></td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
        </table>
    </div>
</div>
@endsection

