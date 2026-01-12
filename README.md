PROJECT UAS SSISTEM OPERASI CPU SHEDULER SIMULATOR & KRIPTOGRAFI – ENKRIPSI/DEKRIPSI PDF (AES-256-GCM)

Nama:Rianto Agus Tinu
NIM:312310814

Cara menjalankan:
1) composer install
2) copy .env.example -> .env
3) php artisan key:generate
4) php artisan migrate
5) php artisan serve

Fitur:
- Encrypt PDF -> output .enc (bundle metadata + ciphertext)
- Decrypt file .enc -> output PDF
- History + delete/clear
- Flow detail + SHA-256 proof
