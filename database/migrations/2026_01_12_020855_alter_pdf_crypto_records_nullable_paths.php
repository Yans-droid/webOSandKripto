<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pdf_crypto_records', function (Blueprint $table) {
            $table->string('original_path')->nullable()->change();
            $table->string('encrypted_path')->nullable()->change();
            $table->string('decrypted_path')->nullable()->change(); // opsional tapi bagus
        });
    }

    public function down(): void
    {
        Schema::table('pdf_crypto_records', function (Blueprint $table) {
            $table->string('original_path')->nullable(false)->change();
            $table->string('encrypted_path')->nullable(false)->change();
            $table->string('decrypted_path')->nullable(false)->change();
        });
    }
};
