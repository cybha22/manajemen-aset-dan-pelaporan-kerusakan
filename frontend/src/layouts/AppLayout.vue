<template>
  <div class="app" id="appMain">
    <aside class="sidebar" :class="{ open: sidebarOpen, collapsed: sidebarCollapsed }" id="sidebar">
      <div class="sidebar-top">
        <div class="logo-row">
          <div class="logo-icon"><img src="/logo2.svg" alt="AsetLink Logo"></div>
          <div class="logo-text">
            <div class="logo-name">AsetLink</div>
            <div class="logo-sub">SMART System</div>
          </div>
          <button type="button" class="sidebar-collapse" :aria-label="sidebarCollapsed ? 'Perluas sidebar' : 'Ciutkan sidebar'" @click="toggleSidebarCollapse">
            <i :class="sidebarCollapsed ? 'ph ph-caret-right' : 'ph ph-caret-left'"></i>
          </button>
          <button type="button" class="sidebar-close" aria-label="Tutup sidebar" @click="sidebarOpen = false"><i class="ph ph-x"></i></button>
        </div>
      </div>

      <nav class="sidebar-nav">
        <div class="nav-label">Menu Publik</div>
        <button type="button" class="nav-link" title="Buat Laporan" :class="{ active: $route.name === 'report' }" @click="goTo('/')">
          <i class="ph ph-note-pencil"></i><span>Buat Laporan</span>
        </button>
        <button type="button" class="nav-link" title="Lacak Tiket" :class="{ active: $route.name === 'track' }" @click="goTo('/track')">
          <i class="ph ph-binoculars"></i><span>Lacak Tiket</span>
        </button>

        <template v-if="auth.isAdmin">
          <div class="nav-label">Panel Admin</div>
          <button type="button" class="nav-link" title="Dasbor Analitik" :class="{ active: $route.name === 'dashboard' }" @click="goTo('/dashboard')">
            <i class="ph ph-chart-line-up"></i><span>Dasbor Analitik</span>
          </button>
          <button type="button" class="nav-link" title="Manajemen Tiket" :class="{ active: $route.name === 'tickets' }" @click="goTo('/tickets')">
            <i class="ph ph-columns"></i><span>Manajemen Tiket</span>
          </button>
          <button type="button" class="nav-link" title="Master Data Aset" :class="{ active: $route.name === 'master' }" @click="goTo('/master')">
            <i class="ph ph-archive"></i><span>Master Data Aset</span>
          </button>
          <button type="button" class="nav-link" title="QR Code Ruangan" :class="{ active: $route.name === 'qrcode' }" @click="goTo('/qrcode')">
            <i class="ph ph-qr-code"></i><span>QR Code Ruangan</span>
          </button>
          <button type="button" class="nav-link" title="Info Bot Teknisi" :class="{ active: $route.name === 'telegram' }" @click="goTo('/telegram')">
            <i class="ph ph-paper-plane-tilt"></i><span>Info Bot Teknisi</span>
          </button>
        </template>

        <div class="nav-divider" v-if="auth.isAdmin"></div>
        <button v-if="auth.isAdmin" type="button" class="nav-link" title="Logout" @click="handleLogout">
          <i class="ph ph-sign-out"></i><span>Logout</span>
        </button>
      </nav>

      <div class="sidebar-bottom">
        <div class="system-badge">
          <span class="dot-pulse"></span><span class="system-text">System Online</span>
        </div>
        <div class="sidebar-copy">&copy; 2026 Sarpras ITATS</div>
      </div>
    </aside>
    <div class="sidebar-overlay" v-if="sidebarOpen" @click="sidebarOpen = false"></div>

    <main class="main" :class="{ 'sidebar-collapsed': sidebarCollapsed }">
      <header class="topbar">
        <button type="button" class="hamburger" :aria-label="sidebarToggleLabel" @click="toggleNavigation"><i :class="sidebarToggleIcon"></i></button>
        <div class="topbar-title">
          <h2>{{ $route.meta.title || '' }}</h2>
          <p>{{ $route.meta.sub || '' }}</p>
        </div>
        <div class="topbar-right">
        </div>
      </header>

      <div class="page-content">
        <router-view v-slot="{ Component, route }">
          <component :is="Component" :key="route.fullPath" />
        </router-view>
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth.js'

const auth = useAuthStore()
const router = useRouter()
const sidebarOpen = ref(false)
const sidebarCollapsed = ref(localStorage.getItem('sidebar-collapsed') === '1')

const sidebarToggleIcon = computed(() => {
  if (sidebarOpen.value) return 'ph ph-x'
  return sidebarCollapsed.value ? 'ph ph-caret-right' : 'ph ph-list'
})

const sidebarToggleLabel = computed(() => {
  if (sidebarOpen.value) return 'Tutup sidebar'
  return sidebarCollapsed.value ? 'Perluas sidebar' : 'Ciutkan sidebar'
})

function isDesktopViewport() {
  return window.matchMedia('(min-width: 901px)').matches
}

function setSidebarCollapsed(value) {
  sidebarCollapsed.value = value
  localStorage.setItem('sidebar-collapsed', value ? '1' : '0')
}

function toggleSidebarCollapse() {
  setSidebarCollapsed(!sidebarCollapsed.value)
}

function toggleNavigation() {
  if (isDesktopViewport()) {
    toggleSidebarCollapse()
    return
  }

  sidebarOpen.value = !sidebarOpen.value
}

function goTo(path) {
  router.push(path)
  sidebarOpen.value = false
}

async function handleLogout() {
  await auth.logout()
  router.push('/')
}
</script>

<style scoped>
.page-fade-enter-active,
.page-fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}
.page-fade-enter-from {
  opacity: 0;
  transform: translateY(12px);
}
.page-fade-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
