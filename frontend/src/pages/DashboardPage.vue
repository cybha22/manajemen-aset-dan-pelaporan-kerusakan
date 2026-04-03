<template>
  <section class="page active">
    <div class="stat-row">
      <div class="stat-card glass-card">
        <div class="stat-icon purple"><i class="ph ph-ticket"></i></div>
        <div><span class="dim">Total Tiket</span><h2>{{ stats.total }}</h2></div>
      </div>
      <div class="stat-card glass-card">
        <div class="stat-icon green"><i class="ph ph-check-circle"></i></div>
        <div><span class="dim">Selesai</span><h2>{{ stats.selesai }}</h2></div>
      </div>
      <div class="stat-card glass-card">
        <div class="stat-icon blue"><i class="ph ph-wrench"></i></div>
        <div><span class="dim">Sedang Dikerjakan</span><h2>{{ stats.dikerjakan }}</h2></div>
      </div>
      <div class="stat-card glass-card">
        <div class="stat-icon red"><i class="ph ph-warning-circle"></i></div>
        <div><span class="dim">Baru / Pending</span><h2>{{ stats.baru }}</h2></div>
      </div>
    </div>
    <div class="chart-row">
      <div class="glass-card chart-card flex2">
        <div class="card-head"><h3>Tren Pelaporan Mingguan</h3></div>
        <div class="chart-wrap"><canvas ref="chartLineRef"></canvas></div>
      </div>
      <div class="glass-card chart-card flex1">
        <div class="card-head"><h3>Distribusi Kategori</h3></div>
        <div class="chart-wrap donut-wrap"><canvas ref="chartDonutRef"></canvas></div>
      </div>
    </div>
    <div class="chart-row">
      <div class="glass-card chart-card flex1">
        <div class="card-head"><h3>Performa per Gedung</h3></div>
        <div class="chart-wrap"><canvas ref="chartBarRef"></canvas></div>
      </div>
      <div class="glass-card chart-card flex1">
        <div class="card-head"><h3>Rata-rata Waktu Respon (Jam)</h3></div>
        <div class="chart-wrap"><canvas ref="chartBarHRef"></canvas></div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import api from '../services/api.js'
import Chart from 'chart.js/auto'

const stats = reactive({ total: 0, selesai: 0, dikerjakan: 0, baru: 0 })
const chartLineRef = ref(null)
const chartDonutRef = ref(null)
const chartBarRef = ref(null)
const chartBarHRef = ref(null)
let charts = []

onMounted(async () => {
  try {
    const res = await api.get('/api/dashboard/stats')
    Object.assign(stats, res.data)
  } catch (e) {}
  await initCharts()
})

onUnmounted(() => {
  charts.forEach((c) => c.destroy())
})

async function initCharts() {
  Chart.defaults.color = '#7B8394'
  Chart.defaults.font.family = "'Outfit', sans-serif"

  let weeklyData = { labels: ['W1','W2','W3','W4','W5','W6','W7','W8'], data: [0,0,0,0,0,0,0,0] }
  let categoryData = { labels: ['AC','Proyektor','Kelistrikan','Furnitur'], data: [0,0,0,0] }
  let buildingData = { labels: ['Ged.A','Ged.B','Ged.C','Ged.D','Ged.E','Ged.F','Ged.G','Ged.H'], data: [0,0,0,0,0,0,0,0] }
  let responseTimeData = { labels: [], data: [] }

  try {
    const [wR, cR, bR, rtR] = await Promise.all([
      api.get('/api/dashboard/chart/weekly'),
      api.get('/api/dashboard/chart/category'),
      api.get('/api/dashboard/chart/building'),
      api.get('/api/dashboard/chart/response-time'),
    ])
    if (wR.data.labels) weeklyData = wR.data
    if (cR.data.labels) categoryData = cR.data
    if (bR.data.labels) buildingData = bR.data
    if (rtR.data.labels) responseTimeData = rtR.data
  } catch (e) {}

  const ctxL = chartLineRef.value.getContext('2d')
  const grad = ctxL.createLinearGradient(0, 0, 0, 280)
  grad.addColorStop(0, 'rgba(138,43,226,.35)')
  grad.addColorStop(1, 'rgba(10,15,26,0)')

  charts.push(new Chart(ctxL, {
    type: 'line',
    data: {
      labels: weeklyData.labels,
      datasets: [{ label: 'Laporan Masuk', data: weeklyData.data, borderColor: '#8A2BE2', backgroundColor: grad, borderWidth: 3, pointBackgroundColor: '#0A0F1A', pointBorderColor: '#00FF7F', pointBorderWidth: 3, pointRadius: 5, pointHoverRadius: 7, fill: true, tension: .4 }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { backgroundColor: 'rgba(10,15,26,.95)', bodyColor: '#00FF7F', borderColor: 'rgba(255,255,255,.1)', borderWidth: 1, padding: 12, displayColors: false } }, scales: { y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,.04)', borderDash: [4,4] }, border: { display: false } }, x: { grid: { display: false }, border: { display: false } } } }
  }))

  charts.push(new Chart(chartDonutRef.value, {
    type: 'doughnut',
    data: { labels: categoryData.labels, datasets: [{ data: categoryData.data, backgroundColor: ['#00BFFF','#8A2BE2','#00FF7F','#374151'], borderWidth: 0, hoverOffset: 8 }] },
    options: { responsive: true, maintainAspectRatio: false, cutout: '72%', plugins: { legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true, pointStyle: 'circle' } } } }
  }))

  charts.push(new Chart(chartBarRef.value, {
    type: 'bar',
    data: { labels: buildingData.labels, datasets: [{ label: 'Total Laporan', data: buildingData.data, backgroundColor: 'rgba(0,191,255,.6)', borderRadius: 6, barThickness: 20 }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,.04)' }, border: { display: false } }, x: { grid: { display: false }, border: { display: false } } } }
  }))

  const rtColors = ['rgba(0,191,255,.5)','rgba(138,43,226,.5)','rgba(0,255,127,.5)','rgba(255,99,71,.5)','rgba(255,215,0,.5)','rgba(255,105,180,.5)']
  const rtBg = responseTimeData.data.map((_,i) => rtColors[i % rtColors.length])

  charts.push(new Chart(chartBarHRef.value, {
    type: 'bar',
    data: { labels: responseTimeData.labels.length ? responseTimeData.labels : ['Belum ada data'], datasets: [{ label: 'Jam', data: responseTimeData.data.length ? responseTimeData.data : [0], backgroundColor: rtBg.length ? rtBg : ['rgba(100,100,100,.3)'], borderRadius: 6, barThickness: 30 }] },
    options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, grid: { color: 'rgba(255,255,255,.04)' }, border: { display: false } }, y: { grid: { display: false }, border: { display: false } } } }
  }))
}
</script>
