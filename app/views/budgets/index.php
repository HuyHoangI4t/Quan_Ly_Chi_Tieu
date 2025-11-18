<?php $this->partial('header'); ?>

<!-- Budgets Specific Styles -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/budgets.css">

<section>
    <h3 class="mb-3">Ngân sách</h3>

    <div class="card p-3">
        <div class="row">
            <div class="col-md-4">
                <div class="stats-card card p-3">
                    <div class="card-title-small">Tổng ngân sách</div>
                    <div class="card-value-large">₫ 60.000</div>
                    <small class="text-muted">Cho Tháng 10/2025</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="chart-container">
                    <h6>Chi tiêu theo danh mục</h6>
                    <canvas id="budgetPie" style="height:140px;"></canvas>
                </div>
            </div>
        </div>

        <div class="card p-3 mt-3">
            <h6>Bảng ngân sách</h6>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Danh mục</th>
                            <th>Giới hạn</th>
                            <th>Đã chi</th>
                            <th>Còn lại</th>
                            <th>Tiến độ</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($budgets)): ?>
                            <?php foreach ($budgets as $b): ?>
                                <tr>
                                    <td><?php echo $this->escape($b['category']); ?></td>
                                    <td><?php echo '₫ ' . number_format($b['limit'], 0, ',', '.'); ?></td>
                                    <td><?php echo '₫ ' . number_format($b['spent'], 0, ',', '.'); ?></td>
                                    <td><?php echo '₫ ' . number_format($b['remaining'], 0, ',', '.'); ?></td>
                                    <td style="width:240px;">
                                        <div class="progress-small">
                                            <span class="fill" style="width:<?php echo $b['progress']; ?>%"></span>
                                        </div>
                                        <small class="text-muted"><?php echo $b['progress']; ?>%</small>
                                    </td>
                                    <td><a class="btn btn-sm btn-outline-primary">Sửa</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="center-muted">Không có ngân sách.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<?php $this->partial('footer'); ?>
