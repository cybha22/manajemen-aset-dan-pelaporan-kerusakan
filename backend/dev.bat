@echo off
TITLE AsetLink Development Suite
set "PATH=C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64;%PATH%"

echo [1/2] Menjalankan Laravel Server di Alacritty...
start "" "C:\Program Files\Alacritty\alacritty.exe" --title "AsetLink - Laravel Server" -e cmd /k "cd /d %~dp0 && set PATH=C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64;%%PATH%% && php artisan serve"

echo [2/2] Menjalankan Ngrok di Alacritty...
start "" "C:\Program Files\Alacritty\alacritty.exe" --title "AsetLink - Ngrok Tunnel" -e cmd /k "cd /d %~dp0 && ngrok http 8000"

echo.
echo Kedua layanan berhasil dijalankan di Alacritty.
echo Terminal ini bisa ditutup.
timeout /t 3 >nul
