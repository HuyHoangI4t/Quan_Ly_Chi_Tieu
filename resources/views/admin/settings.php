<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?php echo $title; ?></title>
    <?php use App\Middleware\CsrfProtection; echo CsrfProtection::getTokenMeta(); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/css/admin.css" rel="stylesheet">
</head>
<body>
<div class="admin-wrapper">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    <main class="main-content">
        <div class="top-bar">
            <div class="page-title">
                <h1>Cài đặt hệ thống</h1>
            </div>
            <div class="d-flex gap-2">
                <a href="<?php echo BASE_URL; ?>/admin/settings" class="btn btn-primary">Tài khoản Admin</a>
                <a href="<?php echo BASE_URL; ?>/admin/settings/budgets" class="btn btn-light border">Quản lý Ngân sách</a>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-5">
                <div class="card-box text-center p-5">
                    <div class="avatar mx-auto mb-3" style="width:80px;height:80px;font-size:2rem;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;border-radius:50%;">
                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                    </div>
                    <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                    <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                    <span class="badge bg-primary">Administrator</span>
                </div>
            </div>
            <div class="col-md-7">
                <div class="card-box">
                    <h5 class="fw-bold mb-4">Đổi mật khẩu</h5>
                    <form id="changePassForm">
                        <div class="mb-3">
                            <label class="form-label">Mật khẩu hiện tại</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mật khẩu mới</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Xác nhận mật khẩu mới</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Cập nhật</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>
<script>
    document.getElementById('changePassForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = Object.fromEntries(new FormData(this));
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        try {
            const res = await fetch('<?php echo BASE_URL; ?>/admin/settings/api_change_password', {
                method: 'POST', headers: {'Content-Type':'application/json', 'X-CSRF-Token': csrfToken},
                body: JSON.stringify(formData)
            });
            const data = await res.json();
            alert(data.message);
            if(data.success) this.reset();
        } catch(e) { alert('Lỗi kết nối'); }
    });
</script>
</body>
</html>