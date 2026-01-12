<?php

namespace App\Models;

use App\Models\User;

use Illuminate\Database\Eloquent\Model;

class PdfCryptoRecord extends Model
{
    protected $fillable = [
        'user_id',
        'original_name',
        'original_path',
        'encrypted_path',
        'decrypted_path',
        'cipher',
        'kdf',
        'iterations',
        'salt_b64',
        'iv_b64',
        'tag_b64',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    private function packEnc(array $meta, string $ciphertext): string
{
    $magic = "PDFE";
    $ver = chr(1);
    $json = json_encode($meta, JSON_UNESCAPED_SLASHES);
    $len = pack('N', strlen($json)); // 4 byte big-endian
    return $magic . $ver . $len . $json . $ciphertext;
}

private function unpackEnc(string $bytes): array
{
    if (strlen($bytes) < 9) {
        throw new \RuntimeException("Invalid enc file");
    }

    $magic = substr($bytes, 0, 4);
    if ($magic !== "PDFE") {
        throw new \RuntimeException("Invalid magic header");
    }

    $ver = ord($bytes[4]);
    if ($ver !== 1) {
        throw new \RuntimeException("Unsupported version");
    }

    $len = unpack('N', substr($bytes, 5, 4))[1];
    $json = substr($bytes, 9, $len);
    $meta = json_decode($json, true);

    if (!is_array($meta)) {
        throw new \RuntimeException("Invalid meta json");
    }

    $ciphertext = substr($bytes, 9 + $len);
    if ($ciphertext === '') {
        throw new \RuntimeException("Empty ciphertext");
    }

    return [$meta, $ciphertext];
}

}
