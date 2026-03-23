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
                    <div class="current-time" id="current-time">{{ \Carbon\Carbon::now()->format('H:i') }}</div>
                    <div class="button-form">
                        <ul>
                            <li class="{{ (!isset($timestamp)) ? '' : 'disabled' }}">
                                <form action="{{ route('attendance.punchin') }}" method="POST">
                                    @csrf
                                    @method('POST')
                                    <button type="submit" class="btn btn-primary">出勤</button>
                                </form>
                            </li>
                            <li class="{{ (isset($timestamp) && $timestamp->punchIn && !$timestamp->punchOut && !$timestamp->breakTime()->whereNull('breakOut')->first()) ? '' : 'disabled' }}">
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

<script>
// クライアントの現在時刻を常に表示する（ローカルタイム）
(function(){
    function pad(n){ return n < 10 ? '0' + n : n; }
    function updateTime(){
        try{
            var now = new Date();
            var h = pad(now.getHours());
            var m = pad(now.getMinutes());
            var el = document.getElementById('current-time');
            if(el) el.textContent = h + ':' + m;
        }catch(e){ /* silent */ }
    }
    updateTime();
    // 1秒ごとに更新（分の変化を確実に反映するため）
    setInterval(updateTime, 1000);
})();
</script>

@endsection
