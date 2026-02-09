<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClickupApiService
{
    protected string $v2Url = 'https://api.clickup.com/api/v2';
    protected string $v3Url = 'https://api.clickup.com/api/v3';
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = env('CLICKUP_API_KEY');
    }

    /**
     * Get the workspace ID for "Tiga Tekno"
     * 
     * @return string|null
     */
    public function getAuthorizedWorkspace(): ?string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
                'accept' => 'application/json'
            ])->get("{$this->v2Url}/team");

            if ($response->successful()) {
                $teams = $response->json()['teams'] ?? [];
                foreach ($teams as $team) {
                    if (($team['name'] ?? '') === 'Tiga Tekno') {
                        return $team['id'];
                    }
                }
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('ClickupApiService getAuthorizedWorkspace error', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Search for a doc named "Februari" and return its ID
     * 
     * @param string $workspaceId
     * @return string|null
     */
    public function searchForDoc(string $workspaceId): ?string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
                'accept' => 'application/json'
            ])->get("{$this->v3Url}/workspaces/{$workspaceId}/docs", [
                'deleted' => 'false',
                'archived' => 'false',
                'limit' => 50
            ]);

            if ($response->successful()) {
                $docs = $response->json()['docs'] ?? [];
                foreach ($docs as $doc) {
                    if (($doc['name'] ?? '') === 'DAILY REPORT') {
                        return $doc['id'];
                    }
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('ClickupApiService searchForDoc error', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Fetch pages of a doc, find one named "Februari", and return its content
     * 
     * @param string $workspaceId
     * @param string $docId
     * @param int $maxPageDepth
     * @param string $contentFormat
     * @return string|null
     */
    public function fetchPageBelongingToDoc(string $workspaceId, string $docId, int $maxPageDepth = -1, string $contentFormat = 'text/md'): ?string
    {
        try {
            Log::info('ClickUp fetchPageBelongingToDoc attempt', [
                'workspace_id' => $workspaceId,
                'doc_id' => $docId,
            ]);

            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
                'accept' => 'application/json'
            ])
            ->timeout(30)
            ->get("{$this->v3Url}/workspaces/{$workspaceId}/docs/{$docId}/pages", [
                'max_page_depth' => $maxPageDepth,
                'content_format' => $contentFormat
            ]);

            if ($response->successful()) {
                $pages = $response->json();
                
                // Some endpoints might wrap results in 'pages' or 'docs'
                if (isset($pages['pages'])) {
                    $pages = $pages['pages'];
                } elseif (isset($pages['docs'])) {
                    $pages = $pages['docs'];
                }

                if (is_array($pages)) {
                    foreach ($pages as $parentPage) {
                        // Look for the "2026" page
                        if (($parentPage['name'] ?? '') === '2026') {
                            $subPages = $parentPage['pages'] ?? [];
                            foreach ($subPages as $subPage) {
                                // Find "Februari" inside "2026"
                                if (($subPage['name'] ?? '') === 'Februari') {
                                    return $subPage['content'] ?? null;
                                }
                            }
                        }
                    }
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('ClickUp fetchPageBelongingToDoc error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return null;
        }
    }

    private function handleResponse($response, string $method): array
    {
        if ($response->failed()) {
            Log::error("ClickUp {$method} failed response", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'HTTP ' . $response->status() . ': ' . $response->body()
            ];
        }

        $result = $response->json();

        Log::info("ClickUp {$method} success", [
            'response' => $result
        ]);

        return $result ?? ['success' => true];
    }
}