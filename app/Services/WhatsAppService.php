<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private static function getServer(): string
    {
        return rtrim((string) config('services.wablas.server', 'https://pati.wablas.com'), '/');
    }

    private static function getToken(): ?string
    {
        return config('services.wablas.token');
    }

    private static function getDriver(): string
    {
        return (string) config('services.wablas.driver', 'log');
    }

    private static function getDebugPhone(): ?string
    {
        return config('services.wablas.debug_phone');
    }

    private static function formatPhoneNumber(?string $phone): ?string
    {
        if (empty($phone) || (! str_starts_with($phone, '+628') && ! str_starts_with($phone, '08') && ! str_starts_with($phone, '62'))) {
            return null;
        }

        if (str_starts_with($phone, '+628')) {
            return substr($phone, 1);
        } elseif (str_starts_with($phone, '08')) {
            return '62' . substr($phone, 1);
        }

        return $phone;
    }

    public static function sendMessage(?string $phone, string $message): ?array
    {
        $driver = static::getDriver();
        $debugPhone = static::getDebugPhone();

        if (! empty($debugPhone)) {
            $phone = $debugPhone;
        }

        if ($driver === 'disabled') {
            return [
                'success' => true,
                'driver' => 'disabled',
            ];
        }

        if ($driver === 'log') {
            $formattedPhone = static::formatPhoneNumber($phone);

            Log::info('WhatsApp notification logged', [
                'phone' => $formattedPhone ?? $phone,
                'message' => $message,
            ]);

            return [
                'success' => true,
                'driver' => 'log',
            ];
        }

        $phone = static::formatPhoneNumber($phone);

        if (empty($phone)) {
            return null;
        }

        if (empty(static::getToken())) {
            Log::warning('WhatsApp notification skipped because WABLAS_TOKEN is empty', [
                'phone' => $phone,
            ]);

            return [
                'success' => false,
                'driver' => $driver,
                'message' => 'WABLAS_TOKEN is empty',
            ];
        }

        $res = Http::withHeaders([
            'Authorization' => static::getToken(),
            'Content-Type' => 'application/json',
        ])->post(static::getServer() . '/api/send-message', [
            'phone' => $phone,
            'message' => $message,
        ]);

        return json_decode($res->body(), true);
    }
}
