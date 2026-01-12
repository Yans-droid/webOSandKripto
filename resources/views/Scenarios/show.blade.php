@extends('layouts.app')

@section('content')
<div class="container">
  <div class="card">

    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">
      <div>
        <div class="card-title">{{ $scenario->name }}</div>
        <p style="color:#6b7280; margin-top:4px;">
          {{ $scenario->description ?? '—' }}
        </p>
      </div>

      <form method="POST" action="{{ route('scenarios.destroy', $scenario) }}"
            onsubmit="return confirm('Hapus scenario ini beserta proses & history?')">
        @csrf
        @method('DELETE')
        <button class="btn btn-danger">Delete Scenario</button>
      </form>
    </div>

    <hr style="margin:16px 0;">

    {{-- ADD PROCESS --}}
    <div class="card-title">Add Process</div>

<form method="POST" action="{{ route('scenarios.processes.store', $scenario) }}"
      class="form-grid">
  @csrf

  <div class="form-field">
    <label>PID</label>
    <input class="input" name="pid" placeholder="P1" required>
  </div>

  <div class="form-field">
    <label>Arrival Time (AT)</label>
    <input class="input" name="arrival_time" type="number" min="0" placeholder="0" required>
  </div>

  <div class="form-field">
    <label>Burst Time (BT)</label>
    <input class="input" name="burst_time" type="number" min="1" placeholder="5" required>
  </div>

  <div class="form-field">
    <label>Priority</label>
    <input class="input" name="priority" type="number" placeholder="optional">
  </div>

  <div class="form-actions">
    <button class="btn btn-primary">Add Process</button>
  </div>
</form>


    <hr style="margin:16px 0;">
    <p style="margin-top:6px; font-size:12px; color:#6b7280;">
  PID unik (P1, P2, dst). AT ≥ 0, BT ≥ 1.
</p>


    {{-- PROCESS TABLE --}}
    <div class="card-title">Processes</div>
    <table class="table" style="margin-top:8px;">
      <thead>
        <tr>
          <th>PID</th><th>AT</th><th>BT</th><th>Priority</th><th style="text-align:right;">Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($scenario->processes as $p)
          <tr>
            <td><b>{{ $p->pid }}</b></td>
            <td>{{ $p->arrival_time }}</td>
            <td>{{ $p->burst_time }}</td>
            <td>{{ $p->priority ?? '—' }}</td>
            <td style="text-align:right;">
              <form method="POST" action="{{ route('processes.destroy', $p) }}"
                    onsubmit="return confirm('Hapus {{ $p->pid }}?')">
                @csrf @method('DELETE')
                <button class="btn btn-danger">Delete</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="5" style="text-align:center;color:#6b7280;">No process yet.</td></tr>
        @endforelse
      </tbody>
    </table>

    <hr style="margin:16px 0;">

    {{-- RUN SIMULATION --}}
   <div class="card-title">Run Simulation</div>

<form method="POST" action="{{ route('scenarios.simulate', $scenario) }}" class="runbar">
  @csrf

  <div class="form-field">
    <label>Algorithm</label>
    <select class="input" name="algorithm" id="algo">
      <option value="fcfs">FCFS</option>
      <option value="rr">Round Robin</option>
      <option value="sjf">SJF</option>
      <option value="srtf">SRTF</option>
      <option value="priority">Priority</option>

    </select>
  </div>

  <div class="form-field small" id="quantumWrap" style="display:none;">
    <label>Quantum</label>
    <input class="input" name="quantum" id="quantum" type="number" min="1" value="2">
  </div>

  <div class="form-actions">
    <button class="btn btn-primary">Run & Save</button>
  </div>
</form>

<p class="help">Tip: RR butuh quantum. Hover Gantt untuk lihat detail (AT/BT/WT).</p>

<script>
  const algo = document.getElementById('algo');
  const qWrap = document.getElementById('quantumWrap');

  function syncRun() {
    qWrap.style.display = (algo.value === 'rr') ? 'block' : 'none';
  }
  algo.addEventListener('change', syncRun);
  syncRun();
</script>

    <hr style="margin:16px 0;">

    {{-- HISTORY --}}
    <div class="card-title">Simulation History</div>
    <ul style="margin-top:8px;">
      @forelse($scenario->simulations as $sim)
        <li style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
          <a href="{{ route('simulations.show', $sim) }}">
            <span class="badge">{{ strtoupper($sim->algorithm) }}</span>
            {{ $sim->created_at->format('Y-m-d H:i') }}
          </a>
          <form method="POST" action="{{ route('simulations.destroy', $sim) }}">
            @csrf @method('DELETE')
            <button class="btn btn-danger">Delete</button>
          </form>
        </li>
      @empty
        <li style="color:#6b7280;">No simulation yet.</li>
      @endforelse
    </ul>

  </div>
</div>
@endsection
