<?php

namespace App\Services;

class PdfCryptoService
{
    private function packEnc(array $meta, string $ciphertext): string
    {
        $magic = "PDFE";     // 4 bytes
        $ver   = chr(1);     // 1 byte version
        $json  = json_encode($meta, JSON_UNESCAPED_SLASHES);
        $len   = pack('N', strlen($json)); // 4 bytes big-endian
        return $magic . $ver . $len . $json . $ciphertext;
    }

    private function unpackEnc(string $bytes): array
    {
        if (strlen($bytes) < 9) {
            throw new \RuntimeException("Invalid .enc file (too short)");
        }

        if (substr($bytes, 0, 4) !== "PDFE") {
            throw new \RuntimeException("Invalid .enc file (bad magic)");
        }

        $ver = ord($bytes[4]);
        if ($ver !== 1) {
            throw new \RuntimeException("Unsupported .enc version");
        }

        $len = unpack('N', substr($bytes, 5, 4))[1];
        $json = substr($bytes, 9, $len);

        $meta = json_decode($json, true);
        if (!is_array($meta)) {
            throw new \RuntimeException("Invalid metadata json");
        }

        $ciphertext = substr($bytes, 9 + $len);
        if ($ciphertext === '') {
            throw new \RuntimeException("Empty ciphertext");
        }

        return [$meta, $ciphertext];
    }

    public function encryptBundle(string $pdfBytes, string $password, int $iterations = 150000): array
    {
        $cipher = 'aes-256-gcm';
        $salt = random_bytes(16);
        $iv   = random_bytes(12);

        $key = hash_pbkdf2('sha256', $password, $salt, $iterations, 32, true);

        $tag = '';
        $ciphertext = openssl_encrypt(
            $pdfBytes,
            $cipher,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16
        );

        if ($ciphertext === false) {
            throw new \RuntimeException('Encryption failed');
        }

        $meta = [
            'cipher'     => 'AES-256-GCM',
            'kdf'        => 'PBKDF2-SHA256',
            'iterations' => $iterations,
            'salt_b64'   => base64_encode($salt),
            'iv_b64'     => base64_encode($iv),
            'tag_b64'    => base64_encode($tag),
        ];

        $bundle = $this->packEnc($meta, $ciphertext);

        return [
            'bundle' => $bundle,
            'meta'   => $meta,
        ];
    }

    public function decryptBundle(string $encBytes, string $password): array
    {
        [$meta, $ciphertext] = $this->unpackEnc($encBytes);

        $key = hash_pbkdf2(
            'sha256',
            $password,
            base64_decode($meta['salt_b64']),
            (int) $meta['iterations'],
            32,
            true
        );

        $plain = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            base64_decode($meta['iv_b64']),
            base64_decode($meta['tag_b64']),
            ''
        );

        if ($plain === false) {
            throw new \RuntimeException('Decryption failed (wrong password or modified file)');
        }

        return [
            'plaintext' => $plain,
            'meta'      => $meta,
        ];
    }
}
