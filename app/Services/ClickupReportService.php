<?php

namespace App\Services;

use App\Services\Api\ClickupApiService;
use App\Services\Api\TelegramApiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ClickupReportService
{
    protected ClickupApiService $clickupService;
    protected TelegramApiService $telegramService;

    public function __construct(ClickupApiService $clickupService, TelegramApiService $telegramService)
    {
        $this->clickupService = $clickupService;
        $this->telegramService = $telegramService;
    }

    public function generateAndSendReport(?string $date = null): array
    {
        // $date = $date ?: Carbon::now()->format('Y-m-d');
        $date = $date ?: '2026-02-06';

        // 1. Get Workspace ID
        $workspaceId = $this->clickupService->getAuthorizedWorkspace();
        if (!$workspaceId) {
            return ['success' => false, 'message' => 'Workspace "Tiga Tekno" not found.'];
        }

        // 2. Search for Doc ID
        $docId = $this->clickupService->searchForDoc($workspaceId);
        if (!$docId) {
            return ['success' => false, 'message' => 'Doc "DAILY REPORT" not found.'];
        }

        // 3. Fetch Page Content
        $content = $this->clickupService->fetchPageBelongingToDoc($workspaceId, $docId);
        if (!$content) {
            return ['success' => false, 'message' => 'Content for page "Februari" is empty or not found.'];
        }

        // 4. Parse and Filter for Specific Date
        $dailySection = $this->extractDailySection($content, $date);
        if (!$dailySection) {
            return ['success' => false, 'message' => "No report entries found for date: {$date}"];
        }

        // 5. Group by Category
        $grouped = $this->groupEntriesByProject($dailySection);
        
        // 6. Format for Telegram
        $formattedMessage = $this->formatTelegramMessage($grouped, $date);

        // 7. Send to Telegram
        $success = $this->telegramService->sendMessage($formattedMessage, 'HTML');

        if ($success) {
            return ['success' => true, 'message' => 'Report sent to Telegram.'];
        }

        return ['success' => false, 'message' => 'Failed to send message to Telegram.'];
    }

    private function extractDailySection(string $content, string $date): ?string
    {
        // Pattern: # [YYYY-MM-DD] or # \[YYYY-MM-DD\]
        $pattern = "/# \\\\?\[" . preg_quote($date) . "\\\\?\](.*?)(?=# \\\\?\[|$)/s";
        if (preg_match($pattern, $content, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }

    private function groupEntriesByProject(string $section): array
    {
        $categories = [];
        $lines = explode("\n", $section);
        $activeCategory = 'Others';

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Detect name headers (e.g., **Name** or short plain text line without bullets/brackets)
            if (preg_match('/^\*\*.*\*\*$/', $line) || 
                (strlen($line) < 30 && !str_contains($line, '[') && !str_contains($line, '*') && !str_contains($line, '-'))) {
                continue;
            }

            // Detect if this line switches the category
            $detected = $this->detectCategory($line);
            if ($detected) {
                $activeCategory = $detected;
            }

            $cleaned = $this->cleanEntry($line);
            
            // If the line was just a tag (like "[panda]"), skip it now that category is set
            if (empty($cleaned)) {
                continue;
            }

            // Avoid adding just generic project tags or metadata
            if (strtolower($cleaned) === 'project') continue;

            if (!isset($categories[$activeCategory])) {
                $categories[$activeCategory] = [];
            }

            if (!in_array($cleaned, $categories[$activeCategory])) {
                $categories[$activeCategory][] = $cleaned;
            }
        }

        return $categories;
    }

    private function detectCategory(string $line): ?string
    {
        if (preg_match('/\\\\?\[(.*?)\\\\?\]/', $line, $matches)) {
            $tag = trim($matches[1]);
            
            // Map "project" or "others" to "Others"
            if (in_array(strtolower($tag), ['project', 'others'])) {
                return 'Others';
            }
            
            return ucfirst($tag);
        }
        return null;
    }

    private function cleanEntry(string $line): string
    {
        // 1. Remove Markdown bullets (* or - or +)
        $line = preg_replace('/^[\*\-\+]\s+/', '', $line);
        
        // 2. Remove all project tags like [panda], \[panda\], [[panda]], etc.
        $line = preg_replace('/\\\\?\[.*?\\\\?\]\s*:?\s*/', '', $line);
        
        return trim($line);
    }

    private function formatTelegramMessage(array $grouped, string $date): string
    {
        $formattedDate = Carbon::parse($date)->translatedFormat('j F Y');
        $message = "<b>Daily Report {$formattedDate}</b>\n\n";

        foreach ($grouped as $category => $items) {
            if (empty($items)) continue;
            
            $message .= "<b>{$category}</b>\n";
            foreach ($items as $item) {
                $message .= "- " . htmlspecialchars($item) . "\n";
            }
            $message .= "\n";
        }

        return $message;
    }
}
