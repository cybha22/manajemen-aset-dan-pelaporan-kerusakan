<template>
  <div class="login-screen" style="display:flex;">
    <div class="login-wrapper">
      <div class="login-brand">
        <div class="login-brand-row">
          <div class="logo-icon large"><img src="/logo2.svg" alt="AsetLink Logo"></div>
          <div class="login-brand-text">
            <h1>AsetLink</h1>
            <span class="logo-sub">Modul Otentikasi Admin</span>
          </div>
        </div>
      </div>
      <div class="glass-card login-card">
        <h3>Masuk ke Panel Admin</h3>
        <p class="dim">Hanya untuk staf Unit Sarana dan Prasarana ITATS.</p>
        <form @submit.prevent="doLogin">
          <div class="form-field icon-field">
            <i class="ph ph-user"></i>
            <div><label>Username</label><input type="text" v-model="username" placeholder="admin" required></div>
          </div>
          <div class="form-field icon-field">
            <i class="ph ph-lock-key"></i>
            <div><label>Password</label><input type="password" v-model="password" placeholder="********" required></div>
          </div>
          <div class="login-error" v-if="errorMsg">{{ errorMsg }}</div>
          <button type="submit" class="btn primary full-w">
            <span>Masuk</span><i class="ph ph-sign-in"></i>
          </button>
        </form>
        <button class="btn ghost full-w mt" @click="goBack">
          <i class="ph ph-arrow-left"></i><span>Kembali ke Menu Publik</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth.js'

const auth = useAuthStore()
const router = useRouter()
const username = ref('')
const password = ref('')
const errorMsg = ref('')

async function doLogin() {
  errorMsg.value = ''
  try {
    await auth.login(username.value.trim(), password.value.trim())
    router.push('/dashboard')
  } catch (err) {
    const msg = err.response?.data?.message
    errorMsg.value = msg || 'Gagal terhubung ke server.'
  }
}

function goBack() {
  router.push('/')
}
</script>
