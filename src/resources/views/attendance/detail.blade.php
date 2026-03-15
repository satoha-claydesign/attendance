@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail.css') }}">
@endsection

@section('content')
<div class="main-container">
    <div class="container mt-4 attendance-detail">
        <h2>勤怠詳細</h2>

        @php
            // normalize values
            $breaks = $attendance->breaks ?? $attendance->break_times ?? [];
            $name = $attendance->user->name ?? ($attendance->name ?? '—');
            $date = $attendance->work_date ?? ($attendance->date ?? ($attendance->created_at ? \Carbon\Carbon::parse($attendance->created_at)->format('Y-m-d') : '—'));
            // detect punch times
            $punchIn = $attendance->punchIn ?? $attendance->start_time ?? null;
            $punchOut = $attendance->punchOut ?? $attendance->end_time ?? null;
            $note = $attendance->note ?? $attendance->memo ?? '';

            // helper to format time for input[type=time]
            $formatTime = function($t) {
                if(!$t) return '';
                try { return \Carbon\Carbon::parse($t)->format('H:i'); } catch(\Exception $e) { return (string)$t; }
            };

            $break1 = $breaks[0] ?? null;
            $break2 = $breaks[1] ?? null;
            $b1start = is_array($break1) ? ($break1['start'] ?? null) : ($break1->start ?? $break1);
            $b1end = is_array($break1) ? ($break1['end'] ?? null) : ($break1->end ?? null);
            $b2start = is_array($break2) ? ($break2['start'] ?? null) : ($break2->start ?? $break2);
            $b2end = is_array($break2) ? ($break2['end'] ?? null) : ($break2->end ?? null);
        @endphp

        @php
            $pendingApproval = isset($approval) && $approval && ($approval->status === 'pending');
            if ($pendingApproval) {
                $ap = (array) $approval->payload;
                $ap_punch_in = $ap['punch_in'] ?? null;
                $ap_punch_out = $ap['punch_out'] ?? null;
                $ap_breaks = $ap['breaks'] ?? [];
                $ap_b1 = $ap_breaks[0] ?? null;
                $ap_b2 = $ap_breaks[1] ?? null;
            }
        @endphp

        @if($pendingApproval)
            <div class="alert alert-warning">現在、この勤怠には承認待ちの変更申請があります。以下は申請された変更内容です（編集不可）。</div>
            <table class="table table-bordered">
                <tbody>
                    <tr>
                        <th style="width:160px">申請者</th>
                        <td>{{ $approval->name ?? $name }}</td>
                    </tr>
                    <tr>
                        <th>申請日時</th>
                        <td>{{ optional($approval->created_at)->format('Y-m-d H:i') }}</td>
                    </tr>
                    <tr>
                        <th>状態</th>
                        <td>承認待ち</td>
                    </tr>
                    <tr>
                        <th>対象日</th>
                        <td>{{ $approval->target_date ?? $date }}</td>
                    </tr>
                    <tr>
                        <th>申請理由</th>
                        <td>{{ $approval->reason ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>申請された出勤・退勤</th>
                        <td>
                            出勤: {{ $ap_punch_in ?? '—' }}
                            &nbsp;&nbsp; 退勤: {{ $ap_punch_out ?? '—' }}
                        </td>
                    </tr>
                    <tr>
                        <th>申請された休憩</th>
                        <td>
                            休憩1: {{ is_array($ap_b1) ? ($ap_b1['start'] ?? '—') . ' - ' . ($ap_b1['end'] ?? '—') : ($ap_b1 ?? '—') }}<br>
                            休憩2: {{ is_array($ap_b2) ? ($ap_b2['start'] ?? '—') . ' - ' . ($ap_b2['end'] ?? '—') : ($ap_b2 ?? '—') }}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" class="text-end">
                            <a href="{{ url()->previous() }}" class="btn btn-secondary">戻る</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        @else
            <form method="POST" action="{{ route('attendance.update', $attendance->id ?? '') }}">
                @csrf
                @method('PUT')
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <th style="width:160px">名前</th>
                            <td>{{ $name }}</td>
                        </tr>
                        <tr>
                            <th>日付</th>
                            <td>{{ $date }}</td>
                        </tr>
                        <tr>
                            <th>出勤・退勤</th>
                            <td>
                                <label>出勤 <input type="time" name="punch_in" value="{{ $formatTime($punchIn) }}"></label>
                                &nbsp;&nbsp;
                                <label>退勤 <input type="time" name="punch_out" value="{{ $formatTime($punchOut) }}"></label>
                            </td>
                        </tr>
                        <tr>
                            <th>休憩</th>
                            <td>
                                <label>開始 <input type="time" name="break1_start" value="{{ $formatTime($b1start) }}"></label>
                                &nbsp;&nbsp;
                                <label>終了 <input type="time" name="break1_end" value="{{ $formatTime($b1end) }}"></label>
                            </td>
                        </tr>
                        <tr>
                            <th>休憩2</th>
                            <td>
                                <label>開始 <input type="time" name="break2_start" value="{{ $formatTime($b2start) }}"></label>
                                &nbsp;&nbsp;
                                <label>終了 <input type="time" name="break2_end" value="{{ $formatTime($b2end) }}"></label>
                            </td>
                        </tr>
                        <tr>
                            <th>備考</th>
                            <td>
                                <textarea name="note" rows="3" class="form-control" style="width:100%">{{ old('note', $note) }}</textarea>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" class="text-end">
                                <a href="{{ url()->previous() }}" class="btn btn-secondary">戻る</a>
                                <button type="submit" class="btn btn-primary">保存</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </form>
        @endif

        {{-- 複数レコードを一覧で表示したい場合の例（コメント） --}}
        {{--
        <table class="table table-striped">
            <thead>
            <tr><th>氏名</th><th>日付</th><th>出勤時間</th><th>退勤時間</th><th>休憩時間</th></tr>
            </thead>
            <tbody>
            @foreach($attendances as $item)
                @php
                $bks = $item->breaks ?? $item->break_times ?? [];
                @endphp
                @if(empty($bks))
                <tr>
                    <td>{{ $item->user->name ?? ($item->name ?? '—') }}</td>
                    <td>{{ $item->date ?? $item->created_at }}</td>
                    <td>{{ $item->start_time ?? '—' }}</td>
                    <td>{{ $item->end_time ?? '—' }}</td>
                    <td>—</td>
                </tr>
                @else
                @foreach($bks as $i => $bk)
                    <tr>
                    @if($i === 0)
                        <td>{{ $item->user->name ?? ($item->name ?? '—') }}</td>
                        <td>{{ $item->date ?? $item->created_at }}</td>
                        <td>{{ $item->start_time ?? '—' }}</td>
                        <td>{{ $item->end_time ?? '—' }}</td>
                    @else
                        <td></td><td></td><td></td><td></td>
                    @endif
                    <td>
                        @if(is_array($bk) || is_object($bk))
                        {{ $bk['start'] ?? $bk->start ?? '—' }} @if(($bk['end'] ?? $bk->end ?? null)) - {{ $bk['end'] ?? $bk->end }} @endif
                        @else
                        {{ $bk }}
                        @endif
                    </td>
                    </tr>
                @endforeach
                @endif
            @endforeach
            </tbody>
        </table>
        --}}

    </div>
</div>
@endsection
