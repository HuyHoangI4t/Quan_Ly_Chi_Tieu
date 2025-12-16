function formatInputMoney(input) {
    // Lấy giá trị hiện tại, loại bỏ các ký tự không phải số
    let rawValue = (input.value || '').toString().replace(/\D/g, '');

    if (rawValue) {
        // Định dạng lại số tiền (ví dụ: 1000000 -> 1.000.000)
        let formattedValue = new Intl.NumberFormat('vi-VN').format(rawValue);
        input.value = formattedValue;

        // Cập nhật giá trị thực (chỉ số) vào input hidden
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
    let budgetsListCache = []; // Cache dữ liệu ngân sách

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

    // Hàm gọi API đồng bộ JARS (dùng cho smart-budget.js)
    window.syncJarsApi = async function () {
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const resp = await fetch(`${BASE_URL}/dashboard/sync_jars`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-Token': csrf
                }
            });

            if (resp.status === 401 || resp.status === 403) {
                if (window.SmartSpending && window.SmartSpending.showToast) window.SmartSpending.showToast('Lỗi phiên đăng nhập hoặc CSRF token.', 'error');
                return { success: false, message: 'Auth/CSRF Error' };
            }

            if (resp.ok) {
                const resultText = await resp.text();

                if (resultText.includes('🎉 Đã Fix Xong!')) {
                    return { success: true };
                } else {
                    console.error('Sync Jars Failed:', resultText);
                    return { success: false, message: 'Sync API failed, check console for PHP output.' };
                }
            } else {
                if (window.SmartSpending && window.SmartSpending.showToast) window.SmartSpending.showToast(`Lỗi HTTP ${resp.status} khi đồng bộ.`, 'error');
                return { success: false, message: 'Server responded with error status: ' + resp.status };
            }

        } catch (e) {
            console.error('Error syncing Jars:', e);
            if (window.SmartSpending && window.SmartSpending.showToast) window.SmartSpending.showToast('Lỗi kết nối mạng khi đồng bộ ví Jars.', 'error');
            return { success: false, message: e.message };
        }
    }

    // Hàm load Số dư Jars từ API và cập nhật UI (real-time)
    async function loadJarBalances() {
        console.log("DEBUG: loadJarBalances() called to refresh JARS UI.");
        try {
            const response = await fetch(`${BASE_URL}/budgets/api_get_wallets`, { cache: 'no-store' });
            if (!response.ok) throw new Error('API error');
            const data = await response.json();

            if (data.success && Array.isArray(data.data)) {
                // Cập nhật từng hũ
                data.data.forEach(jar => {
                    const code = jar.jar_code;
                    const balance = parseFloat(jar.balance || 0);
                    const percent = jar.percent;

                    // 1. Cập nhật Số dư
                    const balanceEl = document.getElementById(`jar-balance-${code}`);
                    if (balanceEl) {
                        balanceEl.innerHTML = `${formatCurrencyLocal(balance)} <small class="text-muted fs-6">₫</small>`;
                    }

                    // 2. Cập nhật Tỷ lệ
                    const percentEl = document.getElementById(`jar-percent-${code}`);
                    if (percentEl) {
                        percentEl.innerText = `${percent}%`;
                    }

                    // 3. Cập nhật Hiệu ứng nước
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
            // Reset form và trạng thái khi mở modal tạo mới
            createForm.reset();
            if (modalTitle) modalTitle.innerText = 'Thiết lập ngân sách';
            if (budgetIdInput) budgetIdInput.value = '';

            if (modalEl) {
                const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
                modalInstance.show();
            }
        });

        // [FIX TẠO 1 RA 3 & ĐỔI TÊN HÀM] Hủy đăng ký sự kiện trước khi đăng ký lại (handleBudgetSubmit xử lý cả tạo và sửa)
        if (createForm) {
            createForm.removeEventListener('submit', handleBudgetSubmit);
            createForm.addEventListener('submit', handleBudgetSubmit);
        }

        // [FIX LỖI XÓA] (Giữ nguyên logic)
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

                let res;
                try {
                    res = await resp.json();
                } catch (e) {
                    console.error("Failed to parse JSON response on delete:", e);
                    if (window.SmartSpending && window.SmartSpending.showToast) window.SmartSpending.showToast('Lỗi server: Phản hồi không hợp lệ.', 'error');
                    return;
                }

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
                budgetsListCache = data.data; // Cache dữ liệu
                renderTable(data.data);
                loadCharts();
            } else {
                console.error('API Error:', data.message);
                renderTable([]);
            }
        } catch (error) {
            console.error('Error loading budgets:', error);
            renderTable([]);
        }
    }

// [FIX LỆCH DÒNG VÀ BACKGROUND NÚT] Tối ưu hóa renderTable
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

            let percent = amount > 0 ? (spent / amount) * 100 : 0;
            if (percent > 100) percent = 100;

            const alertThreshold = parseFloat(b.alert_threshold || 80);

            // Logic màu JARS và Tên Hũ
            const jarCode = (b.category_group || 'none').toLowerCase(); 
            const jarBgClass = `bg-${jarCode}-subtle`;
            const jarTextClass = `text-${jarCode}`;
            
            // Tính toán màu sắc cho thanh tiến trình
            let pColorClass = `bg-${jarCode}`; 
            
            if (percent >= 100) {
                pColorClass = 'bg-danger'; 
            } else if (percent >= alertThreshold) {
                pColorClass = 'bg-warning'; 
            }
            if (jarCode === 'none') {
                 pColorClass = percent >= 100 ? 'bg-danger' : (percent >= alertThreshold ? 'bg-warning' : 'bg-success');
            }


            const spentFormatted = formatCurrencyLocal(spent);
            const amountFormatted = formatCurrencyLocal(amount);
            
            // Màu nền mờ cho nút Sửa (Vàng/Cam subtle)
            const editBgClass = 'bg-ffa-subtle'; 
            // Màu nền mờ cho nút Xóa (Đỏ subtle - dùng NEC)
            const deleteBgClass = 'bg-nec-subtle'; 
            
            // Kích thước nút hành động
            const actionStyle = "width: 32px; height: 32px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; transition: background 0.3s;";
            

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
                </td>
                
                <td class="ps-4 align-middle" style="min-width: 150px;">
                    <div class="progress" style="height: 6px; border-radius: 3px;">
                        <div class="progress-bar ${pColorClass}" style="width: ${percent}%"></div>
                    </div>
                </td>
                
                <td class="text-end pe-4 align-middle">
                    <div class="d-flex gap-2 justify-content-end align-items-center">
                        <span class="${editBgClass} text-ffa opacity-80 hover-opacity-100" style="${actionStyle}">
                            <button class="btn btn-sm p-0 text-ffa" onclick="openEditBudget(${b.id})" style="line-height: 1;">
                                <i class="fas fa-edit"></i>
                            </button>
                        </span>
                        
                        <span class="${deleteBgClass} text-danger opacity-80 hover-opacity-100" style="${actionStyle}">
                            <button class="btn btn-sm p-0 text-danger" onclick="deleteBudget(${b.id}, this)" style="line-height: 1;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </span>
                    </div>
                </td>
            `;
            tableBody.appendChild(row);
        });
    }

    // [HÀM MỚI] Xử lý mở modal để sửa ngân sách
    window.openEditBudget = function (budgetId) {
        const budget = budgetsListCache.find(b => b.id === budgetId);
        const modalEl = document.getElementById('createBudgetModal');
        const modalTitle = document.getElementById('budgetModalTitle');

        if (!budget || !modalEl) {
            console.error('Budget data not found for ID:', budgetId);
            return;
        }

        // Set form title
        if (modalTitle) modalTitle.innerText = 'Sửa Ngân Sách';

        // Đổ dữ liệu vào form
        document.getElementById('budget_id').value = budget.id;
        document.getElementById('budget_category').value = budget.category_id;
        document.getElementById('budget_category_picker').value = budget.category_name;

        // Đổ tiền vào input display (cần định dạng lại)
        const formattedAmount = formatCurrencyLocal(budget.amount);
        document.getElementById('budget_amount_display').value = formattedAmount;
        document.getElementById('budget_amount').value = budget.amount;

        document.getElementById('budget_period').value = budget.period;
        document.getElementById('budget_threshold').value = budget.alert_threshold;

        // Cập nhật giá trị hiển thị của thanh trượt cảnh báo
        const thresholdValueEl = document.getElementById('thresholdValue');
        if (thresholdValueEl) thresholdValueEl.innerText = budget.alert_threshold + '%';

        // Mở modal
        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
        modalInstance.show();
    };


    // [HÀM MỚI] Xử lý cả Tạo và Sửa Ngân sách
    async function handleBudgetSubmit(e) {
        e.preventDefault();
        const btn = e.submitter;
        const oldText = btn.innerHTML;

        // Lấy ID ngân sách để xác định là Tạo mới hay Sửa
        const budgetId = document.getElementById('budget_id')?.value || 0;
        const isEdit = budgetId > 0;
        const apiEndpoint = isEdit ? `${BASE_URL}/budgets/api_update` : `${BASE_URL}/budgets/api_create`;

        // --- HARD FIX: KIỂM TRA ĐĂNG KÝ TRÙNG LẶP ---
        if (btn.classList.contains('is-submitting')) {
            console.warn("Submit ignored: Already processing.");
            return;
        }
        btn.classList.add('is-submitting');
        // --- END HARD FIX ---

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
            if (window.SmartSpending && window.SmartSpending.showToast) window.SmartSpending.showToast('Vui lòng chọn danh mục.', 'warning');
            else alert('Vui lòng chọn danh mục.');

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

            const responseText = await resp.text();

            let res;
            try {
                res = JSON.parse(responseText);
            } catch (e) {
                console.error('Non-JSON response received (FATAL ERROR LIKELY):', responseText);
                res = { success: false, message: 'Lỗi API Server hoặc Lỗi PHP nghiêm trọng (FATAL ERROR). Vui lòng kiểm tra PHP Error Log.' };
            }

            if (res.success) {
                const modal = document.getElementById('createBudgetModal');
                if (modal) {
                    bootstrap.Modal.getInstance(modal)?.hide();
                }
                loadBudgets();
                loadJarBalances();
                window.dispatchEvent(new CustomEvent('jars:updated'));

                e.target.reset();
                // Reset modal state
                if (document.getElementById('budgetModalTitle')) document.getElementById('budgetModalTitle').innerText = 'Thiết lập ngân sách';
                document.getElementById('budget_id').value = '';

                if (window.SmartSpending && window.SmartSpending.showToast) window.SmartSpending.showToast(res.message, 'success');
                else alert(res.message);
            } else {
                let msg = res.message || 'Lỗi';

                // Hiển thị thông báo số dư chi tiết
                if (res.data && res.data.jar_code) {
                    const balance = res.data.current_balance;
                    const jar = res.data.jar_code;
                    const missing = res.data.missing_amount;

                    msg = `❌ ${msg} Hũ **${jar}** chỉ còn ${balance}₫. (Cần thêm ${missing}₫)`;
                } else if (res.data && res.data.message) {
                    msg += "\n" + res.data.message;
                }

                if (window.SmartSpending && window.SmartSpending.showToast) window.SmartSpending.showToast(msg, 'error');
                else alert(msg);
            }
        } catch (err) {
            if (window.SmartSpending && window.SmartSpending.showToast) window.SmartSpending.showToast('Lỗi hệ thống', 'error');
            else alert('Lỗi hệ thống');
            console.error(err);
        }
        finally {
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
                try { existing.destroy(); } catch (e) { /* ignore */ }
            }
        } catch (e) { /* ignore */ }

        try {
            const newCanvas = canvasEl.cloneNode(true);
            canvasEl.parentNode.replaceChild(newCanvas, canvasEl);
            return newCanvas;
        } catch (e) {
            return canvasEl;
        }
    }

    // Hàm load Biểu đồ Xu hướng (Bar Chart)
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
            } catch (e) {
                console.warn('loadTrendChart error:', e);
                if (window.SmartSpending && window.SmartSpending.showToast) {
                    window.SmartSpending.showToast('Lỗi tải biểu đồ xu hướng. Vui lòng kiểm tra Console (F12).', 'error');
                }
            }
        }
    }

    // Hàm load Biểu đồ Phân bổ JARS (Doughnut Chart)
    async function loadDistributionChart() {
        const freshPie = ensureFreshCanvas(document.getElementById('budgetPie'));
        if (freshPie) {
            if (pieChartInstance) { try { pieChartInstance.destroy(); } catch (e) { } pieChartInstance = null; }
            try {
                const resp = await fetch(`${BASE_URL}/budgets/api_get_jars`, { cache: 'no-store', credentials: 'same-origin' });
                let jarsData = [55, 10, 10, 10, 10, 5];
                if (resp.ok) {
                    try {
                        const jr = await resp.json();
                        if (jr && jr.success && jr.data && Array.isArray(jr.data.jars) && jr.data.jars.length === 6) {
                            jarsData = jr.data.jars.map(Number);
                        }
                    } catch (e) { /* fall back to defaults */ }
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
                                labels: {
                                    usePointStyle: true,
                                    pointStyle: 'rect',
                                    boxWidth: 10,
                                    padding: 12,
                                    font: { size: 12 }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        const label = context.label;
                                        const value = Number(context.raw || 0);
                                        return label + ': ' + value + '%';
                                    }
                                }
                            }
                        },
                        layout: { padding: { left: 10, right: 10, top: 6, bottom: 6 } },
                        elements: { arc: { borderWidth: 0 } }
                    }
                });
            } catch (e) {
                console.warn('loadDistributionChart error', e);
            }
        }
    }

    // [CẬP NHẬT] Hàm gọi cả hai biểu đồ
    async function loadCharts() {
        await loadTrendChart();
        await loadDistributionChart();
    }


    document.addEventListener('DOMContentLoaded', init);
})();

    /* --- Migrated SmartSpending.showToast from view (ensure it's available) --- */
    if (typeof window.SmartSpending === 'undefined') window.SmartSpending = {};

    window.SmartSpending.showToast = function(message, type = 'success') {
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
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas ${icon} fa-lg"></i>
                <span>${message}</span>
            </div>
            <i class="fas fa-times toast-close" onclick="this.parentElement.remove()"></i>
        `;

        container.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 400);
        }, 3000);
    };

    console.log("✅ Toast System Loaded Successfully!");

    /* --- End migrated toast --- */