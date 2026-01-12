@extends('layouts.app')

@section('content')
<div class="container">
  <div class="card">
  Lihat Alur Enkripsi
</a>

    <div class="card-title">Crypto PDF (AES-256-GCM)</div>

    @if(session('status'))
      <div class="alert success">{{ session('status') }}</div>
    @endif

    @if($errors->any())
      <div class="alert danger">{{ $errors->first() }}</div>
    @endif

    <form id="cryptoForm" method="POST" enctype="multipart/form-data">
      @csrf

      <div class="form-group">
        <label>Upload File (PDF untuk Encrypt / .enc untuk Decrypt)</label>
        <input class="input" type="file" name="file" required>
        @error('file') <div class="text-danger">{{ $message }}</div> @enderror
      </div>

      <div class="form-group">
        <label>Password (tidak disimpan)</label>
        <input class="input" type="password" name="password" required>
        @error('password') <div class="text-danger">{{ $message }}</div> @enderror
      </div>

      <button type="submit"
              class="btn btn-primary"
              formaction="{{ route('crypto.encrypt') }}"
              formmethod="POST">
        Encrypt
      </button>

      <button type="submit"
              class="btn"
              formaction="{{ route('crypto.decryptUpload') }}"
              formmethod="POST">
        Decrypt
      </button>
    </form>
  </div>

  <div class="card" style="margin-top:16px;">
    <div class="card-title">History</div>
    <form method="POST" action="{{ route('crypto.history.clear') }}" style="margin-bottom:10px;"
      onsubmit="return confirm('Hapus semua history? Ini akan menghapus file original/encrypted/decrypted juga.')">
  @csrf
  @method('DELETE')
  <button class="btn btn-danger">Clear All</button>
</form>


    <table class="table">
      <thead>
        <tr>
          <th>File</th>
          <th>Crypto Params</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        @foreach($records as $r)
          <tr>
            <td>{{ $r->original_name }}</td>
            <td>
              <div><strong>Cipher:</strong> {{ $r->cipher ?? 'AES-256-GCM' }}</div>
              <div><strong>KDF:</strong> {{ $r->kdf ?? 'PBKDF2-SHA256' }}</div>
              <div><strong>Iterations:</strong> {{ $r->iterations ?? '-' }}</div>
            </td>
            <td>
  @if($r->original_path)
    <a class="btn" href="{{ route('crypto.download', [$r, 'original']) }}">Original</a>
  @endif

  @if($r->encrypted_path)
    <a class="btn" href="{{ route('crypto.download', [$r, 'encrypted']) }}">Encrypted</a>
  @endif

  @if($r->decrypted_path)
    <a class="btn" href="{{ route('crypto.download', [$r, 'decrypted']) }}">Decrypted</a>
  @endif
  @if($r->encrypted_path)
  <a class="btn" href="{{ route('crypto.flow', $r) }}">Flow</a>
  @endif


  <form method="POST" action="{{ route('crypto.history.destroy', $r) }}" style="display:inline;"
        onsubmit="return confirm('Hapus record ini? File terkait juga akan dihapus.')">
    @csrf
    @method('DELETE')
    <button class="btn btn-danger">Delete</button>
  </form>
</td>

          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection
