(function(){
    const keys = ['nec', 'ffa', 'ltss', 'edu', 'play', 'give'];
    const inputs = {};
    const displays = {};
    const amounts = {};
    const incomeInput = document.getElementById('smartIncome');
    const totalEl = document.getElementById('totalPercent');
    const saveBtn = document.getElementById('saveRatiosBtn');
    let chart = null;

    // Map DOM elements
    keys.forEach(k => {
        inputs[k] = document.getElementById(k + 'Input');
        displays[k] = document.getElementById(k + 'Percent');
        amounts[k] = document.getElementById(k + 'Amount');
    });

    function getValues() {
        let vals = {};
        let total = 0;
        keys.forEach(k => {
            let v = parseInt(inputs[k]?.value || 0);
            vals[k] = v;
            total += v;
        });
        return { vals, total };
    }

    function updateUI() {
        const { vals, total } = getValues();
        const income = parseFloat(incomeInput?.value || 0) || 0;

        // Update Text & Color
        keys.forEach(k => {
            if(displays[k]) displays[k].innerText = vals[k];
            if(amounts[k]) amounts[k].innerText = Math.round(income * vals[k] / 100).toLocaleString('vi-VN') + ' ₫';
        });

        // Validate Total
        if(totalEl) {
            totalEl.innerText = total + '%';
            if(total === 100) {
                totalEl.className = 'fw-bold text-success';
                saveBtn.disabled = false;
            } else {
                totalEl.className = 'fw-bold text-danger';
                saveBtn.disabled = true;
            }
        }

        updateChart(vals);
    }

    function updateChart(vals) {
        const ctx = document.getElementById('smartBudgetChart')?.getContext('2d');
        if (!ctx || typeof Chart === 'undefined') return;
        
        const data = keys.map(k => vals[k]);
        const colors = ['#dc3545', '#ffc107', '#0d6efd', '#0dcaf0', '#d63384', '#198754']; // Màu tương ứng 6 hũ

        if (!chart) {
            chart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: keys.map(k => k.toUpperCase()),
                    datasets: [{ data: data, backgroundColor: colors, borderWidth: 0 }]
                },
                options: { responsive: true, cutout: '75%', plugins: { legend: { display: false } } }
            });
        } else {
            chart.data.datasets[0].data = data;
            chart.update();
        }
    }

    // Event Listeners: snap values to 5% increments then update UI
    keys.forEach(k => {
        if (inputs[k]) {
            inputs[k].addEventListener('input', (e) => {
                const raw = parseInt(e.target.value || 0, 10) || 0;
                const snapped = Math.max(0, Math.min(100, Math.round(raw / 5) * 5));
                if (snapped !== raw) e.target.value = snapped;
                updateUI();
            });
        }
    });

    // Save Button
    saveBtn?.addEventListener('click', async () => {
        const { vals, total } = getValues();
        if (total !== 100) return;

        // disable button to prevent duplicate submits
        saveBtn.disabled = true;
        const originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang lưu';

        try {
            const resp = await fetch(BASE_URL + '/budgets/api_update_ratios', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify(vals)
            });

            let res = null;
            try { res = await resp.json(); } catch(err) { res = { success: false, message: 'Phản hồi không hợp lệ từ server' }; }
            console.debug('api_update_ratios response:', res);

            if (res && res.success) {
                SmartSpending.showToast('Lưu thành công', 'success');
                // Ensure modal is properly closed and backdrop removed
                try {
                    const modalEl = document.getElementById('smartBudgetModal');
                    if (modalEl) {
                        const inst = bootstrap.Modal.getOrCreateInstance(modalEl);
                        inst.hide();
                    }
                    // Force cleanup of backdrop and body class in case Bootstrap instance was not found
                    document.body.classList.remove('modal-open');
                    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                } catch (err) {
                    console.warn('Error hiding modal cleanly', err);
                }
                // trigger refresh immediately and also dispatch event
                if (window.budgets && typeof window.budgets.refresh === 'function') {
                    window.budgets.refresh();
                }
                window.dispatchEvent(new CustomEvent('smartBudget:updated'));
            } else {
                SmartSpending.showToast(res?.message || 'Lỗi khi lưu cấu hình', 'error');
                console.warn('saveRatios failed', res);
            }
        } catch(e) {
            console.error('saveRatios error', e);
            SmartSpending.showToast('Lỗi kết nối tới server', 'error');
        } finally {
            // restore button state
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    });

    // Initial Load
    async function loadData() {
        try {
            const resp = await fetch(BASE_URL + '/budgets/api_get_smart_budget');
            const json = await resp.json();
            console.debug('api_get_smart_budget response:', json);
                if(json.success) {
                const s = json.data.settings;
                keys.forEach(k => {
                    if(inputs[k]) {
                        const raw = Number(s[k + '_percent'] || 0);
                        const snapped = Math.max(0, Math.min(100, Math.round(raw / 5) * 5));
                        inputs[k].value = snapped;
                    }
                });
                if(incomeInput) incomeInput.value = json.data.income || 0;
                updateUI();
            }
        } catch(e) { console.error(e); }
    }

    document.getElementById('smartBudgetModal')?.addEventListener('shown.bs.modal', loadData);
    
    // Also run once to render chart on dashboard (if logic separated)
    loadData();

    // Cleanup on modal hidden: remove backdrops, restore body class and reset UI
    (function(){
        const modalEl = document.getElementById('smartBudgetModal');
        if (!modalEl) return;
        modalEl.addEventListener('hidden.bs.modal', function(){
            try {
                // Restore save button to default state
                if (saveBtn) {
                    saveBtn.disabled = false;
                    // If spinner present, restore visible text
                    const spinner = saveBtn.querySelector('.spinner-border');
                    if (spinner) saveBtn.innerHTML = 'Lưu Cấu Hình';
                }

                // Destroy chart instance if exists to avoid stale overlays
                if (chart) {
                    try { chart.destroy(); } catch(e) {}
                    chart = null;
                }

                // Remove any lingering modal backdrop(s) and modal-open body class
                document.body.classList.remove('modal-open');
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            } catch (err) {
                console.warn('Error during smartBudgetModal hidden cleanup', err);
            }
        });
    })();

})();