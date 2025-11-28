document.addEventListener('DOMContentLoaded', function() {

    // --- Data for Line Chart (from PHP) ---
    let lineLabels = [];
    let lineIncomeData = [];
    let lineExpenseData = [];
    if (window.lineChartData && window.lineChartData.labels) {
        lineLabels = window.lineChartData.labels;
        lineIncomeData = window.lineChartData.income;
        lineExpenseData = window.lineChartData.expense;
    }

    // --- Data for Pie Chart (from PHP) ---
    let pieLabels = ['Không có dữ liệu'];
    let pieData = [1];
    if (window.pieChartData && window.pieChartData.length > 0) {
        pieLabels = window.pieChartData.map(item => item.name);
        pieData = window.pieChartData.map(item => item.total);
    }


    // Bar Chart: Income vs Expense
    const lineCtx = document.getElementById('lineChart');
    if (lineCtx) {
        new Chart(lineCtx, {
            type: 'bar',
            data: {
                labels: lineLabels,
                datasets: [{
                    label: 'Thu nhập',
                    data: lineIncomeData,
                    backgroundColor: '#10B981',
                    borderRadius: 8,
                }, {
                    label: 'Chi tiêu',
                    data: lineExpenseData,
                    backgroundColor: '#EF4444',
                    borderRadius: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#E5E7EB', // Lighter grid lines
                            borderDash: [5, 5], // Dashed lines
                        },
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000000) return (value / 1000000) + 'tr';
                                if (value >= 1000) return (value / 1000) + 'k';
                                return value;
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false, // Hide X-axis grid lines
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false // Hide legend, labels in datasets are enough
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: '#1F2937',
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 12 },
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += new Intl.NumberFormat('vi-VN').format(context.parsed.y) + ' ₫';
                                return label;
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
            }
        });
    }

    // Pie Chart: Expense Distribution
    const pieCtx = document.getElementById('pieChart');
    if (pieCtx) {
        // A modern, clean color palette
        const pieColors = [
            '#0ea5e9', // sky-500
            '#f97316', // orange-500
            '#10b981', // emerald-500
            '#8b5cf6', // violet-500
            '#f43f5e', // rose-500
            '#eab308', // amber-500
            '#3b82f6', // blue-500
            '#9ca3af', // slate-400 (for remaining balance)
        ];
        
        let backgroundColors = [];
        let colorIndex = 0;
        pieLabels.forEach(label => {
            if (label === 'Số dư còn lại') {
                backgroundColors.push(pieColors[7]); // Explicitly use grey
            } else {
                backgroundColors.push(pieColors[colorIndex % (pieColors.length - 1)]);
                colorIndex++;
            }
        });

        new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: pieLabels,
                datasets: [{
                    label: 'Phân bổ chi tiêu',
                    data: pieData,
                    backgroundColor: backgroundColors,
                    // Use a thick white border to create spacing, a very modern look
                    borderWidth: 2,
                    borderColor: '#ffffff', 
                    hoverOffset: 15,
                    hoverBorderWidth: 3,
                    hoverBorderColor: '#ffffff',
                    borderRadius: 8, 
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
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
                        backgroundColor: '#1F2937',
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 12 },
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed !== null) {
                                    label += new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(context.parsed);
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }

});
