<?php

namespace App\Http\Controllers;

use App\Models\Scenario;
use App\Models\Simulation;

class DashboardController extends Controller
{
    public function index()
    {
        $scenarioCount = Scenario::count();
        $simulationCount = Simulation::count();

        $recentScenarios = Scenario::latest()->take(5)->get();
        $recentSimulations = Simulation::with('scenario')->latest()->take(5)->get();

        return view('dashboard', compact(
            'scenarioCount',
            'simulationCount',
            'recentScenarios',
            'recentSimulations'
        ));
    }
}
