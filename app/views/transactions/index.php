<?php $this->partial('header'); ?>

<!-- Transactions Specific Styles -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/transactions.css">

<section>
    <h3 class="mb-3">Giao dịch</h3>

    <div class="card p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex" style="gap:10px;">
                <select class="form-select" style="width:160px">
                    <option>Tháng này</option>
                    <option>Tháng trước</option>
                </select>
                <select class="form-select" style="width:180px">
                    <option>Tất cả danh mục</option>
                </select>
            </div>
            <div>
                <a href="#" class="btn btn-success">+ Thêm giao dịch</a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ngày</th>
                        <th>Danh mục</th>
                        <th>Mô tả</th>
                        <th>Số tiền</th>
                        <th>Loại</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($transactions)): ?>
                        <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td><?php echo $this->escape($t['date']); ?></td>
                                <td><?php echo $this->escape($t['category']); ?></td>
                                <td><?php echo $this->escape($t['description']); ?></td>
                                <td style="text-align:right;">
                                    <?php if ($t['amount'] < 0): ?>
                                        - <?php echo number_format(abs($t['amount']), 0, ',', '.'); ?> ₫
                                    <?php else: ?>
                                        + <?php echo number_format($t['amount'], 0, ',', '.'); ?> ₫
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($t['type'] === 'income'): ?>
                                        <span class="badge badge-income">Thu nhập</span>
                                    <?php else: ?>
                                        <span class="badge badge-expense">Chi tiêu</span>
                                    <?php endif; ?>
                                </td>
                                <td class="table-actions">
                                    <a href="#" class="btn btn-sm btn-outline-primary">Sửa</a>
                                    <a href="#" class="btn btn-sm btn-danger">Xoá</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="center-muted">Không có giao dịch để hiển thị.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php $this->partial('footer'); ?>
