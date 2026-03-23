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
                    @php $ts = $timestamps[$user->id] ?? null; $uid = $user->id; @endphp
                    <tr>
                        <td data-label="名前">{{ $user->name }}</td>
                        <td data-label="出勤">{{ $punchInByUser[$uid] ?? '—' }}</td>
                        <td data-label="退勤">{{ $punchOutByUser[$uid] ?? '—' }}</td>
                        <td data-label="休憩">{{ $breakDisplayByUser[$uid] ?? '0:00' }}</td>
                        <td data-label="合計">{{ $workDisplayByUser[$uid] ?? '—' }}</td>
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
