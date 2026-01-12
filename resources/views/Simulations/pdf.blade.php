<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    @page { margin: 110px 38px 70px 38px; }

    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color:#111827; }

    /* Header / Footer fixed */
    header {
      position: fixed;
      top: -90px;
      left: 0; right: 0;
      height: 85px;
      border-bottom: 1px solid #e5e7eb;
    }
    footer {
      position: fixed;
      bottom: -50px;
      left: 0; right: 0;
      height: 40px;
      border-top: 1px solid #e5e7eb;
      color: #6b7280;
      font-size: 10px;
    }

    .header-wrap { width:100%; }
    .h-table { width:100%; border-collapse: collapse; }
    .h-left { width: 70px; vertical-align: middle; }
    .h-mid { vertical-align: middle; }
    .h-right { width: 230px; vertical-align: middle; text-align: right; }

    .logo {
      width: 58px;
      height: 58px;
      object-fit: contain;
    }

    .title { font-size: 16px; font-weight: 900; margin: 0; }
    .sub { margin: 4px 0 0; color:#6b7280; font-size: 11px; }

    .meta { font-size: 11px; color:#111827; }
    .meta b { font-weight: 900; }
    .meta-row { margin: 2px 0; }

    .box {
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      padding: 10px 12px;
      margin: 10px 0;
    }
    .box-title { font-weight: 900; margin: 0 0 8px; }

    table { width:100%; border-collapse: collapse; }
    th, td { border: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; }
    th { background: #f9fafb; font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color:#6b7280; }

    .badge { display:inline-block; padding:2px 10px; border-radius:999px; background:#f3f4f6; font-weight:900; font-size: 11px; }

    /* Gantt */
    .gantt-wrap { margin-top: 6px; overflow: hidden; }
    .gantt {
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      padding: 8px;
    }
    .seg {
      display: inline-block;
      border: 1px solid rgba(0,0,0,.08);
      border-radius: 10px;
      padding: 6px 8px;
      margin: 0 6px 6px 0;
      color:#111827;
      vertical-align: top;
    }
    .seg .pid { font-weight: 900; font-size: 11px; }
    .seg .time { font-size: 10px; color:#374151; margin-top: 2px; }

    .legend { margin-top: 8px; }
    .legend-item { display:inline-block; margin-right: 10px; margin-bottom: 6px; }
    .dot { display:inline-block; width:10px; height:10px; border-radius:3px; border:1px solid rgba(0,0,0,.1); vertical-align: middle; margin-right: 6px; }

    .avg { margin-top: 8px; font-size: 12px; }
    .muted { color:#6b7280; }

  </style>
</head>
<body>

@php
  // warna PID (samain sama UI kamu)
  $colors = [
    'P1' => '#bfdbfe',
    'P2' => '#bbf7d0',
    'P3' => '#fde68a',
    'P4' => '#fecaca',
    'P5' => '#ddd6fe',
    'P6' => '#fed7aa',
    'IDLE' => '#e5e7eb',
  ];

  $uniquePids = collect($timeline)->pluck('pid')->unique()->values();
@endphp

<header>
  <table class="h-table">
    <tr>
      <td class="h-left">
        @php $logoPath = public_path('images/logoUPB.png'); @endphp
        @if(file_exists($logoPath))
          <img class="logo" src="{{ $logoPath }}" alt="Logo">
        @else
          <div style="width:58px;height:58px;border:1px dashed #d1d5db;border-radius:12px;text-align:center;line-height:58px;color:#9ca3af;font-size:10px;">
            LOGO
          </div>
        @endif
      </td>

      <td class="h-mid">
        <p class="title">CPU Scheduling Simulation Report</p>
        <p class="sub">
          {{ $meta['campus'] ?? '-' }} • {{ $meta['course'] ?? 'Sistem Operasi' }}
        </p>
      </td>

      <td class="h-right">
        <div class="meta">
          <div class="meta-row"><b>Nama:</b> {{ $meta['student_name'] ?? '-' }}</div>
          <div class="meta-row"><b>NIM:</b> {{ $meta['student_id'] ?? '-' }}</div>
          <div class="meta-row"><b>Kelas:</b> {{ $meta['class'] ?? '-' }}</div>
        </div>
      </td>
    </tr>
  </table>
</header>

<footer>
  <div style="position:absolute; left:0; right:0; bottom:10px;">
    <span class="muted">Generated: {{ $generatedAt ?? '' }}</span>
    <span style="float:right;" class="muted">Page <span class="pageNumber"></span> / <span class="totalPages"></span></span>
  </div>
</footer>

{{-- Page number script for Dompdf --}}
<script type="text/php">
if (isset($pdf)) {
  $pdf->page_script('
    $font = $fontMetrics->get_font("DejaVu Sans", "normal");
    $pdf->text(520, 815, "Page $PAGE_NUM / $PAGE_COUNT", $font, 9, array(107,114,128));
  ');
}
</script>

<div class="box">
  <div class="box-title">Simulation Info</div>
  <div>
    Scenario: <b>{{ $simulation->scenario->name ?? ('#'.$simulation->scenario_id) }}</b><br>
    Algorithm: <span class="badge">{{ strtoupper($simulation->algorithm) }}</span>
    @if($simulation->algorithm === 'rr')
      <span class="muted">• Quantum:</span> <b>{{ $simulation->quantum }}</b>
    @endif
    @if($simulation->algorithm === 'priority')
      <span class="muted">• Preemptive:</span> <b>{{ $simulation->is_preemptive ? 'Yes' : 'No' }}</b>
    @endif
    <br>
    Run time: {{ $simulation->created_at->format('Y-m-d H:i') }}
  </div>
</div>

<div class="box">
  <div class="box-title">Processes</div>
  <table>
    <thead>
      <tr><th>PID</th><th>AT</th><th>BT</th><th>Priority</th></tr>
    </thead>
    <tbody>
      @foreach(($simulation->scenario->processes ?? []) as $p)
        <tr>
          <td>{{ $p->pid }}</td>
          <td>{{ $p->arrival_time }}</td>
          <td>{{ $p->burst_time }}</td>
          <td>{{ $p->priority ?? '-' }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>

<div class="box">
  <div class="box-title">Gantt Chart</div>

  <div class="gantt">
    @foreach($timeline as $seg)
      @php
        $pid = $seg['pid'];
        $bg  = $colors[$pid] ?? '#f3f4f6';
      @endphp

      <span class="seg" style="background: {{ $bg }};">
        <div class="pid">{{ $pid }}</div>
        <div class="time">{{ $seg['start'] }}–{{ $seg['end'] }}</div>
      </span>
    @endforeach
  </div>

  <div class="legend">
    @foreach($uniquePids as $pid)
      @php $bg = $colors[$pid] ?? '#f3f4f6'; @endphp
      <span class="legend-item">
        <span class="dot" style="background: {{ $bg }};"></span>
        <span style="font-size:11px;">{{ $pid }}</span>
      </span>
    @endforeach
  </div>
</div>

<div class="box">
  <div class="box-title">Statistics</div>
  <table>
    <thead>
      <tr><th>PID</th><th>CT</th><th>TAT</th><th>WT</th><th>RT</th></tr>
    </thead>
    <tbody>
      @foreach($stats as $pid => $s)
        <tr>
          <td>{{ $pid }}</td>
          <td>{{ $s['ct'] }}</td>
          <td>{{ $s['tat'] }}</td>
          <td>{{ $s['wt'] }}</td>
          <td>{{ $s['rt'] }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <div class="avg">
    <b>Averages:</b>
    <span class="muted">WT</span> <b>{{ number_format($avg['wt'] ?? 0, 2) }}</b> •
    <span class="muted">TAT</span> <b>{{ number_format($avg['tat'] ?? 0, 2) }}</b> •
    <span class="muted">RT</span> <b>{{ number_format($avg['rt'] ?? 0, 2) }}</b>
  </div>
</div>

</body>
</html>
