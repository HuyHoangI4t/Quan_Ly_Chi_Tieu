// === REPORTS PAGE JS ===

document.addEventListener('DOMContentLoaded', function() {
    // Current filter state
    let currentFilters = {
        period: document.getElementById('periodFilter')?.value || 'last_3_months',
        type: document.getElementById('typeFilter')?.value || 'all'
    };

    // Chart instances
    let lineChartInstance = null;
    let pieChartInstance = null;

    /**
     * Load report data via AJAX
     */
    async function loadReportData(showLoader = true) {
        if (showLoader && typeof SmartSpending !== 'undefined' && SmartSpending.showLoader) {
            SmartSpending.showLoader();
        }

        try {
            const params = new URLSearchParams({
                period: currentFilters.period,
                type: currentFilters.type
            });

            const response = await fetch(`${BASE_URL}/reports/api_get_report_data?${params}`);
            const data = await response.json();

            if (data.success) {
                updateCharts(data.data);
                
                // Update URL without reloading
                const newUrl = `${BASE_URL}/reports/index/${currentFilters.period}/${currentFilters.type}`;
                window.history.pushState({ filters: currentFilters }, '', newUrl);
            } else {
                SmartSpending.showToast(data.message || 'Không thể tải báo cáo', 'error');
            }
        } catch (error) {
            console.error('Error loading report data:', error);
            SmartSpending.showToast('Lỗi khi tải báo cáo', 'error');
        } finally {
            if (showLoader && typeof SmartSpending !== 'undefined' && SmartSpending.hideLoader) {
                SmartSpending.hideLoader();
            }
        }
    }

    /**
     * Update charts with new data
     */
    function updateCharts(data) {
        const styles = getComputedStyle(document.documentElement);
        const gridColor = styles.getPropertyValue('--chart-grid').trim();
        const textColor = styles.getPropertyValue('--chart-text').trim();

        // Update Line Chart
        const lineChartCanvas = document.getElementById('lineChart');
        if (lineChartCanvas) {
            if (lineChartInstance) {
                lineChartInstance.destroy();
            }

            const lineCtx = lineChartCanvas.getContext('2d');
            lineChartInstance = new Chart(lineCtx, {
                type: 'bar',
                data: {
                    labels: data.lineChart.labels,
                    datasets: [{
                        label: 'Thu nhập',
                        data: data.lineChart.income,
                        backgroundColor: '#10B981',
                        borderRadius: 8,
                    }, {
                        label: 'Chi tiêu',
                        data: data.lineChart.expense,
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
                            grid: { color: gridColor },
                            ticks: {
                                color: textColor,
                                callback: function(value) {
                                    if (value >= 1000000) return (value / 1000000) + 'tr';
                                    if (value >= 1000) return (value / 1000) + 'k';
                                    return value;
                                }
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: textColor }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: { color: textColor }
                        },
                        tooltip: {
                            backgroundColor: '#1F2937',
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) label += ': ';
                                    label += new Intl.NumberFormat('vi-VN').format(context.parsed.y) + ' ₫';
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Update Pie Chart
        const pieChartCanvas = document.getElementById('pieChart');
        if (pieChartCanvas) {
            if (pieChartInstance) {
                pieChartInstance.destroy();
            }

            const pieCtx = pieChartCanvas.getContext('2d');
            
            const pieColors = [
                '#3B82F6', '#F97316', '#10B981', '#EF4444', '#8B5CF6',
                '#F59E0B', '#EC4899', '#14B8A6', '#6366F1', '#F43F5E'
            ];

            pieChartInstance = new Chart(pieCtx, {
                type: 'doughnut',
                data: {
                    labels: data.pieChart.labels,
                    datasets: [{
                        label: 'Phân bổ chi tiêu',
                        data: data.pieChart.data,
                        backgroundColor: pieColors,
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 15
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: textColor,
                                padding: 15,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            backgroundColor: '#1F2937',
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) label += ': ';
                                    label += new Intl.NumberFormat('vi-VN', {
                                        style: 'currency',
                                        currency: 'VND'
                                    }).format(context.parsed);
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Update summary stats
        updateSummaryStats(data.lineChart);
    }

    /**
     * Update summary statistics
     */
    function updateSummaryStats(lineChartData) {
        const totalIncome = lineChartData.income.reduce((a, b) => a + b, 0);
        const totalExpense = lineChartData.expense.reduce((a, b) => a + b, 0);
        const balance = totalIncome - totalExpense;
        const savingsRate = totalIncome > 0 ? ((balance / totalIncome) * 100).toFixed(1) : 0;

        const incomeEl = document.getElementById('totalIncome');
        const expenseEl = document.getElementById('totalExpense');
        const balanceEl = document.getElementById('balance');
        const savingsEl = document.getElementById('savingsRate');

        if (incomeEl) incomeEl.textContent = new Intl.NumberFormat('vi-VN').format(totalIncome) + ' ₫';
        if (expenseEl) expenseEl.textContent = new Intl.NumberFormat('vi-VN').format(totalExpense) + ' ₫';
        if (balanceEl) {
            balanceEl.textContent = new Intl.NumberFormat('vi-VN').format(Math.abs(balance)) + ' ₫';
            balanceEl.className = balance >= 0 ? 'text-success mb-0' : 'text-danger mb-0';
        }
        if (savingsEl) savingsEl.textContent = savingsRate + '%';
    }

    // Handle filter changes
    const periodFilter = document.getElementById('periodFilter');
    const typeFilter = document.getElementById('typeFilter');

    if (periodFilter) {
        periodFilter.addEventListener('change', function() {
            currentFilters.period = this.value;
            loadReportData();
        });
    }

    if (typeFilter) {
        typeFilter.addEventListener('change', function() {
            currentFilters.type = this.value;
            loadReportData();
        });
    }

    // Export functionality
    const exportBtn = document.getElementById('exportReport');
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            exportReport();
        });
    }

    /**
     * Export report to PDF/Image
     */
    async function exportReport() {
        SmartSpending.showToast('Đang chuẩn bị xuất báo cáo...', 'info');

        try {
            // Get chart images
            const lineCanvas = document.getElementById('lineChart');
            const pieCanvas = document.getElementById('pieChart');

            if (!lineCanvas || !pieCanvas) {
                SmartSpending.showToast('Không tìm thấy biểu đồ', 'error');
                return;
            }

            // Create a new window with printable content
            const printWindow = window.open('', '_blank');
            const lineImage = lineCanvas.toDataURL('image/png');
            const pieImage = pieCanvas.toDataURL('image/png');

            const periodText = {
                'this_month': 'Tháng này',
                'last_3_months': '3 tháng gần đây',
                'last_6_months': '6 tháng gần đây',
                'this_year': 'Năm nay'
            }[currentFilters.period] || '3 tháng gần đây';

            const typeText = {
                'all': 'Tất cả',
                'income': 'Thu nhập',
                'expense': 'Chi tiêu'
            }[currentFilters.type] || 'Tất cả';

            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Báo cáo - SmartSpending</title>
                    <style>
                        body {
                            font-family: 'Segoe UI', Arial, sans-serif;
                            padding: 20px;
                            max-width: 1200px;
                            margin: 0 auto;
                        }
                        h1 {
                            color: #1abc9c;
                            text-align: center;
                            margin-bottom: 10px;
                        }
                        .subtitle {
                            text-align: center;
                            color: #666;
                            margin-bottom: 30px;
                        }
                        .chart-container {
                            margin: 30px 0;
                            page-break-inside: avoid;
                        }
                        .chart-title {
                            font-size: 18px;
                            font-weight: bold;
                            margin-bottom: 15px;
                            color: #333;
                        }
                        img {
                            max-width: 100%;
                            height: auto;
                            border: 1px solid #ddd;
                            border-radius: 8px;
                        }
                        .footer {
                            margin-top: 40px;
                            text-align: center;
                            color: #999;
                            font-size: 12px;
                        }
                        @media print {
                            body { padding: 10px; }
                            .no-print { display: none; }
                        }
                    </style>
                </head>
                <body>
                    <h1>📊 Báo cáo Chi tiêu - SmartSpending</h1>
                    <div class="subtitle">
                        Kỳ báo cáo: ${periodText} | Loại: ${typeText}<br>
                        Ngày xuất: ${new Date().toLocaleDateString('vi-VN')}
                    </div>

                    <div class="chart-container">
                        <div class="chart-title">📈 Thu nhập và Chi tiêu theo thời gian</div>
                        <img src="${lineImage}" alt="Line Chart">
                    </div>

                    <div class="chart-container">
                        <div class="chart-title">🥧 Phân bổ theo danh mục</div>
                        <img src="${pieImage}" alt="Pie Chart">
                    </div>

                    <div class="footer">
                        © 2025 SmartSpending - Quản lý chi tiêu thông minh
                    </div>

                    <div class="no-print" style="margin-top: 30px; text-align: center;">
                        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer; background: #1abc9c; color: white; border: none; border-radius: 5px;">
                            🖨️ In báo cáo
                        </button>
                        <button onclick="window.close()" style="padding: 10px 20px; font-size: 16px; cursor: pointer; background: #e74c3c; color: white; border: none; border-radius: 5px; margin-left: 10px;">
                            ✖️ Đóng
                        </button>
                    </div>
                </body>
                </html>
            `);

            printWindow.document.close();
            SmartSpending.showToast('Báo cáo đã sẵn sàng!', 'success');

        } catch (error) {
            console.error('Export error:', error);
            SmartSpending.showToast('Lỗi khi xuất báo cáo', 'error');
        }
    }

    // Listen for theme changes to update charts
    window.addEventListener('themeChanged', () => {
        if (lineChartInstance || pieChartInstance) {
            setTimeout(() => {
                loadReportData(false);
            }, 100);
        }
    });

    // Handle browser back/forward
    window.addEventListener('popstate', function(event) {
        if (event.state && event.state.filters) {
            currentFilters = event.state.filters;
            if (periodFilter) periodFilter.value = currentFilters.period;
            if (typeFilter) typeFilter.value = currentFilters.type;
            loadReportData(false);
        }
    });
});
