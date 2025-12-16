// === REPORTS PAGE JS ===

document.addEventListener('DOMContentLoaded', function () {
    // Current filter state
    let currentFilters = {
        period: document.getElementById('periodFilter')?.value || 'last_3_months',
        type: document.getElementById('typeFilter')?.value || 'expense'
    };

    // Chart instances
    let lineChartInstance = null;
    let pieChartInstance = null;

    // Safely destroy any Chart.js instance attached to a canvas or id
    function safeDestroyChart(idOrElement) {
        if (typeof Chart === 'undefined') return;
        try {
            let existing = null;
            if (typeof Chart.getChart === 'function') {
                // Try by id or element directly
                existing = Chart.getChart(idOrElement);
                // If passed a string id and not found, try to resolve element
                if (!existing && typeof idOrElement === 'string') {
                    const el = document.getElementById(idOrElement);
                    if (el) existing = Chart.getChart(el);
                }
                // If passed an element with an id, try by its id
                if (!existing && idOrElement && idOrElement.id) {
                    existing = Chart.getChart(idOrElement.id);
                }
            }
            if (existing) existing.destroy();
        } catch (e) {
            // best-effort: ignore errors
        }
    }

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
        console.log("Dữ liệu nhận được:", data);
        const styles = getComputedStyle(document.documentElement);
        const gridColor = styles.getPropertyValue('--chart-grid').trim();
        const textColor = styles.getPropertyValue('--chart-text').trim();

        // Update Line Chart
        const lineChartCanvas = document.getElementById('lineChart');
        if (lineChartCanvas) {
            // Kiểm tra dữ liệu đầu vào
            const hasLineData = data.lineChart && Array.isArray(data.lineChart.income) && data.lineChart.income.length > 0 && Array.isArray(data.lineChart.expense) && data.lineChart.expense.length > 0;
            if (!hasLineData) {
                // Destroy any existing chart attached to this canvas id or element
                safeDestroyChart('lineChart');
                safeDestroyChart(lineChartCanvas);
                lineChartInstance = null;
                // Hiển thị thông báo không có dữ liệu
                const container = lineChartCanvas && lineChartCanvas.parentElement ? lineChartCanvas.parentElement : document.querySelector('#lineChart')?.parentElement;
                if (container) container.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 text-muted">Chưa có dữ liệu để hiển thị</div>';
            } else {
                // Nếu có dữ liệu thì vẽ biểu đồ
                // Always destroy any previous chart (either stored or attached to the canvas)
                safeDestroyChart('lineChart');
                safeDestroyChart(lineChartCanvas);
                if (lineChartInstance) {
                    try { lineChartInstance.destroy(); } catch (e) { }
                }
                lineChartInstance = null;
                // Đảm bảo canvas tồn tại (có thể đã bị thay thế bởi thông báo)
                const container = lineChartCanvas && lineChartCanvas.parentElement ? lineChartCanvas.parentElement : document.querySelector('#lineChart')?.parentElement;
                if (!document.getElementById('lineChart')) {
                    const newCanvas = document.createElement('canvas');
                    newCanvas.id = 'lineChart';
                    newCanvas.style.height = '350px';
                    newCanvas.style.position = 'relative';
                    if (container) {
                        container.innerHTML = '';
                        container.appendChild(newCanvas);
                    }
                }
                const lineCtx = document.getElementById('lineChart').getContext('2d');
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
                                    callback: function (value) {
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
                                    label: function (context) {
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
        }

        // Update Pie Chart
        const pieChartCanvas = document.getElementById('pieChart');
        if (pieChartCanvas) {
            const hasPieData = data.pieChart && Array.isArray(data.pieChart.data) && data.pieChart.data.length > 0;
            if (!hasPieData) {
                // Ensure any existing pie chart is destroyed
                safeDestroyChart('pieChart');
                safeDestroyChart(pieChartCanvas);
                pieChartInstance = null;
                const container = pieChartCanvas && pieChartCanvas.parentElement ? pieChartCanvas.parentElement : document.querySelector('#pieChart')?.parentElement;
                if (container) container.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 text-muted">Chưa có dữ liệu để hiển thị</div>';
            } else {
                // Destroy any existing pie chart
                safeDestroyChart('pieChart');
                safeDestroyChart(pieChartCanvas);
                if (pieChartInstance) {
                    try { pieChartInstance.destroy(); } catch (e) { }
                }
                pieChartInstance = null;
                const container = pieChartCanvas && pieChartCanvas.parentElement ? pieChartCanvas.parentElement : document.querySelector('#pieChart')?.parentElement;
                if (!document.getElementById('pieChart')) {
                    const newCanvas = document.createElement('canvas');
                    newCanvas.id = 'pieChart';
                    newCanvas.style.height = '350px';
                    newCanvas.style.position = 'relative';
                    if (container) {
                        container.innerHTML = '';
                        container.appendChild(newCanvas);
                    }
                }
                const pieCtx = document.getElementById('pieChart').getContext('2d');
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
                            //backgroundColor: pieColors,
                            borderWidth: 4,
                            hoverOffset: 8,
                            borderRadius: 4
                        }]
                    },
                    options: {
                        cutout: '50%',
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
                                    label: function (context) {
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
        periodFilter.addEventListener('change', function () {
            currentFilters.period = this.value;
            loadReportData();
        });
    }

    if (typeFilter) {
        typeFilter.addEventListener('change', function () {
            currentFilters.type = this.value;
            loadReportData();
        });
    }

    // Export functionality
    const exportBtn = document.getElementById('exportReport');
    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            exportExcel();
        });
    }

    /**
     * Export report to PDF/Image
     */
    function exportExcel() {
        try {
            const params = new URLSearchParams({
                period: currentFilters.period,
                type: currentFilters.type
            });
            const url = `${BASE_URL}/reports/export_excel?${params.toString()}`;
            window.location.href = url; // Trigger download
        } catch (e) {
            console.error('Export error:', e);
            if (typeof SmartSpending !== 'undefined' && SmartSpending.showToast) {
                SmartSpending.showToast('Lỗi khi xuất Excel', 'error');
            }
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
    window.addEventListener('popstate', function (event) {
        if (event.state && event.state.filters) {
            currentFilters = event.state.filters;
            if (periodFilter) periodFilter.value = currentFilters.period;
            if (typeFilter) typeFilter.value = currentFilters.type;
            loadReportData(false);
        }
    });
    loadReportData();
});
