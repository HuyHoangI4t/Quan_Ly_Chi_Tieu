/**
 * Goals Page Specific JavaScript
 * Handles financial goal management and tracking
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize goals page features
    initializeGoalActions();
    initializeGoalProgress();
});

/**
 * Initialize goal action buttons
 */
function initializeGoalActions() {
    // Add new goal button
    const addGoalBtn = document.querySelector('.btn-add-goal');
    if (addGoalBtn) {
        addGoalBtn.addEventListener('click', function() {
            openGoalModal();
        });
    }

    // Edit goal
    document.querySelectorAll('.btn-edit-goal').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const goalId = this.dataset.id;
            editGoal(goalId);
        });
    });

    // Delete goal
    document.querySelectorAll('.btn-delete-goal').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const goalId = this.dataset.id;
            deleteGoal(goalId);
        });
    });

    // Mark goal as completed
    document.querySelectorAll('.btn-complete-goal').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const goalId = this.dataset.id;
            completeGoal(goalId);
        });
    });
}

/**
 * Initialize goal progress tracking
 */
function initializeGoalProgress() {
    // Update progress bars for goals
    document.querySelectorAll('.goal-progress').forEach(goal => {
        const current = parseFloat(goal.dataset.current) || 0;
        const target = parseFloat(goal.dataset.target) || 0;
        const percentage = (current / target) * 100;
        
        const progressBar = goal.querySelector('.progress-bar');
        if (progressBar) {
            progressBar.style.width = Math.min(percentage, 100) + '%';
            
            // Change color based on percentage
            if (percentage >= 100) {
                progressBar.classList.add('bg-success');
                progressBar.classList.remove('bg-warning', 'bg-info');
            } else if (percentage >= 75) {
                progressBar.classList.add('bg-info');
                progressBar.classList.remove('bg-warning', 'bg-success');
            } else {
                progressBar.classList.add('bg-warning');
                progressBar.classList.remove('bg-info', 'bg-success');
            }
        }
    });
}

/**
 * Open goal creation/edit modal
 */
function openGoalModal(goalId = null) {
    const modal = new bootstrap.Modal(document.querySelector('#goalModal'));
    
    if (goalId) {
        // Load goal data for editing
        loadGoalData(goalId);
    } else {
        // Reset form for new goal
        document.querySelector('#goalForm').reset();
    }
    
    modal.show();
}

/**
 * Load goal data for editing
 */
function loadGoalData(goalId) {
    // API call to fetch goal data
    console.log('Loading goal:', goalId);
}

/**
 * Edit goal
 */
function editGoal(goalId) {
    openGoalModal(goalId);
}

/**
 * Delete goal with confirmation
 */
function deleteGoal(goalId) {
    showConfirmDialog('Xoá mục tiêu?', 'Hành động này không thể hoàn tác.', function() {
        // API call to delete goal
        console.log('Deleting goal:', goalId);
    });
}

/**
 * Mark goal as completed
 */
function completeGoal(goalId) {
    showConfirmDialog('Hoàn thành mục tiêu?', 'Bạn chắc chắn đã đạt được mục tiêu này?', function() {
        // API call to mark goal as completed
        console.log('Completing goal:', goalId);
    });
}
