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
                <a href="<?php echo BASE_URL; ?>/admin/settings/budgets" class="text-decoration-none text-muted"><i class="fas fa-arrow-left"></i> Quay lại</a>
            </div>
        </div>

        <div class="card-box" style="max-width: 600px; margin: 0 auto;">
            <form action="<?php echo BASE_URL; ?>/admin/settings/save" method="POST">
                <input type="hidden" name="id" value="<?php echo $budget['id'] ?? 0; ?>">

                <div class="mb-3">
                    <label class="form-label">Người dùng</label>
                    <select name="user_id" class="form-select" required>
                        <?php foreach($users as $u): ?>
                            <option value="<?php echo $u['id']; ?>" <?php echo (isset($budget) && $budget['user_id'] == $u['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($u['username']); ?> (ID: <?php echo $u['id']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Danh mục</label>
                    <select name="category_id" class="form-select" required>
                        <?php foreach($categories as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo (isset($budget) && $budget['category_id'] == $c['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Số tiền giới hạn</label>
                    <input type="text" name="amount" class="form-control" value="<?php echo isset($budget) ? number_format($budget['amount']) : ''; ?>" required>
                </div>

                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Ngày bắt đầu</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo $budget['start_date'] ?? date('Y-m-01'); ?>">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Ngày kết thúc</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo $budget['end_date'] ?? date('Y-m-t'); ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Cảnh báo khi đạt (%)</label>
                    <input type="number" name="alert_threshold" class="form-control" value="<?php echo $budget['alert_threshold'] ?? 80; ?>">
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Lưu thông tin</button>
                </div>
            </form>
        </div>
    </main>
</div>
</body>
</html>