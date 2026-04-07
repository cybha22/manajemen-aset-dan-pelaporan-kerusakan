import { createRouter, createWebHistory } from 'vue-router'
import ReportPage from '../pages/ReportPage.vue'
import TrackPage from '../pages/TrackPage.vue'
import LoginPage from '../pages/LoginPage.vue'

const DashboardPage = () => import('../pages/DashboardPage.vue')
const TicketsPage = () => import('../pages/TicketsPage.vue')
const MasterPage = () => import('../pages/MasterPage.vue')
const QrCodePage = () => import('../pages/QrCodePage.vue')
const TelegramPage = () => import('../pages/TelegramPage.vue')

const routes = [
    { path: '/', name: 'report', component: ReportPage, meta: { title: 'Buat Laporan Kerusakan', sub: 'Isi data secara lengkap untuk memproses tiket baru.', public: true } },
    { path: '/report', redirect: '/' },
    { path: '/track', name: 'track', component: TrackPage, meta: { title: 'Lacak Status Tiket', sub: 'Pantau progres perbaikan aset Anda secara real-time.', public: true } },
    { path: '/login', name: 'login', component: LoginPage, meta: { title: 'Login Admin', sub: '', isLogin: true } },
    { path: '/dashboard', name: 'dashboard', component: DashboardPage, meta: { title: 'Dasbor Analitik', sub: 'Statistik performa pemeliharaan ruang kelas bulan ini.', admin: true } },
    { path: '/tickets', name: 'tickets', component: TicketsPage, meta: { title: 'Manajemen Tiket', sub: 'Kelola penugasan dan pantau status seluruh tiket.', admin: true } },
    { path: '/master', name: 'master', component: MasterPage, meta: { title: 'Master Data Aset', sub: 'Inventarisasi aset kelas Gedung A hingga H.', admin: true } },
    { path: '/qrcode', name: 'qrcode', component: QrCodePage, meta: { title: 'QR Code Generator Ruangan', sub: 'Generate dan cetak QR Code unik untuk setiap ruangan.', admin: true } },
    { path: '/telegram', name: 'telegram', component: TelegramPage, meta: { title: 'Integrasi Bot Telegram Teknisi', sub: 'Alur koordinasi tugas teknisi via Chatbot Telegram.', admin: true } },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

router.beforeEach((to, from, next) => {
    if (to.meta.admin) {
        const token = localStorage.getItem('token')
        if (!token) {
            next('/login')
            return
        }
    }
    next()
})

export default router
