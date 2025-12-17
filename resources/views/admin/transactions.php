<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Giao dịch</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="<?php echo BASE_URL; ?>/css/admin.css" rel="stylesheet">
</head>
<body>
<div class="admin-wrapper">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    <main class="main-content">
        <div class="top-bar">
            <div class="page-title">
                <h1>Giao dịch</h1>
                <p>Kiểm soát dòng tiền người dùng</p>
            </div>
            <a href="<?php echo BASE_URL; ?>/admin/transactions/create" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Tạo mới</a>
        </div>

        <div class="card-box p-3 mb-4">
            <form class="row g-2 align-items-center">
                <div class="col-md-4">
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="Tìm nội dung, user..." value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-select form-select-sm">
                        <option value="">-- Tất cả loại --</option>
                        <option value="income" <?php echo ($_GET['type'] ?? '') == 'income' ? 'selected' : ''; ?>>Thu nhập</option>
                        <option value="expense" <?php echo ($_GET['type'] ?? '') == 'expense' ? 'selected' : ''; ?>>Chi tiêu</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-sm btn-primary w-100">Lọc dữ liệu</button>
                </div>
            </form>
        </div>

        <div class="card-box p-0 overflow-hidden">
            <table class="table-pro">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Người thực hiện</th>
                        <th>Nội dung</th>
                        <th>Số tiền</th>
                        <th>Ngày</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($transactions)): foreach($transactions as $t): ?>
                    <tr>
                        <td class="text-muted small">#<?php echo $t['id']; ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar" style="width:24px;height:24px;font-size:0.7rem;background:#f1f5f9;"><?php echo strtoupper(substr($t['username'] ?? 'U',0,1)); ?></div>
                                <span class="fw-bold small text-dark"><?php echo htmlspecialchars($t['username'] ?? 'Unknown'); ?></span>
                            </div>
                        </td>
                        <td>
                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($t['description']); ?></div>
                            <div class="small text-muted"><?php echo htmlspecialchars($t['category_name']); ?></div>
                        </td>
                        <td>
                            <?php if($t['type'] == 'income'): ?>
                                <span class="text-success fw-bold">+<?php echo number_format($t['amount']); ?></span>
                            <?php else: ?>
                                <span class="text-danger fw-bold">-<?php echo number_format($t['amount']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted"><?php echo date('d/m/Y', strtotime($t['date'])); ?></td>
                        <td class="text-end">
                            <a href="<?php echo BASE_URL; ?>/admin/transactions/edit/<?php echo $t['id']; ?>" class="btn btn-light btn-sm border px-2 py-1"><i class="fas fa-pen fa-xs"></i></a>
                            <a href="<?php echo BASE_URL; ?>/admin/transactions/delete/<?php echo $t['id']; ?>" onclick="return confirm('Xóa?')" class="btn btn-light btn-sm border px-2 py-1 text-danger"><i class="fas fa-trash fa-xs"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">Không có dữ liệu</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>