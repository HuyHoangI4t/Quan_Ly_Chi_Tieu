/**
 * Transactions Page Specific JavaScript
 * Handles transaction listing, filtering, and management
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize transaction page features
    initializeTransactionFilters();
    initializeTransactionActions();
});

/**
 * Initialize transaction filters (date, category, type)
 */
function initializeTransactionFilters() {
    const filterForm = document.querySelector('.filter-form');
    if (!filterForm) return;

    // Filter by date
    const dateFilter = document.querySelector('input[name="date"]');
    if (dateFilter) {
        dateFilter.addEventListener('change', function() {
            filterTransactions();
        });
    }

    // Filter by category
    const categoryFilter = document.querySelector('select[name="category"]');
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function() {
            filterTransactions();
        });
    }

    // Filter by type (Income/Expense)
    const typeFilters = document.querySelectorAll('input[name="type"]');
    typeFilters.forEach(filter => {
        filter.addEventListener('change', function() {
            filterTransactions();
        });
    });
}

/**
 * Filter transactions based on current filter values
 */
function filterTransactions() {
    const dateFilter = document.querySelector('input[name="date"]')?.value || '';
    const categoryFilter = document.querySelector('select[name="category"]')?.value || '';
    const typeFilter = document.querySelector('input[name="type"]:checked')?.value || '';

    // Add filtering logic - can be API call or client-side filtering
    console.log('Filtering by:', { date: dateFilter, category: categoryFilter, type: typeFilter });
}

/**
 * Initialize transaction action buttons (edit, delete)
 */
function initializeTransactionActions() {
    // Edit transaction
    document.querySelectorAll('.btn-edit-transaction').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const transactionId = this.dataset.id;
            editTransaction(transactionId);
        });
    });

    // Delete transaction
    document.querySelectorAll('.btn-delete-transaction').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const transactionId = this.dataset.id;
            deleteTransaction(transactionId);
        });
    });

    // Add new transaction (FAB button)
    const fabBtn = document.querySelector('.fab-add-transaction');
    if (fabBtn) {
        fabBtn.addEventListener('click', function() {
            openTransactionModal();
        });
    }
}

/**
 * Open transaction creation/edit modal
 */
function openTransactionModal(transactionId = null) {
    const modal = new bootstrap.Modal(document.querySelector('#transactionModal'));
    
    if (transactionId) {
        // Load transaction data for editing
        loadTransactionData(transactionId);
    } else {
        // Reset form for new transaction
        document.querySelector('#transactionForm').reset();
    }
    
    modal.show();
}

/**
 * Load transaction data for editing
 */
function loadTransactionData(transactionId) {
    // API call to fetch transaction data
    console.log('Loading transaction:', transactionId);
}

/**
 * Edit transaction
 */
function editTransaction(transactionId) {
    openTransactionModal(transactionId);
}

/**
 * Delete transaction with confirmation
 */
function deleteTransaction(transactionId) {
    showConfirmDialog('Xoá giao dịch?', 'Hành động này không thể hoàn tác.', function() {
        // API call to delete transaction
        console.log('Deleting transaction:', transactionId);
    });
}
