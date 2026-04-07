<template>
  <div class="ambient-bg" :class="{ 'ambient-bg-lite': isPublicGuest }">
    <template v-if="!isPublicGuest">
      <div class="blob b1"></div>
      <div class="blob b2"></div>
      <div class="blob b3"></div>
      <div class="blob b4"></div>
      <div class="noise"></div>
    </template>
  </div>
  <router-view v-if="$route.meta.isLogin" />
  <PublicLayout v-else-if="$route.meta.public && !auth.isAdmin" />
  <AppLayout v-else />
  <ToastContainer ref="toastRef" />
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useAuthStore } from './stores/auth.js'
import { useRoute, useRouter } from 'vue-router'
import { provideToast } from './composables/useToast.js'
import AppLayout from './layouts/AppLayout.vue'
import PublicLayout from './layouts/PublicLayout.vue'
import ToastContainer from './components/ToastContainer.vue'

const { toastRef } = provideToast()
const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const isPublicGuest = computed(() => route.meta.public && !auth.isAdmin)

onMounted(async () => {
  if (route.meta.public && route.path !== '/') return
  const ok = await auth.checkAuth()
  if (ok && router.currentRoute.value.path === '/') {
    router.replace('/dashboard')
  }
})
</script>
