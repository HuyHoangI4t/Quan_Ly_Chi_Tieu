<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Cài đặt hệ thống</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/css/admin.css" rel="stylesheet">
    <style>
        /* CSS riêng cho trang Settings */
        .nav-tabs .nav-link {
            border: none;
            color: #64748b;
            font-weight: 500;
            padding: 12px 20px;
            border-bottom: 2px solid transparent;
        }
        .nav-tabs .nav-link.active {
            color: var(--primary);
            border-bottom: 2px solid var(--primary);
            background: none;
        }
        .nav-tabs .nav-link:hover { border-color: transparent; color: var(--primary); }
        .form-label { font-size: 0.85rem; font-weight: 600; color: #475569; }
        .form-control, .form-select { padding: 10px 15px; border-radius: 8px; border: 1px solid #e2e8f0; }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
        .setting-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; }
    </style>
</head>
<body>

<div class="admin-wrapper">
    
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <main class="main-content">
        <div class="top-bar">
            <div class="page-title">
                <h1>Cài đặt hệ thống</h1>
                <p>Cấu hình các thông số vận hành website</p>
            </div>
            <button class="btn-primary" onclick="saveSettings()">
                <i class="fas fa-save me-2"></i> Lưu thay đổi
            </button>
        </div>

        <div class="card-box p-0 overflow-hidden">
            <ul class="nav nav-tabs px-4 pt-2 border-bottom">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#general"><i class="fas fa-sliders-h me-2"></i>Chung</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#security"><i class="fas fa-shield-alt me-2"></i>Bảo mật</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#mail"><i class="fas fa-envelope me-2"></i>Mail Server</a>
                </li>
            </ul>

            <div class="tab-content p-4 bg-light">
                
                <div class="tab-pane fade show active" id="general">
                    <div class="setting-card">
                        <h5 class="fw-bold mb-4 text-dark">Thông tin Website</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Tên Website</label>
                                <input type="text" class="form-control" value="SmartSpending">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Slogan</label>
                                <input type="text" class="form-control" value="Quản lý tài chính thông minh">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Đơn vị tiền tệ</label>
                                <select class="form-select">
                                    <option value="VND" selected>Việt Nam Đồng (₫)</option>
                                    <option value="USD">US Dollar ($)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Múi giờ</label>
                                <select class="form-select">
                                    <option value="Asia/Ho_Chi_Minh" selected>(GMT+07:00) Bangkok, Hanoi, Jakarta</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Chế độ bảo trì (Maintenance Mode)</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="maintenanceMode">
                                    <label class="form-check-label" for="maintenanceMode">Bật chế độ bảo trì (Chỉ Admin mới truy cập được)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="security">
                    <div class="setting-card">
                        <h5 class="fw-bold mb-4 text-dark">Cấu hình đăng ký & Đăng nhập</h5>
                        <div class="mb-3">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" checked>
                                <label class="form-check-label">Cho phép đăng ký thành viên mới</label>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" checked>
                                <label class="form-check-label">Yêu cầu xác thực Email khi đăng ký</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox">
                                <label class="form-check-label">Bật Google reCAPTCHA</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="mail">
                    <div class="setting-card">
                        <h5 class="fw-bold mb-4 text-dark">Cấu hình SMTP (Gửi mail)</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Mail Host</label>
                                <input type="text" class="form-control" placeholder="smtp.gmail.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mail Port</label>
                                <input type="text" class="form-control" placeholder="587">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" placeholder="admin@gmail.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" placeholder="••••••••">
                            </div>
                            <div class="col-12 text-end">
                                <button class="btn btn-light border"><i class="fas fa-paper-plane me-2"></i>Gửi mail test</button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function saveSettings() {
        // Giả lập lưu
        const btn = document.querySelector('.btn-primary');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Đang lưu...';
        btn.disabled = true;

        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            alert('Đã lưu cấu hình thành công!');
        }, 1000);
    }
</script>
</body>
</html>