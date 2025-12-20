function formatInputMoney(input) {
    let rawValue = (input.value || '').toString().replace(/\D/g, '');
    if (rawValue) {
        let formattedValue = new Intl.NumberFormat('vi-VN').format(rawValue);
        input.value = formattedValue;
        const hiddenInputId = input.id.replace('_display', '');
        const hiddenInput = document.getElementById(hiddenInputId);
        if (hiddenInput) {
            hiddenInput.value = rawValue;
        }
    } else {
        input.value = '';
        const hiddenInputId = input.id.replace('_display', '');
        const hiddenInput = document.getElementById(hiddenInputId);
        if (hiddenInput) {
            hiddenInput.value = '';
        }
    }
}

(function () {
    let currentPeriod = 'monthly';
    const tableBody = document.getElementById('budgetsList');
    const emptyState = document.getElementById('emptyState');
    const periodSelect = document.getElementById('periodFilter') || document.getElementById('periodSelect');

    let trendChartInstance = null;
    let pieChartInstance = null;
    let budgetsListCache = [];

    function formatCurrencyLocal(amount) {
        if (window.SmartSpending && typeof window.SmartSpending.formatCurrency === 'function') {
            return window.SmartSpending.formatCurrency(amount);
        }
        try {
            const num = parseFloat(amount || 0);
            return num.toLocaleString('vi-VN');
        } catch (e) {
            return amount;
        }
    }

    function init() {
        bindUI();
        loadJarBalances();
        loadBudgets();
        loadCharts();
    }

    window.syncJarsApi = async function () {
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const resp = await fetch(`${BASE_URL}/dashboard/sync_jars`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-CSRF-Token': csrf }
            });

            if (resp.ok) {
                const resultText = await resp.text();
                if (resultText.includes('🎉 Đã Fix Xong!')) {
                    return { success: true };
                }
            }
            return { success: false };
        } catch (e) {
            console.error('Error syncing Jars:', e);
            return { success: false, message: e.message };
        }
    }

    async function loadJarBalances() {
        try {
            const response = await fetch(`${BASE_URL}/budgets/api_get_wallets`, { cache: 'no-store' });
            if (!response.ok) throw new Error('API error');
            const data = await response.json();

            if (data.success && Array.isArray(data.data)) {
                data.data.forEach(jar => {
                    const code = jar.jar_code;
                    const balance = parseFloat(jar.balance || 0);
                    const percent = jar.percent;

                    const balanceEl = document.getElementById(`jar-balance-${code}`);
                    if (balanceEl) balanceEl.innerHTML = `${formatCurrencyLocal(balance)} <small class="text-muted fs-6">₫</small>`;

                    const percentEl = document.getElementById(`jar-percent-${code}`);
                    if (percentEl) percentEl.innerText = `${percent}%`;

                    const waterEl = document.getElementById(`jar-water-${code}`);
                    if (waterEl) {
                        const waterHeight = Math.min(100, (balance / 10000000) * 100);
                        waterEl.style.height = `${balance > 0 && waterHeight < 15 ? 15 : waterHeight}%`;
                    }
                });
            }
        } catch (error) {
            console.error('Error loading Jar Balances:', error);
        }
    }

    function bindUI() {
        periodSelect?.addEventListener('change', (e) => {
            currentPeriod = e.target.value;
            loadBudgets();
        });

        const modalEl = document.getElementById('createBudgetModal');
        const modalTitle = document.getElementById('budgetModalTitle');
        const budgetIdInput = document.getElementById('budget_id');
        const createForm = document.getElementById('createBudgetForm');

        document.getElementById('openCreateBudget')?.addEventListener('click', () => {
            createForm.reset();
            if (modalTitle) modalTitle.innerText = 'Thiết lập ngân sách';
            if (budgetIdInput) budgetIdInput.value = '';
            if (modalEl) {
                const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
                modalInstance.show();
            }
        });

        if (createForm) {
            createForm.removeEventListener('submit', handleBudgetSubmit);
            createForm.addEventListener('submit', handleBudgetSubmit);
        }

        window.deleteBudget = async function (id, btn) {
            if (!confirm('Đại ca có chắc chắn muốn xóa ngân sách này không?')) return;
            let originalHtml = null;
            if (btn) {
                btn.disabled = true;
                originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            }
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const resp = await fetch(`${BASE_URL}/budgets/api_delete_budget`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                    body: JSON.stringify({ id: id, csrf_token: csrf })
                });
                let res = await resp.json();
                if (res.success) {
                    if (window.SmartSpending && window.SmartSpending.showToast) window.SmartSpending.showToast('Đã xóa ngân sách!', 'success');
                    loadBudgets();
                    loadJarBalances();
                    window.dispatchEvent(new CustomEvent('jars:updated'));
                } else {
                    if (window.SmartSpending && window.SmartSpending.showToast) window.SmartSpending.showToast(res.message, 'error');
                }
            } catch (e) {
                console.error(e);
            } finally {
                if (btn && originalHtml !== null) {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            }
        };

        const categoryList = document.getElementById('categoryList');
        const chooserModalEl = document.getElementById('categoryChooserModal');
        const createModalEl = document.getElementById('createBudgetModal');

        if (categoryList) {
            categoryList.addEventListener('click', function (e) {
                const item = e.target.closest('.category-item');
                if (item) {
                    e.preventDefault();
                    const categoryId = item.dataset.categoryId;
                    const categoryName = item.dataset.categoryName;
                    document.getElementById('budget_category_picker').value = categoryName;
                    document.getElementById('budget_category').value = categoryId;
                    bootstrap.Modal.getInstance(chooserModalEl)?.hide();
                    bootstrap.Modal.getInstance(createModalEl)?.show();
                }
            });
        }

        if (chooserModalEl && createModalEl) {
            chooserModalEl.addEventListener('show.bs.modal', function () {
                bootstrap.Modal.getInstance(createModalEl)?.hide();
            });
        }

        const smartBudgetModal = document.getElementById('smartBudgetModal');
        if (smartBudgetModal) {
            smartBudgetModal.addEventListener('hidden.bs.modal', function () {
                loadJarBalances();
                loadBudgets();
            });
        }
    }

    async function loadBudgets() {
        try {
            const response = await fetch(`${BASE_URL}/budgets/api_get_list?period=${currentPeriod}`);
            if (!response.ok) throw new Error('Network response was not ok');
            const data = await response.json();
            if (data.success && data.data) {
                budgetsListCache = data.data;
                renderTable(data.data);
                loadCharts();
                await loadAlerts();
            } else {
                console.error('API Error:', data.message);
                renderTable([]);
            }
        } catch (error) {
            console.error('Error loading budgets:', error);
            renderTable([]);
        }
    }

    // Load server-side alerts (budgets that reached alert threshold)
    async function loadAlerts() {
        try {
            const resp = await fetch(`${BASE_URL}/budgets/api_get_alerts?period=${currentPeriod}`, { cache: 'no-store' });
            if (!resp.ok) return;
            const res = await resp.json();
            if (res.success && Array.isArray(res.data) && res.data.length > 0) {
                res.data.forEach(a => {
                    const pct = parseFloat(a.percentage_used || ((a.spent && a.amount) ? (a.spent / a.amount * 100) : 0)).toFixed(1);
                    const msg = `⚠️ Ngân sách ${a.category_name} đã đạt ${pct}% (${formatCurrencyLocal(a.spent)} / ${formatCurrencyLocal(a.amount)})`;
                    if (window.SmartSpending && window.SmartSpending.showToast) {
                        window.SmartSpending.showToast(msg, 'warning');
                    } else {
                        console.warn(msg);
                    }
                });
            }
        } catch (e) {
            console.error('Error loading budget alerts:', e);
        }
    }

    // [QUAN TRỌNG] Render bảng với icon cảnh báo
    function renderTable(budgets) {
        if (!tableBody) return;
        tableBody.innerHTML = '';
        if (!budgets || budgets.length === 0) {
            if (emptyState) emptyState.style.display = 'block';
            return;
        }
        if (emptyState) emptyState.style.display = 'none';

        budgets.forEach(b => {
            const spent = parseFloat(b.spent || 0);
            const amount = parseFloat(b.amount || 0);
            // Lấy số dư hũ từ backend gửi xuống
            const jarBalance = parseFloat(b.current_jar_balance || 0); 

            let percent = amount > 0 ? (spent / amount) * 100 : 0;
            if (percent > 100) percent = 100;

            const alertThreshold = parseFloat(b.alert_threshold || 80);
            const jarCode = (b.category_group || 'none').toLowerCase();
            const jarBgClass = `bg-${jarCode}-subtle`;
            const jarTextClass = `text-${jarCode}`;

            let pColorClass = `bg-${jarCode}`;
            if (percent >= 100) {
                pColorClass = 'bg-danger';
            } else if (percent >= alertThreshold) {
                pColorClass = 'bg-warning';
            }

            const spentFormatted = formatCurrencyLocal(spent);
            const amountFormatted = formatCurrencyLocal(amount);

            // LOGIC CẢNH BÁO: Cải tiến
            // - Nếu hũ không tồn tại (none) => không cảnh báo
            // - Nếu số dư <= 0 => cảnh báo nghiêm trọng (đỏ)
            // - Nếu số dư < amount => cảnh báo, phân biệt nghiêm trọng nếu thiếu >= 50% ngân sách
            let warningIcon = '';
            if (jarCode !== 'none') {
                const current = parseFloat(jarBalance || 0);
                const target = parseFloat(amount || 0);

                if (target <= 0) {
                    // Nếu ngân sách bằng 0, chỉ báo nếu hũ rỗng
                    if (current <= 0) {
                        const tooltip = `⚠️ <b>Hũ ${jarCode.toUpperCase()} rỗng</b><br>Hiện có: ${formatCurrencyLocal(current)} đ`;
                        warningIcon = `<span class="ms-2" data-bs-toggle="tooltip" data-bs-html="true" title="${tooltip}"><i class="fas fa-exclamation-circle text-danger" style="cursor:help"></i></span>`;
                    }
                } else {
                    // Treat negative jar balances as 0 for available funds when computing shortfall
                    const available = Math.max(0, current);
                    const shortfall = Math.max(0, target - available);
                        if (shortfall > 0) {
                            const coveredPct = target > 0 ? Math.min(100, (available / target) * 100) : 0;
                            const shortPct = 100 - coveredPct;
                            const shortFmt = formatCurrencyLocal(shortfall);
                            const currentFmt = formatCurrencyLocal(current);
                            const tooltip = `⚠️ <b>Thiếu tiền hũ</b><br>Hũ ${jarCode.toUpperCase()}: ${currentFmt} đ<br>Ngân sách: ${formatCurrencyLocal(target)} đ<br>Thiếu: ${shortFmt} đ (${shortPct.toFixed(1)}%)`;

                            // Nếu thiếu lớn (>=50% ngân sách) => đỏ, ngược lại vàng
                            if (shortPct >= 50) {
                                warningIcon = `<span class="ms-2" data-bs-toggle="tooltip" data-bs-html="true" title="${tooltip}"><i class="fas fa-exclamation-triangle text-danger" style="cursor: help"></i></span>`;
                            } else {
                                warningIcon = `<span class="ms-2" data-bs-toggle="tooltip" data-bs-html="true" title="${tooltip}"><i class="fas fa-exclamation-triangle text-warning" style="cursor: help"></i></span>`;
                            }
                    } else if (current > 0 && current <= (target * 0.1)) {
                        // Dư rất thấp nhưng chưa thiếu => cảnh báo nhẹ
                        const tooltip = `⚠️ <b>Hũ ${jarCode.toUpperCase()} còn ít</b><br>Hiện có: ${formatCurrencyLocal(current)} đ<br>Ngân sách: ${formatCurrencyLocal(target)} đ`;
                        warningIcon = `<span class="ms-2" data-bs-toggle="tooltip" data-bs-html="true" title="${tooltip}"><i class="fas fa-info-circle text-warning" style="cursor: help"></i></span>`;
                    }
                }
            }

            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="ps-4">
                    <div class="d-flex align-items-center">
                        <div class="me-3 ${jarBgClass} ${jarTextClass}" 
                            style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 12px; font-size: 1.1rem;">
                            <i class="fas ${b.category_icon || 'fa-circle'}"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark">${b.category_name}</div>
                            <small class="fw-semibold ${jarTextClass}">${(jarCode).toUpperCase()}</small>
                        </div>
                    </div>
                </td>
                
                <td class="text-end fw-semibold budget-amount-cell" style="white-space: nowrap;">
                    <span class="text-danger">${spentFormatted} </span>
                    <span class="text-muted"> / ${amountFormatted} </span>
                    ${warningIcon}
                </td>
                
                <td class="ps-4 align-middle" style="min-width: 150px;">
                    <div class="progress" style="height: 18px; border-radius: 10px; position: relative; background-color: #e9ecef;">
                        <div class="progress-bar ${pColorClass}" 
                            role="progressbar" 
                            style="width: ${Math.min(percent, 100)}%; border-radius: 10px;">
                        </div>
                        <span style="position: absolute; left: 0; width: 100%; text-align: center; color: #444; font-weight: 700; font-size: 11px; line-height: 18px; text-shadow: 0px 0px 2px #fff;">
                            ${parseFloat(percent).toFixed(1)}%
                        </span>
                    </div>
                </td>
                
                <td class="text-end pe-4 align-middle">
                    <div class="d-flex gap-2 justify-content-end align-items-center">
                        <button class="btn btn-sm btn-light text-primary" onclick="openEditBudget(${b.id})" title="Sửa">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-light text-danger" onclick="deleteBudget(${b.id}, this)" title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            `;
            tableBody.appendChild(row);
        });

        // Kích hoạt tooltip bootstrap
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    window.openEditBudget = function (budgetId) {
        const budget = budgetsListCache.find(b => b.id == budgetId);
        const modalEl = document.getElementById('createBudgetModal');
        const modalTitle = document.getElementById('budgetModalTitle');

        if (!budget || !modalEl) return;

        if (modalTitle) modalTitle.innerText = 'Sửa Ngân Sách';
        document.getElementById('budget_id').value = budget.id;
        document.getElementById('budget_category').value = budget.category_id;
        document.getElementById('budget_category_picker').value = budget.category_name;

        const formattedAmount = formatCurrencyLocal(budget.amount);
        document.getElementById('budget_amount_display').value = formattedAmount;
        document.getElementById('budget_amount').value = budget.amount;
        document.getElementById('budget_period').value = budget.period;
        document.getElementById('budget_threshold').value = budget.alert_threshold;

        const thresholdValueEl = document.getElementById('thresholdValue');
        if (thresholdValueEl) thresholdValueEl.innerText = budget.alert_threshold + '%';

        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
        modalInstance.show();
    };

    async function handleBudgetSubmit(e) {
        e.preventDefault();
        const btn = e.submitter;
        const oldText = btn.innerHTML;
        const budgetId = document.getElementById('budget_id')?.value || 0;
        const isEdit = budgetId > 0;
        const apiEndpoint = isEdit ? `${BASE_URL}/budgets/api_update` : `${BASE_URL}/budgets/api_create`;

        if (btn.classList.contains('is-submitting')) return;
        btn.classList.add('is-submitting');
        btn.disabled = true;
        btn.innerHTML = 'Đang xử lý...';

        const fd = new FormData(e.target);
        const amountRaw = document.getElementById('budget_amount')?.value || '';

        const data = {
            budget_id: isEdit ? budgetId : undefined,
            category_id: fd.get('category_id'),
            amount: amountRaw,
            period: fd.get('period'),
            alert_threshold: document.getElementById('budget_threshold').value
        };

        if (!data.category_id) {
            alert('Vui lòng chọn danh mục.');
            btn.classList.remove('is-submitting');
            btn.disabled = false; btn.innerHTML = oldText;
            return;
        }

        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const resp = await fetch(apiEndpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(Object.assign({}, data, { csrf_token: csrf }))
            });

            let res = await resp.json();

            if (res.success) {
                const modal = document.getElementById('createBudgetModal');
                if (modal) bootstrap.Modal.getInstance(modal)?.hide();
                loadBudgets();
                loadJarBalances();
                window.dispatchEvent(new CustomEvent('jars:updated'));
                e.target.reset();
                if (document.getElementById('budgetModalTitle')) document.getElementById('budgetModalTitle').innerText = 'Thiết lập ngân sách';
                document.getElementById('budget_id').value = '';
                if (window.SmartSpending && window.SmartSpending.showToast) window.SmartSpending.showToast(res.message, 'success');
            } else {
                if (window.SmartSpending && window.SmartSpending.showToast) window.SmartSpending.showToast(res.message, 'error');
                else alert(res.message);
            }
        } catch (err) {
            console.error(err);
        } finally {
            btn.classList.remove('is-submitting');
            btn.disabled = false;
            btn.innerHTML = oldText;
        }
    }

    function ensureFreshCanvas(canvasEl) {
        if (!canvasEl) return null;
        try {
            const existing = (typeof Chart !== 'undefined' && Chart.getChart) ? Chart.getChart(canvasEl) : null;
            if (existing && typeof existing.destroy === 'function') {
                try { existing.destroy(); } catch (e) { }
            }
        } catch (e) { }
        try {
            const newCanvas = canvasEl.cloneNode(true);
            canvasEl.parentNode.replaceChild(newCanvas, canvasEl);
            return newCanvas;
        } catch (e) {
            return canvasEl;
        }
    }

    async function loadTrendChart() {
        const freshTrend = ensureFreshCanvas(document.getElementById('budgetTrend'));
        if (freshTrend) {
            if (trendChartInstance) { try { trendChartInstance.destroy(); } catch (e) { } trendChartInstance = null; }
            try {
                const resp = await fetch(`${BASE_URL}/budgets/api_get_trend?months=6`, { cache: 'no-store' });
                if (!resp.ok) throw new Error('API error');
                const res = await resp.json();
                if (res.success && res.data && res.data.trend) {
                    const ctxReal = (freshTrend.getContext && freshTrend.getContext('2d')) ? freshTrend.getContext('2d') : freshTrend;
                    trendChartInstance = new Chart(ctxReal, {
                        type: 'bar',
                        data: {
                            labels: res.data.trend.labels || [],
                            datasets: [
                                {
                                    label: 'Ngân sách',
                                    data: (res.data.trend.budget || []).map(Number),
                                    backgroundColor: '#a3a3a3',
                                    borderRadius: 4,
                                    borderSkipped: false
                                },
                                {
                                    label: 'Thực chi',
                                    data: (res.data.trend.spent || []).map(Number),
                                    backgroundColor: '#0d6efd',
                                    borderRadius: 4,
                                    borderSkipped: false
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'top', labels: { boxWidth: 10, padding: 20 } },
                                tooltip: {
                                    callbacks: {
                                        label: function (c) {
                                            return c.dataset.label + ': ' + formatCurrencyLocal(c.parsed.y) + ' ₫';
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: { grid: { display: false } },
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: (val) => formatCurrencyLocal(val),
                                        padding: 10
                                    }
                                }
                            }
                        }
                    });
                }
            } catch (e) { }
        }
    }

    async function loadDistributionChart() {
        const freshPie = ensureFreshCanvas(document.getElementById('budgetPie'));
        if (freshPie) {
            if (pieChartInstance) { try { pieChartInstance.destroy(); } catch (e) { } pieChartInstance = null; }
            try {
                // Use existing API that returns wallet objects: [{jar_code,balance,percent},...]
                const resp = await fetch(`${BASE_URL}/budgets/api_get_wallets`, { cache: 'no-store', credentials: 'same-origin' });
                // Default safe percentages
                let jarsData = [55, 10, 10, 10, 10, 5];
                if (resp.ok) {
                    try {
                        const jr = await resp.json();
                        if (jr && jr.success && Array.isArray(jr.data) && jr.data.length >= 6) {
                            // Map by jar_code to ensure correct order
                            const order = ['nec','ffa','ltss','edu','play','give'];
                            const map = {};
                            jr.data.forEach(d => { map[(d.jar_code||'').toLowerCase()] = d; });

                            // Try to use percent values first
                            const percents = order.map(code => {
                                const item = map[code] || {};
                                return (item.percent !== undefined && item.percent !== null && item.percent !== '') ? Number(item.percent) : null;
                            });

                            const hasAllPercents = percents.every(v => v !== null && !isNaN(v));
                            if (hasAllPercents) {
                                jarsData = percents.map(v => +Number(v).toFixed(1));
                                // ensure sums to 100
                                let total = jarsData.reduce((a,b)=>a+b,0);
                                if (Math.abs(total - 100) > 0.1) {
                                    const maxIdx = jarsData.indexOf(Math.max(...jarsData));
                                    jarsData[maxIdx] = +(jarsData[maxIdx] + (100 - total)).toFixed(1);
                                }
                            } else {
                                // Fallback: compute from balances
                                const balances = order.map(code => {
                                    const item = map[code] || {};
                                    return Number(item.balance || 0);
                                });
                                const sum = balances.reduce((s, v) => s + (isFinite(v) ? v : 0), 0);
                                if (sum > 0) {
                                    jarsData = balances.map(v => +((v / sum) * 100).toFixed(1));
                                    let total = jarsData.reduce((a,b)=>a+b,0);
                                    if (Math.abs(total - 100) > 0.1) {
                                        const maxIdx = balances.indexOf(Math.max(...balances));
                                        jarsData[maxIdx] = +(jarsData[maxIdx] + (100 - total)).toFixed(1);
                                    }
                                }
                            }
                        }
                    } catch (e) { }
                }

                const labels = ['Thiết yếu (NEC)', 'Tự do TC (FFA)', 'TK dài hạn (LTSS)', 'Giáo dục (EDU)', 'Hưởng thụ (PLAY)', 'Cho đi (GIVE)'];
                const colors = ['#dc3545', '#f59e0b', '#0d6efd', '#0dcaf0', '#d63384', '#198754'];

                const ctxPieReal = (freshPie.getContext && freshPie.getContext('2d')) ? freshPie.getContext('2d') : freshPie;
                pieChartInstance = new Chart(ctxPieReal, {
                    type: 'doughnut',
                    data: {
                        labels: labels.slice(0, jarsData.length),
                        datasets: [{
                            data: jarsData,
                            backgroundColor: colors.slice(0, jarsData.length),
                            borderWidth: 2,
                            hoverOffset: 10,
                            borderRadius: 5
                        }]
                    },
                    options: {
                        cutout: '50%',
                        responsive: true,
                        maintainAspectRatio: false,
                        aspectRatio: 1.2,
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: { usePointStyle: true, pointStyle: 'rect', boxWidth: 10, padding: 12, font: { size: 12 } }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        var val = Number(context.raw || 0);
                                        // show with 1 decimal if needed, otherwise integer
                                        var display = (Math.abs(val - Math.round(val)) >= 0.05) ? val.toFixed(1) : Math.round(val);
                                        return context.label + ': ' + display + '%';
                                    }
                                }
                            }
                        },
                        layout: { padding: { left: 10, right: 10, top: 6, bottom: 6 } },
                        elements: { arc: { borderWidth: 0 } }
                    }
                });
            } catch (e) { }
        }
    }

    async function loadCharts() {
        await loadTrendChart();
        await loadDistributionChart();
    }

    document.addEventListener('DOMContentLoaded', init);
})();

if (typeof window.SmartSpending === 'undefined') window.SmartSpending = {};
window.SmartSpending.showToast = function (message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }
    let icon = 'fa-check-circle';
    if (type === 'error') icon = 'fa-times-circle';
    if (type === 'warning') icon = 'fa-exclamation-triangle';
    const toast = document.createElement('div');
    toast.className = `custom-toast toast-${type}`;
    toast.innerHTML = `<div class="toast-content"><i class="fas ${icon} fa-lg"></i><span>${message}</span></div><i class="fas fa-times toast-close" onclick="this.parentElement.remove()"></i>`;
    container.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 400);
    }, 3000);
};