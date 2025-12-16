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
        console.log("DEBUG: loadJarBalances() called to refresh JARS UI."); // DEBUG LOG
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

        document.getElementById('openCreateBudget')?.addEventListener('click', () => {
            const modalEl = document.getElementById('createBudgetModal');
            if (modalEl) {
                const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
                modalInstance.show();
            }
        });

        const createForm = document.getElementById('createBudgetForm');
        // [FIX LỖI TẠO 1 RA 3] Hủy đăng ký sự kiện trước khi đăng ký lại (đảm bảo hàm chỉ được gọi 1 lần)
        if (createForm) {
            createForm.removeEventListener('submit', handleCreateBudget); 
            createForm.addEventListener('submit', handleCreateBudget);
        }

        // [FIX LỖI XÓA] Cập nhật hàm deleteBudget để gọi API mới và xử lý tham số 'this'
        window.deleteBudget = async function (id, btn) {
            if (!confirm('Đại ca có chắc chắn muốn xóa ngân sách này không?')) return;
            
            // [FIX SCOPE] Khai báo originalHtml ở phạm vi hàm
            let originalHtml = null; 

            if (btn) {
                btn.disabled = true;
                originalHtml = btn.innerHTML; // Gán giá trị vào biến đã khai báo
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; 
            }

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const resp = await fetch(`${BASE_URL}/budgets/api_delete_budget`, { // <-- Gọi API PHP mới
                    method: 'POST', 
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                    body: JSON.stringify({ id: id, csrf_token: csrf }) // Gửi ID qua body
                });
                
                let res;
                try {
                    res = await resp.json();
                } catch (e) {
                    // Xử lý Lỗi JSON/HTML (lỗi <!)
                    console.error("Failed to parse JSON response on delete:", e);
                    if (resp.status === 401 || resp.status === 403) {
                         if (window.SmartSpending && window.SmartSpending.showToast) window.SmartSpending.showToast('Phiên đăng nhập hết hạn. Vui lòng F5.', 'error');
                    } else if (window.SmartSpending && window.SmartSpending.showToast) {
                        window.SmartSpending.showToast('Lỗi server: Phản hồi không hợp lệ.', 'error');
                    }
                    return;
                }

                if (res.success) {
                    if (window.SmartSpending && window.SmartSpending.showToast) window.SmartSpending.showToast('Đã xóa ngân sách!', 'success');
                    loadBudgets();
                    loadJarBalances(); // [QUAN TRỌNG] Tải lại số dư sau khi xóa (hoàn tiền)
                } else {
                    if (window.SmartSpending && window.SmartSpending.showToast) window.SmartSpending.showToast(res.message, 'error');
                }
            } catch (e) { 
                console.error(e); 
            } finally {
                 if (btn && originalHtml !== null) { // Khôi phục nếu nút tồn tại và HTML đã được lưu
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

        // Tự động load lại sau khi modal đóng hoàn toàn
        const createBudgetModal = document.getElementById('createBudgetModal');
        if (createBudgetModal) {
            createBudgetModal.addEventListener('hidden.bs.modal', function () {
                // Hủy bỏ việc gọi loadJarBalances() ở đây để tránh gọi 2 lần
                // Ta sẽ gọi reload cứng sau khi API thành công
                // console.log("DEBUG: Modal createBudgetModal hidden."); 
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
            let pClass = percent >= 100 ? 'bg-danger' : (percent >= alertThreshold ? 'bg-warning' : 'bg-success');

            const spentFormatted = formatCurrencyLocal(spent);
            const amountFormatted = formatCurrencyLocal(amount);


            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="ps-4">
                    <div class="d-flex align-items-center">
                        <div class="me-3" style="width: 36px; height: 36px; background: ${b.category_color || '#ccc'}20; color: ${b.category_color || '#666'}; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                            <i class="fas ${b.category_icon || 'fa-circle'}"></i>
                        </div>
                        <div><div class="fw-bold text-dark">${b.category_name}</div><small class="text-muted">${(b.category_group || '').toUpperCase()}</small></div>
                    </div>
                </td>
                <td class="text-end">
                    <div class="fw-bold text-dark">${spentFormatted} ₫</div>
                    <small class="text-muted">/ ${amountFormatted} ₫</small>
                </td>
                <td class="ps-4 align-middle">
                    <div class="progress" style="height: 6px; border-radius: 3px;">
                        <div class="progress-bar ${pClass}" style="width: ${percent}%"></div>
                    </div>
                </td>
                <td class="text-end pe-4">
                    <button class="btn btn-sm text-danger opacity-50 hover-opacity-100" onclick="deleteBudget(${b.id}, this)"><i class="fas fa-trash"></i></button>
                </td>
            `;
            tableBody.appendChild(row);
        });
    }


    async function handleCreateBudget(e) {
        e.preventDefault();
        const btn = e.submitter;
        const oldText = btn.innerHTML;
        
        // --- HARD FIX: KIỂM TRA ĐĂNG KÝ TRÙNG LẶP ---
        if (btn.classList.contains('is-submitting')) {
             console.warn("Submit ignored: Already processing.");
             return; // Ngăn chặn nếu đã có submit đang chạy
        }
        btn.classList.add('is-submitting');
        // --- END HARD FIX ---

        btn.disabled = true;
        btn.innerHTML = 'Đang xử lý...';

        const fd = new FormData(e.target);

        // Lấy giá trị thực từ input hidden (đã được formatInputMoney xử lý)
        const amountRaw = document.getElementById('budget_amount')?.value || '';
        
        const data = {
            category_id: fd.get('category_id'),
            amount: amountRaw, // Dùng giá trị đã được làm sạch
            period: fd.get('period'),
            alert_threshold: document.getElementById('budget_threshold').value
        };

        if (!data.category_id) {
            if (window.SmartSpending && window.SmartSpending.showToast) window.SmartSpending.showToast('Vui lòng chọn danh mục.', 'warning');
            else alert('Vui lòng chọn danh mục.');
            
            btn.classList.remove('is-submitting'); // Khôi phục trạng thái
            btn.disabled = false; btn.innerHTML = oldText;
            return;
        }

        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const resp = await fetch(`${BASE_URL}/budgets/api_create`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(Object.assign({}, data, { csrf_token: csrf }))
            });

            // [FIX LỖI STREAM] Đọc response text 1 lần duy nhất
            const responseText = await resp.text();

            let res;
            try {
                res = JSON.parse(responseText);
            } catch (e) {
                // Lỗi này xảy ra khi PHP bị Fatal Error và trả về HTML
                console.error('Non-JSON response received (FATAL ERROR LIKELY):', responseText);
                res = { success: false, message: 'Lỗi API Server hoặc Lỗi PHP nghiêm trọng (FATAL ERROR). Vui lòng kiểm tra PHP Error Log.' };
            }

            if (res.success) {
                const modal = document.getElementById('createBudgetModal');
                if (modal) {
                    // [FIX REAL-TIME] Không chỉ ẩn modal mà còn reload trang để cập nhật JARS
                    bootstrap.Modal.getInstance(modal)?.hide();
                    setTimeout(() => window.location.reload(), 100); 
                }
                e.target.reset();
                if (window.SmartSpending && window.SmartSpending.showToast) window.SmartSpending.showToast('Tạo ngân sách thành công!', 'success');
                else alert('Tạo ngân sách thành công!');
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
            btn.classList.remove('is-submitting'); // Khôi phục trạng thái
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