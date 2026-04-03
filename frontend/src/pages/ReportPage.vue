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
    submitText.value = 'Tiket Berhasil Dibuat!'
    submitIcon.value = 'ph ph-check-circle'
    ticketBadge.value = 'ID: #' + res.data.ticket_code

    setTimeout(() => {
      submitting.value = false
      submitText.value = 'Kirim Laporan'
      submitIcon.value = 'ph ph-paper-plane-right'
      submitBtnStyle.value = {}
      resetForm()
    }, 3000)
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
