<?php
namespace App\Http\Controllers;

use App\Models\Scenario;
use App\Models\Process;
use Illuminate\Http\Request;

class ScenarioController extends Controller
{
  public function index() {
    $scenarios = Scenario::latest()->paginate(10);
    return view('scenarios.index', compact('scenarios'));
  }

  public function create() {
    return view('scenarios.create');
  }

  public function store(Request $r) {
    $data = $r->validate([
      'name' => 'required|string|max:255',
      'description' => 'nullable|string',
    ]);
    $scenario = Scenario::create($data);
    return redirect()->route('scenarios.show', $scenario);
  }

  public function show(Scenario $scenario) {
    $scenario->load(['processes' => fn($q)=>$q->orderBy('pid'), 'simulations' => fn($q)=>$q->latest()]);
    return view('scenarios.show', compact('scenario'));
  }

  public function destroy(Scenario $scenario) {
    $scenario->delete();
    return redirect()->route('scenarios.index');
  }

  public function storeProcess(Request $r, Scenario $scenario) {
    $data = $r->validate([
      'pid' => 'required|string|max:20',
      'arrival_time' => 'required|integer|min:0',
      'burst_time' => 'required|integer|min:1',
      'priority' => 'nullable|integer',
    ]);

    $scenario->processes()->create($data);
    return back();
  }

  public function updateProcess(Request $r, Process $process) {
    $data = $r->validate([
      'arrival_time' => 'required|integer|min:0',
      'burst_time' => 'required|integer|min:1',
      'priority' => 'nullable|integer',
    ]);
    $process->update($data);
    return back();
  }

  public function destroyProcess(Process $process) {
    $process->delete();
    return back();
  }
}
