<?php

namespace App\Http\Controllers;

use App\Models\Technician;
use App\Models\Ticket;
use App\Services\TelegramService;
use Illuminate\Http\Request;

class TelegramBotController extends Controller
{
    // ============================================================
    // FUNGSI UTAMA WEBHOOK — Menerima semua pesan & callback dari Telegram
    // Telegram mengirim data ke URL ini setiap kali ada interaksi dengan bot
    // ============================================================
    public function webhook(Request $request)
    {
        // ========== BAGIAN 1: HANDLE CALLBACK (Teknisi klik tombol inline) ==========
        if ($request->has('callback_query')) {
            $callback = $request->input('callback_query');
            $callbackId = $callback['id'];
            $chatId = $callback['message']['chat']['id'];
            $messageId = $callback['message']['message_id'];
            $data = $callback['data'];

            // Cek apakah pengirim terdaftar sebagai teknisi
            $technician = Technician::where('telegram_chat_id', (string) $chatId)->first();
            if (!$technician) {
                TelegramService::answerCallback($callbackId, '❌ Anda tidak terdaftar sebagai teknisi.');
                return response()->json(['ok' => true]);
            }

            // ---------- Teknisi klik tombol "🔨 Mulai Kerjakan" ----------
            if (str_starts_with($data, 'dikerjakan_')) {
                $ticketId = str_replace('dikerjakan_', '', $data);
                $ticket = Ticket::find($ticketId);

                if (!$ticket) {
                    TelegramService::answerCallback($callbackId, '❌ Tiket tidak ditemukan.');
                    return response()->json(['ok' => true]);
                }

                // Cegah duplikasi — tiket sudah selesai atau sudah dikerjakan
                if ($ticket->status === 'Selesai') {
                    TelegramService::answerCallback($callbackId, '⚠️ Tiket ini sudah selesai.');
                    TelegramService::removeKeyboard($chatId, $messageId);
                    return response()->json(['ok' => true]);
                }

                if ($ticket->status === 'Dikerjakan') {
                    TelegramService::answerCallback($callbackId, '⚠️ Tiket ini sudah sedang dikerjakan.');
                    return response()->json(['ok' => true]);
                }

                // Update status tiket di database → "Dikerjakan"
                $ticket->update(['status' => 'Dikerjakan']);

                TelegramService::answerCallback($callbackId, '🔨 Status tiket diubah ke "Dikerjakan".');

                // Ganti tombol: hapus "Mulai Kerjakan", sisakan "Tandai Selesai"
                $keyboard = [
                    'inline_keyboard' => [
                        [['text' => '✅ Tandai Selesai', 'callback_data' => 'selesai_' . $ticket->id]]
                    ]
                ];

                $token = config('services.telegram.bot_token');
                \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$token}/editMessageReplyMarkup", [
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'reply_markup' => json_encode($keyboard),
                ]);

            // ---------- Teknisi klik tombol "✅ Tandai Selesai" ----------
            } elseif (str_starts_with($data, 'selesai_')) {
                $ticketId = str_replace('selesai_', '', $data);
                $ticket = Ticket::find($ticketId);

                if (!$ticket) {
                    TelegramService::answerCallback($callbackId, '❌ Tiket tidak ditemukan.');
                    return response()->json(['ok' => true]);
                }

                if ($ticket->status === 'Selesai') {
                    TelegramService::answerCallback($callbackId, '⚠️ Tiket ini sudah selesai sebelumnya.');
                    TelegramService::removeKeyboard($chatId, $messageId);
                    return response()->json(['ok' => true]);
                }

                // Update status ke "Selesai" + catat waktu penyelesaian
                $ticket->update([
                    'status' => 'Selesai',
                    'resolved_at' => now(),
                ]);

                TelegramService::answerCallback($callbackId, '✅ Tiket berhasil ditandai Selesai!');

                $ticket->load('room.building', 'category');

                // Hapus semua tombol inline dari pesan
                TelegramService::removeKeyboard($chatId, $messageId);

                // Ubah pesan menjadi ringkasan "Tiket Selesai"
                $doneText = "✅ *Tiket Selesai!*\n\n" .
                    "Tiket: #{$ticket->ticket_code}\n" .
                    "Kerusakan: {$ticket->category->name}\n" .
                    "Lokasi: {$ticket->room->building->name} / R.{$ticket->room->room_number}\n" .
                    "Diselesaikan oleh: {$technician->name}\n" .
                    "Waktu: " . now()->format('d M Y, H:i') . "\n\n" .
                    "Terima kasih atas kerja kerasnya! 🙏";

                // Pesan dengan foto → editCaption, pesan teks → editMessage
                if ($ticket->photo_path) {
                    TelegramService::editCaption($chatId, $messageId, $doneText);
                } else {
                    TelegramService::editMessage($chatId, $messageId, $doneText);
                }
            }

            return response()->json(['ok' => true]);
        }

        // ========== BAGIAN 2: HANDLE PESAN TEKS (Command dari teknisi) ==========
        $chatId = $request->input('message.chat.id');
        $text = trim($request->input('message.text', ''));

        // Command /start — Sambutan awal & tampilkan Chat ID
        if ($text === '/start') {
            $technician = Technician::where('telegram_chat_id', (string) $chatId)->first();
            if ($technician) {
                TelegramService::sendMessage($chatId,
                    "👋 Halo *{$technician->name}*!\n\n" .
                    "Bot AsetLink ITATS aktif.\n" .
                    "Anda akan menerima notifikasi tugas perbaikan di sini.\n\n" .
                    "Ketik /help untuk melihat daftar perintah.\n" .
                    "Chat ID Anda: `{$chatId}`"
                );
            } else {
                TelegramService::sendMessage($chatId,
                    "👋 Halo! Bot AsetLink ITATS.\n\n" .
                    "Chat ID Anda: `{$chatId}`\n\n" .
                    "Hubungi Admin untuk didaftarkan sebagai teknisi."
                );
            }

        // Command /help — Tampilkan daftar perintah
        } elseif ($text === '/help') {
            TelegramService::sendMessage($chatId,
                "📋 *Daftar Perintah AsetLink Bot*\n\n" .
                "/start - Mulai bot & cek status\n" .
                "/help - Tampilkan bantuan\n" .
                "/status - Cek status akun teknisi\n\n" .
                "Bot ini akan mengirim notifikasi otomatis saat ada tugas perbaikan baru."
            );

        // Command /status — Tampilkan statistik tugas teknisi
        } elseif ($text === '/status') {
            $technician = Technician::where('telegram_chat_id', (string) $chatId)->first();
            if ($technician) {
                $activeTickets = \App\Models\Ticket::where('technician_id', $technician->id)
                    ->whereIn('status', ['Ditugaskan', 'Dikerjakan'])
                    ->count();
                $completedTickets = \App\Models\Ticket::where('technician_id', $technician->id)
                    ->where('status', 'Selesai')
                    ->count();
                TelegramService::sendMessage($chatId,
                    "📊 *Status Teknisi*\n\n" .
                    "Nama: *{$technician->name}*\n" .
                    "Status: " . ($technician->status === 'aktif' ? '🟢 Aktif' : '🔴 Nonaktif') . "\n\n" .
                    "📌 Tugas aktif: *{$activeTickets}*\n" .
                    "✅ Tugas selesai: *{$completedTickets}*"
                );
            } else {
                TelegramService::sendMessage($chatId,
                    "❌ Anda belum terdaftar sebagai teknisi.\nHubungi Admin untuk didaftarkan."
                );
            }

        // Command tidak dikenal — Arahkan ke /help
        } elseif (str_starts_with($text, '/')) {
            TelegramService::sendMessage($chatId,
                "❓ Perintah *{$text}* tidak dikenali.\n\n" .
                "Ketik /help untuk melihat daftar perintah yang tersedia."
            );
        }

        return response()->json(['ok' => true]);
    }

    // ============================================================
    // Mendaftarkan menu command bot ke Telegram API
    // Agar muncul di menu saat user ketik "/" di chat
    // ============================================================
    public function registerCommands()
    {
        $token = config('services.telegram.bot_token');

        $commands = [
            ['command' => 'start', 'description' => 'Mulai bot & cek status koneksi'],
            ['command' => 'help', 'description' => 'Tampilkan daftar perintah'],
            ['command' => 'status', 'description' => 'Cek status akun teknisi & statistik tugas'],
        ];

        $response = \Illuminate\Support\Facades\Http::post(
            "https://api.telegram.org/bot{$token}/setMyCommands",
            ['commands' => $commands]
        );

        return response()->json($response->json());
    }
}
