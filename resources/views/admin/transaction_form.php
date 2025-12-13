<?php $this->partial('admin_header', ['title' => $title ?? 'Giao dịch']); ?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3><?php echo isset($transaction) ? 'Chỉnh sửa giao dịch' : 'Thêm giao dịch'; ?></h3>
        <div><a href="<?php echo BASE_URL; ?>/admin/transactions" class="btn btn-outline-secondary">Quay lại</a></div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="post" action="<?php echo BASE_URL; ?>/admin/transactions/save">
                <?php if (!empty($transaction)): ?><input type="hidden" name="id" value="<?php echo $transaction['id']; ?>"><?php endif; ?>
                <div class="mb-3">
                    <label class="form-label">Số tiền</label>
                    <input type="text" name="amount" class="form-control" value="<?php echo htmlspecialchars($transaction['amount'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Danh mục</label>
                    <select name="category_id" class="form-select" required>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo (!empty($transaction) && $transaction['category_id']==$c['id'])? 'selected':''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Loại</label>
                    <select name="type" class="form-select">
                        <option value="expense" <?php echo (empty($transaction) || $transaction['type']=='expense')? 'selected':''; ?>>Chi</option>
                        <option value="income" <?php echo (!empty($transaction) && $transaction['type']=='income')? 'selected':''; ?>>Thu</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Ngày</label>
                    <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($transaction['date'] ?? date('Y-m-d')); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Mô tả</label>
                    <textarea name="description" class="form-control"><?php echo htmlspecialchars($transaction['description'] ?? ''); ?></textarea>
                </div>
                <button class="btn btn-primary">Lưu</button>
            </form>
        </div>
    </div>
</div>

<?php $this->partial('admin_footer'); ?>
