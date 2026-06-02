<template>
  <section class="page active">
    <div class="card-grid single">
      <div class="glass-card large-card">
        <div class="card-head">
          <div>
            <h3>Master Data Aset Kelas</h3>
            <p class="dim">Data inventaris aset di seluruh gedung perkuliahan ITATS (Gedung A - H).</p>
          </div>
          <button class="btn primary sm" @click="openAdd"><i class="ph ph-plus"></i> Tambah Aset</button>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>ID</th><th>Ruangan</th><th>Kategori Kerusakan</th><th>Gedung</th><th>Total Laporan</th><th>Kondisi</th><th>Aksi</th></tr>
            </thead>
            <tbody>
              <tr v-if="rooms.length === 0"><td colspan="7" style="text-align:center;padding:2rem;color:var(--dim)">Belum ada data ruangan</td></tr>
              <tr v-for="r in rooms" :key="r.id">
                <td style="font-family:monospace;font-size:.82rem">{{ roomCode(r) }}</td>
                <td><strong style="color:#fff">R.{{ r.room_number }}</strong></td>
                <td>
                  <template v-if="(r.registered_assets || []).length > 0">
                    <span v-for="a in r.registered_assets" :key="a.id" :style="assetBadgeStyle(a)" :title="a.condition">{{ a.name }} ×{{ a.quantity }}</span>
                  </template>
                  <template v-else-if="(r.categories || []).length > 0">
                    <span v-for="c in r.categories" :key="c.id" :style="catBadgeStyle(c)">{{ c.name }}</span>
                  </template>
                  <span v-else style="color:var(--dim);font-size:.8rem">Belum ada aset</span>
                </td>
                <td>{{ buildingName(r) }}</td>
                <td>
                  <span v-if="(r.total_tickets ?? 0) > 0" style="color:#00BFFF;font-weight:600">{{ r.total_tickets }}</span>
                  <span v-else class="dim">0</span>
                </td>
                <td>
                  <span v-if="hasRusak(r)" class="cond bad" style="background:rgba(255,99,71,.15);color:#FF6347">Ada Kerusakan</span>
                  <span v-else class="cond good">Baik</span>
                </td>
                <td>
                  <button class="btn ghost xs" @click="openEdit(r)"><i class="ph ph-pencil-simple"></i></button>
                  <button class="btn ghost xs danger" @click="deleteRoom(r)"><i class="ph ph-trash"></i></button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="roomPagination.total > 0" class="tickets-pagination master-pagination">
          <div class="pagination-info">
            <strong>{{ roomPagination.from || 0 }}-{{ roomPagination.to || 0 }}</strong>
            <span>dari {{ roomPagination.total }} ruangan</span>
          </div>
          <div class="pagination-actions">
            <button type="button" class="page-btn page-btn-wide" :disabled="roomPagination.current_page <= 1" @click="changeRoomPage(roomPagination.current_page - 1)">
              <i class="ph ph-caret-left"></i>
              Sebelumnya
            </button>
            <button v-for="page in visibleRoomPages" :key="page" type="button" class="page-btn page-number" :class="{ active: page === roomPagination.current_page }" @click="changeRoomPage(page)">
              {{ page }}
            </button>
            <button type="button" class="page-btn page-btn-wide" :disabled="roomPagination.current_page >= roomPagination.last_page" @click="changeRoomPage(roomPagination.current_page + 1)">
              Berikutnya
              <i class="ph ph-caret-right"></i>
            </button>
          </div>
          <div class="pagination-page">
            Halaman {{ roomPagination.current_page }} dari {{ roomPagination.last_page }}
          </div>
        </div>
      </div>
    </div>

    <teleport to="body">
      <div v-if="modalVisible" class="room-modal-overlay" @click.self="modalVisible = false">
        <div class="glass-card" style="padding:2rem;width:520px;max-height:88vh;overflow-y:auto">
          <h3 style="margin-bottom:1.5rem">{{ isEdit ? 'Edit Ruangan' : 'Tambah Ruangan Baru' }}</h3>
          <div v-if="isEdit" style="display:flex;gap:.5rem;margin-bottom:1.5rem;border-bottom:1px solid rgba(255,255,255,.08);padding-bottom:.5rem">
            <button type="button" :class="['btn','sm', activeTab === 'info' ? 'primary' : 'ghost']" @click="activeTab = 'info'">Info Ruangan</button>
            <button type="button" :class="['btn','sm', activeTab === 'asset' ? 'primary' : 'ghost']" @click="activeTab = 'asset'">Inventaris Aset</button>
          </div>
          <div v-show="activeTab === 'info'">
            <form @submit.prevent="saveRoom">
              <div class="form-field icon-field" style="margin-bottom:1rem"><i class="ph ph-buildings"></i>
                <div><label>Gedung</label>
                  <select v-model="roomForm.building_id" required style="width:100%;padding:.5rem;background:var(--card,#0d1220);color:var(--text);border:1px solid rgba(255,255,255,.1);border-radius:8px">
                    <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                  </select></div>
              </div>
              <div class="form-field icon-field" style="margin-bottom:1.5rem"><i class="ph ph-door"></i>
                <div><label>Nomor Ruangan</label><input type="text" v-model="roomForm.room_number" placeholder="Contoh: 103" required></div>
              </div>
              <input type="hidden" v-model="roomForm.id">
              <div style="display:flex;gap:1rem;justify-content:flex-end">
                <button type="button" class="btn ghost" @click="modalVisible = false">Batal</button>
                <button type="submit" class="btn primary">Simpan</button>
              </div>
            </form>
          </div>
          <div v-if="isEdit" v-show="activeTab === 'asset'">
            <div style="margin-bottom:1rem">
              <label style="display:block;margin-bottom:.5rem;font-size:.85rem;color:var(--dim)">Tambah Aset Baru</label>
              <div style="display:grid;grid-template-columns:1fr auto auto;gap:.5rem;align-items:end">
                <div><label style="font-size:.78rem;color:var(--dim)">Kategori</label>
                  <select v-model="newAsset.category_id" style="width:100%;padding:.5rem;background:var(--card,#0d1220);color:var(--text);border:1px solid rgba(255,255,255,.1);border-radius:8px;margin-top:.2rem">
                    <option v-for="c in allCategories" :key="c.id" :value="c.id">{{ c.name }}</option>
                  </select></div>
                <div><label style="font-size:.78rem;color:var(--dim)">Jumlah</label>
                  <input type="number" v-model.number="newAsset.quantity" min="1" max="999" style="width:70px;padding:.5rem;background:var(--card,#0d1220);color:var(--text);border:1px solid rgba(255,255,255,.1);border-radius:8px;margin-top:.2rem"></div>
                <button type="button" class="btn primary sm" style="margin-top:1.2rem" @click="addAsset"><i class="ph ph-plus"></i> Tambah</button>
              </div>
              <div style="margin-top:.5rem"><label style="font-size:.78rem;color:var(--dim)">Kondisi</label>
                <select v-model="newAsset.condition" style="width:100%;padding:.45rem;background:var(--card,#0d1220);color:var(--text);border:1px solid rgba(255,255,255,.1);border-radius:8px;margin-top:.2rem">
                  <option value="Baik">Baik</option><option value="Rusak Ringan">Rusak Ringan</option><option value="Rusak Berat">Rusak Berat</option>
                </select></div>
            </div>
            <div style="max-height:280px;overflow-y:auto">
              <p v-if="!modalAssets.length" style="color:var(--dim);font-size:.85rem;text-align:center;padding:1rem">Belum ada aset terdaftar</p>
              <div v-for="a in modalAssets" :key="a.id" style="display:flex;align-items:center;justify-content:space-between;padding:.6rem .8rem;background:rgba(255,255,255,.04);border-radius:8px;margin-bottom:.4rem;gap:.5rem">
                <div>
                  <span style="font-weight:600;color:#fff">{{ a.name }}</span>
                  <span style="color:var(--dim);font-size:.8rem"> × {{ a.quantity }}</span>
                  <span :style="'margin-left:.5rem;font-size:.75rem;color:' + condColor(a.condition)">{{ a.condition }}</span>
                </div>
                <button type="button" class="btn ghost xs danger" @click="delAsset(a.id)"><i class="ph ph-trash"></i></button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </teleport>

    <ConfirmDialog ref="confirmRef" />
  </section>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import api from '../services/api.js'
import { useToast } from '../composables/useToast.js'
import ConfirmDialog from '../components/ConfirmDialog.vue'

const { showToast } = useToast()
const confirmRef = ref(null)
const rooms = ref([])
const buildings = ref([])
const allCategories = ref([])
// State paginator ruangan mengikuti format paginator Laravel.
const roomPagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: 0,
  to: 0,
})
const modalVisible = ref(false)
const isEdit = ref(false)
const activeTab = ref('info')
const modalAssets = ref([])
const roomForm = reactive({ id: '', building_id: '', room_number: '' })
const newAsset = reactive({ category_id: '', quantity: 1, condition: 'Baik' })

const condColors = { 'Baik': '#00FF7F', 'Rusak Ringan': '#FFD700', 'Rusak Berat': '#FF6347' }
// Nomor halaman dibatasi agar kontrol pagination tetap ringkas.
const visibleRoomPages = computed(() => {
  const current = roomPagination.value.current_page
  const last = roomPagination.value.last_page
  const start = Math.max(1, current - 2)
  const end = Math.min(last, current + 2)
  return Array.from({ length: end - start + 1 }, (_, index) => start + index)
})

function condColor(c) { return condColors[c] || '#fff' }

function roomCode(r) {
  const bName = buildingName(r)
  return bName.replace('Gedung ', '') + '-' + String(r.id).padStart(3, '0')
}

function buildingName(r) {
  const b = buildings.value.find(b => b.id === r.building_id)
  return b ? b.name : (r.building ? r.building.name : '-')
}

function hasRusak(r) {
  return (r.registered_assets || []).some(a => a.condition !== 'Baik')
}

function assetBadgeStyle(a) {
  const condBg = { 'Baik': 'rgba(0,255,127,.18)', 'Rusak Ringan': 'rgba(255,215,0,.18)', 'Rusak Berat': 'rgba(255,99,71,.18)' }
  const condTxt = { 'Baik': '#00FF7F', 'Rusak Ringan': '#FFD700', 'Rusak Berat': '#FF6347' }
  return 'display:inline-block;padding:2px 9px;border-radius:20px;background:' + (condBg[a.condition] || 'rgba(255,255,255,.08)') + ';color:' + (condTxt[a.condition] || '#fff') + ';font-size:.75rem;margin:2px 2px 2px 0;white-space:nowrap'
}

function catBadgeStyle(c) {
  const catColors = { 'AC': 'rgba(0,191,255,.18)', 'Proyektor': 'rgba(138,43,226,.18)', 'Kelistrikan': 'rgba(0,255,127,.18)', 'Furnitur': 'rgba(255,99,71,.18)' }
  const catTextColors = { 'AC': '#00BFFF', 'Proyektor': '#BF7FFF', 'Kelistrikan': '#00FF7F', 'Furnitur': '#FF6347' }
  const key = Object.keys(catColors).find(k => c.name.includes(k)) || ''
  return 'display:inline-block;padding:2px 8px;border-radius:20px;background:' + (catColors[key] || 'rgba(255,255,255,.08)') + ';color:' + (catTextColors[key] || '#fff') + ';font-size:.75rem;margin:2px 2px 2px 0;white-space:nowrap'
}

async function loadData(page = roomPagination.value.current_page) {
  try {
    // Hanya daftar ruangan admin yang meminta pagination; data gedung tetap array untuk dropdown.
    const [bR, rR] = await Promise.all([
      api.get('/api/buildings'),
      api.get('/api/rooms', { params: { paginated: 1, page, per_page: roomPagination.value.per_page } }),
    ])
    buildings.value = bR.data
    rooms.value = rR.data.data || rR.data
    roomPagination.value = {
      current_page: rR.data.current_page || 1,
      last_page: rR.data.last_page || 1,
      per_page: rR.data.per_page || rooms.value.length || 15,
      total: rR.data.total || rooms.value.length,
      from: rR.data.from || (rooms.value.length ? 1 : 0),
      to: rR.data.to || rooms.value.length,
    }
    // Jika item terakhir pada halaman ini terhapus, kembali ke halaman sebelumnya.
    if (rooms.value.length === 0 && roomPagination.value.current_page > 1) {
      await loadData(roomPagination.value.current_page - 1)
    }
  } catch (e) {}
}

function changeRoomPage(page) {
  if (page < 1 || page > roomPagination.value.last_page || page === roomPagination.value.current_page) return
  loadData(page)
}

function openAdd() {
  isEdit.value = false
  activeTab.value = 'info'
  roomForm.id = ''
  roomForm.building_id = buildings.value[0]?.id || ''
  roomForm.room_number = ''
  modalAssets.value = []
  modalVisible.value = true
}

async function openEdit(r) {
  isEdit.value = true
  activeTab.value = 'info'
  roomForm.id = r.id
  roomForm.building_id = r.building_id
  roomForm.room_number = r.room_number
  modalAssets.value = (r.registered_assets || []).map(a => ({ ...a }))
  if (!allCategories.value.length) {
    try { allCategories.value = (await api.get('/api/categories')).data } catch (e) {}
  }
  newAsset.category_id = allCategories.value[0]?.id || ''
  modalVisible.value = true
}

async function saveRoom() {
  const payload = { building_id: roomForm.building_id, room_number: roomForm.room_number }
  try {
    const url = roomForm.id ? '/api/rooms/' + roomForm.id : '/api/rooms'
    const method = roomForm.id ? 'put' : 'post'
    await api[method](url, payload)
    modalVisible.value = false
    loadData()
    showToast('Ruangan berhasil disimpan', 'success')
  } catch (err) {
    const d = err.response?.data
    showToast('Error: ' + (d?.errors ? Object.values(d.errors).flat().join(', ') : d?.message || 'Gagal'), 'error')
  }
}

async function deleteRoom(r) {
  const confirmed = await confirmRef.value.show('Yakin ingin menghapus "Ruang ' + r.room_number + '"? Data tidak bisa dikembalikan.', 'Hapus Data')
  if (!confirmed) return
  try {
    await api.delete('/api/rooms/' + r.id)
    showToast('Data berhasil dihapus', 'success')
    loadData()
  } catch (err) {
    showToast(err.response?.data?.message || 'Gagal menghapus', 'error')
  }
}

async function addAsset() {
  try {
    await api.post('/api/room-assets', { room_id: roomForm.id, category_id: newAsset.category_id, quantity: newAsset.quantity, condition: newAsset.condition })
    showToast('Aset berhasil ditambahkan', 'success')
    const refreshed = (await api.get('/api/room-assets?room_id=' + roomForm.id)).data
    modalAssets.value = refreshed.map(a => ({ id: a.id, name: a.category?.name ?? '-', quantity: a.quantity, condition: a.condition }))
    loadData()
  } catch (err) {
    showToast('Error: ' + (err.response?.data?.message || 'Gagal'), 'error')
  }
}

async function delAsset(assetId) {
  try {
    await api.delete('/api/room-assets/' + assetId)
    const refreshed = (await api.get('/api/room-assets?room_id=' + roomForm.id)).data
    modalAssets.value = refreshed.map(a => ({ id: a.id, name: a.category?.name ?? '-', quantity: a.quantity, condition: a.condition }))
    loadData()
  } catch (e) {
    showToast('Gagal menghapus aset', 'error')
  }
}

onMounted(() => { loadData() })
</script>

<style scoped>
.master-pagination {
  border-top: 1px solid rgba(255,255,255,.06);
  margin-top: 8px;
  padding-left: 0;
  padding-right: 0;
}

.room-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.65);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  backdrop-filter: blur(6px);
}
</style>
