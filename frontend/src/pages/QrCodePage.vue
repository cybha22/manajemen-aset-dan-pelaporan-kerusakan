<template>
  <section class="page active">
    <div class="card-grid single">
      <div class="glass-card large-card">
        <div class="card-head">
          <div>
            <h3>QR Code Generator Ruangan</h3>
            <p class="dim">Generate QR Code unik per ruangan. Saat dipindai mahasiswa, langsung diarahkan ke form pelaporan.</p>
          </div>
        </div>
        <div class="form-row">
          <div class="form-field icon-field">
            <i class="ph ph-buildings"></i>
            <div>
              <label>Pilih Gedung</label>
              <select v-model="selectedGedung">
                <option value="">-- Pilih Gedung --</option>
                <option v-for="b in buildings" :key="b.id" :value="b.name">{{ b.name }}</option>
              </select>
            </div>
          </div>
          <div class="form-field icon-field">
            <i class="ph ph-door"></i>
            <div>
              <label>Nomor Ruangan</label>
              <input type="text" v-model="roomNumber" placeholder="Contoh: 103">
            </div>
          </div>
        </div>
        <button class="btn primary" @click="generateQR"><i class="ph ph-qr-code"></i><span>Generate QR Code</span></button>

        <div class="qr-result" v-if="qrVisible" style="animation: fadeUp .4s ease">
          <div class="qr-preview glass-card">
            <div class="qr-image" ref="qrImageRef"></div>
            <div class="qr-info">
              <h4>{{ qrLabel }}</h4>
              <p class="dim">{{ qrUrl }}</p>
              <small class="dim">Cetak dan tempel di dinding ruangan</small>
            </div>
          </div>
        </div>

        <div class="qr-grid">
          <div v-for="item in qrGrid" :key="item.loc" class="qr-grid-item">
            <div v-html="item.html"></div>
            <p>{{ item.label }}</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../services/api.js'

const buildings = ref([])
const selectedGedung = ref('')
const roomNumber = ref('')
const qrVisible = ref(false)
const qrLabel = ref('')
const qrUrl = ref('')
const qrImageRef = ref(null)
const qrGrid = ref([])
let qrcodeLib = null

onMounted(async () => {
  try {
    buildings.value = (await api.get('/api/buildings')).data
  } catch (e) {}

  if (typeof window.qrcode === 'function') {
    qrcodeLib = window.qrcode
  } else {
    const script = document.createElement('script')
    script.src = 'https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js'
    script.onload = () => { qrcodeLib = window.qrcode }
    document.head.appendChild(script)
  }
})

function generateQR() {
  if (!roomNumber.value.trim() || !qrcodeLib) return

  const gedungCode = selectedGedung.value.replace('Gedung ', '')
  const url = window.location.origin + '/?g=' + gedungCode + '&r=' + roomNumber.value.trim()

  const qr = qrcodeLib(0, 'M')
  qr.addData(url)
  qr.make()

  if (qrImageRef.value) {
    qrImageRef.value.innerHTML = qr.createImgTag(5, 8)
  }
  qrLabel.value = selectedGedung.value + ' / Ruang ' + roomNumber.value.trim()
  qrUrl.value = url
  qrVisible.value = true

  const loc = gedungCode + '-' + roomNumber.value.trim()
  if (!qrGrid.value.find(i => i.loc === loc)) {
    const qr2 = qrcodeLib(0, 'M')
    qr2.addData(url)
    qr2.make()
    qrGrid.value.push({
      loc,
      html: qr2.createImgTag(3, 4),
      label: selectedGedung.value + ' / R.' + roomNumber.value.trim()
    })
  }
}
</script>
