@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <h1 class="card-header text-center">勤怠入力</h1>
                <div class="card-body">
                    <div class="state">
                        @if (isset($timestamp) && $timestamp->punchIn)
                            @if ($timestamp->punchOut)
                                <p class="state-button">退勤済</p>
                            @else
                                @if ($timestamp->breakTime()->whereNull('breakOut')->first())
                                    <p class="state-button">休憩中</p>
                                @else
                                <p class="state-button">出勤中</p>
                                @endif
                            @endif
                        @else
                            <p class="state-button">勤務外</p>
                        @endif
                    </div>
                    <div class="date">{{ \Carbon\Carbon::now()->isoFormat('YYYY年MM月DD日（ddd）') }}</div>
                    <div class="current-time">{{ \Carbon\Carbon::now()->format('H:m') }}</div>
                    <div class="button-form">
                        <ul>
                            <li class="{{ (!isset($timestamp)) ? '' : 'disabled' }}">
                                <form action="{{ route('attendance.punchin') }}" method="POST">
                                    @csrf
                                    @method('POST')
                                    <button type="submit" class="btn btn-primary">出勤</button>
                                </form>
                            </li>
                            <li class="{{ (isset($timestamp) && $timestamp->punchIn && !$timestamp->punchOut) ? '' : 'disabled' }}">
                                <form action="{{ route('attendance.punchout') }}" method="POST">
                                    @csrf
                                    @method('POST')
                                    <button type="submit" class="btn btn-primary">退勤</button>
                                </form>
                            </li>
                            <li class="{{ (isset($timestamp) && $timestamp->punchIn && !$timestamp->punchOut &&  $timestamp->breakTime()->whereNull('breakOut')->count() == 0) ? '' : 'disabled' }}">
                                <form action="{{ route('attendance.breakin') }}" method="POST">
                                    @csrf
                                    @method('POST')
                                    <button type="submit" class="btn btn-break">休憩入</button>
                                </form>
                            </li>
                            <li class="{{ (isset($timestamp) && $timestamp->punchIn && !$timestamp->punchOut && $timestamp->breakTime()->whereNull('breakOut')->first() ? true : false) ? '' : 'disabled' }}">
                                <form action="{{ route('attendance.breakout') }}" method="POST">
                                    @csrf
                                    @method('POST')
                                    <button type="submit" class="btn btn-break">休憩戻</button>
                                </form>
                            </li>
                        </ul>
                        <p class="{{ (isset($timestamp) && $timestamp->punchOut) ? '' : 'disabled' }} punch-out-message">
                            お疲れ様でした。
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

@endsection
