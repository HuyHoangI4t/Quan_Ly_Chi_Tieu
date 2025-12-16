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
        <div class="top-bar mb-4">
            <div class="page-title">
                <h1><?php echo $title; ?></h1>
                <a href="<?php echo BASE_URL; ?>/admin/transactions" class="text-muted text-decoration-none"><i class="fas fa-arrow-left"></i> Quay lại</a>
            </div>
        </div>

        <div class="card-box" style="max-width: 600px; margin: 0 auto;">
            <form action="<?php echo BASE_URL; ?>/admin/transactions/save" method="POST">
                <input type="hidden" name="id" value="<?php echo $transaction['id'] ?? 0; ?>">
                
                <div class="mb-3">
                    <label class="form-label">User ID (Người sở hữu)</label>
                    <input type="number" name="user_id" class="form-control" value="<?php echo $transaction['user_id'] ?? ''; ?>" required placeholder="Nhập ID User">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Loại</label>
                        <select name="type" class="form-select">
                            <option value="expense" <?php echo (isset($transaction) && $transaction['type'] == 'expense') ? 'selected' : ''; ?>>Chi tiêu</option>
                            <option value="income" <?php echo (isset($transaction) && $transaction['type'] == 'income') ? 'selected' : ''; ?>>Thu nhập</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Số tiền</label>
                        <input type="text" name="amount" class="form-control" value="<?php echo isset($transaction) ? number_format($transaction['amount']) : ''; ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Danh mục</label>
                    <select name="category_id" class="form-select" required>
                        <?php foreach($categories as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo (isset($transaction) && $transaction['category_id'] == $c['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Ngày giao dịch</label>
                    <input type="date" name="date" class="form-control" value="<?php echo $transaction['date'] ?? date('Y-m-d'); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Mô tả</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($transaction['description'] ?? ''); ?></textarea>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary py-2">Lưu giao dịch</button>
                </div>
            </form>
        </div>
    </main>
</div>
</body>
</html>