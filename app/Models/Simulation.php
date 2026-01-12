<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Simulation extends Model
{
  protected $fillable = [
    'scenario_id','algorithm','quantum','is_preemptive',
    'timeline_json','stats_json','averages_json'
  ];

  protected $casts = [
    'timeline_json' => 'array',
    'stats_json' => 'array',
    'averages_json' => 'array',
    'is_preemptive' => 'boolean',
  ];

  public function scenario() { return $this->belongsTo(Scenario::class); }
}
