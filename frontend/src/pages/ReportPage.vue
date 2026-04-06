<template>
  <section class="page active">
    <div class="card-grid single">
      <div class="glass-card large-card">
        <div class="card-head">
          <div>
            <h3>Form Pelaporan Kerusakan</h3>
            <p class="dim">Lengkapi semua kolom. Tiket akan digenerate otomatis.</p>
          </div>
          <div class="badge purple">{{ ticketBadge }}</div>
        </div>
        <form @submit.prevent="submitReport" @reset="resetForm">
          <div class="form-row">
            <div class="form-field icon-field">
              <i class="ph ph-user"></i>
              <div><label>Nama Pelapor</label><input type="text" v-model="form.reporter_name" placeholder="Masukkan nama Anda" required></div>
            </div>
            <div class="form-field icon-field">
              <i class="ph ph-whatsapp-logo"></i>
              <div><label>No. WhatsApp</label><input type="text" v-model="form.reporter_phone" placeholder="08xx..." required></div>
            </div>
          </div>
          <div class="form-row">
            <div class="form-field icon-field">
              <i class="ph ph-buildings"></i>
              <div>
                <label>Gedung</label>
                <select v-model="form.building_id" required>
                  <option value="" disabled selected hidden>-- Pilih Gedung --</option>
                  <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
              </div>
            </div>
            <div class="form-field icon-field">
              <i class="ph ph-door"></i>
              <div>
                <label>Ruangan</label>
                <input type="text" v-model="form.room_number" placeholder="Contoh: 103" required>
              </div>
            </div>
          </div>
          <div class="form-field icon-field">
            <i class="ph ph-monitor"></i>
            <div>
              <label>Kategori Aset</label>
              <select v-model="form.category_id" required>
                <option value="" disabled selected hidden>Pilih jenis aset...</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
          </div>
          <div class="form-field">
            <label class="standalone-label">Bukti Foto Kerusakan</label>
            <div class="dropzone" :class="{ uploaded: fileName, dragover: isDragging }" @click="triggerFile" @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false" @drop.prevent="handleDrop">
              <div class="dz-inner">
                <template v-if="fileName">
                  <div class="dz-icon"><i class="ph ph-check-circle" style="color:var(--green)"></i></div>
                  <p style="color:var(--green);font-weight:600;">{{ fileName }}</p>
                  <small>File siap dilampirkan</small>
                </template>
                <template v-else>
                  <div class="dz-icon"><i class="ph ph-cloud-arrow-up"></i></div>
                  <p><span class="hl">Klik untuk unggah</span> atau seret foto ke sini</p>
                  <small>JPG / PNG, Maks 5MB</small>
                </template>
              </div>
              <input type="file" ref="fileInputRef" accept="image/*" hidden @change="handleFileChange">
            </div>
          </div>
          <div class="form-field icon-field top-align">
            <i class="ph ph-note-pencil"></i>
            <div>
              <label>Deskripsi Kerusakan</label>
              <textarea v-model="form.description" rows="3" placeholder="Contoh: AC menyala tapi tidak dingin, berbunyi bising..." required></textarea>
            </div>
          </div>
          <div class="form-actions">
            <button type="reset" class="btn ghost"><i class="ph ph-arrow-counter-clockwise"></i> Reset</button>
            <button type="submit" class="btn primary" :disabled="submitting" :style="submitBtnStyle">
              <span>{{ submitText }}</span><i :class="submitIcon" :style="submitting ? 'animation:spin 1s linear infinite' : ''"></i>
            </button>
          </div>
        </form>
      </div>
    </div>
  </section>

  <teleport to="body">
    <div class="success-modal-overlay" v-if="showSuccessModal" @click.self="closeSuccessModal">
        <div class="success-modal-content">
            <!-- Glow -->
            <div class="modal-glow"></div>
            
            <!-- Icon -->
            <div class="modal-icon-wrap">
                <div class="icon-ring-1"></div>
                <div class="icon-ring-2"></div>
                <div class="icon-main">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" /></svg>
                </div>
            </div>

            <!-- Text -->
            <div class="modal-text">
                <h1>Success!</h1>
                <p>Terimakasih, Laporan anda sudah di teruskan ke teknisi. Gunakan tiket di bawah untuk melacak progres.</p>
            </div>

            <!-- Ticket Box -->
            <div class="ticket-box">
                <span class="t-id">{{ createdTicketCode }}</span>
                <span class="t-lbl">Ticket ID</span>
            </div>

            <!-- Button -->
            <button @click="handleCopy" :class="['copy-btn', copied ? 'copied' : '']">
                <div class="copy-content">
                    <template v-if="copied">
                        <svg class="w-6 h-6 animate-bounce-custom" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Copied!</span>
                    </template>
                    <template v-else>
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                        </svg>
                        <span>Copy Ticket ID</span>
                    </template>
                </div>
                <div v-if="copied" class="copy-flash"></div>
            </button>
            <div style="margin-top: 1rem;">
                <button @click="closeSuccessModal" style="background:none;border:none;color:rgba(255,255,255,0.5);font-size:0.9rem;cursor:pointer;text-decoration:underline;">Tutup Form Ini</button>
            </div>
        </div>
    </div>
  </teleport>

</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../services/api.js'
import { useToast } from '../composables/useToast.js'

const { showToast } = useToast()
const route = useRoute()

const buildings = ref([])
const categories = ref([])
const fileName = ref('')
const isDragging = ref(false)
const submitting = ref(false)
const submitText = ref('Kirim Laporan')
const submitIcon = ref('ph ph-paper-plane-right')
const submitBtnStyle = ref({})
const ticketBadge = ref('ID: #TK-00000')
const fileInputRef = ref(null)
let selectedFile = null

// Modal Variables
const showSuccessModal = ref(false)
const createdTicketCode = ref('')
const copied = ref(false)

const form = reactive({
  reporter_name: '',
  reporter_phone: '',
  building_id: '',
  room_number: '',
  category_id: '',
  description: '',
})

function triggerFile() {
  if (fileInputRef.value) fileInputRef.value.click()
}

async function loadDropdowns() {
  try {
    const [bRes, cRes] = await Promise.all([
      api.get('/api/buildings'),
      api.get('/api/categories'),
    ])
    buildings.value = bRes.data
    categories.value = cRes.data
  } catch (e) {}
}

onMounted(async () => {
  await loadDropdowns()

  const gParam = route.query.g
  const rParam = route.query.r
  if (gParam) {
    const target = buildings.value.find((b) => b.name.includes(gParam))
    if (target) form.building_id = target.id
  }
  if (rParam) form.room_number = rParam
})

function handleFileChange(e) {
  if (e.target.files.length) {
    selectedFile = e.target.files[0]
    fileName.value = selectedFile.name
  }
}

function handleDrop(e) {
  isDragging.value = false
  if (e.dataTransfer.files.length) {
    selectedFile = e.dataTransfer.files[0]
    fileName.value = selectedFile.name
  }
}

function resetForm() {
  form.reporter_name = ''
  form.reporter_phone = ''
  form.building_id = ''
  form.room_number = ''
  form.category_id = ''
  form.description = ''
  selectedFile = null
  fileName.value = ''
  ticketBadge.value = 'ID: #TK-00000'
}

function closeSuccessModal() {
  showSuccessModal.value = false;
  resetForm();
}

async function handleCopy() {
    try {
        await navigator.clipboard.writeText(createdTicketCode.value);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch (err) {
        showToast('Gagal menyalin text', 'error');
    }
}

async function submitReport() {
  submitting.value = true
  submitText.value = 'Memproses...'
  submitIcon.value = 'ph ph-circle-notch'
  submitBtnStyle.value = { background: 'rgba(255,255,255,.08)', color: 'var(--dim)', boxShadow: 'none' }

  try {
    const fd = new FormData()
    fd.append('reporter_name', form.reporter_name)
    fd.append('reporter_phone', form.reporter_phone)
    fd.append('building_id', form.building_id)
    fd.append('room_number', form.room_number)
    fd.append('category_id', form.category_id)
    fd.append('description', form.description)
    if (selectedFile) fd.append('photo', selectedFile)

    const res = await api.post('/api/tickets', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    submitBtnStyle.value = { background: 'var(--purple)', color: '#fff' }
    submitText.value = 'Berhasil!'
    submitIcon.value = 'ph ph-check-circle'
    ticketBadge.value = 'ID: #' + res.data.ticket_code
    
    // Tampilkan success modal
    createdTicketCode.value = res.data.ticket_code;
    showSuccessModal.value = true;

  } catch (err) {
    const data = err.response?.data
    const errors = data?.errors ? Object.values(data.errors).flat().join(', ') : data?.message || 'Error koneksi'
    submitText.value = 'Gagal: ' + errors
    submitIcon.value = 'ph ph-x-circle'
    submitBtnStyle.value = { background: 'var(--red)', color: '#fff' }
    setTimeout(() => {
      submitting.value = false
      submitText.value = 'Kirim Laporan'
      submitIcon.value = 'ph ph-paper-plane-right'
      submitBtnStyle.value = {}
    }, 3000)
  }
}
</script>

<style scoped>
.success-modal-overlay {
    position: fixed; inset: 0; display: flex; align-items: center; justify-content: center;
    background: rgba(13, 13, 26, 0.85); padding: 1rem;
    z-index: 1000; backdrop-filter: blur(4px);
}
.success-modal-content {
    position: relative; width: 100%; max-width: 24rem; overflow: hidden;
    background: linear-gradient(to bottom, #1e1b4b, #020617);
    border-radius: 40px; padding: 2rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
    text-align: center; border: 1px solid rgba(255,255,255,0.1);
}
.modal-glow {
    position: absolute; top: 0; left: 50%; transform: translate(-50%, -50%);
    width: 16rem; height: 16rem; background: rgba(99, 102, 241, 0.2);
    filter: blur(80px); border-radius: 50%; z-index: 0;
}
.modal-icon-wrap {
    position: relative; display: flex; justify-content: center; margin-bottom: 2.5rem; margin-top: 1rem;
}
.icon-ring-1 {
    position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
    width: 8rem; height: 8rem; background: rgba(34, 197, 94, 0.05);
    border-radius: 50%; border: 1px solid rgba(34, 197, 94, 0.1);
}
.icon-ring-2 {
    position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
    width: 6rem; height: 6rem; background: rgba(34, 197, 94, 0.1);
    border-radius: 50%; border: 1px solid rgba(34, 197, 94, 0.2);
}
.icon-main {
    position: relative; z-index: 10; background: #50d890; width: 5rem; height: 5rem;
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    box-shadow: 0 0 30px rgba(80, 216, 144, 0.4);
}
.icon-main svg {
    width: 2.5rem; height: 2.5rem; color: white; stroke-width: 4px;
}
.modal-text {
    margin-bottom: 2rem; position: relative; z-index: 10;
}
.modal-text h1 {
    color: white; font-size: 1.875rem; font-weight: 700; letter-spacing: 0.025em; margin-bottom: 0.75rem;
}
.modal-text p {
    color: rgba(199, 210, 254, 0.7); font-size: 1rem; line-height: 1.5; padding: 0 1rem; margin:0;
}
.ticket-box {
    position: relative; z-index: 10;
    background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
    border-radius: 1rem; padding: 1rem; margin-bottom: 2rem;
    display: flex; align-items: center; justify-content: space-between;
}
.t-id {
    color: #818cf8; font-family: monospace; font-weight: 700; letter-spacing: 0.05em; font-size: 1.1rem;
}
.t-lbl {
    color: rgba(165, 180, 252, 0.5); font-size: 0.75rem; text-transform: uppercase; font-weight: 700;
}
.copy-btn {
    position: relative; z-index: 10;
    width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.75rem;
    transition: all 0.3s; font-weight: 700; padding: 1rem; border-radius: 1.5rem; font-size: 1.125rem;
    background: #4f46e5; color: white; box-shadow: 0 10px 15px -3px rgba(49, 46, 129, 0.2);
    border: none; cursor: pointer; overflow: hidden;
}
.copy-btn:hover { background: #6366f1; }
.copy-btn:active { transform: scale(0.98); }
.copy-btn.copied {
    background: #22c55e;
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(20, 83, 45, 0.2);
}
.copy-content {
    display: flex; align-items: center; gap: 0.5rem; pointer-events: none;
}
.copy-content svg {
    width: 1.5rem; height: 1.5rem;
}
@keyframes bounce-custom {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
}
.animate-bounce-custom {
    animation: bounce-custom 0.5s infinite;
}
.copy-flash {
    position: absolute; inset: 0; background: rgba(255,255,255,0.2);
    animation: flash-pulse 2s infinite; pointer-events: none;
}
@keyframes flash-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
</style>
