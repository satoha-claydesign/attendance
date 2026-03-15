@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendancelist.css') }}">
@endsection

@section('content')
<div class="main-container">
    <div class="container mt-4 attendance-list-container">
        <h2 class="attendance-list-title">申請一覧</h2>

        <div class="month-nav">
            <div>
                <a class="month-link {{ ($tab ?? 'pending') === 'pending' ? '' : '' }}" href="{{ url('/stamp_correction_request/list?tab=pending') }}">承認待ち</a>
                <a class="month-link {{ ($tab ?? '') === 'approved' ? '' : '' }}" href="{{ url('/stamp_correction_request/list?tab=approved') }}">承認済み</a>
            </div>
        </div>

        <table class="attendance-table">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
            @foreach($approvals as $ap)
                <tr>
                    <td data-label="状態">{{ $ap->status }}</td>
                    <td data-label="名前">{{ $ap->name ?? ($ap->user->name ?? '—') }}</td>
                    <td data-label="対象日時">{{ $ap->target_date }}</td>
                    <td data-label="申請理由">{{ Str::limit($ap->reason ?? '—', 80) }}</td>
                    <td data-label="申請日時">{{ optional($ap->created_at)->format('Y-m-d H:i') }}</td>
                    <td data-label="詳細">
                        <a class="attendance-link" href="{{ url('/stamp_correction_request/approve/'.$ap->id) }}">詳細</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
