@extends('layouts.app')

@section('content')
@php
  $timeline = $simulation->timeline_json ?? [];
  $stats = $simulation->stats_json ?? [];
  $avg = $simulation->averages_json ?? [];
@endphp

<div class="container">
  <div class="card">

    <div style="display:flex; justify-content:space-between; align-items:center;">
      <div class="card-title">
        Simulation Result
        <span class="badge">{{ strtoupper($simulation->algorithm) }}</span>
      </div>
      <a class="btn" href="{{ route('simulations.pdf', $simulation) }}">Export PDF</a>
      <a class="btn" href="{{ route('scenarios.show', $simulation->scenario_id) }}">← Back</a>
        
    </div>

    <hr style="margin:16px 0;">

    {{-- GANTT --}}
    <div class="card-title">Gantt Chart</div>
    @php
  $timeline = $simulation->timeline_json ?? [];
  $stats = $simulation->stats_json ?? [];
  $avg = $simulation->averages_json ?? [];

  // map AT/BT dari scenario processes
  $procMap = collect($simulation->scenario->processes ?? [])
    ->mapWithKeys(fn($p) => [$p->pid => ['at' => (int)$p->arrival_time, 'bt' => (int)$p->burst_time]]);
@endphp

    @php
  // ambil PID unik dari timeline
  $pids = collect($timeline)
    ->pluck('pid')
    ->unique()
    ->values();
@endphp

<div class="gantt-legend">
  @foreach($pids as $pid)
    <div class="legend-item">
      <span class="legend-color legend-{{ $pid }}"></span>
      <span>{{ $pid }}</span>
    </div>
  @endforeach
</div>

    <div class="gantt" style="margin-top:8px;">
  @foreach($timeline as $seg)
    @php
      $dur = (int)$seg['end'] - (int)$seg['start'];
      $pid = $seg['pid'];

      $pidClass = $pid === 'IDLE'
        ? 'gantt-idle'
        : 'gantt-pid-' . $pid;

      $at = $procMap[$pid]['at'] ?? null;
      $bt = $procMap[$pid]['bt'] ?? null;
      $wt = $stats[$pid]['wt'] ?? null;

      // Tooltip multi-line (pakai \n)
      $tip = $pid === 'IDLE'
        ? "IDLE\n(start: {$seg['start']}, end: {$seg['end']})"
        : "PID: {$pid}\nAT: {$at}\nBT: {$bt}\nWT: {$wt}";
    @endphp

    <div class="gantt-block {{ $pidClass }}"
         data-tip="{{ $tip }}"
         style="width:{{ max(1,$dur) * 36 }}px">
      <div class="gantt-pid">{{ $pid }}</div>
      <div class="gantt-time">{{ $seg['start'] }}–{{ $seg['end'] }}</div>
    </div>
  @endforeach
</div>



    <hr style="margin:16px 0;">

    {{-- STATS --}}
    <div class="card-title">Statistics</div>
    <table class="table" style="margin-top:8px;">
      <thead>
        <tr><th>PID</th><th>CT</th><th>TAT</th><th>WT</th><th>RT</th></tr>
      </thead>
      <tbody>
        @foreach($stats as $pid => $s)
          <tr>
            <td><b>{{ $pid }}</b></td>
            <td>{{ $s['ct'] }}</td>
            <td>{{ $s['tat'] }}</td>
            <td>{{ $s['wt'] }}</td>
            <td>{{ $s['rt'] }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>

    <hr style="margin:16px 0;">

    {{-- AVERAGES --}}
    <div class="card-title">Average</div>
    <ul style="margin-top:6px;">
      <li>Average WT: <b>{{ number_format($avg['wt'],2) }}</b></li>
      <li>Average TAT: <b>{{ number_format($avg['tat'],2) }}</b></li>
      <li>Average RT: <b>{{ number_format($avg['rt'],2) }}</b></li>
    </ul>

  </div>


</div>
@endsection
