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

Route::get('/admin', function () {
    return file_get_contents(public_path('admin.html'));
});
