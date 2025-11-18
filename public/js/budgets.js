/**
 * Budgets Page Specific JavaScript
 * Handles budget creation, tracking, and progress visualization
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize budget page features
    initializeBudgetActions();
    initializeBudgetCharts();
});

/**
 * Initialize budget action buttons
 */
function initializeBudgetActions() {
    // Add new budget button
    const addBudgetBtn = document.querySelector('.btn-add-budget');
    if (addBudgetBtn) {
        addBudgetBtn.addEventListener('click', function() {
            openBudgetModal();
        });
    }

    // Edit budget
    document.querySelectorAll('.btn-edit-budget').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const budgetId = this.dataset.id;
            editBudget(budgetId);
        });
    });

    // Delete budget
    document.querySelectorAll('.btn-delete-budget').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const budgetId = this.dataset.id;
            deleteBudget(budgetId);
        });
    });
}

/**
 * Initialize budget charts and progress bars
 */
function initializeBudgetCharts() {
    // Update progress bars dynamically
    document.querySelectorAll('.budget-progress').forEach(progress => {
        const spent = parseFloat(progress.dataset.spent) || 0;
        const limit = parseFloat(progress.dataset.limit) || 0;
        const percentage = (spent / limit) * 100;
        
        const progressBar = progress.querySelector('.progress-bar');
        if (progressBar) {
            progressBar.style.width = Math.min(percentage, 100) + '%';
            
            // Change color based on percentage
            if (percentage > 100) {
                progressBar.classList.add('bg-danger');
                progressBar.classList.remove('bg-success', 'bg-warning');
            } else if (percentage > 80) {
                progressBar.classList.add('bg-warning');
                progressBar.classList.remove('bg-success', 'bg-danger');
            } else {
                progressBar.classList.add('bg-success');
                progressBar.classList.remove('bg-warning', 'bg-danger');
            }
        }
    });
}

/**
 * Open budget creation/edit modal
 */
function openBudgetModal(budgetId = null) {
    const modal = new bootstrap.Modal(document.querySelector('#budgetModal'));
    
    if (budgetId) {
        // Load budget data for editing
        loadBudgetData(budgetId);
    } else {
        // Reset form for new budget
        document.querySelector('#budgetForm').reset();
    }
    
    modal.show();
}

/**
 * Load budget data for editing
 */
function loadBudgetData(budgetId) {
    // API call to fetch budget data
    console.log('Loading budget:', budgetId);
}

/**
 * Edit budget
 */
function editBudget(budgetId) {
    openBudgetModal(budgetId);
}

/**
 * Delete budget with confirmation
 */
function deleteBudget(budgetId) {
    showConfirmDialog('Xoá ngân sách?', 'Hành động này không thể hoàn tác.', function() {
        // API call to delete budget
        console.log('Deleting budget:', budgetId);
    });
}
