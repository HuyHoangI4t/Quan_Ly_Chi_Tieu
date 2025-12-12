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

    // Event Listeners
    keys.forEach(k => {
        inputs[k]?.addEventListener('input', updateUI);
    });

    // Save Button
    saveBtn?.addEventListener('click', async () => {
        const { vals, total } = getValues();
        if (total !== 100) return;

        try {
            const resp = await fetch(BASE_URL + '/budgets/api_update_ratios', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify(vals)
            });
            const res = await resp.json();
            if(res.success) {
                SmartSpending.showToast('Lưu thành công', 'success');
                bootstrap.Modal.getInstance(document.getElementById('smartBudgetModal')).hide();
                window.dispatchEvent(new CustomEvent('smartBudget:updated')); // Refresh parent view
            } else {
                SmartSpending.showToast(res.message, 'error');
            }
        } catch(e) { console.error(e); }
    });

    // Initial Load
    async function loadData() {
        try {
            const resp = await fetch(BASE_URL + '/budgets/api_get_smart_budget');
            const json = await resp.json();
            if(json.success) {
                const s = json.data.settings;
                keys.forEach(k => {
                    if(inputs[k]) inputs[k].value = s[k + '_percent'];
                });
                if(incomeInput) incomeInput.value = json.data.income || 0;
                updateUI();
            }
        } catch(e) { console.error(e); }
    }

    document.getElementById('smartBudgetModal')?.addEventListener('shown.bs.modal', loadData);
    
    // Also run once to render chart on dashboard (if logic separated)
    loadData();

})();