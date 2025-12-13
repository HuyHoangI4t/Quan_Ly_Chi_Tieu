<?php $this->partial('admin_header', ['title' => $title ?? 'Thiết lập - Budgets']); ?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Ngân sách người dùng</h3>
        <div><a href="<?php echo BASE_URL; ?>/admin/settings/new" class="btn btn-primary">Thêm ngân sách</a></div>
    </div>

    <div class="card table-card">
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead><tr><th>ID</th><th>Người dùng</th><th>Danh mục</th><th>Số tiền</th><th>Kỳ</th><th>Alert %</th><th></th></tr></thead>
                <tbody>
                    <?php if (!empty($budgets)): foreach ($budgets as $b): ?>
                        <tr>
                            <td><?php echo $b['id']; ?></td>
                            <td><?php echo htmlspecialchars($b['username'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($b['category_name'] ?? ''); ?></td>
                            <td class="text-end"><?php echo number_format($b['amount'] ?? 0,0,',','.'); ?>đ</td>
                            <td><?php echo htmlspecialchars($b['period'] ?? 'monthly'); ?></td>
                            <td><?php echo intval($b['alert_threshold'] ?? 0); ?>%</td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?php echo BASE_URL; ?>/admin/settings/edit/<?php echo $b['id']; ?>">Sửa</a> <a class="btn btn-sm btn-outline-danger" href="<?php echo BASE_URL; ?>/admin/settings/delete/<?php echo $b['id']; ?>" onclick="return confirm('Xác nhận xóa?')">Xóa</a></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="7" class="text-center">Chưa có ngân sách</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $this->partial('admin_footer'); ?>
