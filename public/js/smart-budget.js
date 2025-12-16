/**
 * SmartSpending JARS Logic
 * Xử lý: Cấu hình tỷ lệ & Phân bổ thu nhập
 */
// Ensure global SmartSpending object exists without redeclaring
if (typeof window.SmartSpending === 'undefined') window.SmartSpending = {};
var SmartSpending = window.SmartSpending;

(function() {
    // --- PHẦN 1: CẤU HÌNH TỶ LỆ ---
    const keys = ['nec', 'ffa', 'ltss', 'edu', 'play', 'give'];
    const inputs = {}, displays = {};
    const saveBtn = document.getElementById('saveRatiosBtn');
    const totalEl = document.getElementById('totalPercent');

    // Init inputs
    keys.forEach(k => {
        inputs[k] = document.getElementById(k + 'Input');
        displays[k] = document.getElementById(k + 'Percent');
        if(inputs[k]) inputs[k].addEventListener('input', updateUI);
    });

    function updateUI() {
        let total = 0;
        keys.forEach(k => {
            let v = parseInt(inputs[k]?.value || 0);
            if(displays[k]) displays[k].innerText = v;
            total += v;
        });

        if(totalEl) {
            totalEl.innerText = total + '%';
            totalEl.className = total === 100 ? 'fw-bold text-success' : 'fw-bold text-danger';
            if(saveBtn) saveBtn.disabled = (total !== 100);
        }
    }

    if (saveBtn) {
        saveBtn.addEventListener('click', async (ev) => {
            try { ev.preventDefault(); ev.stopPropagation(); } catch (e) { /* ignore */ }

            let vals = {};
            keys.forEach(k => vals[k] = parseInt(inputs[k]?.value || 0));

            saveBtn.disabled = true; saveBtn.innerHTML = 'Đang lưu...';
            const modalEl = document.getElementById('smartBudgetModal');
            const modalInstance = (modalEl && typeof bootstrap !== 'undefined') ? bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl) : null;

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const resRaw = await fetch(`${BASE_URL}/budgets/api_update_ratios`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify(Object.assign({}, vals, { csrf_token: csrf }))
                });

                const ctype = (resRaw.headers && resRaw.headers.get) ? (resRaw.headers.get('content-type') || '') : '';
                if (!ctype.includes('application/json')) {
                    const txt = await resRaw.text();
                    if ((txt || '').trim().startsWith('<')) {
                        SmartSpending.showModal('Phiên đăng nhập có thể đã hết hạn. Bạn sẽ được chuyển tới trang đăng nhập.', 'Phiên hết hạn', 'error', false);
                        setTimeout(() => window.location.href = `${BASE_URL}/auth/login`, 1400);
                        return;
                    }
                }

                let res;
                try { res = await resRaw.json(); } catch (e) { res = { success: false, message: 'Invalid JSON response' }; }

                if (res.success) {
                    SmartSpending.showModal('Đã lưu cấu hình! Đang đồng bộ lại ví...', 'Thành công', 'success', false);
                    
                    // [QUAN TRỌNG] CHỜ HÀM ĐỒNG BỘ HOÀN TẤT TRONG DB
                    if (typeof window.syncJarsApi === 'function') {
                        const syncResult = await window.syncJarsApi(); 

                        if (syncResult.success) {
                            SmartSpending.showModal('Đã lưu và đồng bộ ví thành công!', 'Hoàn tất', 'success', false);
                        } else {
                            SmartSpending.showModal('Lưu tỷ lệ thành công, nhưng đồng bộ ví thất bại. Vui lòng kiểm tra console.', 'Cảnh báo', 'warning', false);
                        }
                    } else {
                         SmartSpending.showModal('Đã lưu cấu hình, nhưng không thể đồng bộ ví (syncJarsApi bị thiếu).', 'Cảnh báo', 'warning', false);
                    }

                    // ĐÓNG MODAL. Việc này sẽ kích hoạt sự kiện 'hidden.bs.modal' -> loadJarBalances() -> Cập nhật số dư.
                    if (modalInstance && typeof modalInstance.hide === 'function') {
                        modalInstance.hide(); 
                    }
                    
                    try { window.dispatchEvent(new CustomEvent('smartbudget:ratios_updated', { detail: vals })); } catch (e) {}

                } else {
                    SmartSpending.showModal('Lỗi: ' + (res.message || 'Không lưu được'), 'Lỗi', 'error', false);
                }
            } catch (e) {
                console.error('Error saving ratios', e);
                SmartSpending.showModal('Lỗi hệ thống khi lưu cấu hình', 'Lỗi', 'error', false);
            } finally {
                saveBtn.disabled = false; saveBtn.innerHTML = 'Lưu Cấu Hình';
            }
        });
    }

    // --- PHẦN 2: PHÂN BỔ THU NHẬP (VIP PRO) ---
    const jarNames = { nec: 'Thiết yếu', ffa: 'Tự do TC', ltss: 'Tiết kiệm', edu: 'Giáo dục', play: 'Hưởng thụ', give: 'Cho đi' };
    const jarColors = { nec: 'danger', ffa: 'warning', ltss: 'primary', edu: 'info', play: 'pink', give: 'success' };

    SmartSpending.previewIncome = function(input) {
        let rawValue = input.value.replace(/\D/g, '');
        if (!rawValue) {
            document.getElementById('incomePreviewList').innerHTML = '<div class="text-center text-muted py-3 small">Nhập số tiền để xem phân bổ</div>';
            return;
        }
        input.value = new Intl.NumberFormat('vi-VN').format(rawValue);
        
        let amount = parseInt(rawValue);
        let html = '';
        
        // Lấy settings từ biến Global PHP truyền xuống hoặc mặc định
        let currentSettings = window.JARS_SETTINGS || { nec_percent: 55, ffa_percent: 10, ltss_percent: 10, edu_percent: 10, play_percent: 10, give_percent: 5 };

        keys.forEach(key => {
            let percent = currentSettings[key + '_percent'] || 0;
            let jarAmount = amount * (percent / 100);
            let color = jarColors[key];
            
            // Style riêng cho màu hồng (Bootstrap ko có text-pink chuẩn)
            let colorClass = key === 'play' ? 'color: #d63384;' : `color: var(--bs-${color});`;
            let bgClass = key === 'play' ? 'background-color: #fce7f3;' : `background-color: var(--bs-${color}-bg-subtle);`;

            html += `
            <div class="col-6">
                <div class="p-2 rounded-3 border d-flex justify-content-between align-items-center" style="background: #fff;">
                    <div>
                        <div class="small fw-bold text-uppercase text-muted" style="font-size: 0.7rem;">${jarNames[key]} (${percent}%)</div>
                        <div class="fw-bold" style="${colorClass}">${new Intl.NumberFormat('vi-VN').format(jarAmount)} ₫</div>
                    </div>
                    <div class="rounded-circle p-1" style="${bgClass} width: 10px; height: 10px;"></div>
                </div>
            </div>`;
        });
        document.getElementById('incomePreviewList').innerHTML = html;
    };

    SmartSpending.submitIncome = async function() {
        const input = document.getElementById('incomeAmountInput');
        const amount = input.value.replace(/\D/g, '');
        const btn = document.getElementById('confirmDistributeBtn');
        
        if(!amount || amount <= 0) { SmartSpending.showModal('Vui lòng nhập số tiền!', 'Lỗi', 'error', false); return; }
        
        btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang chia tiền...';
        
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const resRaw = await fetch(`${BASE_URL}/budgets/api_distribute_income`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' 
                },
                body: JSON.stringify({ amount: amount, csrf_token: csrf })
            });
            const res = await (async () => { try { return await resRaw.json(); } catch(e) { return { success:false, message:'Invalid JSON response' }; } })();

            if(res.success) {
                SmartSpending.showModal('Đã phân bổ thành công!', 'Thành công', 'success', false);
                setTimeout(() => window.location.reload(), 500); 
            } else {
                SmartSpending.showModal('Lỗi: ' + res.message, 'Lỗi', 'error', false);
            }
        } catch(e) {
            console.error(e);
            SmartSpending.showModal('Lỗi kết nối server', 'Lỗi', 'error', false);
        } finally {
            btn.disabled = false; btn.innerHTML = '<i class="fas fa-check me-2"></i>Xác nhận Nạp';
        }
    };

    // Run once on load
    updateUI();

    // Listen for jar updates from transactions flow and refresh balances in UI
    window.addEventListener('smartbudget:updated', function(e) {
        try {
            var detail = e && e.detail ? e.detail : null;
            if (!detail || !detail.jar_updates) return;
            var jars = detail.jar_updates;
            // jars expected: { nec: number, ffa: number, ltss: number, edu: number, play: number, give: number }
            Object.keys(jars).forEach(function(code) {
                var el = document.querySelector('.jar-balance[data-jar="' + code + '"]');
                if (el) {
                    // format number as VN currency without symbol (we append symbol in markup)
                    try {
                        el.textContent = new Intl.NumberFormat('vi-VN').format(parseFloat(jars[code] || 0));
                    } catch (err) {
                        el.textContent = (jars[code] || 0);
                    }
                }
            });
        } catch (err) { console.warn('Error applying jar updates', err); }
    });
})();