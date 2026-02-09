<?php

namespace App\Http\Controllers;

use App\Services\ClickupReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClickupController extends Controller
{
    protected ClickupReportService $reportService;

    public function __construct(ClickupReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Fetch the daily report from ClickUp and send it to Telegram group.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendReport(Request $request)
    {
        try {
            $date = $request->query('date');
            $result = $this->reportService->generateAndSendReport($date);

            if ($result['success']) {
                return redirect()->back()->with('success', $result['message']);
            }

            return redirect()->back()->with('error', $result['message']);

        } catch (\Exception $e) {
            Log::error('ClickupController sendReport error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Internal Server Error: ' . $e->getMessage());
        }
    }
}
