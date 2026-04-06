<template>
  <section class="page active">
    <div class="kanban-board">
      <div class="kanban-col">
        <div class="kanban-header"><span class="k-dot red-dot"></span> Baru Masuk <span class="k-count">{{ colBaru.length }}</span></div>
        <div class="kanban-cards">
          <div v-for="t in colBaru" :key="t.id" class="k-card glass-card" @click="openModal(t)">
            <div class="k-tag red">{{ t.status }}</div>
            <h4>{{ t.category?.name }}</h4>
            <p class="dim">{{ formatLoc(t) }}</p>
            <p class="dim" style="font-size:.78rem;margin-top:2px">#{{ t.ticket_code }}</p>
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
            <p class="dim" style="font-size:.78rem;margin-top:2px">#{{ t.ticket_code }}</p>
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
            <p class="dim" style="font-size:.78rem;margin-top:2px">#{{ t.ticket_code }}</p>
            <div class="k-footer"><i class="ph ph-check-circle" style="color:var(--green)"></i><small class="dim">Selesai {{ formatDate(t.created_at) }}</small></div>
          </div>
        </div>
      </div>
    </div>

    <teleport to="body">
      <div v-if="modalVisible" class="ticket-modal-overlay" @click.self="modalVisible = false">
        <div class="glass-card" style="padding:2rem;min-width:450px;max-width:550px;max-height:85vh;overflow-y:auto">
          <h3 style="margin-bottom:1.5rem">Tiket #{{ modalTicket.ticket_code }}</h3>
          <div v-if="modalTicket.photo_path" style="margin-bottom:1.5rem;text-align:center">
            <img :src="storageUrl + modalTicket.photo_path" style="max-width:100%;max-height:250px;border-radius:12px;object-fit:cover;border:1px solid rgba(255,255,255,.1)" alt="Bukti Foto">
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
const modalVisible = ref(false)
const modalTicket = ref({})
const modalStatus = ref('')
const modalTechId = ref('')
let pollInterval = null
const storageUrl = '/api/file/'

const colBaru = computed(() => tickets.value.filter(t => t.status === 'Baru' || t.status === 'Divalidasi'))
const colProses = computed(() => tickets.value.filter(t => t.status === 'Ditugaskan' || t.status === 'Dikerjakan'))
const colSelesai = computed(() => tickets.value.filter(t => t.status === 'Selesai'))

function formatLoc(t) {
  if (!t.room) return ''
  return (t.room.building ? t.room.building.name : '') + ' / R.' + t.room.room_number
}

function formatDate(d) {
  return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

async function loadTickets() {
  try {
    const res = await api.get('/api/tickets')
    tickets.value = res.data.data || res.data
  } catch (e) {}
}

async function openModal(ticket) {
  modalTicket.value = ticket
  modalStatus.value = ticket.status
  modalTechId.value = ticket.technician_id || ''
  try {
    const res = await api.get('/api/technicians')
    technicians.value = res.data
  } catch (e) {}
  modalVisible.value = true
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
  pollInterval = setInterval(loadTickets, 20000)
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
