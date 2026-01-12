<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('processes', function (Blueprint $table) {
      $table->id();
      $table->foreignId('scenario_id')->constrained()->cascadeOnDelete();
      $table->string('pid'); // P1, P2, ...
      $table->unsignedInteger('arrival_time');
      $table->unsignedInteger('burst_time');
      $table->integer('priority')->nullable();
      $table->timestamps();

      $table->unique(['scenario_id', 'pid']);
    });
  }
  public function down(): void {
    Schema::dropIfExists('processes');
  }
};
