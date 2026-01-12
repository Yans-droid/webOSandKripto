@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 980px;">
  <div class="card">
    <div class="card-title">🔎 Alur Enkripsi (Real) — {{ $record->original_name }}</div>

    @if(session('status'))
      <div class="alert success">{{ session('status') }}</div>
    @endif

    <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:10px;">
      <a class="btn" href="{{ route('crypto.index') }}">← Back</a>

      @if($record->original_path)
        <a class="btn" href="{{ route('crypto.download', [$record, 'original']) }}">Download Original</a>
      @endif
      @if($record->encrypted_path)
        <a class="btn btn-primary" href="{{ route('crypto.download', [$record, 'encrypted']) }}">Download Encrypted (.enc)</a>
      @endif
      @if($record->decrypted_path)
        <a class="btn" href="{{ route('crypto.download', [$record, 'decrypted']) }}">Download Decrypted</a>
      @endif
    </div>
  </div>

  <div class="card" style="margin-top:16px;">
    <div class="card-title">Tabs</div>

    <div class="tabs">
      <button class="tab active" data-tab="summary">Ringkasan</button>
      <button class="tab" data-tab="steps">Step-by-step</button>
      <button class="tab" data-tab="meta">Metadata</button>
      <button class="tab" data-tab="output">Output</button>
    </div>

    <div class="tab-content active" id="summary">
      <ul class="kv">
        <li><b>File:</b> {{ $record->original_name }}</li>
        <li><b>Cipher:</b> {{ $record->cipher ?? 'AES-256-GCM' }}</li>
        <li><b>KDF:</b> {{ $record->kdf ?? 'PBKDF2-SHA256' }}</li>
        <li><b>Iterations:</b> {{ $record->iterations ?? '-' }}</li>
        <li><b>Size Original:</b> {{ $origSize ? number_format($origSize) . ' bytes' : '-' }}</li>
        <li><b>Size Encrypted (.enc):</b> {{ $encSize ? number_format($encSize) . ' bytes' : '-' }}</li>
        <li><b>Created at:</b> {{ $record->created_at }}</li>
      </ul>

      <div class="note">
        Ini “real flow”: data di bawah diambil dari hasil enkripsi yang benar-benar terjadi (salt/iv/tag/iterations tersimpan).
      </div>
    </div>

    <div class="tab-content" id="steps">
      <ol class="steps">
        <li>
          <b>Input (Plaintext PDF)</b><br>
          Sistem menerima file PDF dari user dan membaca bytes-nya di server.
          <div class="mini">Ukuran: {{ $origSize ? number_format($origSize) . ' bytes' : '-' }}</div>
        </li>

        <li>
          <b>Generate Salt</b><br>
          Sistem membuat <i>salt random</i> (untuk mencegah rainbow table).
          <div class="mini">Salt length: {{ $saltLen ? $saltLen.' bytes' : '-' }}</div>
        </li>

        <li>
          <b>KDF: PBKDF2 (SHA-256)</b><br>
          Password user diproses dengan PBKDF2 menggunakan salt dan iterasi.
          <div class="mini">Iterations: {{ $record->iterations ?? '-' }} → Output key: 32 bytes (AES-256)</div>
        </li>

        <li>
          <b>Generate IV / Nonce</b><br>
          Sistem membuat IV random untuk AES-GCM.
          <div class="mini">IV length: {{ $ivLen ? $ivLen.' bytes' : '-' }}</div>
        </li>

        <li>
          <b>Encrypt: AES-256-GCM</b><br>
          PDF bytes dienkripsi menggunakan key + IV, menghasilkan ciphertext dan authentication tag.
          <div class="mini">Tag length: {{ $tagLen ? $tagLen.' bytes' : '-' }}</div>
        </li>

        <li>
          <b>Bundle ke file .enc</b><br>
          Metadata (salt/iv/tag/iterations/cipher/kdf) dibungkus bersama ciphertext agar file bisa didecrypt tanpa DB.
          <div class="mini">Output: file <code>.enc</code> (download tersedia)</div>
        </li>
      </ol>
    </div>

    <div class="tab-content" id="meta">
      <div class="grid2">
        <div class="box">
          <div class="box-title">Salt (base64)</div>
          <code class="code">{{ $record->salt_b64 ?? '-' }}</code>
        </div>
        <div class="box">
          <div class="box-title">IV (base64)</div>
          <code class="code">{{ $record->iv_b64 ?? '-' }}</code>
        </div>
        <div class="box">
          <div class="box-title">Tag (base64)</div>
          <code class="code">{{ $record->tag_b64 ?? '-' }}</code>
        </div>
        <div class="box">
          <div class="box-title">Params</div>
          <div class="mini">
            Cipher: {{ $record->cipher ?? 'AES-256-GCM' }}<br>
            KDF: {{ $record->kdf ?? 'PBKDF2-SHA256' }}<br>
            Iterations: {{ $record->iterations ?? '-' }}<br>
            Key length: 32 bytes (AES-256)
          </div>
        </div>
      </div>

      <div class="note">
        Catatan: Password tidak disimpan. Yang disimpan hanya metadata yang diperlukan untuk proses derive key dan verifikasi integritas.
      </div>
    </div>

    <div class="tab-content" id="output">
      <ul class="kv">
        <li><b>Encrypted Path:</b> {{ $record->encrypted_path ?? '-' }}</li>
        <li><b>Decrypted Path:</b> {{ $record->decrypted_path ?? '-' }}</li>
        <li><b>Size Encrypted:</b> {{ $encSize ? number_format($encSize) . ' bytes' : '-' }}</li>
        <li><b>Size Decrypted:</b> {{ $decSize ? number_format($decSize) . ' bytes' : '-' }}</li>
      </ul>
      @if($origHash || $decHash)
  <div class="box" style="margin-top:12px;">
    <div class="box-title">Proof: SHA-256 Hash</div>

    @if($origHash)
      <div class="mini"><b>SHA-256 Original:</b></div>
      <code class="code">{{ $origHash }}</code>
    @endif

    @if($decHash)
      <div class="mini" style="margin-top:10px;"><b>SHA-256 Decrypted:</b></div>
      <code class="code">{{ $decHash }}</code>
    @endif

    @if($origHash && $decHash)
      @if($origHash === $decHash)
        <div class="note" style="margin-top:10px;">✅ Hash sama → decrypted identik dengan original (integritas terjaga)</div>
      @else
        <div class="note" style="margin-top:10px;">⚠️ Hash beda → file decrypted tidak identik (kemungkinan password salah / file berubah)</div>
      @endif
    @endif
  </div>
@endif


      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        @if($record->encrypted_path)
          <a class="btn btn-primary" href="{{ route('crypto.download', [$record, 'encrypted']) }}">Download .enc</a>
        @endif
        @if($record->original_path)
          <a class="btn" href="{{ route('crypto.download', [$record, 'original']) }}">Download Original</a>
        @endif
        @if($record->decrypted_path)
          <a class="btn" href="{{ route('crypto.download', [$record, 'decrypted']) }}">Download Decrypted</a>
        @endif
      </div>
    </div>
  </div>
</div>

<style>
.tabs { display:flex; gap:8px; flex-wrap:wrap; }
.tab { padding:8px 12px; border-radius:6px; border:1px solid #ddd; background:#f6f6f6; cursor:pointer; }
.tab.active { background:#cce5ff; border-color:#9ec5fe; }
.tab-content { display:none; margin-top:12px; }
.tab-content.active { display:block; }
.kv { margin:0; padding-left:18px; }
.note { margin-top:12px; padding:10px; border-radius:6px; background:#f4f4f4; opacity:.9; }
.steps { padding-left:18px; }
.steps li { margin-bottom:10px; }
.mini { opacity:.8; font-size:13px; margin-top:4px; }
.grid2 { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.box { padding:10px; border:1px solid #eee; border-radius:8px; background:#fff; }
.box-title { font-weight:700; margin-bottom:6px; }
.code { display:block; padding:8px; background:#111; color:#0f0; border-radius:6px; overflow:auto; }
@media (max-width: 720px) { .grid2 { grid-template-columns:1fr; } }
</style>

<script>
document.querySelectorAll('.tab').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.tab').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

    btn.classList.add('active');
    document.getElementById(btn.dataset.tab).classList.add('active');
  });
});
</script>
@endsection
