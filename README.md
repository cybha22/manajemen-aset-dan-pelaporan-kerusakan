# AsetLink — Class Asset Management & Damage Reporting System

**AsetLink** adalah Sistem Informasi berbasis web untuk **manajemen aset ruang kelas** dan **pelaporan kerusakan** di lingkungan kampus ITATS. Dibangun dengan stack **Laravel 12 (Backend REST API)** dan **Vue.js 3 + Vite (Frontend SPA)**.

---

## Fitur & Aktor Terlibat

Sistem ini melibatkan 2 aktor utama:

1. **Mahasiswa / Publik**: Mengisi form pelaporan kerusakan aset kelas dan melacak status tiket secara real-time.
2. **Admin (Sarpras)**: Mengelola tiket kerusakan, master data aset & ruangan, penugasan teknisi, serta memantau dashboard analitik.

---

## Struktur & Perancangan Sistem

### 1. Entity Relationship Diagram (ERD)
Relasi antar tabel dalam database sistem.
![ERD Diagram](flowcharts/diagram/ERD-qus.png)

### 2. Use Case Diagram
Diagram interaksi setiap aktor terhadap fungsionalitas sistem.
![Use Case](flowcharts/diagram/usecase.svg)

### 3. Data Flow Diagram (DFD Level 1)
Aliran data antar proses dalam sistem.
![DFD Level 1](flowcharts/diagram/DFDlevel1diagram.svg)

### 4. User Flow
Alur interaksi pengguna pada sistem.
![User Flow](flowcharts/diagram/userflows.svg)

---

## Tampilan Aplikasi (UI/UX)

### Tampilan Publik (Mahasiswa)

Form Pelaporan Kerusakan Aset Kelas
![Buat Laporan](flowcharts/previews/mhs/buat-laporan-kerusakan.png)

Lacak Status Tiket
![Lacak Tiket](flowcharts/previews/mhs/lacak.png)

---

### Tampilan Admin (Panel Sarpras)

Dashboard Analitik — Statistik performa pemeliharaan
![Dashboard Analitik](flowcharts/previews/admin/dashboard-analitik.png)

Manajemen Tiket — Papan Kanban penugasan kerusakan
![Manajemen Tiket](flowcharts/previews/admin/mnjmn-tiket-pngsn.png)

Pilih & Tugaskan Teknisi
![Pilih Teknisi](flowcharts/previews/admin/pilihteknisi.png)
![Tugaskan ke Teknisi](flowcharts/previews/admin/tugaskanketeknisi.png)

Master Data Aset
![Master Data Aset](flowcharts/previews/admin/masterdataaset.png)

Inventarisasi Aset per Ruangan
![Inventaris Aset](flowcharts/previews/admin/inverntarisaset.png)

Tambah Ruangan Baru
![Tambah Ruangan](flowcharts/previews/admin/tambahruangan.png)

QR Code Generator Ruangan
![Generate QR](flowcharts/previews/admin/generate-qrruangan.png)

---

### Tampilan Teknisi (via Telegram Bot)

Notifikasi tugas masuk ke teknisi via Telegram
![Job Masuk](flowcharts/previews/teknisi/job-masuk.png)

---

## Video Demo

https://github.com/user-attachments/assets/5e8b14f9-0268-4363-b7f4-b3914878f3c9

---

## Menjalankan Aplikasi

Aplikasi berjalan pada ekosistem lokal PC anda menggunakan stack berikut:
- **PHP** >= 8.2
- **Composer** v2+
- **Node.js** v20+
- **Database** MySQL 8.0

### Menjalankan via Docker (Recommended)

Pada project ini sudah disediakan Docker Compose untuk menjalankan.

```bash
docker compose up -d --build
```

Setelah dijalankan, akses URL berikut:
- **Frontend App:** `http://localhost`
- **Backend/API:** `http://localhost:8000`

Login Admin: `http://localhost/login`
| Field | Value |
|-------|-------|
| Username | `admin` |
| Password | `qwe123` |

### Menjalankan Tanpa Docker (Manual)

**1. Siapkan Database**

Buat database MySQL dengan nama `asetlink`, pastikan service MySQL/MariaDB sudah berjalan di komputer Anda.

**2. Setup Backend**

```bash
cd backend
composer install
copy .env.example .env
```

Sesuaikan file `.env` pada bagian database:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=asetlink
DB_USERNAME=root
DB_PASSWORD=
```

Lanjutkan:
```bash
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan serve --port=8000
```

**3. Setup Frontend**

Buka terminal baru:
```bash
cd frontend
npm install
```

**Opsi A — Mode Development** (hot-reload, 2 port):
```bash
npm run dev
```
Akses frontend di `http://localhost:5173`, API di `http://localhost:8000`.

**Opsi B — Mode Single Server** (1 port, seperti production):
```bash
npm run build
```
Lalu copy hasil build ke backend:
```bash
copy dist\index.html ..\backend\public\index.html
xcopy dist\assets ..\backend\public\assets /E /Y
```
Akses seluruh aplikasi di `http://localhost:8000` saja.

**4. Akses Aplikasi**

| Mode | Frontend | API |
|------|----------|-----|
| Development | `http://localhost:5173` | `http://localhost:8000` |
| Single Server | `http://localhost:8000` | `http://localhost:8000` |
| Docker | `http://localhost` | `http://localhost:8000` |

---

## Struktur Project

```
asetlinkV2/
|-- backend/                        # Laravel 12 (REST API)
|   |-- app/
|   |   |-- Http/
|   |   |   |-- Controllers/
|   |   |   |   |-- AuthController.php          # Login & Logout Admin
|   |   |   |   |-- TicketController.php        # CRUD Tiket + Lacak
|   |   |   |   |-- BuildingController.php      # Master Data Gedung
|   |   |   |   |-- RoomController.php          # Master Data Ruangan
|   |   |   |   |-- CategoryController.php      # Kategori Kerusakan
|   |   |   |   |-- DashboardController.php     # Statistik & Chart
|   |   |   |   |-- TechnicianController.php    # Data Teknisi
|   |   |   |   |-- RoomAssetController.php     # Inventaris Aset
|   |   |   |   |-- QrCodeController.php        # Generate QR Code
|   |   |   |   |-- TelegramBotController.php   # Webhook & Bot Telegram
|   |   |   |-- Middleware/
|   |   |   |   |-- EnsureApiCsrfOrToken.php    # CSRF/Token guard API
|   |   |-- Models/
|   |-- bootstrap/
|   |   |-- app.php                 # Konfigurasi middleware & routing
|   |-- database/
|   |   |-- migrations/             # File migrasi tabel
|   |   |-- seeders/                # Seeder data awal (admin, gedung, dll)
|   |-- routes/
|   |   |-- api.php                 # Definisi route REST API
|   |   |-- web.php                 # Route SPA & file serving
|   |-- public/                     # Build frontend (index.html + assets)
|   |-- Dockerfile
|   |-- .env.example
|   |-- composer.json
|
|-- frontend/                       # Vue.js 3 + Vite (SPA)
|   |-- src/
|   |   |-- services/
|   |   |   |-- api.js              # Axios instance + token interceptor
|   |   |-- assets/
|   |   |   |-- style.css           # Design system & tema global
|   |   |-- components/
|   |   |   |-- ToastContainer.vue  # Notifikasi toast
|   |   |   |-- ConfirmDialog.vue   # Dialog konfirmasi
|   |   |-- composables/
|   |   |   |-- useToast.js         # Composable provide/inject toast
|   |   |-- layouts/
|   |   |   |-- AppLayout.vue       # Layout admin (sidebar + topbar)
|   |   |   |-- PublicLayout.vue    # Layout publik (header minimal)
|   |   |-- pages/
|   |   |   |-- ReportPage.vue      # Form pelaporan kerusakan
|   |   |   |-- TrackPage.vue       # Lacak status tiket
|   |   |   |-- LoginPage.vue       # Login admin
|   |   |   |-- DashboardPage.vue   # Dashboard analitik + animasi chart
|   |   |   |-- TicketsPage.vue     # Kanban manajemen tiket
|   |   |   |-- MasterPage.vue      # Master data aset & ruangan
|   |   |   |-- QrCodePage.vue      # QR Code generator ruangan
|   |   |   |-- TelegramPage.vue    # Info bot Telegram teknisi
|   |   |-- router/
|   |   |   |-- index.js            # Route + auth guard admin
|   |   |-- stores/
|   |   |   |-- auth.js             # Pinia store (login, token, user)
|   |   |-- App.vue                 # Root component + layout switcher
|   |   |-- main.js                 # Entry point Vue + Pinia + Router
|   |-- Dockerfile
|   |-- nginx.conf
|   |-- package.json
|   |-- vite.config.js
|
|-- flowcharts/
|   |-- diagram/                    # ERD, Use Case, DFD, User Flow
|   |-- previews/                   # Screenshot UI per aktor
|       |-- admin/
|       |-- mhs/
|       |-- teknisi/
|
|-- docker-compose.yml
|-- .gitignore
|-- README.md
```

---

## Lisensi

Project ini dilisensikan di bawah [MIT License](LICENSE).

```
MIT License

Copyright (c) 2026 Cybha

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```
