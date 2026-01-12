<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Scenario extends Model
{
  protected $fillable = ['name', 'description'];

  public function processes() { return $this->hasMany(Process::class); }
  public function simulations() { return $this->hasMany(Simulation::class); }
}
