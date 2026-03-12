@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail.css') }}">
@endsection

@section('content')
<div class="container mt-4 attendance-detail">
  <h2>勤怠詳細</h2>

  <table class="table table-bordered">
    <thead>
      <tr>
        <th>氏名</th>
        <th>日付</th>
        <th>出勤時間</th>
        <th>退勤時間</th>
        <th>休憩時間</th>
      </tr>
    </thead>
    <tbody>
      {{-- 単一の勤怠レコードを表示する想定。コントローラから $attendance を渡してください --}}
      @php
        // breaks はコントローラで配列やコレクションとして渡す (例: [['start'=>'12:00','end'=>'12:30'], ...])
        $breaks = $attendance->breaks ?? $attendance->break_times ?? [];
        $name = $attendance->user->name ?? ($attendance->name ?? '—');
        $date = $attendance->date ?? ($attendance->created_at ?? '—');
        $start = $attendance->start_time ?? $attendance->punchIn ?? '—';
        $end = $attendance->end_time ?? $attendance->punchOut ?? '—';
      @endphp

      @if(empty($breaks) || count($breaks) === 0)
        <tr>
          <td data-label="氏名">{{ $name }}</td>
          <td data-label="日付">{{ $date }}</td>
          <td data-label="出勤時間">{{ $start }}</td>
          <td data-label="退勤時間">{{ $end }}</td>
          <td data-label="休憩時間">—</td>
        </tr>
      @else
        @foreach($breaks as $i => $b)
          <tr class="break-row">
            @if($i === 0)
              <td data-label="氏名">{{ $name }}</td>
              <td data-label="日付">{{ $date }}</td>
              <td data-label="出勤時間">{{ $start }}</td>
              <td data-label="退勤時間">{{ $end }}</td>
            @else
              <td data-label="氏名"></td>
              <td data-label="日付"></td>
              <td data-label="出勤時間"></td>
              <td data-label="退勤時間"></td>
            @endif
            {{-- $b が配列/オブジェクト/文字列のいずれでも対応できるように表示 --}}
            <td data-label="休憩時間" class="break-time">
              @if(is_array($b) || is_object($b))
                {{ $b['start'] ?? $b->start ?? '—' }}@if(($b['end'] ?? $b->end ?? null)) - {{ $b['end'] ?? $b->end }}@endif
              @else
                {{ $b }}
              @endif
            </td>
          </tr>
        @endforeach
      @endif
    </tbody>
  </table>

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
@endsection
