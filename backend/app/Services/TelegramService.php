<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

// ============================================================
// TelegramService — Kumpulan fungsi untuk berkomunikasi dengan API Telegram
// Digunakan untuk mengirim notifikasi, mengedit pesan, dan mengelola tombol inline
// ============================================================
class TelegramService
{
    // ============================================================
    // Kirim notifikasi tugas baru ke teknisi via Telegram
    // Dipanggil saat admin mengubah status tiket ke "Ditugaskan"
    // Jika tiket ada foto → kirim sebagai foto+caption
    // Jika tiket tanpa foto → kirim sebagai pesan teks biasa
    // ============================================================
    public static function sendTaskNotification(Ticket $ticket)
    {
        $ticket->load(['room.building', 'category', 'technician']);

        $message = "🔧 *Tugas Baru!*\n\n" .
            "Kerusakan: *{$ticket->category->name}*\n" .
            "Lokasi: {$ticket->room->building->name} / R.{$ticket->room->room_number}\n" .
            "Pelapor: {$ticket->reporter_name} ({$ticket->reporter_phone})\n" .
            "Deskripsi: {$ticket->description}\n\n" .
            "Tiket: #{$ticket->ticket_code}\n" .
            "Waktu Lapor: {$ticket->created_at->format('d M Y, H:i')}";

        // Tombol inline: Mulai Kerjakan & Tandai Selesai
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🔨 Mulai Kerjakan', 'callback_data' => 'dikerjakan_' . $ticket->id],
                    ['text' => '✅ Tandai Selesai', 'callback_data' => 'selesai_' . $ticket->id],
                ]
            ]
        ];

        $chatId = $ticket->technician->telegram_chat_id;

        // Cek apakah tiket punya foto → kirim sesuai tipe
        if ($ticket->photo_path && Storage::disk('public')->exists($ticket->photo_path)) {
            self::sendPhoto($chatId, Storage::disk('public')->path($ticket->photo_path), $message, $keyboard);
        } else {
            self::sendMessage($chatId, $message, $keyboard);
        }
    }

    // Kirim pesan teks biasa ke Telegram
    public static function sendMessage($chatId, $text, $replyMarkup = null)
    {
        $token = config('services.telegram.bot_token');

        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ];

        if ($replyMarkup) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        Http::post("https://api.telegram.org/bot{$token}/sendMessage", $payload);
    }

    // Kirim foto + caption ke Telegram (untuk tiket yang ada bukti foto)
    public static function sendPhoto($chatId, $filePath, $caption = '', $replyMarkup = null)
    {
        $token = config('services.telegram.bot_token');

        $request = Http::attach('photo', file_get_contents($filePath), basename($filePath));

        $payload = [
            'chat_id' => $chatId,
            'caption' => $caption,
            'parse_mode' => 'Markdown',
        ];

        if ($replyMarkup) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        $request->post("https://api.telegram.org/bot{$token}/sendPhoto", $payload);
    }

    // Kirim popup notifikasi ke teknisi saat klik tombol callback
    public static function answerCallback($callbackId, $text)
    {
        $token = config('services.telegram.bot_token');

        Http::post("https://api.telegram.org/bot{$token}/answerCallbackQuery", [
            'callback_query_id' => $callbackId,
            'text' => $text,
            'show_alert' => true,
        ]);
    }

    // Edit pesan TEKS yang sudah terkirim (untuk tiket tanpa foto)
    public static function editMessage($chatId, $messageId, $text, $replyMarkup = null)
    {
        $token = config('services.telegram.bot_token');

        $payload = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode($replyMarkup ?? ['inline_keyboard' => []]),
        ];

        Http::post("https://api.telegram.org/bot{$token}/editMessageText", $payload);
    }

    // Edit CAPTION pada pesan foto yang sudah terkirim (untuk tiket dengan foto)
    public static function editCaption($chatId, $messageId, $caption, $replyMarkup = null)
    {
        $token = config('services.telegram.bot_token');

        Http::post("https://api.telegram.org/bot{$token}/editMessageCaption", [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'caption' => $caption,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode($replyMarkup ?? ['inline_keyboard' => []]),
        ]);
    }

    // Hapus semua tombol inline dari pesan (setelah tiket selesai)
    public static function removeKeyboard($chatId, $messageId)
    {
        $token = config('services.telegram.bot_token');

        Http::post("https://api.telegram.org/bot{$token}/editMessageReplyMarkup", [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'reply_markup' => json_encode(['inline_keyboard' => []]),
        ]);
    }
}
