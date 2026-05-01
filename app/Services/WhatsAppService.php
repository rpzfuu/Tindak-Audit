<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    private static $server = "https://pati.wablas.com";

    private static function getToken()
    {
        return "KXCwBNP19Q3L5O7AlNR3IXGMlZnYjUyCZRkg1uH916uRpIwKaXlNCXc2QvoeeuzH";
    }

    private static function getIsDebug()
    {
        return true;
    }

    private static function formatPhoneNumber($phone)
    {
        if ((!str_starts_with($phone, '+628') && !str_starts_with($phone, '08') && !str_starts_with($phone, '62')) || empty($phone)) {
            return null;
        }

        if (str_starts_with($phone, '+628')) {
            return substr($phone, 1);
        } else if (str_starts_with($phone, '08')) {
            return '62' . substr($phone, 1);
        }
        return $phone;
    }

    public static function sendMessage($phone, $message)
    {
        if (static::getIsDebug() == true) {
            $phone = '089652100512';
        }

        $phone = static::formatPhoneNumber($phone);

        if (empty($phone)) {
            return null;
        }

        $res = Http::withHeaders(['Authorization' => static::getToken(), 'Content-Type' => 'application/json'])->post(static::$server . '/api/send-message', [
            'phone' => $phone,
            'message' => $message,
        ]);

        return json_decode($res->body(), true);
    }
}