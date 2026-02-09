<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramApiService
{
    protected string $baseUrl;
    protected string $chatId;

    public function __construct()
    {
        $this->baseUrl = "https://api.telegram.org/bot" . env('TELEGRAM_API_KEY');
        $this->chatId = env('TELEGRAM_GROUP_ID');
    }

    /**
     * Send message to the configured Telegram group
     * 
     * @param string $message
     * @param string $parseMode (HTML or MarkdownV2)
     * @return bool
     */
    public function sendMessage(string $message, string $parseMode = 'HTML'): bool
    {
        try {
            $response = Http::post("{$this->baseUrl}/sendMessage", [
                'chat_id' => $this->chatId,
                'text' => $message,
                'parse_mode' => $parseMode,
            ]);

            if ($response->successful()) {
                Log::info('Telegram message sent successfully');
                return true;
            }

            Log::error('Telegram sendMessage failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('TelegramApiService sendMessage error', ['message' => $e->getMessage()]);
            return false;
        }
    }
}
