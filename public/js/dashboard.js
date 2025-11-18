document.addEventListener("DOMContentLoaded", function () {

    // --- CHART DESTRUCTION ---
    // To prevent "Canvas is already in use" error, destroy existing charts before creating new ones.
    if (window.lineChart && typeof window.lineChart.destroy === 'function') {
        window.lineChart.destroy();
    }
    if (window.pieChart && typeof window.pieChart.destroy === 'function') {
        window.pieChart.destroy();
    }

    // --- 1. BIỂU ĐỒ ĐƯỜNG (Thu vs Chi) ---
    const lineChartCanvas = document.getElementById('lineChart');
    if (lineChartCanvas) {
        const ctx = lineChartCanvas.getContext('2d');
        window.lineChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Th1', 'Th2', 'Th3', 'Th4', 'Th5', 'Th6'],
                datasets: [
                    {
                        label: 'Thu nhập',
                        data: [12000, 15000, 13000, 16000, 17000, 19000],
                        borderColor: '#00b083',
                        backgroundColor: 'transparent',
                        borderWidth: 2.5,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#00b083',
                        pointRadius: 0,
                        pointHoverRadius: 6,
                        tension: 0.4
                    },
                    {
                        label: 'Chi tiêu',
                        data: [9000, 10000, 11000, 12000, 12500, 13000],
                        borderColor: '#ff6b6b',
                        backgroundColor: 'transparent',
                        borderWidth: 2.5,
                        pointRadius: 0,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        align: 'center',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 8,
                            font: { size: 12, family: "'Poppins', sans-serif" },
                            color: '#888'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            // Thêm chữ "đ" vào tooltip khi di chuột
                            label: function (context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('vi-VN').format(context.parsed.y * 1000) + ' ₫';
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f0f0f0', borderDash: [5, 5] },
                        ticks: {
                            font: { size: 10 },
                            color: '#999',
                            // Format trục Y thành dạng tiền tệ rút gọn (10tr, 15tr...)
                            callback: function (value) {
                                return value / 1000 + ' tr';
                            }
                        },
                        border: { display: false }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10 }, color: '#999' },
                        border: { display: false }
                    }
                }
            }
        });
    }

    // --- 2. BIỂU ĐỒ TRÒN (Phân bổ chi tiêu) ---
    const pieChartCanvas = document.getElementById('pieChart');
    if (pieChartCanvas) {
        const ctxPie = pieChartCanvas.getContext('2d');
        window.pieChart = new Chart(ctxPie, {
            type: 'pie',
            data: {
                // Danh mục tiếng Việt
                labels: ['Ăn uống', 'Di chuyển', 'Giải trí', 'Mua sắm', 'Khác'],
                datasets: [{
                    data: [26.1, 22.8, 16.3, 13.1, 21.7],
                    backgroundColor: [
                        '#2ecc71', // Ăn uống - Xanh lá
                        '#3498db', // Di chuyển - Xanh dương
                        '#f1c40f', // Giải trí - Vàng
                        '#ff6b6b', // Mua sắm - Đỏ cam
                        '#95a5a6'  // Khác - Xám
                    ],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 10,
                            padding: 20,
                            font: { size: 12, family: "'Poppins', sans-serif" },
                            color: '#555'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                let label = context.label || '';
                                if (label) { label += ': '; }
                                label += context.parsed + '%';
                                return label;
                            }
                        }
                    }
                },
                layout: {
                    padding: { left: 0, right: 0, top: 0, bottom: 0 }
                }
            }
        });
    }
});
