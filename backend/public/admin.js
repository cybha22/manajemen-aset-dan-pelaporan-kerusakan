document.addEventListener('DOMContentLoaded', () => {

    Chart.defaults.color = '#9CA3AF';
    Chart.defaults.font.family = "'Outfit', sans-serif";

    // --- Line Chart: Tren Pelaporan Bulan Ini ---
    const ctxLine = document.getElementById('lineChart').getContext('2d');

    // Create Gradient for Line Fill
    const purpleGradient = ctxLine.createLinearGradient(0, 0, 0, 400);
    purpleGradient.addColorStop(0, 'rgba(138, 43, 226, 0.4)');   // Neon Purple start
    purpleGradient.addColorStop(1, 'rgba(10, 15, 26, 0.0)');    // Fade to bg

    // Create Gradient for Line Stroke
    const strokeGradient = ctxLine.createLinearGradient(0, 0, 600, 0);
    strokeGradient.addColorStop(0, '#00BFFF'); // Neon Blue
    strokeGradient.addColorStop(1, '#8A2BE2'); // Neon Purple

    const lineChart = new Chart(ctxLine, {
        type: 'line',
        data: {
            labels: ['M1', 'M2', 'M3', 'M4', 'M5', 'M6', 'M7', 'HARI INI'],
            datasets: [{
                label: 'Jumlah Laporan Kerusakan',
                data: [12, 19, 15, 25, 22, 30, 28, 45],
                borderColor: strokeGradient,
                backgroundColor: purpleGradient,
                borderWidth: 4,
                pointBackgroundColor: '#0A0F1A',
                pointBorderColor: '#00FF7F', // Emerald Green Dots
                pointBorderWidth: 3,
                pointRadius: 6,
                pointHoverRadius: 8,
                fill: true,
                tension: 0.4 // Smooth curves
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(10, 15, 26, 0.9)',
                    titleColor: '#FFF',
                    bodyColor: '#00FF7F',
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.05)',
                        borderDash: [5, 5]
                    },
                    border: { display: false }
                },
                x: {
                    grid: { display: false },
                    border: { display: false }
                }
            }
        }
    });

    // --- Doughnut Chart: Distribusi Kategori Kerusakan ---
    const ctxDoughnut = document.getElementById('doughnutChart').getContext('2d');

    const doughnutChart = new Chart(ctxDoughnut, {
        type: 'doughnut',
        data: {
            labels: ['AC', 'Proyektor', 'Kelistrikan', 'Kursi/Meja'],
            datasets: [{
                data: [45, 25, 20, 10],
                backgroundColor: [
                    '#00BFFF', // Blue
                    '#8A2BE2', // Purple
                    '#00FF7F', // Emerald Green
                    '#374151'  // Neutral Dark Gray
                ],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '75%', // Modern thin donut
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(10, 15, 26, 0.9)',
                    titleColor: '#FFF',
                    bodyColor: '#FFF',
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1,
                    padding: 12,
                    callbacks: {
                        label: function (context) {
                            return ' ' + context.parsed + '% Total Kasus';
                        }
                    }
                }
            }
        }
    });
});
