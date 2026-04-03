// ============================================================
// SCRIPT UTAMA  — Single Page Application (SPA)
// Semua interaksi frontend dikelola di file ini:
// Form Pelaporan, Pelacakan Tiket, Login Admin, Dashboard,
// Manajemen Tiket (Kanban), Master Data Aset, QR Code, Grafik
// ============================================================
document.addEventListener('DOMContentLoaded', () => {

    const API = '';

    // ========== KOMPONEN TOAST — Notifikasi popup (sukses, error, warning) ==========
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.className = 'toast-container';
        document.body.appendChild(toastContainer);
    }

    const toastIcons = {
        error: 'ph-x-circle',
        success: 'ph-check-circle',
        warning: 'ph-warning',
        info: 'ph-info'
    };

    function showToast(message, type = 'error') {
        const toast = document.createElement('div');
        toast.className = 'toast ' + type;
        toast.innerHTML = '<i class="ph ' + (toastIcons[type] || 'ph-info') + '"></i><span>' + message + '</span>';
        toastContainer.appendChild(toast);
        setTimeout(() => {
            toast.classList.add('toast-out');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }


    // break asar

    function showConfirm(message, title = 'Konfirmasi') {
        return new Promise((resolve) => {
            const overlay = document.createElement('div');
            overlay.className = 'confirm-overlay';
            overlay.innerHTML = '<div class="confirm-box"><div class="confirm-icon"><i class="ph ph-warning-circle"></i></div><h4>' + title + '</h4><p>' + message + '</p><div class="confirm-actions"><button class="btn ghost" id="cfCancel">Batal</button><button class="btn danger-fill" id="cfOk">Ya, Hapus</button></div></div>';
            document.body.appendChild(overlay);
            document.getElementById('cfCancel').addEventListener('click', () => { overlay.remove(); resolve(false); });
            document.getElementById('cfOk').addEventListener('click', () => { overlay.remove(); resolve(true); });
            overlay.addEventListener('click', (e) => { if (e.target === overlay) { overlay.remove(); resolve(false); } });
        });
    }

    const navLinks = document.querySelectorAll('.nav-link');
    const pages = document.querySelectorAll('.page');
    const topTitle = document.getElementById('topbarTitle');
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.getElementById('menuToggle');
    const loginScreen = document.getElementById('loginScreen');
    const appMain = document.getElementById('appMain');
    const loginForm = document.getElementById('loginForm');
    const loginError = document.getElementById('loginError');
    const navDividerAdmin = document.getElementById('navDividerAdmin');
    const logoutBtn = document.getElementById('logoutBtn');
    const backToPublic = document.getElementById('backToPublic');
    const adminLabel = document.getElementById('adminLabel');
    const adminLinks = document.querySelectorAll('.admin-only');

    let isAdmin = false;

    // ========== AUTENTIKASI — CSRF Token & Bearer Token untuk Laravel Sanctum ==========
    function getCsrfToken() {
        const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        return match ? decodeURIComponent(match[1]) : '';
    }

    async function initCsrf() {
        await fetch(API + '/sanctum/csrf-cookie', { credentials: 'include' });
    }

    function authHeaders() {
        const t = localStorage.getItem('token');
        const headers = { 'Accept': 'application/json', 'X-XSRF-TOKEN': getCsrfToken() };
        if (t) headers['Authorization'] = 'Bearer ' + t;
        return headers;
    }

    // Wrapper fetch() — otomatis menyertakan token autentikasi di setiap request API
    function apiFetch(url, options = {}) {
        options.credentials = 'include';
        if (!options.headers) options.headers = authHeaders();
        else {
            const ah = authHeaders();
            Object.keys(ah).forEach(k => { if (!options.headers[k]) options.headers[k] = ah[k]; });
        }
        return fetch(url, options);
    }

    const pageMeta = {
        report: { title: 'Buat Laporan Kerusakan', sub: 'Isi data secara lengkap untuk memproses tiket baru.' },
        track: { title: 'Lacak Status Tiket', sub: 'Pantau progres perbaikan aset Anda secara real-time.' },
        dashboard: { title: 'Dasbor Analitik', sub: 'Statistik performa pemeliharaan ruang kelas bulan ini.' },
        tickets: { title: 'Manajemen Tiket', sub: 'Kelola penugasan dan pantau status seluruh tiket.' },
        master: { title: 'Master Data Aset', sub: 'Inventarisasi aset kelas Gedung A hingga H.' },
        qrcode: { title: 'QR Code Generator Ruangan', sub: 'Generate dan cetak QR Code unik untuk setiap ruangan.' },
        telegram: { title: 'Integrasi Bot Telegram Teknisi', sub: 'Alur koordinasi tugas teknisi via Chatbot Telegram.' }
    };

    function getPageFromPath() {
        const path = window.location.pathname.replace('/', '') || 'report';
        return path;
    }

    // ========== POLLING REAL-TIME — Auto-refresh tiket setiap 20 detik ==========
    let _ticketPollInterval = null;

    function startTicketPolling() {
        if (_ticketPollInterval) return;
        _ticketPollInterval = setInterval(() => {
            if (document.getElementById('page-tickets').classList.contains('active')) {
                loadTickets();
            }
        }, 20000);
    }

    function stopTicketPolling() {
        if (_ticketPollInterval) {
            clearInterval(_ticketPollInterval);
            _ticketPollInterval = null;
        }
    }

    // ========== NAVIGASI SPA — Pindah halaman tanpa reload browser ==========
    function switchPage(targetId) {
        if (targetId === 'login') { history.pushState(null, '', '/login'); showLogin(); return; }
        if (targetId === 'logout') { doLogout(); return; }

        const url = targetId === 'report' ? '/' + window.location.search : '/' + targetId;
        history.pushState(null, '', url);

        pages.forEach(p => p.classList.remove('active'));
        navLinks.forEach(n => n.classList.remove('active'));

        const page = document.getElementById('page-' + targetId);
        if (page) page.classList.add('active');
        navLinks.forEach(n => { if (n.dataset.page === targetId) n.classList.add('active'); });

        const meta = pageMeta[targetId];
        if (meta) {
            topTitle.querySelector('h2').textContent = meta.title;
            topTitle.querySelector('p').textContent = meta.sub;
        }

        if (targetId === 'dashboard') loadDashboard();
        if (targetId === 'master') loadMasterData();

        if (targetId === 'tickets') {
            loadTickets();
            startTicketPolling();
        } else {
            stopTicketPolling();
        }

        if (sidebar.classList.contains('open')) sidebar.classList.remove('open');
    }

    window.addEventListener('popstate', () => {
        const page = getPageFromPath();
        if (page === 'login') { showLogin(); return; }
        if (pageMeta[page]) switchPage(page);
    });

    navLinks.forEach(btn => {
        btn.addEventListener('click', () => switchPage(btn.dataset.page));
    });
    menuToggle.addEventListener('click', () => sidebar.classList.toggle('open'));

    // ========== LOGIN & LOGOUT ADMIN (Laravel Sanctum Token) ==========
    function showLogin() {
        loginScreen.style.display = 'flex';
        appMain.style.display = 'none';
        loginError.style.display = 'none';
        document.getElementById('loginUser').value = '';
        document.getElementById('loginPass').value = '';
    }

    async function doLogin() {
        const user = document.getElementById('loginUser').value.trim();
        const pass = document.getElementById('loginPass').value.trim();

        try {
            await initCsrf();
            const res = await apiFetch(API + '/api/auth/login', {
                method: 'POST',
                headers: { ...authHeaders(), 'Content-Type': 'application/json' },
                body: JSON.stringify({ username: user, password: pass })
            });

            const data = await res.json();

            if (res.ok && data.token) {
                localStorage.setItem('token', data.token);
                isAdmin = true;
                loginScreen.style.display = 'none';
                appMain.style.display = 'flex';
                adminLabel.style.display = '';
                adminLinks.forEach(l => l.style.display = '');
                if (navDividerAdmin) navDividerAdmin.style.display = '';
                logoutBtn.style.display = '';
                document.getElementById('uName').textContent = data.user.name;
                document.getElementById('uRole').textContent = 'Admin Sarpras';
                document.getElementById('uAvatar').src = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(data.user.name) + '&background=131B2A&color=8A2BE2&bold=true&size=80';
                switchPage('dashboard');
            } else {
                loginError.textContent = data.message || 'Username atau password salah.';
                loginError.style.display = 'block';
            }
        } catch (err) {
            loginError.textContent = 'Gagal terhubung ke server.';
            loginError.style.display = 'block';
        }
    }

    async function doLogout() {
        const token = localStorage.getItem('token');
        if (token) {
            await apiFetch(API + '/api/auth/logout', {
                method: 'POST',
                headers: authHeaders()
            }).catch(() => { });
            localStorage.removeItem('token');
        }
        isAdmin = false;
        adminLabel.style.display = 'none';
        adminLinks.forEach(l => l.style.display = 'none');
        if (navDividerAdmin) navDividerAdmin.style.display = 'none';
        logoutBtn.style.display = 'none';
        document.getElementById('uName').textContent = 'Mahasiswa';
        document.getElementById('uRole').textContent = 'Pengguna Publik';
        document.getElementById('uAvatar').src = 'image.png';
        history.pushState(null, '', '/');
        switchPage('report');
    }

    loginForm.addEventListener('submit', e => { e.preventDefault(); doLogin(); });
    backToPublic.addEventListener('click', () => {
        loginScreen.style.display = 'none';
        appMain.style.display = 'flex';
    });

    // ========== FORM PELAPORAN KERUSAKAN (Halaman Publik Mahasiswa) ==========
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('fileInput');
    const dzInner = document.getElementById('dzInner');
    const reportForm = document.getElementById('reportForm');
    const submitBtn = document.getElementById('submitBtn');
    const ticketBadge = document.getElementById('ticketBadge');
    const reportGedung = document.getElementById('reportGedung');
    const reportRuangan = document.getElementById('reportRuangan');
    const assetType = document.getElementById('assetType');

    async function loadDropdowns() {
        try {
            const [bRes, cRes] = await Promise.all([
                apiFetch(API + '/api/buildings').then(r => r.json()),
                apiFetch(API + '/api/categories').then(r => r.json())
            ]);

            if (reportGedung) {
                reportGedung.innerHTML = '<option value="">-- Pilih Gedung --</option>';
                bRes.forEach(b => {
                    const opt = document.createElement('option');
                    opt.value = b.id;
                    opt.textContent = b.name;
                    reportGedung.appendChild(opt);
                });
            }

            if (assetType) {
                assetType.innerHTML = '<option value="">-- Pilih Kategori --</option>';
                cRes.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.name;
                    assetType.appendChild(opt);
                });
            }
            const qrGedungSel = document.getElementById('qrGedung');
            if (qrGedungSel) {
                qrGedungSel.innerHTML = '<option value="">-- Pilih Gedung --</option>';
                bRes.forEach(b => {
                    const opt = document.createElement('option');
                    opt.value = b.name;
                    opt.textContent = b.name;
                    qrGedungSel.appendChild(opt);
                });
            }
        } catch (err) { }
    }

    initCsrf().then(() => loadDropdowns().then(() => {
        const urlParams = new URLSearchParams(window.location.search);
        const gParam = urlParams.get('g');
        const rParam = urlParams.get('r');
        if (gParam && reportGedung) {
            const targetOption = Array.from(reportGedung.options).find(opt => opt.text.includes(gParam));
            if (targetOption) {
                targetOption.selected = true;
                reportGedung.dispatchEvent(new Event('change'));
            }
        }
        if (rParam) {
            const roomInput = document.getElementById('reportRuangan');
            if (roomInput) roomInput.value = rParam;
        }
    }));

    ticketBadge.textContent = 'Otomatis dari server';

    dropzone.addEventListener('click', () => fileInput.click());
    dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('dragover'); });
    dropzone.addEventListener('dragleave', e => { e.preventDefault(); dropzone.classList.remove('dragover'); });
    dropzone.addEventListener('drop', e => {
        e.preventDefault(); dropzone.classList.remove('dragover');
        if (e.dataTransfer.files.length) { fileInput.files = e.dataTransfer.files; showUploaded(e.dataTransfer.files[0].name); }
    });
    fileInput.addEventListener('change', () => { if (fileInput.files.length) showUploaded(fileInput.files[0].name); });

    function showUploaded(name) {
        dzInner.innerHTML = '<div class="dz-icon"><i class="ph ph-check-circle" style="color:var(--green)"></i></div><p style="color:var(--green);font-weight:600;">' + name + '</p><small>File siap dilampirkan</small>';
        dropzone.classList.add('uploaded');
    }
    function resetDropzone() {
        dzInner.innerHTML = '<div class="dz-icon"><i class="ph ph-cloud-arrow-up"></i></div><p><span class="hl">Klik untuk unggah</span> atau seret foto ke sini</p><small>JPG / PNG, Maks 5MB</small>';
        dropzone.classList.remove('uploaded');
    }

    // Submit laporan kerusakan → POST /api/tickets (FormData + foto)
    reportForm.addEventListener('submit', async e => {
        e.preventDefault();
        const span = submitBtn.querySelector('span');
        const icon = submitBtn.querySelector('i');
        submitBtn.disabled = true;
        submitBtn.style.background = 'rgba(255,255,255,.08)'; submitBtn.style.color = 'var(--dim)'; submitBtn.style.boxShadow = 'none';
        span.textContent = 'Memproses...'; icon.className = 'ph ph-circle-notch'; icon.style.animation = 'spin 1s linear infinite';

        try {
            const formData = new FormData();
            formData.append('reporter_name', document.getElementById('reporterName').value);
            formData.append('reporter_phone', document.getElementById('reporterPhone').value);
            formData.append('building_id', reportGedung.value);
            formData.append('room_number', reportRuangan.value);
            formData.append('category_id', assetType.value);
            formData.append('description', document.getElementById('reportDesc').value);
            if (fileInput.files[0]) formData.append('photo', fileInput.files[0]);

            const res = await apiFetch(API + '/api/tickets', {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: formData
            });

            const data = await res.json();

            if (res.ok) {
                submitBtn.style.background = 'var(--purple)'; submitBtn.style.color = '#fff';
                span.textContent = 'Tiket Berhasil Dibuat!'; icon.className = 'ph ph-check-circle'; icon.style.animation = 'none';
                ticketBadge.textContent = 'ID: #' + data.ticket_code;
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.style.background = 'var(--green)'; submitBtn.style.color = 'var(--bg)';
                    span.textContent = 'Kirim Laporan'; icon.className = 'ph ph-paper-plane-right';
                    reportForm.reset(); resetDropzone();
                }, 3000);
            } else {
                const errors = data.errors ? Object.values(data.errors).flat().join(', ') : data.message;
                span.textContent = 'Gagal: ' + errors; icon.className = 'ph ph-x-circle'; icon.style.animation = 'none';
                submitBtn.style.background = 'var(--red)'; submitBtn.style.color = '#fff';
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.style.background = 'var(--green)'; submitBtn.style.color = 'var(--bg)';
                    span.textContent = 'Kirim Laporan'; icon.className = 'ph ph-paper-plane-right';
                }, 3000);
            }
        } catch (err) {
            span.textContent = 'Error koneksi'; icon.className = 'ph ph-x-circle'; icon.style.animation = 'none';
            submitBtn.style.background = 'var(--red)'; submitBtn.style.color = '#fff';
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.style.background = 'var(--green)'; submitBtn.style.color = 'var(--bg)';
                span.textContent = 'Kirim Laporan'; icon.className = 'ph ph-paper-plane-right';
            }, 3000);
        }
    });

    // ========== PELACAKAN TIKET — Mahasiswa input kode TK-XXXXX ==========
    const trackBtn = document.getElementById('trackBtn');
    const trackInput = document.getElementById('trackInput');
    const trackResult = document.getElementById('trackResult');

    trackBtn.addEventListener('click', async () => {
        const val = trackInput.value.trim();
        if (!val) { trackInput.style.borderColor = 'var(--red)'; setTimeout(() => trackInput.style.borderColor = '', 1500); return; }

        try {
            const res = await apiFetch(API + '/api/tickets/track/' + val);
            const data = await res.json();

            if (res.ok) {
                document.getElementById('trId').textContent = data.ticket_code;
                document.getElementById('trLoc').textContent = data.location;
                document.getElementById('trAsset').textContent = data.category;
                document.getElementById('trDate').textContent = data.created_at;

                const timelineEl = document.getElementById('trackTimeline');
                timelineEl.innerHTML = '';

                const allSteps = [
                    { status: 'Baru', label: 'Laporan Diterima' },
                    { status: 'Divalidasi', label: 'Divalidasi Admin' },
                    { status: 'Ditugaskan', label: 'Ditugaskan ke Teknisi' },
                    { status: 'Dikerjakan', label: 'Sedang Dikerjakan' },
                    { status: 'Selesai', label: 'Perbaikan Selesai' }
                ];

                const timelineMap = {};
                if (data.timeline) {
                    data.timeline.forEach(t => { timelineMap[t.status] = t; });
                }

                let lastDoneIndex = -1;
                allSteps.forEach((step, i) => {
                    if (timelineMap[step.status] && timelineMap[step.status].done) lastDoneIndex = i;
                });

                allSteps.forEach((step, i) => {
                    const apiStep = timelineMap[step.status];
                    const isDone = apiStep && apiStep.done;
                    const isActive = (i === lastDoneIndex);
                    const timeText = isDone && apiStep.time ? apiStep.time : 'Menunggu';

                    const item = document.createElement('div');
                    item.className = 'tl-item' + (isDone ? ' done' : '') + (isActive ? ' active' : '');
                    item.innerHTML = '<div class="tl-dot"></div><div class="tl-body"><strong>' + step.label + '</strong><small>' + timeText + '</small></div>';
                    timelineEl.appendChild(item);
                });

                trackResult.style.display = 'block'; trackResult.style.animation = 'fadeUp .4s ease';
            } else {
                showToast(data.message || 'Tiket tidak ditemukan', 'warning');
            }
        } catch (err) {
            showToast('Gagal menghubungi server', 'error');
        }
    });
    trackInput.addEventListener('keydown', e => { if (e.key === 'Enter') trackBtn.click(); });

    // ========== DASBOR ANALITIK — Load statistik + inisialisasi grafik ==========
    async function loadDashboard() {
        try {
            const stats = await apiFetch(API + '/api/dashboard/stats', { headers: authHeaders() }).then(r => r.json());
            const statCards = document.querySelectorAll('.stat-card h2');
            if (statCards[0]) statCards[0].textContent = stats.total || 0;
            if (statCards[1]) statCards[1].textContent = stats.selesai || 0;
            if (statCards[2]) statCards[2].textContent = stats.dikerjakan || 0;
            if (statCards[3]) statCards[3].textContent = stats.baru || 0;
        } catch (err) { }

        if (!window._chartsReady) {
            initCharts();
            window._chartsReady = true;
        }
    }

    // ========== MANAJEMEN TIKET — Render Kanban Board (Baru / Proses / Selesai) ==========
    async function loadTickets() {
        try {
            const res = await apiFetch(API + '/api/tickets', { headers: authHeaders() });
            const data = await res.json();
            const tickets = data.data || data;

            const colBaru = document.querySelector('.kanban-col:nth-child(1) .kanban-cards');
            const colProses = document.querySelector('.kanban-col:nth-child(2) .kanban-cards');
            const colSelesai = document.querySelector('.kanban-col:nth-child(3) .kanban-cards');

            if (colBaru) colBaru.innerHTML = '';
            if (colProses) colProses.innerHTML = '';
            if (colSelesai) colSelesai.innerHTML = '';

            let cBaru = 0, cProses = 0, cSelesai = 0;

            tickets.forEach(t => {
                const loc = t.room ? (t.room.building ? t.room.building.name : '') + ' / R.' + t.room.room_number : '';
                const cat = t.category ? t.category.name : '';
                const tech = t.technician ? t.technician.name : '';
                const date = new Date(t.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });

                const card = document.createElement('div');
                card.className = 'k-card glass-card';

                card.style.cursor = 'pointer';
                card.addEventListener('click', () => openTicketModal(t));

                if (t.status === 'Baru' || t.status === 'Divalidasi') {
                    card.innerHTML = '<div class="k-tag red">' + t.status + '</div><h4>' + cat + '</h4><p class="dim">' + loc + '</p><p class="dim" style="font-size:.78rem;margin-top:2px">#' + t.ticket_code + '</p><div class="k-footer"><small class="dim">' + date + '</small></div>';
                    if (colBaru) colBaru.appendChild(card);
                    cBaru++;
                } else if (t.status === 'Ditugaskan' || t.status === 'Dikerjakan') {
                    card.innerHTML = '<div class="k-tag blue-tag">' + t.status + '</div><h4>' + cat + '</h4><p class="dim">' + loc + '</p><p class="dim" style="font-size:.78rem;margin-top:2px">#' + t.ticket_code + '</p><div class="k-footer"><small class="dim">Tek. ' + tech + '</small></div>';
                    if (colProses) colProses.appendChild(card);
                    cProses++;
                } else if (t.status === 'Selesai') {
                    card.className += ' done-card';
                    card.innerHTML = '<h4>' + cat + '</h4><p class="dim">' + loc + '</p><p class="dim" style="font-size:.78rem;margin-top:2px">#' + t.ticket_code + '</p><div class="k-footer"><i class="ph ph-check-circle" style="color:var(--green)"></i><small class="dim">Selesai ' + date + '</small></div>';
                    if (colSelesai) colSelesai.appendChild(card);
                    cSelesai++;
                }
            });

            const counts = document.querySelectorAll('.k-count');
            if (counts[0]) counts[0].textContent = cBaru;
            if (counts[1]) counts[1].textContent = cProses;
            if (counts[2]) counts[2].textContent = cSelesai;
        } catch (err) { }
    }

    // ========== MODAL DETAIL TIKET — Update status & tugaskan teknisi ==========
    async function openTicketModal(ticket) {
        let modal = document.getElementById('ticketModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'ticketModal';
            modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.6);display:flex;align-items:center;justify-content:center;z-index:9999;backdrop-filter:blur(4px)';
            modal.innerHTML = '<div class="glass-card" style="padding:2rem;min-width:450px;max-width:550px;max-height:85vh;overflow-y:auto"><h3 id="tmTitle" style="margin-bottom:1.5rem">Detail Tiket</h3><div id="tmPhotoWrap" style="display:none;margin-bottom:1.5rem;text-align:center"><img id="tmPhoto" style="max-width:100%;max-height:250px;border-radius:12px;object-fit:cover;border:1px solid rgba(255,255,255,.1)" alt="Bukti Foto"></div><div style="margin-bottom:1.5rem;display:grid;grid-template-columns:1fr 1fr;gap:1rem"><div class="dim">Pelapor:<br><strong style="color:#fff" id="tmRep"></strong></div><div class="dim">Lokasi:<br><strong style="color:#fff" id="tmLoc"></strong></div><div class="dim">Kategori:<br><strong style="color:#fff" id="tmCat"></strong></div><div class="dim">Tanggal:<br><strong style="color:#fff" id="tmDate"></strong></div><div class="dim" style="grid-column:1/-1">Deskripsi:<br><strong style="color:#fff;font-weight:400" id="tmDesc"></strong></div></div><form id="tmForm"><div class="form-field"><label>Status Tiket</label><select id="tmStatus" required style="width:100%;padding:.6rem;background:var(--card);color:var(--text);border:1px solid rgba(255,255,255,.1);border-radius:8px"><option value="Baru">Baru</option><option value="Divalidasi">Divalidasi</option><option value="Ditugaskan">Ditugaskan ke Teknisi</option><option value="Dikerjakan">Sedang Dikerjakan</option><option value="Selesai">Selesai</option></select></div><div class="form-field" id="tmTechGroup" style="display:none;margin-top:1rem"><label>Pilih Teknisi</label><select id="tmTech" style="width:100%;padding:.6rem;background:var(--card);color:var(--text);border:1px solid rgba(255,255,255,.1);border-radius:8px"></select></div><input type="hidden" id="tmId"><div style="display:flex;gap:1rem;justify-content:flex-end;margin-top:1.5rem"><button type="button" class="btn ghost" id="tmCancel">Tutup</button><button type="submit" class="btn primary">Simpan</button></div></form></div>';
            document.body.appendChild(modal);

            document.getElementById('tmCancel').addEventListener('click', () => { modal.style.display = 'none'; });
            modal.addEventListener('click', e => { if (e.target === modal) modal.style.display = 'none'; });

            const statusSel = document.getElementById('tmStatus');
            const techGroup = document.getElementById('tmTechGroup');
            statusSel.addEventListener('change', () => {
                techGroup.style.display = (statusSel.value === 'Ditugaskan' || statusSel.value === 'Dikerjakan') ? 'block' : 'none';
            });

            document.getElementById('tmForm').addEventListener('submit', async e => {
                e.preventDefault();
                const ticketId = document.getElementById('tmId').value;
                const status = statusSel.value;
                const technician_id = document.getElementById('tmTech').value;

                const payload = { status };
                if (status === 'Ditugaskan' || status === 'Dikerjakan') {
                    if (!technician_id) { showToast('Pilih teknisi terlebih dahulu!', 'warning'); return; }
                    payload.technician_id = technician_id;
                }

                try {
                    const res = await apiFetch(API + '/api/tickets/' + ticketId, {
                        method: 'PUT',
                        headers: { ...authHeaders(), 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    if (res.ok) {
                        modal.style.display = 'none';
                        loadTickets();
                        loadDashboard(); // Refresh stats too
                    } else {
                        const data = await res.json();
                        showToast('Error: ' + (data.message || 'Gagal menyimpan'), 'error');
                    }
                } catch (err) { showToast('Gagal terhubung ke server', 'error'); }
            });
        }

        document.getElementById('tmId').value = ticket.id;
        document.getElementById('tmTitle').textContent = 'Tiket #' + ticket.ticket_code;
        document.getElementById('tmRep').textContent = ticket.reporter_name + ' (WA: ' + ticket.reporter_phone + ')';
        document.getElementById('tmLoc').textContent = ticket.room ? ((ticket.room.building ? ticket.room.building.name : '') + ' / R.' + ticket.room.room_number) : '-';
        document.getElementById('tmCat').textContent = ticket.category ? ticket.category.name : '-';
        document.getElementById('tmDate').textContent = new Date(ticket.created_at).toLocaleString('id-ID');
        document.getElementById('tmDesc').textContent = ticket.description;

        const photoWrap = document.getElementById('tmPhotoWrap');
        const photoImg = document.getElementById('tmPhoto');
        if (ticket.photo_path) {
            photoImg.src = '/storage/' + ticket.photo_path;
            photoWrap.style.display = 'block';
        } else {
            photoWrap.style.display = 'none';
        }

        const statusSel = document.getElementById('tmStatus');
        statusSel.value = ticket.status;

        const techGroup = document.getElementById('tmTechGroup');
        const techSel = document.getElementById('tmTech');

        try {
            const techs = await apiFetch(API + '/api/technicians', { headers: authHeaders() }).then(r => r.json());
            techSel.innerHTML = '<option value="">-- Pilih Teknisi --</option>';
            techs.forEach(t => {
                const opt = document.createElement('option');
                opt.value = t.id;
                const isOnline = t.telegram_chat_id && t.status === 'aktif';
                opt.textContent = (isOnline ? '🟢 ' : '🔴 ') + t.name + (isOnline ? '' : ' (Nonaktif)');
                if (!isOnline) opt.style.color = '#7B8394';
                techSel.appendChild(opt);
            });
            if (ticket.technician_id) techSel.value = ticket.technician_id;
        } catch (e) { }

        techGroup.style.display = (ticket.status === 'Ditugaskan' || ticket.status === 'Dikerjakan') ? 'block' : 'none';
        modal.style.display = 'flex';
    }

    let masterBuildings = [];

    // ========== MASTER DATA ASET — Tabel ruangan + aset terdaftar per ruangan ==========
    async function loadMasterData() {
        try {
            masterBuildings = await apiFetch(API + '/api/buildings').then(r => r.json());
            const rooms = await apiFetch(API + '/api/rooms').then(r => r.json());

            const tbody = document.querySelector('#page-master table tbody');
            if (!tbody) return;
            tbody.innerHTML = '';

            const buildingMap = {};
            masterBuildings.forEach(b => { buildingMap[b.id] = b.name; });

            const catColors = {
                'AC': 'rgba(0,191,255,.18)',
                'Proyektor': 'rgba(138,43,226,.18)',
                'Kelistrikan': 'rgba(0,255,127,.18)',
                'Furnitur': 'rgba(255,99,71,.18)',
            };
            const catTextColors = {
                'AC': '#00BFFF',
                'Proyektor': '#BF7FFF',
                'Kelistrikan': '#00FF7F',
                'Furnitur': '#FF6347',
            };

            rooms.forEach(r => {
                const bName = buildingMap[r.building_id] || (r.building ? r.building.name : '-');
                const code = bName.replace('Gedung ', '') + '-' + String(r.id).padStart(3, '0');
                const tr = document.createElement('tr');

                const registeredAssets = r.registered_assets || [];
                const categories = r.categories || [];
                let catHtml = '';
                if (registeredAssets.length > 0) {
                    const condColor = { 'Baik': 'rgba(0,255,127,.18)', 'Rusak Ringan': 'rgba(255,215,0,.18)', 'Rusak Berat': 'rgba(255,99,71,.18)' };
                    const condText = { 'Baik': '#00FF7F', 'Rusak Ringan': '#FFD700', 'Rusak Berat': '#FF6347' };
                    catHtml = registeredAssets.map(a => {
                        const bg = condColor[a.condition] || 'rgba(255,255,255,.08)';
                        const color = condText[a.condition] || '#fff';
                        return `<span style="display:inline-block;padding:2px 9px;border-radius:20px;background:${bg};color:${color};font-size:.75rem;margin:2px 2px 2px 0;white-space:nowrap" title="${a.condition}">${a.name} ×${a.quantity}</span>`;
                    }).join('');
                } else if (categories.length > 0) {
                    catHtml = categories.map(c => {
                        const key = Object.keys(catColors).find(k => c.name.includes(k)) || '';
                        const bg = catColors[key] || 'rgba(255,255,255,.08)';
                        const color = catTextColors[key] || '#fff';
                        return `<span style="display:inline-block;padding:2px 8px;border-radius:20px;background:${bg};color:${color};font-size:.75rem;margin:2px 2px 2px 0;white-space:nowrap">${c.name}</span>`;
                    }).join('');
                } else {
                    catHtml = '<span style="color:var(--dim);font-size:.8rem">Belum ada aset</span>';
                }

                const hasRusak = registeredAssets.some(a => a.condition !== 'Baik');
                const totalTickets = r.total_tickets ?? 0;

                const tdId = document.createElement('td'); tdId.textContent = code; tdId.style.fontFamily = 'monospace'; tdId.style.fontSize = '.82rem';
                const tdName = document.createElement('td'); tdName.innerHTML = '<strong style="color:#fff">R.' + r.room_number + '</strong>';
                const tdCat = document.createElement('td'); tdCat.innerHTML = catHtml;
                const tdGedung = document.createElement('td'); tdGedung.textContent = bName;
                const tdTotal = document.createElement('td');
                tdTotal.innerHTML = totalTickets > 0
                    ? `<span style="color:#00BFFF;font-weight:600">${totalTickets}</span> <span class="dim" style="font-size:.78rem">tiket</span>`
                    : `<span class="dim">0</span>`;
                const tdCond = document.createElement('td');
                tdCond.innerHTML = hasRusak
                    ? '<span class="cond bad" style="background:rgba(255,99,71,.15);color:#FF6347">Ada Kerusakan</span>'
                    : '<span class="cond good">Baik</span>';
                const tdAksi = document.createElement('td');

                const editBtn = document.createElement('button');
                editBtn.className = 'btn ghost xs';
                editBtn.innerHTML = '<i class="ph ph-pencil-simple"></i>';
                editBtn.addEventListener('click', () => openRoomModal('edit', r.id, r.building_id, r.room_number, r.registered_assets || []));

                const delBtn = document.createElement('button');
                delBtn.className = 'btn ghost xs danger';
                delBtn.innerHTML = '<i class="ph ph-trash"></i>';
                delBtn.addEventListener('click', () => deleteRoom(r.id, 'Ruang ' + r.room_number));

                tdAksi.appendChild(editBtn);
                tdAksi.appendChild(document.createTextNode(' '));
                tdAksi.appendChild(delBtn);

                tr.appendChild(tdId); tr.appendChild(tdName); tr.appendChild(tdCat);
                tr.appendChild(tdGedung); tr.appendChild(tdTotal); tr.appendChild(tdCond); tr.appendChild(tdAksi);
                tbody.appendChild(tr);
            });

            if (rooms.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--dim)">Belum ada data ruangan</td></tr>';
            }
        } catch (err) { }
    }

    async function deleteRoom(id, name) {
        const confirmed = await showConfirm('Yakin ingin menghapus "' + name + '"? Data tidak bisa dikembalikan.', 'Hapus Data');
        if (!confirmed) return;
        try {
            const res = await apiFetch(API + '/api/rooms/' + id, { method: 'DELETE', headers: authHeaders() });
            if (res.ok) {
                showToast('Data berhasil dihapus', 'success');
                loadMasterData();
                loadDropdowns();
            } else {
                const data = await res.json();
                showToast(data.message || 'Gagal menghapus', 'error');
            }
        } catch (err) { showToast('Gagal menghubungi server', 'error'); }
    }

    let _allCategories = [];

    // ========== MODAL RUANGAN — Tambah/Edit ruangan + kelola inventaris aset ==========
    async function openRoomModal(mode, id, buildingId, roomNumber, registeredAssets = []) {
        if (_allCategories.length === 0) {
            _allCategories = await apiFetch(API + '/api/categories').then(r => r.json()).catch(() => []);
        }

        let modal = document.getElementById('roomModal');
        if (modal) modal.remove();

        modal = document.createElement('div');
        modal.id = 'roomModal';
        modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.65);display:flex;align-items:center;justify-content:center;z-index:9999;backdrop-filter:blur(6px)';

        const isEdit = mode === 'edit';
        modal.innerHTML = `
        <div class="glass-card" style="padding:2rem;width:520px;max-height:88vh;overflow-y:auto">
            <h3 id="roomModalTitle" style="margin-bottom:1.5rem">${isEdit ? 'Edit Ruangan' : 'Tambah Ruangan Baru'}</h3>

            ${isEdit ? `
            <div style="display:flex;gap:.5rem;margin-bottom:1.5rem;border-bottom:1px solid rgba(255,255,255,.08);padding-bottom:.5rem">
                <button type="button" id="tabInfo" class="btn primary sm">Info Ruangan</button>
                <button type="button" id="tabAsset" class="btn ghost sm">Inventaris Aset</button>
            </div>` : ''}

            <div id="panelInfo">
                <form id="roomModalForm">
                    <div class="form-field icon-field" style="margin-bottom:1rem">
                        <i class="ph ph-buildings"></i>
                        <div><label>Gedung</label>
                        <select id="modalGedung" required style="width:100%;padding:.5rem;background:var(--card);color:var(--text);border:1px solid rgba(255,255,255,.1);border-radius:8px"></select></div>
                    </div>
                    <div class="form-field icon-field" style="margin-bottom:1.5rem">
                        <i class="ph ph-door"></i>
                        <div><label>Nomor Ruangan</label>
                        <input type="text" id="modalRoomNumber" placeholder="Contoh: 103" required></div>
                    </div>
                    <input type="hidden" id="modalRoomId">
                    <div style="display:flex;gap:1rem;justify-content:flex-end">
                        <button type="button" class="btn ghost" id="modalCancel">Batal</button>
                        <button type="submit" class="btn primary">Simpan</button>
                    </div>
                </form>
            </div>

            ${isEdit ? `
            <div id="panelAsset" style="display:none">
                <div style="margin-bottom:1rem">
                    <label style="display:block;margin-bottom:.5rem;font-size:.85rem;color:var(--dim)">Tambah Aset Baru</label>
                    <div style="display:grid;grid-template-columns:1fr auto auto;gap:.5rem;align-items:end">
                        <div>
                            <label style="font-size:.78rem;color:var(--dim)">Kategori</label>
                            <select id="newAssetCat" style="width:100%;padding:.5rem;background:var(--card);color:var(--text);border:1px solid rgba(255,255,255,.1);border-radius:8px;margin-top:.2rem"></select>
                        </div>
                        <div>
                            <label style="font-size:.78rem;color:var(--dim)">Jumlah</label>
                            <input type="number" id="newAssetQty" min="1" max="999" value="1" style="width:70px;padding:.5rem;background:var(--card);color:var(--text);border:1px solid rgba(255,255,255,.1);border-radius:8px;margin-top:.2rem">
                        </div>
                        <button type="button" id="btnAddAsset" class="btn primary sm" style="margin-top:1.2rem"><i class="ph ph-plus"></i> Tambah</button>
                    </div>
                    <div style="margin-top:.5rem">
                        <label style="font-size:.78rem;color:var(--dim)">Kondisi</label>
                        <select id="newAssetCond" style="width:100%;padding:.45rem;background:var(--card);color:var(--text);border:1px solid rgba(255,255,255,.1);border-radius:8px;margin-top:.2rem">
                            <option value="Baik">Baik</option>
                            <option value="Rusak Ringan">Rusak Ringan</option>
                            <option value="Rusak Berat">Rusak Berat</option>
                        </select>
                    </div>
                </div>
                <div id="assetList" style="max-height:280px;overflow-y:auto"></div>
            </div>` : ''}
        </div>`;

        document.body.appendChild(modal);

        document.getElementById('modalCancel').addEventListener('click', () => modal.remove());
        modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });

        const selectGedung = document.getElementById('modalGedung');
        masterBuildings.forEach(b => {
            const opt = document.createElement('option');
            opt.value = b.id; opt.textContent = b.name;
            selectGedung.appendChild(opt);
        });

        if (isEdit) {
            document.getElementById('modalRoomId').value = id;
            selectGedung.value = buildingId;
            document.getElementById('modalRoomNumber').value = roomNumber;

            document.getElementById('tabInfo').addEventListener('click', () => {
                document.getElementById('panelInfo').style.display = '';
                document.getElementById('panelAsset').style.display = 'none';
                document.getElementById('tabInfo').className = 'btn primary sm';
                document.getElementById('tabAsset').className = 'btn ghost sm';
            });

            document.getElementById('tabAsset').addEventListener('click', () => {
                document.getElementById('panelInfo').style.display = 'none';
                document.getElementById('panelAsset').style.display = '';
                document.getElementById('tabInfo').className = 'btn ghost sm';
                document.getElementById('tabAsset').className = 'btn primary sm';
            });

            const catSel = document.getElementById('newAssetCat');
            _allCategories.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id; opt.textContent = c.name;
                catSel.appendChild(opt);
            });

            const renderAssetList = (assets) => {
                const condColor = { 'Baik': '#00FF7F', 'Rusak Ringan': '#FFD700', 'Rusak Berat': '#FF6347' };
                const list = document.getElementById('assetList');
                if (!assets || assets.length === 0) {
                    list.innerHTML = '<p style="color:var(--dim);font-size:.85rem;text-align:center;padding:1rem">Belum ada aset terdaftar</p>';
                    return;
                }
                list.innerHTML = assets.map(a => `
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:.6rem .8rem;background:rgba(255,255,255,.04);border-radius:8px;margin-bottom:.4rem;gap:.5rem">
                        <div>
                            <span style="font-weight:600;color:#fff">${a.name}</span>
                            <span style="color:var(--dim);font-size:.8rem"> × ${a.quantity}</span>
                            <span style="margin-left:.5rem;font-size:.75rem;color:${condColor[a.condition] || '#fff'}">${a.condition}</span>
                        </div>
                        <button type="button" data-asset-id="${a.id}" class="btn ghost xs danger del-asset-btn"><i class="ph ph-trash"></i></button>
                    </div>`).join('');

                list.querySelectorAll('.del-asset-btn').forEach(btn => {
                    btn.addEventListener('click', async () => {
                        const assetId = btn.dataset.assetId;
                        const res = await apiFetch(API + '/api/room-assets/' + assetId, { method: 'DELETE', headers: authHeaders() });
                        if (res.ok) {
                            const refreshed = await apiFetch(API + '/api/room-assets?room_id=' + id, { headers: authHeaders() }).then(r => r.json());
                            renderAssetList(refreshed.map(a => ({ id: a.id, name: a.category?.name ?? '-', quantity: a.quantity, condition: a.condition })));
                            loadMasterData();
                        } else { showToast('Gagal menghapus aset', 'error'); }
                    });
                });
            };

            renderAssetList(registeredAssets);

            document.getElementById('btnAddAsset').addEventListener('click', async () => {
                const payload = {
                    room_id: id,
                    category_id: document.getElementById('newAssetCat').value,
                    quantity: parseInt(document.getElementById('newAssetQty').value) || 1,
                    condition: document.getElementById('newAssetCond').value,
                };
                const res = await apiFetch(API + '/api/room-assets', {
                    method: 'POST',
                    headers: { ...authHeaders(), 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                if (res.ok || res.status === 201) {
                    showToast('Aset berhasil ditambahkan', 'success');
                    const refreshed = await apiFetch(API + '/api/room-assets?room_id=' + id, { headers: authHeaders() }).then(r => r.json());
                    renderAssetList(refreshed.map(a => ({ id: a.id, name: a.category?.name ?? '-', quantity: a.quantity, condition: a.condition })));
                    loadMasterData();
                } else {
                    const d = await res.json();
                    showToast('Error: ' + (d.message || 'Gagal menyimpan'), 'error');
                }
            });
        } else {
            document.getElementById('modalRoomId').value = '';
            document.getElementById('modalRoomNumber').value = '';
        }

        document.getElementById('roomModalForm').addEventListener('submit', async e => {
            e.preventDefault();
            const roomId = document.getElementById('modalRoomId').value;
            const payload = { building_id: selectGedung.value, room_number: document.getElementById('modalRoomNumber').value };
            try {
                const url = roomId ? API + '/api/rooms/' + roomId : API + '/api/rooms';
                const res = await apiFetch(url, {
                    method: roomId ? 'PUT' : 'POST',
                    headers: { ...authHeaders(), 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok || res.status === 201) {
                    modal.remove();
                    loadMasterData();
                    loadDropdowns();
                    showToast('Ruangan berhasil disimpan', 'success');
                } else {
                    const errors = data.errors ? Object.values(data.errors).flat().join(', ') : data.message;
                    showToast('Error: ' + errors, 'error');
                }
            } catch (err) { showToast('Gagal menghubungi server', 'error'); }
        });

        modal.style.display = 'flex';
    }

    const addAssetBtn = document.querySelector('#page-master .card-head .btn');
    if (addAssetBtn) {
        addAssetBtn.addEventListener('click', () => {
            if (masterBuildings.length === 0) {
                apiFetch(API + '/api/buildings').then(r => r.json()).then(b => { masterBuildings = b; openRoomModal('add'); });
            } else {
                openRoomModal('add');
            }
        });
    }

    // ========== QR CODE GENERATOR — Buat QR Code unik per ruangan ==========
    const generateQRBtn = document.getElementById('generateQR');
    generateQRBtn.addEventListener('click', () => {
        const gedung = document.getElementById('qrGedung').value;
        const ruang = document.getElementById('qrRuang').value.trim();
        if (!ruang) return;

        const gedungCode = gedung.replace('Gedung ', '');
        const url = window.location.origin + '/?g=' + gedungCode + '&r=' + ruang;

        const qr = qrcode(0, 'M');
        qr.addData(url);
        qr.make();

        document.getElementById('qrImage').innerHTML = qr.createImgTag(5, 8);
        document.getElementById('qrLabel').textContent = gedung + ' / Ruang ' + ruang;
        document.getElementById('qrUrl').textContent = url;
        document.getElementById('qrResult').style.display = 'block';
        document.getElementById('qrResult').style.animation = 'fadeUp .4s ease';

        const grid = document.getElementById('qrGrid');
        const existingItem = grid.querySelector('[data-loc="' + gedungCode + '-' + ruang + '"]');
        if (!existingItem) {
            const div = document.createElement('div');
            div.className = 'qr-grid-item';
            div.setAttribute('data-loc', gedungCode + '-' + ruang);
            const qr2 = qrcode(0, 'M');
            qr2.addData(url);
            qr2.make();
            div.innerHTML = qr2.createImgTag(3, 4) + '<p>' + gedung + ' / R.' + ruang + '</p>';
            grid.appendChild(div);
        }
    });

    // ========== GRAFIK CHART.JS — 4 grafik: Tren Mingguan, Distribusi Kategori, Performa Gedung, Waktu Respon ==========
    async function initCharts() {
        Chart.defaults.color = '#7B8394';
        Chart.defaults.font.family = "'Outfit', sans-serif";

        let weeklyData = { labels: ['W1', 'W2', 'W3', 'W4', 'W5', 'W6', 'W7', 'W8'], data: [0, 0, 0, 0, 0, 0, 0, 0] };
        let categoryData = { labels: ['AC', 'Proyektor', 'Kelistrikan', 'Furnitur'], data: [0, 0, 0, 0] };
        let buildingData = { labels: ['Ged.A', 'Ged.B', 'Ged.C', 'Ged.D', 'Ged.E', 'Ged.F', 'Ged.G', 'Ged.H'], data: [0, 0, 0, 0, 0, 0, 0, 0] };
        let responseTimeData = { labels: [], data: [] };

        try {
            const [wRes, cRes, bRes, rtRes] = await Promise.all([
                apiFetch(API + '/api/dashboard/chart/weekly', { headers: authHeaders() }).then(r => r.json()),
                apiFetch(API + '/api/dashboard/chart/category', { headers: authHeaders() }).then(r => r.json()),
                apiFetch(API + '/api/dashboard/chart/building', { headers: authHeaders() }).then(r => r.json()),
                apiFetch(API + '/api/dashboard/chart/response-time', { headers: authHeaders() }).then(r => r.json())
            ]);
            if (wRes.labels) weeklyData = wRes;
            if (cRes.labels) categoryData = cRes;
            if (bRes.labels) buildingData = bRes;
            if (rtRes.labels) responseTimeData = rtRes;
        } catch (err) { }

        const ctxL = document.getElementById('chartLine').getContext('2d');
        const grad = ctxL.createLinearGradient(0, 0, 0, 280);
        grad.addColorStop(0, 'rgba(138,43,226,.35)');
        grad.addColorStop(1, 'rgba(10,15,26,0)');

        new Chart(ctxL, {
            type: 'line', data: {
                labels: weeklyData.labels,
                datasets: [{ label: 'Laporan Masuk', data: weeklyData.data, borderColor: '#8A2BE2', backgroundColor: grad, borderWidth: 3, pointBackgroundColor: '#0A0F1A', pointBorderColor: '#00FF7F', pointBorderWidth: 3, pointRadius: 5, pointHoverRadius: 7, fill: true, tension: .4 }]
            }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { backgroundColor: 'rgba(10,15,26,.95)', bodyColor: '#00FF7F', borderColor: 'rgba(255,255,255,.1)', borderWidth: 1, padding: 12, displayColors: false } }, scales: { y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,.04)', borderDash: [4, 4] }, border: { display: false } }, x: { grid: { display: false }, border: { display: false } } } }
        });

        new Chart(document.getElementById('chartDonut'), {
            type: 'doughnut', data: { labels: categoryData.labels, datasets: [{ data: categoryData.data, backgroundColor: ['#00BFFF', '#8A2BE2', '#00FF7F', '#374151'], borderWidth: 0, hoverOffset: 8 }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '72%', plugins: { legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true, pointStyle: 'circle' } } } }
        });

        new Chart(document.getElementById('chartBar'), {
            type: 'bar', data: { labels: buildingData.labels, datasets: [{ label: 'Total Laporan', data: buildingData.data, backgroundColor: 'rgba(0,191,255,.6)', borderRadius: 6, barThickness: 20 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,.04)' }, border: { display: false } }, x: { grid: { display: false }, border: { display: false } } } }
        });

        const rtColors = ['rgba(0,191,255,.5)', 'rgba(138,43,226,.5)', 'rgba(0,255,127,.5)', 'rgba(255,99,71,.5)', 'rgba(255,215,0,.5)', 'rgba(255,105,180,.5)'];
        const rtBg = responseTimeData.data.map((_, i) => rtColors[i % rtColors.length]);

        new Chart(document.getElementById('chartBarH'), {
            type: 'bar', data: { labels: responseTimeData.labels.length ? responseTimeData.labels : ['Belum ada data'], datasets: [{ label: 'Jam', data: responseTimeData.data.length ? responseTimeData.data : [0], backgroundColor: rtBg.length ? rtBg : ['rgba(100,100,100,.3)'], borderRadius: 6, barThickness: 30 }] },
            options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, grid: { color: 'rgba(255,255,255,.04)' }, border: { display: false } }, y: { grid: { display: false }, border: { display: false } } } }
        });
    }

    // ========== INISIALISASI AWAL — Cek token tersimpan, jika valid langsung masuk dashboard ==========
    const savedToken = localStorage.getItem('token');
    if (savedToken) {
        apiFetch(API + '/api/auth/me', { headers: authHeaders() })
            .then(r => { if (!r.ok) throw new Error(); return r.json(); })
            .then(user => {
                isAdmin = true;
                adminLabel.style.display = '';
                adminLinks.forEach(l => l.style.display = '');
                if (navDividerAdmin) navDividerAdmin.style.display = '';
                logoutBtn.style.display = '';
                document.getElementById('uName').textContent = user.name;
                document.getElementById('uRole').textContent = 'Admin Sarpras';
                document.getElementById('uAvatar').src = 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.name) + '&background=131B2A&color=8A2BE2&bold=true&size=80';

                const page = getPageFromPath();
                switchPage(page === 'report' ? 'dashboard' : page);
            })
            .catch(() => {
                localStorage.removeItem('token');
                const page = getPageFromPath();
                switchPage(page);
            });
    } else {
        const page = getPageFromPath();
        switchPage(page);
    }
});
