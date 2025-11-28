<?php $this->partial('header'); ?>

<!-- Transactions Specific Styles -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/transactions.css">

<section>
    <h3 class="mb-3">Giao dịch</h3>

    <div class="card p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex" style="gap:10px;">
                <select class="form-select" style="width:160px" id="rangeFilter">
                    <option value="all" <?php echo ($current_range == 'all') ? 'selected' : ''; ?>>Tất cả thời gian</option>
                    <option value="this_week" <?php echo ($current_range == 'this_week') ? 'selected' : ''; ?>>Tuần này</option>
                    <option value="this_month" <?php echo ($current_range == 'this_month') ? 'selected' : ''; ?>>Tháng này</option>
                    <option value="this_year" <?php echo ($current_range == 'this_year') ? 'selected' : ''; ?>>Năm nay</option>
                </select>
                <select class="form-select" style="width:180px" id="categoryFilter">
                    <option value="all">Tất cả danh mục</option>
                    <?php if (!empty($categories)): ?>
                        <optgroup label="Chi tiêu">
                            <?php foreach ($categories as $cat): ?>
                                <?php if ($cat['type'] == 'expense'): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo ($current_category == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo $this->escape($cat['name']); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="Thu nhập">
                            <?php foreach ($categories as $cat): ?>
                                <?php if ($cat['type'] == 'income'): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo ($current_category == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo $this->escape($cat['name']); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                </select>
            </div>
            <div>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                    + Thêm giao dịch
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Ngày</th>
                        <th>Danh mục</th>
                        <th>Mô tả</th>
                        <th style="text-align: right;">Số tiền</th>
                        <th style="text-align: center;">Loại</th>
                        <th style="text-align: center;">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($transactions)): ?>
                        <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($t['transaction_date'])); ?></td>
                                <td><?php echo $this->escape($t['category_name']); ?></td>
                                <td><?php echo $this->escape($t['description']); ?></td>
                                <td style="text-align:right; color: <?php echo ($t['amount'] < 0) ? 'var(--danger)' : 'var(--success)'; ?>; font-weight: 500;">
                                    <?php if ($t['amount'] < 0): ?>
                                        - <?php echo number_format(abs($t['amount']), 0, ',', '.'); ?> ₫
                                    <?php else: ?>
                                        + <?php echo number_format($t['amount'], 0, ',', '.'); ?> ₫
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($t['amount'] > 0): ?>
                                        <span class="badge income">Thu nhập</span>
                                    <?php else: ?>
                                        <span class="badge expense">Chi tiêu</span>
                                    <?php endif; ?>
                                </td>
                                <td class="table-actions" style="text-align: center;">
                                    <a href="#" class="btn btn-sm btn-secondary">Sửa</a>
                                    <a href="#" class="btn btn-sm btn-danger">Xoá</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center text-muted">Không có giao dịch để hiển thị.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const rangeFilter = document.getElementById('rangeFilter');
    const categoryFilter = document.getElementById('categoryFilter');

    function applyFilters() {
        const range = rangeFilter.value;
        const category = categoryFilter.value;
        const baseUrl = "<?php echo BASE_URL; ?>";
        
        window.location.href = `${baseUrl}/transactions/index/${range}/${category}`;
    }

    rangeFilter.addEventListener('change', applyFilters);
    categoryFilter.addEventListener('change', applyFilters);
});
</script>

<?php $this->partial('footer'); ?>
