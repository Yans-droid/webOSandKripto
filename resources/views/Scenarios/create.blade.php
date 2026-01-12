@extends('layouts.app')

@section('content')
<div class="container">
  <div class="form-wrap">
    <div class="card">
      <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
        <div>
          <div class="card-title">Create Scenario</div>
          <p style="color:#6b7280; margin-top:4px;">
            Buat skenario baru untuk input proses dan simulasi CPU scheduling.
          </p>
        </div>
        <a class="btn" href="{{ route('scenarios.index') }}">← Back</a>
      </div>

      <hr style="margin:16px 0;">

      <form method="POST" action="{{ route('scenarios.store') }}">
        @csrf

        <div class="form-row">
          <div class="form-group">
            <label>Name</label>
            <input class="input" name="name" value="{{ old('name') }}" placeholder="Contoh: Case 1" required>
          </div>

          <div class="form-group">
            <label>Description (optional)</label>
            <textarea class="input" name="description" rows="4"
                      placeholder="Contoh: Uji FCFS dan RR dengan 4 proses...">{{ old('description') }}</textarea>
          </div>
        </div>

        <div class="form-footer">
          <button type="submit" class="btn btn-primary">Save</button>
          <a class="btn" href="{{ route('scenarios.index') }}">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
