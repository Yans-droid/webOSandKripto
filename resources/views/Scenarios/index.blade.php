@extends('layouts.app')

@section('content')
<div class="container">
  <div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
      <div class="card-title">Scenarios</div>
      <a class="btn btn-primary" href="{{ route('scenarios.create') }}">+ Create</a>
    </div>

    @if($scenarios->count() === 0)
      <p style="margin-top:12px; color:#6b7280;">Belum ada scenario.</p>
    @else
      <table class="table" style="margin-top:12px;">
        <thead>
          <tr>
            <th>Name</th>
            <th>Description</th>
            <th style="text-align:right;">Action</th>
          </tr>
        </thead>
        <tbody>
          @foreach($scenarios as $scenario)
            <tr>
              <td><b>{{ $scenario->name }}</b></td>
              <td>{{ $scenario->description ?? '—' }}</td>
              <td style="text-align:right;">
                <div style="display:inline-flex; gap:8px;">
                  <a class="btn" href="{{ route('scenarios.show', $scenario) }}">Open</a>

                  <form method="POST" action="{{ route('scenarios.destroy', $scenario) }}"
                        onsubmit="return confirm('Hapus scenario ini beserta proses & history?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>

      <div style="margin-top:12px;">
        {{ $scenarios->links() }}
      </div>
    @endif
  </div>
</div>
@endsection
