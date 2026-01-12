<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('simulations', function (Blueprint $table) {
      $table->id();
      $table->foreignId('scenario_id')->constrained()->cascadeOnDelete();

      $table->string('algorithm'); // fcfs|sjf|srtf|priority|rr
      $table->unsignedInteger('quantum')->nullable(); // RR
      $table->boolean('is_preemptive')->nullable();   // priority toggle

      $table->json('timeline_json');  // gantt segments
      $table->json('stats_json');     // per pid: ct,tat,wt,rt
      $table->json('averages_json');  // avg wt,tat,rt

      $table->timestamps();

      $table->index(['scenario_id', 'algorithm']);
    });
  }
  public function down(): void {
    Schema::dropIfExists('simulations');
  }
};
