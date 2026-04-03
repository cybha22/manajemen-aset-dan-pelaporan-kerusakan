<template>
  <section class="page active">
    <div class="card-grid single">
      <div class="glass-card large-card">
        <div class="card-head">
          <div>
            <h3>Lacak Status Tiket</h3>
            <p class="dim">Masukkan ID Tiket untuk melihat progres perbaikan.</p>
          </div>
        </div>
        <div class="search-bar">
          <i class="ph ph-magnifying-glass"></i>
          <input type="text" v-model="trackCode" placeholder="Contoh: TK-83921" @keydown.enter="doTrack">
          <button class="btn primary sm" @click="doTrack">Lacak</button>
        </div>
        <div class="track-result" v-if="result" style="animation: fadeUp .4s ease">
          <div class="track-info-row">
            <div class="track-info-item"><span class="dim">ID Tiket</span><strong>{{ result.ticket_code }}</strong></div>
            <div class="track-info-item"><span class="dim">Lokasi</span><strong>{{ result.location }}</strong></div>
            <div class="track-info-item"><span class="dim">Aset</span><strong>{{ result.category }}</strong></div>
            <div class="track-info-item"><span class="dim">Tanggal Lapor</span><strong>{{ result.created_at }}</strong></div>
          </div>
          <div class="timeline">
            <div v-for="(step, i) in timelineSteps" :key="i" class="tl-item" :class="{ done: step.isDone, active: step.isActive }">
              <div class="tl-dot"></div>
              <div class="tl-body">
                <strong>{{ step.label }}</strong>
                <small>{{ step.time }}</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, computed } from 'vue'
import api from '../services/api.js'
import { useToast } from '../composables/useToast.js'

const { showToast } = useToast()
const trackCode = ref('')
const result = ref(null)
const rawTimeline = ref([])

const allSteps = [
  { status: 'Baru', label: 'Laporan Diterima' },
  { status: 'Divalidasi', label: 'Divalidasi Admin' },
  { status: 'Ditugaskan', label: 'Ditugaskan ke Teknisi' },
  { status: 'Dikerjakan', label: 'Sedang Dikerjakan' },
  { status: 'Selesai', label: 'Perbaikan Selesai' },
]

const timelineSteps = computed(() => {
  const map = {}
  if (rawTimeline.value) {
    rawTimeline.value.forEach((t) => { map[t.status] = t })
  }
  let lastDoneIndex = -1
  allSteps.forEach((s, i) => {
    if (map[s.status] && map[s.status].done) lastDoneIndex = i
  })
  return allSteps.map((s, i) => {
    const api = map[s.status]
    const isDone = api && api.done
    const isActive = i === lastDoneIndex
    const time = isDone && api.time ? api.time : 'Menunggu'
    return { ...s, isDone, isActive, time }
  })
})

async function doTrack() {
  const val = trackCode.value.trim()
  if (!val) return
  try {
    const res = await api.get('/api/tickets/track/' + val)
    result.value = res.data
    rawTimeline.value = res.data.timeline || []
  } catch (err) {
    const msg = err.response?.data?.message || 'Tiket tidak ditemukan'
    showToast(msg, 'warning')
    result.value = null
  }
}
</script>
