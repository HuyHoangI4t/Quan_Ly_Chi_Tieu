// Budgets Management JavaScript
const BASE_URL = window.location.origin;
let budgetModal;
let currentEditId = null;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize modal
    budgetModal = new bootstrap.Modal(document.getElementById('budgetModal'));
    
    // Period filter change
    document.getElementById('periodFilter')?.addEventListener('change', function() {
        loadBudgets(this.value);
    });

    // Get CSRF token from meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        window.CSRF_TOKEN = csrfToken;
    }
});

// Show add budget modal
function showAddBudgetModal() {
    document.getElementById('budgetModalTitle').textContent = 'Thêm Ngân sách';
    document.getElementById('budgetForm').reset();
    document.getElementById('budgetId').value = '';
    currentEditId = null;
    budgetModal.show();
}

// Edit budget
function editBudget(budget) {
    document.getElementById('budgetModalTitle').textContent = 'Sửa Ngân sách';
    document.getElementById('budgetId').value = budget.id;
    document.getElementById('categoryId').value = budget.category_id;
    document.getElementById('limitAmount').value = budget.limit_amount;
    document.getElementById('period').value = budget.period;
    currentEditId = budget.id;
    budgetModal.show();
}

// Save budget (add or update)
async function saveBudget() {
    const form = document.getElementById('budgetForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const budgetId = document.getElementById('budgetId').value;
    const formData = {
        category_id: parseInt(document.getElementById('categoryId').value),
        limit_amount: parseFloat(document.getElementById('limitAmount').value),
        period: document.getElementById('period').value,
        csrf_token: window.CSRF_TOKEN
    };

    const url = budgetId 
        ? `${BASE_URL}/budgets/api_update/${budgetId}`
        : `${BASE_URL}/budgets/api_add`;

    try {
        showButtonLoading('saveBudgetBtn', true);
        
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': window.CSRF_TOKEN
            },
            body: JSON.stringify(formData)
        });

        const result = await response.json();

        if (result.success) {
            showToast('success', result.message);
            budgetModal.hide();
            loadBudgets(document.getElementById('periodFilter').value);
        } else {
            showToast('error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('error', 'Có lỗi xảy ra. Vui lòng thử lại!');
    } finally {
        showButtonLoading('saveBudgetBtn', false);
    }
}

// Delete budget
async function deleteBudget(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa ngân sách này?')) {
        return;
    }

    try {
        const response = await fetch(`${BASE_URL}/budgets/api_delete/${id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': window.CSRF_TOKEN
            },
            body: JSON.stringify({ csrf_token: window.CSRF_TOKEN })
        });

        const result = await response.json();

        if (result.success) {
            showToast('success', result.message);
            loadBudgets(document.getElementById('periodFilter').value);
        } else {
            showToast('error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('error', 'Có lỗi xảy ra. Vui lòng thử lại!');
    }
}

// Load budgets for a specific period
async function loadBudgets(period) {
    try {
        const response = await fetch(`${BASE_URL}/budgets/api_get_budgets/${period}`);
        const result = await response.json();

        if (result.success) {
            updateBudgetsTable(result.data.budgets);
            updateBudgetSummary(result.data.summary);
        }
    } catch (error) {
        console.error('Error loading budgets:', error);
    }
}

// Update budgets table
function updateBudgetsTable(budgets) {
    const tbody = document.getElementById('budgetsTableBody');
    
    if (!budgets || budgets.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-muted py-4">
                    Chưa có ngân sách nào. Nhấn "Thêm Ngân sách" để bắt đầu!
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = budgets.map(budget => {
        const remaining = budget.remaining_amount || 0;
        const percentage = budget.percentage_used || 0;
        const status = budget.status || 'safe';
        
        let progressClass = 'bg-success';
        if (status === 'exceeded') progressClass = 'bg-danger';
        else if (status === 'warning') progressClass = 'bg-warning';

        return `
            <tr data-budget-id="${budget.id}">
                <td><strong>${escapeHtml(budget.category_name)}</strong></td>
                <td class="text-end">₫ ${formatNumber(budget.limit_amount)}</td>
                <td class="text-end">₫ ${formatNumber(budget.spent_amount)}</td>
                <td class="text-end">
                    <span class="${remaining < 0 ? 'text-danger' : 'text-success'}">
                        ₫ ${formatNumber(remaining)}
                    </span>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="progress flex-grow-1" style="height: 20px;">
                            <div class="progress-bar ${progressClass}" 
                                 style="width: ${Math.min(percentage, 100)}%">
                                ${percentage.toFixed(1)}%
                            </div>
                        </div>
                        <small>${percentage.toFixed(1)}%</small>
                    </div>
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-primary" 
                            onclick='editBudget(${JSON.stringify(budget)})'>
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" 
                            onclick="deleteBudget(${budget.id})">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

// Update budget summary
function updateBudgetSummary(summary) {
    const summaryDiv = document.getElementById('budgetSummary');
    if (!summaryDiv || !summary) return;

    const percentage = summary.overall_percentage || 0;
    let progressClass = 'bg-success';
    if (percentage >= 100) progressClass = 'bg-danger';
    else if (percentage >= 80) progressClass = 'bg-warning';

    summaryDiv.innerHTML = `
        <div class="col-md-3">
            <div class="card p-3 text-center">
                <div class="text-muted small">Tổng Hạn mức</div>
                <div class="fs-4 fw-bold text-primary">₫ ${formatNumber(summary.total_limit)}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center">
                <div class="text-muted small">Đã Chi tiêu</div>
                <div class="fs-4 fw-bold text-danger">₫ ${formatNumber(summary.total_spent)}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center">
                <div class="text-muted small">Còn Lại</div>
                <div class="fs-4 fw-bold text-success">₫ ${formatNumber(summary.total_remaining)}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 text-center">
                <div class="text-muted small">Tiến độ</div>
                <div class="fs-4 fw-bold">${percentage.toFixed(1)}%</div>
                <div class="progress mt-2" style="height: 8px;">
                    <div class="progress-bar ${progressClass}" 
                         style="width: ${Math.min(percentage, 100)}%"></div>
                </div>
            </div>
        </div>
    `;
}

// Utility functions
function formatNumber(num) {
    return new Intl.NumberFormat('vi-VN').format(num || 0);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showButtonLoading(buttonId, loading) {
    const button = document.getElementById(buttonId);
    if (!button) return;
    
    const text = button.querySelector('.btn-text');
    const spinner = button.querySelector('.spinner-border');
    
    if (loading) {
        button.disabled = true;
        if (text) text.classList.add('d-none');
        if (spinner) spinner.classList.remove('d-none');
    } else {
        button.disabled = false;
        if (text) text.classList.remove('d-none');
        if (spinner) spinner.classList.add('d-none');
    }
}

function showToast(type, message) {
    // Simple toast notification - you can enhance this
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alert = document.createElement('div');
    alert.className = `alert ${alertClass} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
    alert.style.zIndex = '9999';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alert);
    
    setTimeout(() => alert.remove(), 3000);
}
