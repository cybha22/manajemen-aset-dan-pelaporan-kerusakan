<template>
  <div class="app" id="appMain">
    <aside class="sidebar" :class="{ open: sidebarOpen }" id="sidebar">
      <div class="sidebar-top">
        <div class="logo-row">
          <div class="logo-icon"><img src="/logo2.svg" alt="AsetLink Logo"></div>
          <div>
            <div class="logo-name">AsetLink</div>
            <div class="logo-sub">SMART System</div>
          </div>
          <button class="sidebar-close" @click="sidebarOpen = false"><i class="ph ph-x"></i></button>
        </div>
      </div>

      <nav class="sidebar-nav">
        <div class="nav-label">Menu Publik</div>
        <button class="nav-link" :class="{ active: $route.name === 'report' }" @click="goTo('/')">
          <i class="ph ph-pencil-line"></i><span>Buat Laporan</span>
        </button>
        <button class="nav-link" :class="{ active: $route.name === 'track' }" @click="goTo('/track')">
          <i class="ph ph-binoculars"></i><span>Lacak Tiket</span>
        </button>

        <template v-if="auth.isAdmin">
          <div class="nav-label">Panel Admin</div>
          <button class="nav-link" :class="{ active: $route.name === 'dashboard' }" @click="goTo('/dashboard')">
            <i class="ph ph-chart-pie-slice"></i><span>Dasbor Analitik</span>
          </button>
          <button class="nav-link" :class="{ active: $route.name === 'tickets' }" @click="goTo('/tickets')">
            <i class="ph ph-kanban"></i><span>Manajemen Tiket</span>
          </button>
          <button class="nav-link" :class="{ active: $route.name === 'master' }" @click="goTo('/master')">
            <i class="ph ph-database"></i><span>Master Data Aset</span>
          </button>
          <button class="nav-link" :class="{ active: $route.name === 'qrcode' }" @click="goTo('/qrcode')">
            <i class="ph ph-qr-code"></i><span>QR Code Ruangan</span>
          </button>
          <button class="nav-link" :class="{ active: $route.name === 'telegram' }" @click="goTo('/telegram')">
            <i class="ph ph-telegram-logo"></i><span>Info Bot Teknisi</span>
          </button>
        </template>

        <div class="nav-divider" v-if="auth.isAdmin"></div>
        <button v-if="auth.isAdmin" class="nav-link" @click="handleLogout">
          <i class="ph ph-sign-out"></i><span>Logout</span>
        </button>
      </nav>

      <div class="sidebar-bottom">
        <div class="system-badge">
          <span class="dot-pulse"></span> System Online
        </div>
        <div class="sidebar-copy">&copy; 2026 Sarpras ITATS</div>
      </div>
    </aside>
    <div class="sidebar-overlay" v-if="sidebarOpen" @click="sidebarOpen = false"></div>

    <main class="main">
      <header class="topbar">
        <button class="hamburger" @click="sidebarOpen = !sidebarOpen"><i class="ph ph-list"></i></button>
        <div class="topbar-title">
          <h2>{{ $route.meta.title || '' }}</h2>
          <p>{{ $route.meta.sub || '' }}</p>
        </div>
        <div class="topbar-right">
        </div>
      </header>

      <div class="page-content">
        <router-view v-slot="{ Component }">
          <transition name="page-fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth.js'

const auth = useAuthStore()
const router = useRouter()
const sidebarOpen = ref(false)

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
