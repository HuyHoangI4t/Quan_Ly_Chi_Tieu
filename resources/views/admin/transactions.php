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
                <h1>Quản lý Giao dịch</h1>
                <p>Xem và chỉnh sửa toàn bộ giao dịch hệ thống</p>
            </div>
            <a href="<?php echo BASE_URL; ?>/admin/transactions/new" class="btn btn-primary rounded-pill">
                <i class="fas fa-plus me-2"></i>Thêm mới
            </a>
        </div>

        <div class="card-box mb-4">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="q" class="form-control" placeholder="Tìm kiếm nội dung, user..." value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <select name="category_id" class="form-select">
                        <option value="">-- Tất cả danh mục --</option>
                        <?php foreach($categories as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo (isset($_GET['category_id']) && $_GET['category_id'] == $c['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-select">
                        <option value="">-- Loại --</option>
                        <option value="income" <?php echo (isset($_GET['type']) && $_GET['type'] == 'income') ? 'selected' : ''; ?>>Thu nhập</option>
                        <option value="expense" <?php echo (isset($_GET['type']) && $_GET['type'] == 'expense') ? 'selected' : ''; ?>>Chi tiêu</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Lọc</button>
                </div>
            </form>
        </div>

        <div class="card-box p-0 overflow-hidden">
            <table class="table-pro">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Người dùng</th>
                        <th>Danh mục</th>
                        <th>Số tiền</th>
                        <th>Ngày & Nội dung</th>
                        <th class="text-end">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($transactions)): foreach($transactions as $t): ?>
                    <tr>
                        <td>#<?php echo $t['id']; ?></td>
                        <td>
                            <div class="fw-bold"><?php echo htmlspecialchars($t['username'] ?? 'Unknown'); ?></div>
                        </td>
                        <td>
                            <span class="badge <?php echo $t['type'] == 'income' ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo htmlspecialchars($t['category_name'] ?? 'N/A'); ?>
                            </span>
                        </td>
                        <td class="<?php echo $t['type'] == 'income' ? 'text-success' : 'text-danger'; ?> fw-bold">
                            <?php echo $t['type'] == 'expense' ? '-' : '+'; ?>
                            <?php echo number_format($t['amount']); ?> đ
                        </td>
                        <td>
                            <div class="small fw-bold"><?php echo date('d/m/Y', strtotime($t['date'])); ?></div>
                            <div class="text-muted small"><?php echo htmlspecialchars($t['description']); ?></div>
                        </td>
                        <td class="text-end">
                            <a href="<?php echo BASE_URL; ?>/admin/transactions/edit/<?php echo $t['id']; ?>" class="btn btn-sm btn-light border"><i class="fas fa-pen text-primary"></i></a>
                            <a href="<?php echo BASE_URL; ?>/admin/transactions/delete/<?php echo $t['id']; ?>" onclick="return confirm('Xóa giao dịch này?')" class="btn btn-sm btn-light border"><i class="fas fa-trash text-danger"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">Không tìm thấy dữ liệu</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php if ($total_pages > 1): ?>
            <div class="p-3 border-top d-flex justify-content-end gap-2">
                <?php for($i=1; $i<=$total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&q=<?php echo htmlspecialchars($_GET['q']??''); ?>&category_id=<?php echo htmlspecialchars($_GET['category_id']??''); ?>" 
                       class="btn btn-sm <?php echo ($i == $current_page) ? 'btn-primary' : 'btn-light border'; ?>">
                       <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>