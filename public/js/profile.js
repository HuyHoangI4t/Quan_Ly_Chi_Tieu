// === PROFILE PAGE JS (FINAL FIXED) ===

// 1. Helper: Lấy CSRF Token
function getCsrfToken() {
    return document.querySelector('input[name="csrf_token"]')?.value || 
           document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
}

// 2. Helper: Lấy Base URL chuẩn (Đổi sang function để tránh lỗi Identifier already declared)
function getBaseUrl() {
    if (typeof window.BASE_URL !== 'undefined' && window.BASE_URL) return window.BASE_URL;
    // Fallback nếu chưa define BASE_URL
    return window.location.origin + '/Quan_Ly_Chi_Tieu/public'; 
}

document.addEventListener('DOMContentLoaded', function () {
    const BASE_URL = getBaseUrl();

    // --- A. XỬ LÝ ẢNH ĐẠI DIỆN ---
    const avatarInput = document.getElementById('avatarInput');
    if (avatarInput) {
        avatarInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                if (!file.type.startsWith('image/')) {
                    if (typeof SmartSpending !== 'undefined') SmartSpending.showToast('Vui lòng chọn file ảnh', 'error');
                    return;
                }
                if (file.size > 2 * 1024 * 1024) { // 2MB
                    if (typeof SmartSpending !== 'undefined') SmartSpending.showToast('Ảnh quá lớn (>2MB)', 'error');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    const avatarDisplay = document.getElementById('avatarDisplay');
                    if (avatarDisplay) {
                        avatarDisplay.innerHTML = `<img src="${e.target.result}" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">`;
                    }
                };
                reader.readAsDataURL(file);

                // Lưu tạm vào localStorage
                const reader2 = new FileReader();
                reader2.onload = function (e) {
                    localStorage.setItem('userAvatar', e.target.result);
                    if (typeof SmartSpending !== 'undefined') SmartSpending.showToast('Đã chọn ảnh (Nhớ bấm Lưu)', 'success');
                };
                reader2.readAsDataURL(file);
            }
        });

        // Load ảnh từ localStorage nếu server chưa có
        const savedAvatar = localStorage.getItem('userAvatar');
        const avatarDisplay = document.getElementById('avatarDisplay');
        if (savedAvatar && avatarDisplay) {
            const hasServerImg = avatarDisplay.querySelector('img');
            // Chỉ hiện ảnh lưu tạm nếu chưa có ảnh thật từ server (tránh đè ảnh thật)
            if (!hasServerImg || hasServerImg.src.includes('default.svg')) {
                 avatarDisplay.innerHTML = `<img src="${savedAvatar}" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">`;
            }
        }
    }

    // --- B. NÚT GẠT THÔNG BÁO ---
    const notificationToggles = document.querySelectorAll('.notification-toggle');
    notificationToggles.forEach(toggle => {
        toggle.addEventListener('change', function () {
            const key = this.dataset.key;
            const value = this.checked;

            fetch(`${BASE_URL}/profile/api_update_preference`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': getCsrfToken()
                },
                body: JSON.stringify({ key: key, value: value })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success || data.status === 'success') {
                    if (typeof SmartSpending !== 'undefined') SmartSpending.showToast('Đã lưu cài đặt', 'success');
                } else {
                    this.checked = !value; // Hoàn tác
                    if (typeof SmartSpending !== 'undefined') SmartSpending.showToast(data.message || 'Lỗi lưu', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                this.checked = !value;
            });
        });
    });

    // --- C. FORM SỬA PROFILE ---
    const editProfileForm = document.getElementById('editProfileForm');
    if (editProfileForm) {
        editProfileForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = {
                name: this.querySelector('[name="name"]').value,
                email: this.querySelector('[name="email"]').value
            };

            fetch(`${BASE_URL}/profile/api_update`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() },
                body: JSON.stringify(formData)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (typeof SmartSpending !== 'undefined') SmartSpending.showToast('Cập nhật thành công!', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    if (typeof SmartSpending !== 'undefined') SmartSpending.showToast(data.message, 'error');
                }
            })
            .catch(err => console.error(err));
        });
    }

    // --- D. FORM ĐỔI MẬT KHẨU ---
    const changePasswordForm = document.getElementById('changePasswordForm');
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const newPass = this.querySelector('[name="new_password"]').value;
            const confirmPass = this.querySelector('[name="confirm_password"]').value;

            if (newPass !== confirmPass) {
                if (typeof SmartSpending !== 'undefined') SmartSpending.showToast('Mật khẩu không khớp', 'error');
                return;
            }

            const formData = {
                current_password: this.querySelector('[name="current_password"]').value,
                new_password: newPass
            };

            fetch(`${BASE_URL}/profile/api_change_password`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() },
                body: JSON.stringify(formData)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (typeof SmartSpending !== 'undefined') SmartSpending.showToast('Đổi mật khẩu thành công', 'success');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('changePasswordModal'));
                    if(modal) modal.hide();
                    this.reset();
                } else {
                    if (typeof SmartSpending !== 'undefined') SmartSpending.showToast(data.message, 'error');
                }
            })
            .catch(err => console.error(err));
        });
    }

    // --- E. NÚT XÓA DỮ LIỆU (Event Listener) ---
    const btnClearData = document.getElementById('btnClearData');
    if (btnClearData) {
        btnClearData.addEventListener('click', function(e) {
            e.preventDefault();
            clearAllData();
        });
    }
});

// --- GLOBAL FUNCTIONS (Cho onclick trong HTML) ---

function exportData() {
    const BASE_URL = getBaseUrl();
    if (typeof SmartSpending !== 'undefined') SmartSpending.showToast('Đang xuất dữ liệu...', 'success');
    
    const link = document.createElement('a');
    link.href = `${BASE_URL}/profile/export_data`;
    link.download = `Data_${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function clearAllData() {
    const BASE_URL = getBaseUrl();
    
    // Hàm thực thi gọi API
    const performClearData = async () => {
        if (typeof SmartSpending !== 'undefined') SmartSpending.showLoader();
        try {
            const response = await fetch(`${BASE_URL}/profile/api_clear_data`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': getCsrfToken()
                }
            });

            const text = await response.text();
            let data;
            try { data = JSON.parse(text); } catch (e) { throw new Error('Server trả về lỗi không phải JSON: ' + text); }

            if (response.ok && (data.success || data.status === 'success')) {
                if (typeof SmartSpending !== 'undefined') SmartSpending.showToast('Đã Reset tài khoản!', 'success');
                setTimeout(() => window.location.href = `${BASE_URL}/dashboard`, 1000);
            } else {
                throw new Error(data.message || 'Lỗi không xác định');
            }
        } catch (error) {
            console.error(error);
            if (typeof SmartSpending !== 'undefined') {
                SmartSpending.hideLoader();
                SmartSpending.showToast(error.message, 'error');
            } else {
                alert(error.message);
            }
        }
    };

    // Hiện popup xác nhận
    if (typeof SmartSpending !== 'undefined' && SmartSpending.showConfirm) {
        SmartSpending.showConfirm(
            'CẢNH BÁO NGUY HIỂM!',
            'Bạn có chắc chắn muốn XÓA SẠCH mọi dữ liệu? Hành động này KHÔNG THỂ khôi phục!',
            performClearData
        );
    } else {
        if (confirm('CẢNH BÁO: Xóa tất cả dữ liệu? Không thể hoàn tác!')) {
            performClearData();
        }
    }
}