@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail.css') }}">
@endsection

@section('content')
<div class="main-container">
    <div class="container mt-4 attendance-detail">
        <h2>勤怠詳細</h2>

        @php
            $name = $attendance->user->name ?? ($attendance->name ?? '—');
            $pendingApproval = isset($approval) && $approval && ($approval->status === 'pending');
        @endphp

        @if($pendingApproval)
            <table class="table table-bordered">
                <tbody>
                    <tr>
                        <th style="width:160px">名前</th>
                        <td>{{ $approval->name ?? $name }}</td>
                    </tr>
                    <tr>
                        <th>日付</th>
                        <td>
                            <div class="date-blocks">
                                <div class="block year-block">{{ isset($approval->target_date) ? \Carbon\Carbon::parse($approval->target_date)->format('Y') . '年' : $dateYear }}</div>
                                <div class="block md-block">{{ isset($approval->target_date) ? \Carbon\Carbon::parse($approval->target_date)->format('m') . '月' . \Carbon\Carbon::parse($approval->target_date)->format('d') . '日' : $dateMonthDay }}</div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>出勤・退勤</th>
                        <td>
                            <div class="block">
                                <div class="block-inputs">
                                    <div class="block-time"> {{ $ap_punch_in ?? '—' }}</div>
                                    <span>〜</span>
                                    <div class="block-time"> {{ $ap_punch_out ?? '—' }}</div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @php
                        $ap_breaks = $ap_breaks ?? [];
                    @endphp
                    @foreach($ap_breaks as $i => $b)
                        <tr>
                            <th>{{ $i === 0 ? '休憩' : '休憩'.($i+1) }}</th>
                            <td>
                                <div class="block">
                                    <div class="block-inputs">
                                        <div class="block-time">{{ is_array($b) ? ($b['start'] ?? '—') : ($b ?? '—') }}</div>
                                        <span>〜</span>
                                        <div class="block-time">{{ is_array($b) ? ($b['end'] ?? '—') : ($b ?? '—') }}</div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    <tr>
                        <th>備考</th>
                        <td>{{ $approval->reason ?? '—' }}</td>
                    </tr>
                </tbody>
            </table>
            <div class="alert alert-warning">*承認待ちのため修正はできません。</div>
        @else
            @if(auth('admin')->check())
            <form method="POST" action="{{ url('/admin/attendance/'.$attendance->id) }}">
            @else
            <form method="POST" action="{{ route('attendance.update', $attendance->id ?? '') }}">
            @endif
                @csrf
                @method('PUT')
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <th>名前</th>
                            <td>{{ $name }}</td>
                        </tr>
                        <tr>
                            <th>日付</th>
                            <td>
                                <div class="date-blocks">
                                    <div class="block year-block">{{ $dateYear }}</div>
                                    <div class="block md-block">{{ $dateMonthDay }}</div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>出勤・退勤</th>
                            <td>
                                <div class="block">
                                    <div class="block-inputs">
                                        <input type="time" name="punch_in" value="{{ old('punch_in', $punchInFormatted ?? '') }}">
                                        <span>〜</span>
                                        <input type="time" name="punch_out" value="{{ old('punch_out', $punchOutFormatted ?? '') }}">
                                    </div>
                                    @php
                                        $punchErr = $errors->first('punch_in') ?: $errors->first('punch_out');
                                    @endphp
                                    @if($punchErr)
                                        <p class="form__error">{{ $punchErr }}</p>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @for($i = 0; $i < ($rows ?? 2); $i++)
                            @php
                                // default
                                $val = ['start' => null, 'end' => null];

                                // Use data_get which works for arrays, objects and Collections
                                $tmp = data_get($breaksForInput, $i, null);
                                if ($tmp !== null) {
                                    $val = is_array($tmp) ? $tmp : (array) $tmp;
                                }
                            @endphp
                            <tr>
                                <th>{{ $i === 0 ? '休憩' : '休憩'.($i+1) }}</th>
                                <td>
                                    <!-- <div class="break-row-item"> -->
                                    <div class="block">
                                        <div class="block-inputs">
                                            <input type="time" name="breaks[{{ $i }}][start]" value="{{ $val['start'] }}">
                                            <span>〜</span>
                                            <input type="time" name="breaks[{{ $i }}][end]" value="{{ $val['end'] }}">
                                        </div>
                                    </div>
                                    @php
                                        $breakErr = $errors->first("breaks.$i.start") ?: $errors->first("breaks.$i.end");
                                    @endphp
                                    @if($breakErr)
                                        <p class="form__error">{{ $breakErr }}</p>
                                    @endif
                                </td>
                            </tr>
                            
                        @endfor
                        <tr>
                            <th>備考</th>
                            <td>
                                <textarea name="note" rows="3" class="form-control note-input">{{ old('note', $note) }}</textarea>
                                <p class="form__error">
                                    @error('note')
                                    {{ $message }}
                                    @enderror
                                </p>
                            </td>
                        </tr>
                        
                    </tbody>
                </table>
                <div class="correction-button">
                        <button type="submit" class="btn btn-primary correction">修正</button>
                </div>
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
