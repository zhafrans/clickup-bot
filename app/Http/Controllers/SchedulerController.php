<?php

namespace App\Http\Controllers;

use App\Models\Scheduler;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SchedulerController extends Controller
{
    public function index()
    {
        return Inertia::render('schedulers/index', [
            'schedulers' => Scheduler::all()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'run_time' => 'required',
            'days_of_week' => 'required|array',
            'is_active' => 'boolean'
        ]);

        Scheduler::create($validated);

        return redirect()->back()->with('success', 'Scheduler created successfully.');
    }

    public function update(Request $request, Scheduler $scheduler)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'run_time' => 'required',
            'days_of_week' => 'required|array',
            'is_active' => 'boolean'
        ]);

        $scheduler->update($validated);

        return redirect()->back()->with('success', 'Scheduler updated successfully.');
    }

    public function destroy(Scheduler $scheduler)
    {
        $scheduler->delete();

        return redirect()->back()->with('success', 'Scheduler deleted successfully.');
    }
}
