/**
 * Profile Page Specific JavaScript
 * Handles user profile updates, settings, and preferences
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize profile page features
    initializeProfileForm();
    initializePasswordChange();
    initializePreferences();
    initializeAccountActions();
});

/**
 * Initialize profile form handling
 */
function initializeProfileForm() {
    const profileForm = document.querySelector('#profileForm');
    if (!profileForm) return;

    profileForm.addEventListener('submit', function(e) {
        e.preventDefault();
        updateProfile();
    });

    // Avatar upload handler
    const avatarInput = document.querySelector('input[name="avatar"]');
    if (avatarInput) {
        avatarInput.addEventListener('change', function(e) {
            previewProfileImage(e);
        });
    }
}

/**
 * Preview profile image before upload
 */
function previewProfileImage(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.querySelector('.profile-avatar-preview');
            if (preview) {
                preview.src = e.target.result;
            }
        };
        reader.readAsDataURL(file);
    }
}

/**
 * Update user profile
 */
function updateProfile() {
    const formData = new FormData(document.querySelector('#profileForm'));
    
    fetch(BASE_URL + '/profile/update', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Cập nhật hồ sơ thành công', 'success');
        } else {
            showToast('Lỗi: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Lỗi khi cập nhật hồ sơ', 'danger');
    });
}

/**
 * Initialize password change handler
 */
function initializePasswordChange() {
    const passwordForm = document.querySelector('#passwordForm');
    if (!passwordForm) return;

    passwordForm.addEventListener('submit', function(e) {
        e.preventDefault();
        changePassword();
    });

    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(toggle => {
        toggle.addEventListener('click', function() {
            togglePasswordVisibility(this);
        });
    });
}

/**
 * Toggle password input visibility
 */
function togglePasswordVisibility(button) {
    const input = button.previousElementSibling;
    if (input && input.tagName === 'INPUT') {
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        
        const icon = button.querySelector('i');
        if (icon) {
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        }
    }
}

/**
 * Change user password
 */
function changePassword() {
    const currentPassword = document.querySelector('input[name="current_password"]')?.value;
    const newPassword = document.querySelector('input[name="new_password"]')?.value;
    const confirmPassword = document.querySelector('input[name="confirm_password"]')?.value;

    if (!currentPassword || !newPassword || !confirmPassword) {
        showToast('Vui lòng nhập tất cả các trường', 'warning');
        return;
    }

    if (newPassword !== confirmPassword) {
        showToast('Mật khẩu mới không khớp', 'danger');
        return;
    }

    fetch(BASE_URL + '/profile/change_password', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            current_password: currentPassword,
            new_password: newPassword
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Đổi mật khẩu thành công', 'success');
            document.querySelector('#passwordForm').reset();
        } else {
            showToast('Lỗi: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Lỗi khi đổi mật khẩu', 'danger');
    });
}

/**
 * Initialize user preferences/settings
 */
function initializePreferences() {
    // Theme preference
    const themeSelect = document.querySelector('select[name="theme"]');
    if (themeSelect) {
        themeSelect.addEventListener('change', function() {
            updatePreference('theme', this.value);
        });
    }

    // Language preference
    const languageSelect = document.querySelector('select[name="language"]');
    if (languageSelect) {
        languageSelect.addEventListener('change', function() {
            updatePreference('language', this.value);
        });
    }

    // Notification preferences
    document.querySelectorAll('input[name="notification"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updatePreference('notification', this.name, this.checked);
        });
    });
}

/**
 * Update user preference
 */
function updatePreference(key, value) {
    fetch(BASE_URL + '/profile/update_preference', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            key: key,
            value: value
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Cập nhật cài đặt thành công', 'success');
        }
    })
    .catch(error => console.error('Error:', error));
}

/**
 * Initialize account actions (logout, delete account)
 */
function initializeAccountActions() {
    // Logout
    const logoutBtn = document.querySelector('.btn-logout');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            logout();
        });
    }

    // Delete account
    const deleteBtn = document.querySelector('.btn-delete-account');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function(e) {
            e.preventDefault();
            deleteAccount();
        });
    }
}

/**
 * Logout user
 */
function logout() {
    showConfirmDialog('Đăng xuất?', 'Bạn chắc chắn muốn đăng xuất?', function() {
        window.location.href = BASE_URL + '/logout';
    });
}

/**
 * Delete account permanently
 */
function deleteAccount() {
    showConfirmDialog('Xoá tài khoản?', 'Hành động này sẽ xoá vĩnh viễn tài khoản của bạn và không thể hoàn tác.', function() {
        fetch(BASE_URL + '/profile/delete_account', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Tài khoản đã được xoá', 'success');
                setTimeout(() => {
                    window.location.href = BASE_URL + '/';
                }, 1500);
            }
        })
        .catch(error => console.error('Error:', error));
    });
}
