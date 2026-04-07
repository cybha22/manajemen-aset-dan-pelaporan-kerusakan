<?php

use Illuminate\Support\Facades\Route;

$serveSpa = function () {
    return file_get_contents(public_path('index.html'));
};

Route::get('/', $serveSpa);
Route::get('/report', $serveSpa);
Route::get('/track', $serveSpa);
Route::get('/login', $serveSpa);
Route::get('/dashboard', $serveSpa);
Route::get('/tickets', $serveSpa);
Route::get('/master', $serveSpa);
Route::get('/qrcode', $serveSpa);
Route::get('/telegram', $serveSpa);
Route::get('/lapor', $serveSpa);



// Fallback untuk melayani file storage di Windows saat menggunakan php artisan serve
Route::get('/storage/{path}', function ($path) {
    if (app()->environment('local')) {
        $filePath = storage_path('app/public/' . $path);
        if (file_exists($filePath)) {
            return response()->make(file_get_contents($filePath), 200, [
                'Content-Type' => mime_content_type($filePath)
            ]);
        }
    }
    abort(404);
})->where('path', '.*');
