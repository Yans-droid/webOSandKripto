<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('pdf_crypto_records', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();

        $table->string('original_name');
        $table->string('original_path')->nullable();

        $table->string('encrypted_path');
        $table->string('decrypted_path')->nullable();

        $table->string('cipher'); // aes-256-gcm
        $table->string('kdf');    // pbkdf2-sha256
        $table->integer('iterations');

        $table->text('salt_b64');
        $table->text('iv_b64');
        $table->text('tag_b64');

        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_crypto_records');
    }
};
