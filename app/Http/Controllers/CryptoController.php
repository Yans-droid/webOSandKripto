<?php

namespace App\Http\Controllers;

use App\Models\PdfCryptoRecord;
use App\Services\PdfCryptoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CryptoController extends Controller
{
    public function index()
    {
        $records = PdfCryptoRecord::where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('crypto.index', compact('records'));
    }

    // Upload PDF -> Encrypt -> output .enc
    public function encrypt(Request $request, PdfCryptoService $svc)
    {
    $data = $request->validate([
        'file' => 'required|file|mimes:pdf|max:5120',
        'password' => 'required|string|min:6',
    ]);

    $pdfBytes = file_get_contents($data['file']->getRealPath());
    $originalName = $data['file']->getClientOriginalName();

    // 🔐 PROSES ENKRIPSI
    $res = $svc->encryptBundle($pdfBytes, $data['password'], 150000);

    // SIMPAN FILE
    $origPath = 'crypto/original/' . uniqid('orig_') . '.pdf';
    Storage::put($origPath, $pdfBytes);

    $encPath = 'crypto/encrypted/' . uniqid('enc_') . '.enc';
    Storage::put($encPath, $res['bundle']);

    // ✅ INI YANG KEMARIN HILANG
    $record = PdfCryptoRecord::create([
        'user_id'        => auth()->id(),
        'original_name'  => $originalName,
        'original_path'  => $origPath,
        'encrypted_path' => $encPath,
        'decrypted_path' => null,

        'cipher'     => $res['meta']['cipher'],
        'kdf'        => $res['meta']['kdf'],
        'iterations' => $res['meta']['iterations'],
        'salt_b64'   => $res['meta']['salt_b64'],
        'iv_b64'     => $res['meta']['iv_b64'],
        'tag_b64'    => $res['meta']['tag_b64'],
    ]);

    // 🔁 BARU BOLEH PAKAI $record
    return redirect()
        ->route('crypto.flow', $record->id)
        ->with('status', 'Encrypt berhasil. Berikut alur proses enkripsi.');
    }

    // Upload .enc -> Decrypt -> output PDF
    public function decryptUpload(Request $request, PdfCryptoService $svc)
    {
        $data = $request->validate([
            'file' => 'required|file|max:5120', // .enc gak bisa dicek mimes default
            'password' => 'required|string|min:4',
        ]);

        $name = strtolower($data['file']->getClientOriginalName());
        if (!str_ends_with($name, '.enc')) {
            return back()->withErrors(['file' => 'Untuk Decrypt, upload file .enc']);
        }

        $encBytes = file_get_contents($data['file']->getRealPath());

        try {
            $out = $svc->decryptBundle($encBytes, $data['password']);
        } catch (\Throwable $e) {
            return back()->withErrors(['password' => 'Decrypt gagal: password salah atau file rusak/diubah.']);
        }

        $decPath = 'crypto/decrypted/' . uniqid('dec_') . '.pdf';
        Storage::put($decPath, $out['plaintext']);

        $record = PdfCryptoRecord::create([
            'user_id'        => auth()->id(),
            'original_name'  => $data['file']->getClientOriginalName(),
            'original_path'  => null,
            'encrypted_path' => null,
            'decrypted_path' => $decPath,

            'cipher'     => $out['meta']['cipher'] ?? 'AES-256-GCM',
            'kdf'        => $out['meta']['kdf'] ?? 'PBKDF2-SHA256',
            'iterations' => $out['meta']['iterations'] ?? null,
            'salt_b64'   => $out['meta']['salt_b64'] ?? null,
            'iv_b64'     => $out['meta']['iv_b64'] ?? null,
            'tag_b64'    => $out['meta']['tag_b64'] ?? null,
        ]);

        return redirect()
            ->route('crypto.index')
            ->with('status', 'Decrypt berhasil. Masuk history ID: ' . $record->id);
    }
    public function flow(PdfCryptoRecord $record)
{
    abort_unless($record->user_id === auth()->id(), 403);

    // info real dari storage
    $origSize = $record->original_path && Storage::exists($record->original_path)
        ? Storage::size($record->original_path)
        : null;

    $encSize = $record->encrypted_path && Storage::exists($record->encrypted_path)
        ? Storage::size($record->encrypted_path)
        : null;

    $decSize = $record->decrypted_path && Storage::exists($record->decrypted_path)
        ? Storage::size($record->decrypted_path)
        : null;

    // info real dari metadata (panjang bytes)
    $saltLen = $record->salt_b64 ? strlen(base64_decode($record->salt_b64)) : null;
    $ivLen   = $record->iv_b64   ? strlen(base64_decode($record->iv_b64)) : null;
    $tagLen  = $record->tag_b64  ? strlen(base64_decode($record->tag_b64)) : null;

    // ✅ HASH PROOF (REAL)
    $origHash = null;
    $decHash  = null;

    if ($record->original_path && Storage::exists($record->original_path)) {
        $origHash = hash('sha256', Storage::get($record->original_path));
    }

    if ($record->decrypted_path && Storage::exists($record->decrypted_path)) {
        $decHash = hash('sha256', Storage::get($record->decrypted_path));
    }


    return view('crypto.flow_detail', compact(
  'record','origSize','encSize','decSize','saltLen','ivLen','tagLen','origHash','decHash'
));
}


    // Download original/encrypted/decrypted (tampil sesuai path yang ada)
    public function download(PdfCryptoRecord $record, string $type)
    {
        abort_unless($record->user_id === auth()->id(), 403);

        $baseName = pathinfo($record->original_name ?? 'file', PATHINFO_FILENAME);

        if ($type === 'original') {
            abort_unless($record->original_path && Storage::exists($record->original_path), 404);
            return Storage::download($record->original_path, $baseName . '.pdf');
        }

        if ($type === 'encrypted') {
            abort_unless($record->encrypted_path && Storage::exists($record->encrypted_path), 404);
            return Storage::download($record->encrypted_path, $baseName . '.enc');
        }

        if ($type === 'decrypted') {
            abort_unless($record->decrypted_path && Storage::exists($record->decrypted_path), 404);
            return Storage::download($record->decrypted_path, $baseName . '_decrypted.pdf');
        }

        abort(404);
    }
    public function destroy(PdfCryptoRecord $record)
{
    abort_unless($record->user_id === auth()->id(), 403);

    // hapus file-file terkait kalau ada
    foreach (['original_path', 'encrypted_path', 'decrypted_path'] as $col) {
        $path = $record->{$col};
        if ($path && Storage::exists($path)) {
            Storage::delete($path);
        }
    }

    $record->delete();

    return redirect()
        ->route('crypto.index')
        ->with('status', '1 history berhasil dihapus.');
}

public function clear()
{
    $records = PdfCryptoRecord::where('user_id', auth()->id())->get();

    foreach ($records as $record) {
        foreach (['original_path', 'encrypted_path', 'decrypted_path'] as $col) {
            $path = $record->{$col};
            if ($path && Storage::exists($path)) {
                Storage::delete($path);
            }
        }
        $record->delete();
    }

    return redirect()
        ->route('crypto.index')
        ->with('status', 'Semua history berhasil dihapus.');
}

}
