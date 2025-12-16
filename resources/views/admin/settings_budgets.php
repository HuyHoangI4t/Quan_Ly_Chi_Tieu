<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?php echo $title; ?></title>
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
                <h1>Quản lý Ngân sách</h1>
            </div>
            <div class="d-flex gap-2">
                <a href="<?php echo BASE_URL; ?>/admin/settings" class="btn btn-light border">Tài khoản Admin</a>
                <a href="<?php echo BASE_URL; ?>/admin/settings/budgets" class="btn btn-primary">Quản lý Ngân sách</a>
            </div>
        </div>
        
        <div class="d-flex justify-content-end mb-3">
            <a href="<?php echo BASE_URL; ?>/admin/settings/new" class="btn btn-success"><i class="fas fa-plus me-2"></i>Tạo Ngân sách</a>
        </div>

        <div class="card-box p-0 overflow-hidden">
            <table class="table-pro">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Danh mục</th>
                        <th>Số tiền</th>
                        <th>Thời gian</th>
                        <th class="text-end">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($budgets)): foreach($budgets as $b): ?>
                    <tr>
                        <td>#<?php echo $b['id']; ?></td>
                        <td class="fw-bold"><?php echo htmlspecialchars($b['username']); ?></td>
                        <td><?php echo htmlspecialchars($b['category_name']); ?></td>
                        <td class="text-primary fw-bold"><?php echo number_format($b['amount']); ?></td>
                        <td class="small">
                            <?php echo $b['start_date']; ?> <i class="fas fa-arrow-right mx-1"></i> <?php echo $b['end_date']; ?>
                        </td>
                        <td class="text-end">
                            <a href="<?php echo BASE_URL; ?>/admin/settings/edit/<?php echo $b['id']; ?>" class="btn btn-sm btn-light border"><i class="fas fa-pen text-primary"></i></a>
                            <a href="<?php echo BASE_URL; ?>/admin/settings/delete/<?php echo $b['id']; ?>" onclick="return confirm('Xóa?')" class="btn btn-sm btn-light border"><i class="fas fa-trash text-danger"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="6" class="text-center py-4">Chưa có dữ liệu</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>