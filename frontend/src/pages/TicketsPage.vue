<template>
  <section class="page active tickets-page">
    <div class="kanban-board">
      <div class="kanban-col">
        <div class="kanban-header"><span class="k-dot red-dot"></span> Baru Masuk <span class="k-count">{{ colBaru.length }}</span></div>
        <div class="kanban-cards">
          <div v-for="t in colBaru" :key="t.id" class="k-card glass-card" @click="openModal(t)">
            <div class="k-tag red">{{ t.status }}</div>
            <h4>{{ t.category?.name }}</h4>
            <p class="dim">{{ formatLoc(t) }}</p>
            <p class="dim k-code">#{{ t.ticket_code }}</p>
            <div class="k-footer"><small class="dim">{{ formatDate(t.created_at) }}</small></div>
          </div>
        </div>
      </div>
      <div class="kanban-col">
        <div class="kanban-header"><span class="k-dot blue-dot"></span> Sedang Dikerjakan <span class="k-count">{{ colProses.length }}</span></div>
        <div class="kanban-cards">
          <div v-for="t in colProses" :key="t.id" class="k-card glass-card" @click="openModal(t)">
            <div class="k-tag blue-tag">{{ t.status }}</div>
            <h4>{{ t.category?.name }}</h4>
            <p class="dim">{{ formatLoc(t) }}</p>
            <p class="dim k-code">#{{ t.ticket_code }}</p>
            <div class="k-footer"><small class="dim">Tek. {{ t.technician?.name }}</small></div>
          </div>
        </div>
      </div>
      <div class="kanban-col">
        <div class="kanban-header"><span class="k-dot green-dot"></span> Selesai <span class="k-count">{{ colSelesai.length }}</span></div>
        <div class="kanban-cards">
          <div v-for="t in colSelesai" :key="t.id" class="k-card glass-card done-card" @click="openModal(t)">
            <h4>{{ t.category?.name }}</h4>
            <p class="dim">{{ formatLoc(t) }}</p>
            <p class="dim k-code">#{{ t.ticket_code }}</p>
            <div class="k-footer done-footer"><i class="ph ph-check-circle"></i><small class="dim">Selesai {{ formatDate(t.resolved_at || t.updated_at || t.created_at) }}</small></div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="pagination.total > 0" class="tickets-pagination glass-card">
      <div class="pagination-info">
        <strong>{{ pagination.from || 0 }}-{{ pagination.to || 0 }}</strong>
        <span>dari {{ pagination.total }} tiket</span>
      </div>
      <div class="pagination-actions">
        <button type="button" class="page-btn page-btn-wide" :disabled="pagination.current_page <= 1" @click="changePage(pagination.current_page - 1)">
          <i class="ph ph-caret-left"></i>
          Sebelumnya
        </button>
        <button v-for="page in visiblePages" :key="page" type="button" class="page-btn page-number" :class="{ active: page === pagination.current_page }" @click="changePage(page)">
          {{ page }}
        </button>
        <button type="button" class="page-btn page-btn-wide" :disabled="pagination.current_page >= pagination.last_page" @click="changePage(pagination.current_page + 1)">
          Berikutnya
          <i class="ph ph-caret-right"></i>
        </button>
      </div>
      <div class="pagination-page">
        Halaman {{ pagination.current_page }} dari {{ pagination.last_page }}
      </div>
    </div>

    <teleport to="body">
      <div v-if="modalVisible" class="ticket-modal-overlay" @click.self="modalVisible = false">
        <div class="glass-card" style="padding:2rem;min-width:450px;max-width:550px;max-height:85vh;overflow-y:auto">
          <h3 style="margin-bottom:1.5rem">Tiket #{{ modalTicket.ticket_code }}</h3>
          <div v-if="modalTicket.photo_path && currentPhotoSrc" style="margin-bottom:1.5rem;text-align:center">
            <img :src="currentPhotoSrc" @error="handlePhotoError" style="max-width:100%;max-height:250px;border-radius:12px;object-fit:cover;border:1px solid rgba(255,255,255,.1)" alt="Bukti Foto">
          </div>
          <div style="margin-bottom:1.5rem;display:grid;grid-template-columns:1fr 1fr;gap:1rem">
            <div class="dim">Pelapor:<br><strong style="color:#fff">{{ modalTicket.reporter_name }} (WA: {{ modalTicket.reporter_phone }})</strong></div>
            <div class="dim">Lokasi:<br><strong style="color:#fff">{{ formatLoc(modalTicket) }}</strong></div>
            <div class="dim">Kategori:<br><strong style="color:#fff">{{ modalTicket.category?.name }}</strong></div>
            <div class="dim">Tanggal:<br><strong style="color:#fff">{{ new Date(modalTicket.created_at).toLocaleString('id-ID') }}</strong></div>
            <div class="dim" style="grid-column:1/-1">Deskripsi:<br><strong style="color:#fff;font-weight:400">{{ modalTicket.description }}</strong></div>
          </div>
          <form @submit.prevent="saveTicket">
            <div class="form-field">
              <label>Status Tiket</label>
              <select v-model="modalStatus" required style="width:100%;padding:.6rem;background:var(--card,#0d1220);color:var(--text);border:1px solid rgba(255,255,255,.1);border-radius:8px">
                <option value="Baru">Baru</option>
                <option value="Divalidasi">Divalidasi</option>
                <option value="Ditugaskan">Ditugaskan ke Teknisi</option>
                <option value="Dikerjakan">Sedang Dikerjakan</option>
                <option value="Selesai">Selesai</option>
              </select>
            </div>
            <div class="form-field" v-if="modalStatus === 'Ditugaskan' || modalStatus === 'Dikerjakan'" style="margin-top:1rem">
              <label>Pilih Teknisi</label>
              <select v-model="modalTechId" style="width:100%;padding:.6rem;background:var(--card,#0d1220);color:var(--text);border:1px solid rgba(255,255,255,.1);border-radius:8px">
                <option value="">-- Pilih Teknisi --</option>
                <option v-for="t in technicians" :key="t.id" :value="t.id" :style="!(t.telegram_chat_id && t.status==='aktif') ? 'color:#7B8394' : ''">
                  {{ (t.telegram_chat_id && t.status==='aktif') ? '🟢 ' : '🔴 ' }}{{ t.name }}{{ !(t.telegram_chat_id && t.status==='aktif') ? ' (Nonaktif)' : '' }}
                </option>
              </select>
            </div>
            <div style="display:flex;gap:1rem;justify-content:flex-end;margin-top:1.5rem">
              <button type="button" class="btn ghost" @click="modalVisible = false">Tutup</button>
              <button type="submit" class="btn primary">Simpan</button>
            </div>
          </form>
        </div>
      </div>
    </teleport>
  </section>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import api from '../services/api.js'
import { useToast } from '../composables/useToast.js'

const { showToast } = useToast()
const tickets = ref([])
const technicians = ref([])
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: 0,
  to: 0,
})
const modalVisible = ref(false)
const modalTicket = ref({})
const modalStatus = ref('')
const modalTechId = ref('')
const photoCandidates = ref([])
const photoIndex = ref(0)
let pollInterval = null
const storageUrl = '/api/file/'
const currentPhotoSrc = computed(() => photoCandidates.value[photoIndex.value] || '')

const colBaru = computed(() => tickets.value.filter(t => t.status === 'Baru' || t.status === 'Divalidasi'))
const colProses = computed(() => tickets.value.filter(t => t.status === 'Ditugaskan' || t.status === 'Dikerjakan'))
const colSelesai = computed(() => tickets.value.filter(t => t.status === 'Selesai'))
const visiblePages = computed(() => {
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  const start = Math.max(1, current - 2)
  const end = Math.min(last, current + 2)
  return Array.from({ length: end - start + 1 }, (_, index) => start + index)
})

function formatLoc(t) {
  if (!t.room) return ''
  return (t.room.building ? t.room.building.name : '') + ' / R.' + t.room.room_number
}

function formatDate(d) {
  return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

async function loadTickets(page = pagination.value.current_page) {
  try {
    const res = await api.get('/api/tickets', { params: { page } })
    tickets.value = res.data.data || res.data
    pagination.value = {
      current_page: res.data.current_page || 1,
      last_page: res.data.last_page || 1,
      per_page: res.data.per_page || tickets.value.length || 15,
      total: res.data.total || tickets.value.length,
      from: res.data.from || (tickets.value.length ? 1 : 0),
      to: res.data.to || tickets.value.length,
    }
  } catch (e) {}
}

function changePage(page) {
  if (page < 1 || page > pagination.value.last_page || page === pagination.value.current_page) return
  loadTickets(page)
}

async function openModal(ticket) {
  modalTicket.value = ticket
  modalStatus.value = ticket.status
  modalTechId.value = ticket.technician_id || ''
  preparePhotoCandidates(ticket)
  try {
    const res = await api.get('/api/technicians')
    technicians.value = res.data
  } catch (e) {}
  modalVisible.value = true
}

function normalizePath(path) {
  return String(path || '').replace(/\\/g, '/').replace(/^\/+/, '')
}

function preparePhotoCandidates(ticket) {
  const candidates = []
  const rawPath = normalizePath(ticket?.photo_path)
  const photoUrl = String(ticket?.photo_url || '')

  if (photoUrl) {
    candidates.push(photoUrl)
  }

  if (rawPath) {
    candidates.push(storageUrl + rawPath)
    candidates.push('/api/file/' + encodeURI(rawPath))
  }

  photoCandidates.value = [...new Set(candidates)]
  photoIndex.value = 0
}

function handlePhotoError() {
  if (photoIndex.value < photoCandidates.value.length - 1) {
    photoIndex.value += 1
    return
  }
  showToast('Bukti foto tidak dapat dimuat', 'warning')
}

async function saveTicket() {
  const payload = { status: modalStatus.value }
  if (modalStatus.value === 'Ditugaskan' || modalStatus.value === 'Dikerjakan') {
    if (!modalTechId.value) { showToast('Pilih teknisi terlebih dahulu!', 'warning'); return }
    payload.technician_id = modalTechId.value
  }
  try {
    const res = await api.put('/api/tickets/' + modalTicket.value.id, payload)
    if (res.status === 200) {
      modalVisible.value = false
      loadTickets()
    }
  } catch (err) {
    showToast('Error: ' + (err.response?.data?.message || 'Gagal menyimpan'), 'error')
  }
}

onMounted(() => {
  loadTickets()
  pollInterval = setInterval(() => loadTickets(), 20000)
})

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
})
</script>

<style scoped>
.ticket-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  backdrop-filter: blur(4px);
}
</style>
