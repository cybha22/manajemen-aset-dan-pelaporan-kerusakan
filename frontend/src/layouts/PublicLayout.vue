<template>
  <div class="public-layout">
    <header class="public-header">
      <div class="public-brand" @click="goTo('/')">
        <div class="logo-icon"><img src="/logo2.svg" alt="AsetLink Logo"></div>
        <div>
          <div class="logo-name">AsetLink</div>
          <div class="logo-sub">SMART System</div>
        </div>
      </div>
      <button
        v-if="$route.name === 'report'"
        class="btn primary sm public-nav-btn"
        @click="goTo('/track')"
      >
        <i class="ph ph-binoculars"></i><span>Lacak Tiket</span>
      </button>
      <button
        v-else
        class="btn primary sm public-nav-btn"
        @click="goTo('/')"
      >
        <i class="ph ph-pencil-line"></i><span>Buat Laporan</span>
      </button>
    </header>

    <div class="public-content">
      <router-view v-slot="{ Component }">
        <keep-alive>
          <component :is="Component" />
        </keep-alive>
      </router-view>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'

const router = useRouter()

function goTo(path) {
  if (router.currentRoute.value.path === path) return
  router.push(path)
}
</script>

<style scoped>
.public-layout {
  position: relative;
  z-index: 1;
  min-height: 100vh;
  min-height: 100dvh;
  display: flex;
  flex-direction: column;
}

.public-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 32px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(10, 15, 26, 0.5);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  position: sticky;
  top: 0;
  z-index: 50;
  gap: 12px;
}

.public-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  transition: opacity 0.25s;
  flex-shrink: 0;
}

.public-brand:hover {
  opacity: 0.8;
}

.public-nav-btn {
  flex-shrink: 0;
  white-space: nowrap;
}

.public-content {
  flex: 1;
  padding: 28px 32px;
  width: 100%;
  max-width: 780px;
  margin: 0 auto;
  box-sizing: border-box;
}

@media (max-width: 1024px) {
  .public-header {
    padding: 16px 24px;
  }
  .public-content {
    padding: 24px;
  }
}

@media (max-width: 600px) {
  .public-header {
    padding: 12px 16px;
  }
  .public-content {
    padding: 16px;
  }
  .public-brand .logo-name {
    font-size: 1.25rem;
  }
  .public-brand .logo-sub {
    font-size: 0.6rem;
  }
}

@media (max-width: 380px) {
  .public-header {
    padding: 10px 12px;
    gap: 8px;
  }
  .public-content {
    padding: 12px 10px;
  }
  .public-nav-btn {
    padding: 8px 12px !important;
    font-size: 0.8rem !important;
  }
  .public-brand .logo-icon {
    width: 28px;
    height: 28px;
  }
  .public-brand .logo-name {
    font-size: 1.1rem;
  }
}
</style>
