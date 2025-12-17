<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="<?php echo BASE_URL; ?>/css/admin.css" rel="stylesheet">
</head>
<body>
<div class="admin-wrapper">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    <main class="main-content">
        <div class="top-bar">
            <div class="page-title">
                <h1>Cấu hình hệ thống</h1>
                <p>Thiết lập các thông số vận hành toàn trang</p>
            </div>
        </div>

        <form action="<?php echo BASE_URL; ?>/admin/system/save" method="POST">
            <div class="row">
                <div class="col-md-8">
                    <div class="card-box">
                        <h5 class="fw-bold mb-4 border-bottom pb-2">Thông tin chung</h5>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Tên Website (Site Name)</label>
                            <input type="text" name="site_name" class="form-control" value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="card-box">
                        <h5 class="fw-bold mb-4 border-bottom pb-2 text-danger">Vùng nguy hiểm</h5>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <div class="fw-bold">Chế độ bảo trì</div>
                                <div class="small text-muted">Chỉ Admin mới có thể truy cập hệ thống</div>
                            </div>
                            <div class="form-check form-switch">
                                <input type="hidden" name="maintenance_mode" value="0">
                                <input class="form-check-input" type="checkbox" name="maintenance_mode" value="1" <?php echo ($settings['maintenance_mode'] ?? '0') == '1' ? 'checked' : ''; ?>>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold">Cho phép đăng ký</div>
                                <div class="small text-muted">Người dùng mới có thể tạo tài khoản</div>
                            </div>
                            <div class="form-check form-switch">
                                <input type="hidden" name="allow_registration" value="0">
                                <input class="form-check-input" type="checkbox" name="allow_registration" value="1" <?php echo ($settings['allow_registration'] ?? '0') == '1' ? 'checked' : ''; ?>>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-box">
                        <button type="submit" class="btn btn-primary w-100 py-2 mb-2">Lưu cấu hình</button>
                        <a href="<?php echo BASE_URL; ?>/admin/dashboard" class="btn btn-light w-100">Hủy bỏ</a>
                    </div>
                </div>
            </div>
        </form>
    </main>
</div>
</body>
</html>
