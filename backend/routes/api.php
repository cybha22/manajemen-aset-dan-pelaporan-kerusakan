<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TechnicianController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\TelegramBotController;
use App\Http\Controllers\RoomAssetController;


// ENDPOINT PUBLIK — Bisa diakses tanpa login (mahasiswa/umum)


// Endpoint login admin (mengembalikan token Sanctum)
Route::post('/auth/login', [AuthController::class, 'login']);

// Mahasiswa mengirim laporan kerusakan (form pelaporan publik)
Route::post('/tickets', [TicketController::class, 'store']);

// Mahasiswa melacak status tiket berdasarkan kode TK-XXXXX
Route::get('/tickets/track/{code}', [TicketController::class, 'track']);

// Data dropdown gedung, ruangan, dan kategori untuk form publik
Route::get('/buildings', [BuildingController::class, 'index']);
Route::get('/rooms', [RoomController::class, 'index']);
Route::get('/categories', [CategoryController::class, 'index']);

// Webhook Telegram — menerima pesan & callback dari teknisi via bot
Route::post('/telegram/webhook', [TelegramBotController::class, 'webhook']);

// Mendaftarkan menu command (/start, /help, /status) ke Telegram
Route::get('/telegram/register-commands', [TelegramBotController::class, 'registerCommands']);


// ENDPOINT ADMIN — Harus login dulu (dilindungi auth:sanctum)

Route::middleware('auth:sanctum')->group(function () {

    // Logout dan cek data user yang sedang login
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // CRUD tiket (index, show, update, delete) — store sudah di publik
    Route::apiResource('/tickets', TicketController::class)->except(['store']);

    // CRUD master data gedung dan ruangan
    Route::apiResource('/buildings', BuildingController::class)->except(['index']);
    Route::apiResource('/rooms', RoomController::class)->except(['index']);

    // CRUD inventaris aset fisik per ruangan (AC, Proyektor, dll)
    Route::get('/room-assets', [RoomAssetController::class, 'index']);
    Route::post('/room-assets', [RoomAssetController::class, 'store']);
    Route::put('/room-assets/{roomAsset}', [RoomAssetController::class, 'update']);
    Route::delete('/room-assets/{roomAsset}', [RoomAssetController::class, 'destroy']);

    // CRUD kategori kerusakan dan data teknisi
    Route::apiResource('/categories', CategoryController::class)->except(['index']);
    Route::apiResource('/technicians', TechnicianController::class);

    // Data untuk Dasbor Analitik (statistik, grafik mingguan, per kategori, per gedung, waktu respon)
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/chart/weekly', [DashboardController::class, 'chartWeekly']);
    Route::get('/dashboard/chart/category', [DashboardController::class, 'chartCategory']);
    Route::get('/dashboard/chart/building', [DashboardController::class, 'chartBuilding']);
    Route::get('/dashboard/chart/response-time', [DashboardController::class, 'chartResponseTime']);

    // Generate QR Code unik per ruangan
    Route::get('/qrcode/{room_id}', [QrCodeController::class, 'generate']);
});
