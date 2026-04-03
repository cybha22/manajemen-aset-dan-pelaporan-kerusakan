<template>
  <teleport to="body">
    <div v-if="visible" class="confirm-overlay" @click.self="cancel">
      <div class="confirm-box">
        <div class="confirm-icon"><i class="ph ph-warning-circle"></i></div>
        <h4>{{ title }}</h4>
        <p>{{ message }}</p>
        <div class="confirm-actions">
          <button class="btn ghost" @click="cancel">Batal</button>
          <button class="btn danger-fill" @click="ok">Ya, Hapus</button>
        </div>
      </div>
    </div>
  </teleport>
</template>

<script setup>
import { ref } from 'vue'

const visible = ref(false)
const title = ref('Konfirmasi')
const message = ref('')
let resolvePromise = null

function show(msg, ttl = 'Hapus Data') {
  title.value = ttl
  message.value = msg
  visible.value = true
  return new Promise((resolve) => {
    resolvePromise = resolve
  })
}

function cancel() {
  visible.value = false
  if (resolvePromise) resolvePromise(false)
}

function ok() {
  visible.value = false
  if (resolvePromise) resolvePromise(true)
}

defineExpose({ show })
</script>
