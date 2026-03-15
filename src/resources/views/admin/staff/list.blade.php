@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/adminstaff.css') }}">
@endsection

@section('content')
<div class="main-container">
    <div class="container mt-4 attendance-list-container">
        <h2 class="attendance-list-title">スタッフ一覧</h2>

        <table class="attendance-table">
            <thead>
                <tr>
                    <th>名前</th>
                    <th>メールアドレス</th>
                    <th>月次勤怠</th>
                </tr>
            </thead>
            <tbody>
            @foreach($users as $u)
                <tr>
                    <td data-label="名前">{{ $u->name }}</td>
                    <td data-label="メールアドレス">{{ $u->email }}</td>
                    <td data-label="月次勤怠"><a class="attendance-link" href="{{ url('/admin/attendance/staff/'.$u->id) }}">詳細</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
