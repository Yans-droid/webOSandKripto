<?php
namespace App\Http\Controllers;

use App\Models\Scenario;
use App\Models\Simulation;
use App\Services\SchedulerService;
use Illuminate\Http\Request;

class SimulationController extends Controller
{
  public function run(Request $r, Scenario $scenario, SchedulerService $svc)
  {
    $data = $r->validate([
      'algorithm' => 'required|in:fcfs,rr,sjf,srtf,priority',
      'quantum' => 'nullable|integer|min:1',
      'is_preemptive' => 'nullable|boolean',
    ]);

    $processes = $scenario->processes()
  ->orderBy('arrival_time')
  ->orderBy('pid')
  ->get(['pid','arrival_time','burst_time','priority'])
  ->map(fn($p)=>[
    'pid'=>$p->pid,
    'at'=>(int)$p->arrival_time,
    'bt'=>(int)$p->burst_time,
    'prio'=>$p->priority !== null ? (int)$p->priority : null,
  ])->all();


    if (count($processes) === 0) {
      return back()->withErrors(['processes' => 'Tambahkan minimal 1 proses dulu.']);
    }

    $result = $svc->run(
      $data['algorithm'],
      $processes,
      $data['quantum'] ?? null,
      $data['is_preemptive'] ?? null
    );

    $sim = Simulation::create([
      'scenario_id' => $scenario->id,
      'algorithm' => $data['algorithm'],
      'quantum' => $data['algorithm']==='rr' ? ($data['quantum'] ?? 2) : null,
      'is_preemptive' => $data['algorithm']==='priority' ? (bool)($data['is_preemptive'] ?? true) : null,
      'timeline_json' => $result['timeline'],
      'stats_json' => $result['stats'],
      'averages_json' => $result['averages'],
    ]);

    return redirect()->route('simulations.show', $sim);
  }

  public function show(Simulation $simulation)
  {
    $simulation->load('scenario.processes');
    return view('simulations.show', compact('simulation'));
  }

  public function destroy(Simulation $simulation)
  {
    $scenarioId = $simulation->scenario_id;
    $simulation->delete();
    return redirect()->route('scenarios.show', $scenarioId);
  }
  public function pdf(Simulation $simulation)
{
    $simulation->load('scenario.processes');

    $timeline = $simulation->timeline_json ?? [];
    $stats    = $simulation->stats_json ?? [];
    $avg      = $simulation->averages_json ?? [];

    // isi identitas (boleh kamu hardcode dulu biar cepat)
   $user = auth()->user();

$meta = [
    'campus' => $user->campus ?? '-',
    'course' => 'Sistem Operasi',
    'student_name' => $user->name,
    'student_id' => $user->student_id ?? '-',
    'class' => $user->class ?? '-',
];


    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('simulations.pdf', [
        'simulation' => $simulation,
        'timeline'   => $timeline,
        'stats'      => $stats,
        'avg'        => $avg,
        'meta'       => $meta,
        'generatedAt'=> now()->format('Y-m-d H:i'),
    ])->setPaper('a4', 'portrait');

    $filename = 'simulation_'.$simulation->id.'_'.strtoupper($simulation->algorithm).'.pdf';
    return $pdf->download($filename);
}

}
