<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Người dùng</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="<?php echo BASE_URL; ?>/css/admin.css" rel="stylesheet">
</head>
<body>
<div class="admin-wrapper">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    <main class="main-content">
        <div class="top-bar">
            <div class="page-title">
                <h1>Người dùng</h1>
                <p>Quản lý tài khoản & phân quyền</p>
            </div>
        </div>

        <div class="card-box p-0 overflow-hidden">
            <div class="p-3 border-bottom d-flex justify-content-between">
                <form method="GET" class="d-flex gap-2">
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="Tìm email, username..." value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>" style="width: 250px;">
                    <button class="btn btn-sm btn-light border"><i class="fas fa-search"></i></button>
                </form>
            </div>
            <table class="table-pro">
                <thead>
                    <tr>
                        <th>User Info</th>
                        <th>Vai trò</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th class="text-end">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $u): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar bg-light text-primary fw-bold"><?php echo strtoupper(substr($u['username'], 0, 1)); ?></div>
                                <div>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($u['username']); ?></div>
                                    <div class="small text-muted"><?php echo htmlspecialchars($u['email']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if($u['role'] === 'admin'): ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Admin</span>
                            <?php else: ?>
                                <span class="badge bg-light text-muted border">User</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($u['is_active']): ?>
                                <span class="badge bg-success-subtle text-success"><i class="fas fa-check-circle me-1"></i>Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger"><i class="fas fa-ban me-1"></i>Banned</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted"><?php echo date('d/m/Y', strtotime($u['created_at'])); ?></td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">Action</button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item small" href="<?php echo BASE_URL; ?>/admin/users/toggle_status/<?php echo $u['id']; ?>"><?php echo $u['is_active'] ? 'Khóa tài khoản' : 'Mở khóa'; ?></a></li>
                                    <?php if($u['role'] !== 'admin'): ?>
                                        <li><a class="dropdown-item small" href="<?php echo BASE_URL; ?>/admin/users/promote/<?php echo $u['id']; ?>">Thăng cấp Admin</a></li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item small text-danger" href="#" onclick="alert('Chức năng Reset Pass đang phát triển')">Reset Password</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>