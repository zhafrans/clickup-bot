<?php

namespace App\Console\Commands;

use App\Http\Controllers\ClickupController;
use App\Models\Scheduler;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunScheduledClickupReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:run-scheduled-clickup-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks for scheduled clickup reports and runs them if due.';

    /**
     * Execute the console command.
     */
    public function handle(\App\Services\ClickupReportService $reportService)
    {
        $now = Carbon::now();
        $currentDay = $now->format('l'); // Monday, Tuesday, ...
        $currentHourMinute = $now->format('H:i');

        $schedulers = Scheduler::where('is_active', true)
            ->whereJsonContains('days_of_week', $currentDay)
            ->get();

        foreach ($schedulers as $scheduler) {
            $runTime = Carbon::createFromFormat('H:i:s', $scheduler->run_time)->format('H:i');
            
            // Check if it's the right time and wasn't run today already
            if ($runTime === $currentHourMinute) {
                if (!$scheduler->last_run || !$scheduler->last_run->isToday()) {
                    $this->info("Running scheduler: {$scheduler->name}");
                    Log::info("Running scheduled report: {$scheduler->name}");
                    
                    try {
                        $result = $reportService->generateAndSendReport();
                        
                        if ($result['success']) {
                            $scheduler->update(['last_run' => $now]);
                            $this->info("Successfully run {$scheduler->name}");
                        } else {
                            $this->error("Report failed: " . $result['message']);
                        }
                    } catch (\Exception $e) {
                        Log::error("Failed to run scheduled report {$scheduler->name}: " . $e->getMessage());
                        $this->error("Failed to run scheduled report {$scheduler->name}");
                    }
                } else {
                    $this->info("Scheduler '{$scheduler->name}' already run today.");
                }
            }
        }
    }
}
