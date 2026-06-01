<template>
  <section class="page active dashboard-page">
    <div class="dashboard-hero glass-card">
      <div class="dashboard-hero-copy">
        <span class="dashboard-kicker">Dasbor Operasional</span>
        <h1>Ringkasan tiket sarpras</h1>
        <p>Pantau status laporan, kategori aset, gedung, dan respon teknisi dalam satu panel.</p>
      </div>
      <div class="dashboard-hero-status">
        <i class="ph ph-pulse"></i>
        <div>
          <span>Data aktif</span>
          <strong>{{ stats.total }} tiket</strong>
        </div>
      </div>
    </div>

    <div class="stat-row dashboard-stats">
      <div class="stat-card glass-card accent-purple">
        <div class="stat-icon purple"><i class="ph ph-ticket"></i></div>
        <div class="stat-copy"><span class="dim">Total Tiket</span><h2>{{ stats.total }}</h2></div>
      </div>
      <div class="stat-card glass-card accent-green">
        <div class="stat-icon green"><i class="ph ph-check-circle"></i></div>
        <div class="stat-copy"><span class="dim">Selesai</span><h2>{{ stats.selesai }}</h2></div>
      </div>
      <div class="stat-card glass-card accent-blue">
        <div class="stat-icon blue"><i class="ph ph-wrench"></i></div>
        <div class="stat-copy"><span class="dim">Sedang Dikerjakan</span><h2>{{ stats.dikerjakan }}</h2></div>
      </div>
      <div class="stat-card glass-card accent-red">
        <div class="stat-icon red"><i class="ph ph-hourglass-high"></i></div>
        <div class="stat-copy"><span class="dim">Baru / Pending</span><h2>{{ stats.baru }}</h2></div>
      </div>
    </div>

    <div class="chart-grid">
      <div class="glass-card chart-card chart-card-wide">
        <div class="card-head">
          <div>
            <span class="card-kicker">Aktivitas</span>
            <h3>Tren Pelaporan Mingguan</h3>
          </div>
          <span class="chart-pill">8 minggu</span>
        </div>
        <div class="chart-wrap chart-wrap-lg">
          <canvas ref="chartLineRef"></canvas>
          <div v-if="!weeklyHasData" class="chart-empty-state">
            <i class="ph ph-chart-line"></i>
            <span>Belum ada data mingguan</span>
          </div>
        </div>
      </div>
      <div class="glass-card chart-card chart-card-donut">
        <div class="card-head">
          <div>
            <span class="card-kicker">Kategori</span>
            <h3>Distribusi Kategori</h3>
          </div>
          <span class="chart-pill">Aset</span>
        </div>
        <div class="chart-wrap donut-wrap"><canvas ref="chartDonutRef"></canvas></div>
      </div>
      <div class="glass-card chart-card">
        <div class="card-head">
          <div>
            <span class="card-kicker">Lokasi</span>
            <h3>Performa per Gedung</h3>
          </div>
          <span class="chart-pill">Tiket</span>
        </div>
        <div class="chart-wrap"><canvas ref="chartBarRef"></canvas></div>
      </div>
      <div class="glass-card chart-card">
        <div class="card-head">
          <div>
            <span class="card-kicker">SLA</span>
            <h3>Rata-rata Waktu Respon</h3>
          </div>
          <span class="chart-pill">Jam</span>
        </div>
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
const weeklyHasData = ref(false)
let charts = []

const palette = {
  blue: '#22D3EE',
  cyan: '#38BDF8',
  green: '#34D399',
  purple: '#A78BFA',
  red: '#FB7185',
  yellow: '#FBBF24',
  text: '#E5E7EB',
  dim: '#94A3B8',
  grid: 'rgba(148, 163, 184, .12)',
  panel: 'rgba(7, 12, 24, .96)'
}

onMounted(async () => {
  try {
    const res = await api.get('/api/dashboard/all')
    const d = res.data
    Object.assign(stats, d.stats || {})
    initCharts(d.weekly, d.category, d.building, d.responseTime)
  } catch (e) {
    initCharts()
  }
})

onUnmounted(destroyCharts)

function destroyCharts() {
  charts.forEach((chart) => chart.destroy())
  charts = []
}

function series(source, fallback) {
  const selected = source && Array.isArray(source.labels) ? source : fallback
  return {
    labels: selected.labels || [],
    data: (selected.data || []).map((value) => Number(value) || 0)
  }
}

function verticalGradient(ctx, start, end) {
  const gradient = ctx.createLinearGradient(0, 0, 0, ctx.canvas.clientHeight || 320)
  gradient.addColorStop(0, start)
  gradient.addColorStop(1, end)
  return gradient
}

function horizontalGradient(ctx, start, end) {
  const gradient = ctx.createLinearGradient(0, 0, ctx.canvas.clientWidth || 520, 0)
  gradient.addColorStop(0, start)
  gradient.addColorStop(1, end)
  return gradient
}

function tooltipOptions(extra = {}) {
  return {
    backgroundColor: palette.panel,
    titleColor: '#FFFFFF',
    bodyColor: palette.text,
    borderColor: 'rgba(255,255,255,.12)',
    borderWidth: 1,
    padding: 12,
    boxPadding: 6,
    cornerRadius: 10,
    ...extra
  }
}

function axisGrid() {
  return {
    color: palette.grid,
    drawTicks: false
  }
}

function axisTicks() {
  return {
    color: palette.dim,
    padding: 10,
    font: {
      size: 12,
      weight: 600
    }
  }
}

function initCharts(weekly, category, building, responseTime) {
  if (!chartLineRef.value || !chartDonutRef.value || !chartBarRef.value || !chartBarHRef.value) return

  destroyCharts()

  Chart.defaults.color = palette.dim
  Chart.defaults.font.family = "'Outfit', sans-serif"

  const weeklyData = series(weekly, { labels: ['W1','W2','W3','W4','W5','W6','W7','W8'], data: [0,0,0,0,0,0,0,0] })
  const categoryData = series(category, { labels: ['AC','Proyektor','Kelistrikan','Furnitur'], data: [0,0,0,0] })
  const buildingData = series(building, { labels: ['Ged.A','Ged.B','Ged.C','Ged.D','Ged.E','Ged.F','Ged.G','Ged.H'], data: [0,0,0,0,0,0,0,0] })
  const responseTimeData = series(responseTime, { labels: ['Belum ada data'], data: [0] })

  const ctxL = chartLineRef.value.getContext('2d')
  const lineFill = verticalGradient(ctxL, 'rgba(34, 211, 238, .32)', 'rgba(52, 211, 153, 0)')
  const weeklyMax = Math.max(...weeklyData.data, 0)
  weeklyHasData.value = weeklyMax > 0
  const lineMax = weeklyMax > 0 ? Math.ceil(weeklyMax * 1.25) : 1

  const totalDuration = 1500
  const delayBetweenPoints = totalDuration / Math.max(weeklyData.data.length, 1)

  charts.push(new Chart(ctxL, {
    type: 'line',
    data: {
      labels: weeklyData.labels,
      datasets: [{
        label: 'Laporan Masuk',
        data: weeklyData.data,
        borderColor: palette.blue,
        backgroundColor: lineFill,
        borderWidth: 3,
        pointBackgroundColor: '#0B1220',
        pointBorderColor: palette.green,
        pointBorderWidth: 2,
        pointRadius: 4,
        pointHoverRadius: 7,
        fill: true,
        tension: .4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      animation: {
        x: {
          type: 'number',
          easing: 'easeOutQuart',
          duration: delayBetweenPoints,
          from: NaN,
          delay(ctx) {
            if (ctx.type !== 'data' || ctx.xStarted) return 0
            ctx.xStarted = true
            return ctx.index * delayBetweenPoints
          }
        },
        y: {
          type: 'number',
          easing: 'easeOutQuart',
          duration: delayBetweenPoints,
          from: (ctx) => ctx.chart.scales.y.getPixelForValue(0),
          delay(ctx) {
            if (ctx.type !== 'data' || ctx.yStarted) return 0
            ctx.yStarted = true
            return ctx.index * delayBetweenPoints
          }
        },
        radius: {
          duration: 400,
          easing: 'easeOutQuart',
          from: 0,
          delay(ctx) {
            return totalDuration + ctx.index * 80
          }
        }
      },
      plugins: {
        legend: { display: false },
        tooltip: tooltipOptions({
          displayColors: false,
          callbacks: {
            label(context) {
              return `${context.formattedValue} laporan`
            }
          }
        })
      },
      scales: {
        y: {
          beginAtZero: true,
          suggestedMax: lineMax,
          ticks: { ...axisTicks(), precision: 0 },
          grid: axisGrid(),
          border: { display: false }
        },
        x: {
          ticks: axisTicks(),
          grid: { display: false },
          border: { display: false }
        }
      }
    }
  }))

  const categoryHasValues = categoryData.data.some((value) => value > 0)
  const donutLabels = categoryHasValues ? categoryData.labels : ['Belum ada data']
  const donutData = categoryHasValues ? categoryData.data : [1]
  const donutColors = categoryHasValues
    ? [palette.cyan, palette.purple, palette.green, palette.yellow, palette.red, '#64748B']
    : ['rgba(148, 163, 184, .22)']

  charts.push(new Chart(chartDonutRef.value, {
    type: 'doughnut',
    data: {
      labels: donutLabels,
      datasets: [{
        data: donutData,
        backgroundColor: donutColors,
        borderColor: '#111827',
        borderWidth: 4,
        hoverOffset: 10
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '68%',
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            padding: 16,
            usePointStyle: true,
            pointStyle: 'circle',
            boxWidth: 9,
            color: palette.dim,
            font: { size: 12, weight: 600 }
          }
        },
        tooltip: tooltipOptions({
          callbacks: {
            label(context) {
              if (!categoryHasValues) return 'Belum ada laporan'
              return `${context.label}: ${context.formattedValue} tiket`
            }
          }
        })
      }
    }
  }))

  const ctxBar = chartBarRef.value.getContext('2d')
  const barFill = verticalGradient(ctxBar, 'rgba(56, 189, 248, .88)', 'rgba(14, 165, 233, .3)')

  charts.push(new Chart(chartBarRef.value, {
    type: 'bar',
    data: {
      labels: buildingData.labels,
      datasets: [{
        label: 'Total Laporan',
        data: buildingData.data,
        backgroundColor: barFill,
        borderColor: 'rgba(125, 211, 252, .8)',
        borderWidth: 1,
        borderRadius: 10,
        borderSkipped: false,
        maxBarThickness: 34
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: tooltipOptions()
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { ...axisTicks(), precision: 0 },
          grid: axisGrid(),
          border: { display: false }
        },
        x: {
          ticks: axisTicks(),
          grid: { display: false },
          border: { display: false }
        }
      }
    }
  }))

  const ctxResponse = chartBarHRef.value.getContext('2d')
  const responseLabels = responseTimeData.labels.length ? responseTimeData.labels : ['Belum ada data']
  const responseValues = responseTimeData.data.length ? responseTimeData.data : [0]
  const rtColors = [
    horizontalGradient(ctxResponse, 'rgba(34, 211, 238, .9)', 'rgba(14, 165, 233, .48)'),
    horizontalGradient(ctxResponse, 'rgba(167, 139, 250, .9)', 'rgba(124, 58, 237, .48)'),
    horizontalGradient(ctxResponse, 'rgba(52, 211, 153, .9)', 'rgba(16, 185, 129, .48)'),
    horizontalGradient(ctxResponse, 'rgba(251, 113, 133, .88)', 'rgba(244, 63, 94, .42)'),
    horizontalGradient(ctxResponse, 'rgba(251, 191, 36, .88)', 'rgba(245, 158, 11, .42)')
  ]

  charts.push(new Chart(chartBarHRef.value, {
    type: 'bar',
    data: {
      labels: responseLabels,
      datasets: [{
        label: 'Jam',
        data: responseValues,
        backgroundColor: responseValues.map((_, index) => rtColors[index % rtColors.length]),
        borderRadius: 10,
        borderSkipped: false,
        maxBarThickness: 34
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: tooltipOptions()
      },
      scales: {
        x: {
          beginAtZero: true,
          ticks: axisTicks(),
          grid: axisGrid(),
          border: { display: false }
        },
        y: {
          ticks: {
            ...axisTicks(),
            callback(value) {
              const label = this.getLabelForValue(value)
              return label.length > 28 ? `${label.slice(0, 28)}...` : label
            }
          },
          grid: { display: false },
          border: { display: false }
        }
      }
    }
  }))
}
</script>
