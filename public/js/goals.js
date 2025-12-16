// === GOALS PAGE JS (VANILLA VERSION) ===

(function() {
    'use strict';

    // Prevent this script from initializing multiple times (safeguard for PJAX/turbolinks)
    if (window.__goals_js_inited) return;
    window.__goals_js_inited = true;
    // State
    let currentGoalId = null;
    let formSending = false;

    // DOM Elements
    const goalForm = document.getElementById('goalForm');
    const goalModalElement = document.getElementById('goalModal');
    const goalModalLabel = document.getElementById('goalModalLabel');
    const btnAddGoal = document.getElementById('btnAddGoal');
    const goalsContainer = document.getElementById('goalsContainer');
    const amountInput = document.getElementById('goalTargetAmount');
    
    // Deposit Elements
    const depositForm = document.getElementById('depositForm');
    const depositModalElement = document.getElementById('depositModal');
    const depositAmountInput = document.getElementById('depositAmount');

    function init() {
        setupEventListeners();
        setupAmountMasking();
        setMinDate();
        renderCharts(); // Load charts immediately
    }

    let listenersAttached = false;
    function setupEventListeners() {
        if (listenersAttached) return;
        listenersAttached = true;
        // 1. Add Goal Button
        if (btnAddGoal) btnAddGoal.addEventListener('click', handleAddGoal);
        
        // 2. Main Goal Form Submit
        if (goalForm) goalForm.addEventListener('submit', handleFormSubmit);

        // 3. Deposit Form Submit (Nạp tiền)
        if (depositForm) depositForm.addEventListener('submit', handleDepositSubmit);

        // 4. Global Click Delegation (Edit, Delete, Deposit Trigger)
        document.addEventListener('click', function(e) {
            // Edit Goal
            const editBtn = e.target.closest('.btn-edit-goal');
            if (editBtn) {
                e.preventDefault();
                const goalId = editBtn.dataset.goalId;
                handleEditGoal(goalId);
                return;
            }

            // Delete Goal
            const deleteBtn = e.target.closest('.btn-delete-goal');
            if (deleteBtn) {
                e.preventDefault();
                const goalId = deleteBtn.dataset.goalId;
                if(goalId) handleDeleteGoal(goalId);
                return;
            }

            // Mark Completed
            const markBtn = e.target.closest('.btn-mark-completed');
            if (markBtn) {
                e.preventDefault();
                const goalId = markBtn.dataset.goalId;
                if(goalId) handleMarkCompleted(goalId);
                return;
            }

            // === Trigger Deposit Modal (Nút mở modal nạp tiền) ===
            const depositBtn = e.target.closest('.btn-deposit-trigger');
            if (depositBtn) {
                e.preventDefault();
                const goalId = depositBtn.dataset.id; // Lưu ý: View dùng data-id
                const goalName = depositBtn.dataset.name;
                openDepositModal(goalId, goalName);
                return;
            }

            // Withdraw from goal (Rút về số dư)
            const withdrawBtn = e.target.closest('.btn-withdraw-goal');
            if (withdrawBtn) {
                e.preventDefault();
                const goalId = withdrawBtn.dataset.id;
                if (!goalId) return;

                SmartSpending.showConfirm(
                    'Rút tiền về số dư',
                    'Bạn có chắc chắn muốn rút toàn bộ số tiền đã nạp vào mục tiêu này về số dư chính?',
                    async () => {
                        try {
                            const response = await fetch(`${window.BASE_URL}/goals/api_withdraw/${goalId}`, {
                                method: 'POST',
                                headers: { 'X-CSRF-Token': document.querySelector('input[name="csrf_token"]')?.value || '' }
                            });
                            const text = await response.text();
                            let result = null;
                            try { result = text ? JSON.parse(text) : null; } catch (e) { result = null; }
                            const resp = result || { success: response.ok, message: text };
                            if (resp.success) {
                                SmartSpending.showToast(resp.message || 'Đã rút về số dư', 'success');
                                setTimeout(() => window.location.reload(), 600);
                            } else {
                                SmartSpending.showToast(resp.message || 'Không thể rút tiền', 'error');
                            }
                        } catch (err) {
                            console.error('Withdraw error', err);
                            SmartSpending.showToast('Lỗi kết nối', 'error');
                        }
                    }
                );

                return;
            }
        });

        // Reset forms on modal hide
        if (goalModalElement) {
            goalModalElement.addEventListener('hidden.bs.modal', resetForm);
        }
    }

    function setupAmountMasking() {
        // Masking cho form tạo/sửa
        if (amountInput) {
            amountInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/[^\d]/g, '');
                e.target.value = formatNumber(value);
            });
            amountInput.addEventListener('blur', function(e) {
                if (!e.target.value) e.target.value = '0';
            });
        }
        // Masking cho form nạp tiền
        if (depositAmountInput) {
            depositAmountInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/[^\d]/g, '');
                e.target.value = formatNumber(value);
            });
        }
    }

    function setMinDate() {
        const deadlineInput = document.getElementById('goalDeadline');
        if (!deadlineInput) return;
        const today = new Date().toISOString().split('T')[0];
        deadlineInput.setAttribute('min', today);
    }

    // --- HANDLERS ---

    function handleAddGoal() {
        currentGoalId = null;
        resetForm();
        if (goalModalLabel) goalModalLabel.textContent = 'Thêm Mục Tiêu';
    }


    function handleEditGoal(goalId) {
        currentGoalId = goalId;
        if (goalModalLabel) goalModalLabel.textContent = 'Chỉnh Sửa Mục Tiêu';

        // Lấy data từ thẻ HTML card hiện tại để điền vào form (đỡ phải gọi API get detail)
        const goalCard = document.querySelector(`[data-goal-id="${goalId}"]`) || document.querySelector(`.btn-edit-goal[data-goal-id="${goalId}"]`).closest('.col-md-6');
        
        if (!goalCard) return;

        // Query selectors phải match với cấu trúc HTML trong View
        const name = goalCard.querySelector('.fw-bold')?.textContent.trim() || '';
        // Parse số tiền từ text (vd: "2.000.000₫ mục tiêu")
        const textMuted = goalCard.querySelectorAll('.text-muted');
        let targetAmount = '0';
        if(textMuted.length > 0) {
            const txt = textMuted[0].textContent; 
            const match = txt.match(/(\d{1,3}(?:[.,]\d{3})*)₫ mục tiêu/); // Regex bắt số tiền (hỗ trợ '.' hoặc ',')
            if(match) targetAmount = match[1].replace(/[.,]/g, '');
        }

        // Fill form
        document.getElementById('goalId').value = goalId;
        document.getElementById('goalName').value = name;
        document.getElementById('goalTargetAmount').value = formatNumber(targetAmount);

        // Prefill dates, description, color, current amount when available on the card
        try {
            const startEl = document.getElementById('goalStartDate');
            const deadlineEl = document.getElementById('goalDeadline');
            const descEl = document.getElementById('goalDescription');
            const colorEl = document.getElementById('goalColor');
            const currentAmtEl = document.getElementById('goalCurrentAmount');

            const dataset = goalCard.dataset || {};
            if (startEl && dataset.startDate) startEl.value = dataset.startDate;
            if (deadlineEl && dataset.deadline) deadlineEl.value = dataset.deadline;
            if (descEl && dataset.description) descEl.value = dataset.description;
            if (colorEl && dataset.color) colorEl.value = dataset.color;
            if (currentAmtEl && dataset.currentAmount) currentAmtEl.value = formatNumber(dataset.currentAmount);
        } catch (e) { /* ignore */ }
        
        // Các trường khác (deadline, start_date) nếu không có trên UI thì reset hoặc để trống
        // Nếu muốn chính xác tuyệt đối, nên gọi API getById. Ở đây làm nhanh dùng UI.
        
        const modal = new bootstrap.Modal(goalModalElement);
        modal.show();
    }

    // Xử lý nạp tiền (Mở Modal)
    function openDepositModal(goalId, goalName) {
        if(!depositModalElement) return;

        document.getElementById('depositGoalId').value = goalId;
        const nameEl = document.getElementById('depositGoalName');
        if(nameEl) nameEl.textContent = goalName;
        
        if(depositAmountInput) depositAmountInput.value = '';

        const modal = new bootstrap.Modal(depositModalElement);
        modal.show();
    }

    // Xử lý Submit Nạp tiền
    async function handleDepositSubmit(e) {
        e.preventDefault();
        console.debug('goals.js: handleDepositSubmit called');
        if (formSending) return;
        formSending = true;

        const btnSubmit = depositForm.querySelector('button[type="submit"]');
        if(btnSubmit) { btnSubmit.disabled = true; btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...'; }

        const formData = new FormData(depositForm);
        // Clean amount
        const rawAmount = formData.get('amount') || '';
        formData.set('amount', String(rawAmount).replace(/[^\d]/g, ''));

        // Client-side validation: amount must be provided and > 0
        const cleanedAmount = String(rawAmount).replace(/[^\d]/g, '');
        if (!cleanedAmount || Number(cleanedAmount) <= 0) {
            SmartSpending.showToast('Vui lòng nhập số tiền lớn hơn 0', 'error');
            formSending = false;
            if(btnSubmit) { btnSubmit.disabled = false; btnSubmit.innerHTML = '<i class="fas fa-save me-2"></i> Xác nhận'; }
            return;
        }

        try {
            const response = await fetch(`${window.BASE_URL}/goals/api_deposit`, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            
            const text = await response.text();
            let result = null;
            try { result = JSON.parse(text); } catch (e) { result = null; }
            const resp = result || { success: response.ok, message: text };

            if (resp.success) {
                SmartSpending.showToast(resp.message || 'Nạp tiền thành công', 'success');
                const modal = bootstrap.Modal.getInstance(depositModalElement);
                if (modal) modal.hide();
                setTimeout(() => window.location.reload(), 800);
            } else {
                SmartSpending.showToast(resp.message || 'Có lỗi xảy ra', 'error');
            }
        } catch (err) {
            console.error(err);
            SmartSpending.showToast('Lỗi kết nối server', 'error');
        } finally {
            formSending = false;
            if(btnSubmit) { btnSubmit.disabled = false; btnSubmit.innerHTML = '<i class="fas fa-save me-2"></i> Xác nhận'; }
        }
    }

    
    async function handleFormSubmit(e) {
        e.preventDefault();
        console.debug('goals.js: handleFormSubmit called');
        
        if (formSending) return;
        formSending = true;

        const saveBtn = document.getElementById('btnSaveGoal');
        if (saveBtn) { saveBtn.disabled = true; saveBtn.textContent = 'Đang lưu...'; }

        try {
            // 1. Lấy dữ liệu CHÍNH XÁC theo ID (Không dùng FormData để tránh sai tên)
            // Lưu ý: Các ID này phải khớp với HTML của bạn
            const nameEl = document.getElementById('goalName');
            const targetEl = document.getElementById('goalTargetAmount');
            const deadlineEl = document.getElementById('goalDeadline');
            const colorEl = document.getElementById('goalColor');
            const currentAmountEl = document.getElementById('goalCurrentAmount'); // Nếu có field này

            // Validate sơ bộ
            if (!nameEl || !targetEl || !deadlineEl) {
                throw new Error("Không tìm thấy các trường nhập liệu (ID HTML bị sai). Kiểm tra lại file View.");
            }

            // 2. Làm sạch số tiền (Xóa dấu chấm, phẩy)
            const rawTarget = targetEl.value;
            const cleanTarget = parseInt(rawTarget.replace(/\D/g, '')) || 0;
            
            const rawCurrent = currentAmountEl ? currentAmountEl.value : '0';
            const cleanCurrent = parseInt(rawCurrent.replace(/\D/g, '')) || 0;

            // 3. Đóng gói JSON chuẩn chỉnh (Key phải khớp với PHP Controller)
            const payload = {
                name: nameEl.value.trim(),
                target_amount: cleanTarget,
                current_amount: cleanCurrent,
                deadline: deadlineEl.value,
                color: colorEl ? colorEl.value : '#4e73df' // Màu mặc định nếu không chọn
            };

            console.log("Dữ liệu gửi đi:", payload); // F12 để xem

            // 4. Xác định URL (Tạo mới hay Cập nhật)
            const url = currentGoalId 
                ? `${window.BASE_URL}/goals/api_update_goal/${currentGoalId}` 
                : `${window.BASE_URL}/goals/api_create_goal`;

            // 5. Gửi Request JSON
            const response = await fetch(url, { 
                method: 'POST', 
                headers: {
                    'Content-Type': 'application/json', // Bắt buộc
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload) 
            });

            // 6. Xử lý kết quả
            const text = await response.text();
            let result = null;
            try { result = text ? JSON.parse(text) : null; } catch (err) { result = null; }
            
            // Nếu không phải JSON, in ra text lỗi để debug
            if (!result) {
                console.error("Server trả về lỗi không phải JSON:", text);
                throw new Error("Lỗi Server: " + text.substring(0, 50) + "...");
            }

            if (response.ok && (result.success || result.status === 'success')) {
                SmartSpending.showToast(result.message || 'Thành công!', 'success');
                
                const modal = bootstrap.Modal.getInstance(goalModalElement);
                if (modal) modal.hide();
                
                setTimeout(() => window.location.reload(), 800);
            } else {
                throw new Error(result.message || 'Có lỗi xảy ra (400/500)');
            }

        } catch (err) {
            console.error('Error:', err);
            SmartSpending.showToast(err.message, 'error');
        } finally {
            formSending = false;
            if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = 'Lưu Mục Tiêu'; }
        }
    }
    // Delete using Bootstrap confirmation modal
    const deleteModalElement = document.getElementById('deleteGoalModal');
    let deleteModalInstance = null;
    const deleteConfirmBtn = document.getElementById('btnConfirmDeleteGoal');

    function handleDeleteGoal(goalId) {
        if(!goalId || goalId === 'undefined') {
            console.error('Goal ID is missing');
            return;
        }

        // Fill modal fields
        const goalCard = document.querySelector(`[data-goal-id="${goalId}"]`);
        const goalName = goalCard ? (goalCard.querySelector('h5')?.textContent.trim() || '') : '';
        const deleteIdInput = document.getElementById('deleteGoalId');
        const deleteNameEl = document.getElementById('deleteGoalName');
        if (deleteIdInput) deleteIdInput.value = goalId;
        if (deleteNameEl) deleteNameEl.textContent = goalName;

        if (deleteModalElement) {
            deleteModalInstance = new bootstrap.Modal(deleteModalElement);
            deleteModalInstance.show();
        } else {
            // Fallback to native confirm
            if (!confirm('Bạn có chắc chắn muốn xóa mục tiêu này?')) return;
            performDeleteGoal(goalId);
        }
    }

    // Perform deletion request
    async function performDeleteGoal(goalId) {
        SmartSpending.showLoader();
        try {
            const csrfToken = document.getElementById('deleteCsrfToken')?.value || document.querySelector('input[name="csrf_token"]')?.value || '';
            const headers = {};
            if (csrfToken) headers['X-CSRF-Token'] = csrfToken;

            const response = await fetch(`${window.BASE_URL}/goals/api_delete_goal/${goalId}`, {
                method: 'POST',
                headers: headers,
                credentials: 'same-origin'
            });

            const text = await response.text();
            let respData = null;
            try { respData = JSON.parse(text); } catch (e) {}

            if (response.ok && (respData?.success || respData?.status === 'success')) {
                SmartSpending.showToast('Xóa mục tiêu thành công!', 'success');
                setTimeout(() => window.location.reload(), 500);
            } else {
                SmartSpending.showToast(respData?.message || 'Không thể xóa mục tiêu', 'error');
            }
        } catch (error) {
            console.error('Error deleting goal:', error);
            SmartSpending.showToast('Lỗi khi xóa mục tiêu', 'error');
        } finally {
            SmartSpending.hideLoader();
            if (deleteModalInstance) deleteModalInstance.hide();
        }
    }

    // Bind confirm button (ensure single listener)
    if (deleteConfirmBtn) {
        deleteConfirmBtn.addEventListener('click', function(e) {
            const id = document.getElementById('deleteGoalId')?.value;
            if (id) performDeleteGoal(id);
        });
    }

    async function handleMarkCompleted(goalId) {
        SmartSpending.showLoader();
        try {
            const formData = new FormData();
            const tokenInput = document.querySelector('input[name="csrf_token"]');
            if (tokenInput) formData.append('csrf_token', tokenInput.value);
            formData.append('status', 'completed');
            
            const response = await fetch(`${window.BASE_URL}/goals/api_update_status/${goalId}`, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                SmartSpending.showToast(result.message, 'success');
                setTimeout(() => window.location.reload(), 800);
            } else {
                SmartSpending.showToast(result.message || 'Có lỗi xảy ra', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
        } finally {
            SmartSpending.hideLoader();
        }
    }

    function resetForm() {
        if (goalForm) goalForm.reset();
        currentGoalId = null;
        const idEl = document.getElementById('goalId'); if (idEl) idEl.value = '';
        const amt = document.getElementById('goalTargetAmount'); if (amt) amt.value = '0';
        const startEl = document.getElementById('goalStartDate'); if (startEl) startEl.value = '';
        const deadlineEl = document.getElementById('goalDeadline'); if (deadlineEl) deadlineEl.value = '';
        const descEl = document.getElementById('goalDescription'); if (descEl) descEl.value = '';
        const colorEl = document.getElementById('goalColor'); if (colorEl) colorEl.value = '';
        const currentAmtEl = document.getElementById('goalCurrentAmount'); if (currentAmtEl) currentAmtEl.value = '';
    }

    // Ensure hidden start date defaults to today when creating a new goal
    (function setDefaultStartDate() {
        const startEl = document.getElementById('goalStartDate');
        if (startEl && !startEl.value) {
            const today = new Date().toISOString().split('T')[0];
            startEl.value = today;
        }
    })();

    // Charts
    async function renderCharts() {
        if (typeof Chart === 'undefined') return;
        try {
            const r = await fetch(`${window.BASE_URL}/goals/api_get_goals`, { cache: 'no-store' });
            const json = await r.json();
            if (!json || !json.data || !json.data.goals) return;
            const goals = json.data.goals;

            const labels = goals.map(g => g.name.substring(0,12));
            const saved = goals.map(g => Number(g.current_amount) || 0);
            const remaining = goals.map(g => Math.max(0, (Number(g.target_amount)||0) - (Number(g.current_amount)||0)));

            const ctx = document.getElementById('goalProgressChart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            { label: 'Đã tiết kiệm', data: saved, backgroundColor: '#16a085' },
                            { label: 'Còn lại', data: remaining, backgroundColor: '#9ca3af' }
                        ]
                    },
                    options: { responsive:true, maintainAspectRatio:false }
                });
            }

            const pctx = document.getElementById('goalPieChart');
            if (pctx) {
                new Chart(pctx, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{ data: saved, backgroundColor: ['#10b981','#34d399','#60a5fa','#f97316','#ef4444','#f59e0b'] }]
                    },
                    options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'bottom'}} }
                });
            }
        } catch (e) {
            console.warn('Unable to render charts', e);
        }
    }

    function formatNumber(num) {
        return String(num).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    // Init
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); 
    else init();

    // (Removed goals:updated listener — goal linking feature disabled)

})();