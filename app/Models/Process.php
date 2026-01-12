<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Process extends Model
{
  protected $fillable = ['scenario_id','pid','arrival_time','burst_time','priority'];

  public function scenario() { return $this->belongsTo(Scenario::class); }
}
