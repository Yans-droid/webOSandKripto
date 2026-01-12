@extends('layouts.app')

@section('content')
<div class="container">

  {{-- HERO --}}
  <div class="dash-hero">
    <div>
      <div class="dash-kicker">CPU Scheduling Simulator</div>
      <h1 class="dash-title">Sistem Operasi</h1>
      <p class="dash-sub">
        Ringkasan scenario & riwayat simulasi kamu. Klik quick action untuk mulai.
      </p>

      <div class="dash-actions">
        <a class="btn btn-primary" href="{{ route('scenarios.create') }}">+ Create Scenario</a>
        <a class="btn" href="{{ route('scenarios.index') }}">Open Scenarios</a>
      </div>
    </div>

    <div class="dash-metrics">
      <div class="metric">
        <div class="metric-label">Total Scenarios</div>
        <div class="metric-value">{{ $scenarioCount }}</div>
      </div>
      <div class="metric">
        <div class="metric-label">Total Simulations</div>
        <div class="metric-value">{{ $simulationCount }}</div>
      </div>
    </div>
  </div>

  {{-- GRID --}}
  <div class="dashboard-grid">

    {{-- Recent Scenarios --}}
    <div class="card section">
      <div class="section-head">
        <div>
          <div class="card-title">Recent Scenarios</div>
          <div class="card-sub">Scenario terakhir yang dibuat</div>
        </div>
        <a class="btn" href="{{ route('scenarios.index') }}">View all</a>
      </div>

      @if($recentScenarios->isEmpty())
        <div class="empty">
          <div class="empty-icon">📦</div>
          <div class="empty-title">Belum ada scenario</div>
          <div class="empty-sub">Buat scenario pertama untuk mulai input proses.</div>
        </div>
      @else
        <div class="list">
          @foreach($recentScenarios as $s)
            <a class="list-item" href="{{ route('scenarios.show', $s) }}">
              <div class="list-main">
                <div class="list-title">{{ $s->name }}</div>
                <div class="list-sub">{{ $s->description ?? '—' }}</div>
              </div>
              <div class="pill">Open</div>
            </a>
          @endforeach
        </div>
      @endif
    </div>

    {{-- Recent Simulations --}}
    <div class="card section">
      <div class="section-head">
        <div>
          <div class="card-title">Recent Simulations</div>
          <div class="card-sub">Hasil run terakhir</div>
        </div>
        <a class="btn" href="{{ route('scenarios.index') }}">Go</a>
      </div>

      @if($recentSimulations->isEmpty())
        <div class="empty">
          <div class="empty-icon">🧪</div>
          <div class="empty-title">Belum ada simulasi</div>
          <div class="empty-sub">Masuk scenario → Run & Save.</div>
        </div>
      @else
        <div class="list">
          @foreach($recentSimulations as $sim)
            <a class="list-item" href="{{ route('simulations.show', $sim) }}">
              <div class="list-main">
                <div class="list-title">
                  <span class="badge">{{ strtoupper($sim->algorithm) }}</span>
                  @if($sim->algorithm === 'rr')
                    <span class="muted">q={{ $sim->quantum }}</span>
                  @endif
                  <span class="muted">• {{ $sim->created_at->format('Y-m-d H:i') }}</span>
                </div>
                <div class="list-sub">
                  Scenario: {{ $sim->scenario->name ?? ('#'.$sim->scenario_id) }}
                </div>
              </div>
              <div class="pill">Open</div>
            </a>
          @endforeach
        </div>
      @endif
    </div>

  </div>
</div>
@endsection
