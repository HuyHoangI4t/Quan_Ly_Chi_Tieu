<?php $this->partial('admin_header', ['title' => $title ?? 'Thiết lập ngân sách']); ?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3><?php echo isset($budget) ? 'Chỉnh sửa ngân sách' : 'Thêm ngân sách'; ?></h3>
        <div><a href="<?php echo BASE_URL; ?>/admin/settings/budgets" class="btn btn-outline-secondary">Quay lại</a></div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="post" action="<?php echo BASE_URL; ?>/admin/settings/save">
                <?php if (!empty($budget)): ?><input type="hidden" name="id" value="<?php echo $budget['id']; ?>"><?php endif; ?>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Người dùng</label>
                        <select name="user_id" class="form-select">
                            <?php foreach ($users as $u): ?>
                                <option value="<?php echo $u['id']; ?>" <?php echo (!empty($budget) && $budget['user_id']==$u['id'])? 'selected':''; ?>><?php echo htmlspecialchars($u['username']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Danh mục</label>
                        <select name="category_id" class="form-select">
                            <?php foreach ($categories as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo (!empty($budget) && $budget['category_id']==$c['id'])? 'selected':''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Số tiền</label>
                        <input name="amount" class="form-control" value="<?php echo htmlspecialchars($budget['amount'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Kỳ</label>
                        <select name="period" class="form-select"><option value="monthly">Hàng tháng</option><option value="yearly">Hàng năm</option></select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Bắt đầu</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($budget['start_date'] ?? date('Y-m-01')); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Kết thúc</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($budget['end_date'] ?? date('Y-m-t')); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Alert (%)</label>
                        <input type="number" name="alert_threshold" class="form-control" value="<?php echo htmlspecialchars($budget['alert_threshold'] ?? 80); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-check"><input type="checkbox" name="is_active" <?php echo (!empty($budget) && $budget['is_active'])? 'checked':''; ?>> Kích hoạt</label>
                    </div>
                </div>
                <div class="mt-3"><button class="btn btn-primary">Lưu</button></div>
            </form>
        </div>
    </div>
</div>

<?php $this->partial('admin_footer'); ?>
