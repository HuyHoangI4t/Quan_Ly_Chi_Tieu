<?php $this->partial('header', ['title' => 'SmartSpending - Quản Lý Tài Chính']); ?>

<!-- Dashboard Specific Styles -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/dashboard.css">

<section>
    <h3 class="mb-3">Tổng quan</h3>

    <div class="card p-3">
        <div class="stats-grid">
            <div class="card stat-card">
                <h3>Tổng Số Dư</h3>
                <div class="value"><?php echo number_format($totals['balance'] ?? 0, 0, ',', '.'); ?> ₫</div>
                <div class="trend up"><i class="fas fa-arrow-trend-up"></i> +<?php echo number_format((($totals['income'] ?? 0)-($totals['expense'] ?? 0)), 0, ',', '.'); ?> ₫ tháng này</div>
            </div>
            <div class="card stat-card">
                <h3>Tổng Thu Nhập</h3>
                <div class="value"><?php echo number_format($totals['income'] ?? 0, 0, ',', '.'); ?> ₫</div>
                <div class="trend up"><i class="fas fa-arrow-trend-up"></i> +12% so với tháng trước</div>
            </div>
            <div class="card stat-card">
                <h3>Tổng Chi Tiêu</h3>
                <div class="value"><?php echo number_format($totals['expense'] ?? 0, 0, ',', '.'); ?> ₫</div>
                <div class="trend down"><i class="fas fa-arrow-trend-down"></i> -8% so với tháng trước</div>
            </div>
            <div class="card stat-card">
                <h3>Tỷ Lệ Tiết Kiệm</h3>
                <div class="value"><?php echo ($totals['savingsRate'] ?? 0); ?>%</div>
                <div class="trend up"><i class="fas fa-arrow-trend-up"></i> +3% cải thiện</div>
            </div>
        </div>

        <div class="section-header">
            <h2>Tổng Quan Chi Tiêu</h2>
            <div class="dropdown-container">
                <span class="dropdown-text">Tháng này <i class="fas fa-caret-down"></i></span>
            </div>
        </div>

        <div class="charts-grid">
            <div class="card chart-card">
                <div class="chart-header">
                    <h3>Thu Nhập vs Chi Tiêu</h3>
                    <span class="subtitle">Xu hướng hàng tháng</span>
                </div>
                <div class="chart-area">
                    <canvas id="lineChart"></canvas>
                </div>
            </div>

            <div class="card chart-card">
                <div class="chart-header">
                    <h3>Phân Bổ Chi Tiêu</h3>
                    <span class="subtitle">Danh mục chi tiêu tháng này</span>
                </div>
                <div class="pie-area">
                    <canvas id="pieChart"></canvas>
                </div>
            </div>
        </div>

        <div class="transactions-section">
            <div class="card table-card">
                <div class="card-header">
                    <h3>Giao Dịch Gần Đây</h3>
                    <a href="#" class="view-all">Xem tất cả</a>
                </div>
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th width="35%">Giao dịch</th>
                            <th width="25%">Danh mục</th>
                            <th width="20%">Ngày</th>
                            <th width="20%" style="text-align: right;">Số tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentTransactions as $tx): ?>
                            <tr>
                                <td><?php echo $this->escape($tx['title']); ?></td>
                                <td><?php echo $this->escape($tx['category']); ?></td>
                                <td><?php echo $this->escape($tx['date']); ?></td>
                                <td class="amount" style="text-align:right;">
                                    <?php if ($tx['amount'] < 0): ?>
                                        <span class="text-dark">- <?php echo number_format(abs($tx['amount']), 0, ',', '.'); ?> ₫</span>
                                    <?php else: ?>
                                        <span class="text-green">+ <?php echo number_format($tx['amount'], 0, ',', '.'); ?> ₫</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- Dashboard Specific Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?php echo BASE_URL; ?>/public/js/dashboard.js"></script>

<?php $this->partial('footer'); ?>
