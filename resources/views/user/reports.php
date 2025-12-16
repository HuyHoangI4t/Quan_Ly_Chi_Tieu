<?php $this->partial('header'); ?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/reports.css">

<section class="reports-section">
    <!-- Header with Filters -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">
            <i class="fas fa-chart-line me-2"></i>
            Báo cáo Chi tiêu
        </h3>
        <button id="exportReport" class="btn btn-primary">
            <i class="fas fa-download me-2"></i>
            Xuất báo cáo
        </button>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="periodFilter" class="form-label">
                        <i class="fas fa-calendar me-1"></i>
                        Kỳ báo cáo
                    </label>
                    <select id="periodFilter" class="form-select">
                        <option value="this_month" <?php echo ($current_period ?? 'last_3_months') === 'this_month' ? 'selected' : ''; ?>>
                            Tháng này
                        </option>
                        <option value="last_3_months" <?php echo ($current_period ?? 'last_3_months') === 'last_3_months' ? 'selected' : ''; ?>>
                            3 tháng gần đây
                        </option>
                        <option value="last_6_months" <?php echo ($current_period ?? 'last_3_months') === 'last_6_months' ? 'selected' : ''; ?>>
                            6 tháng gần đây
                        </option>
                        <option value="this_year" <?php echo ($current_period ?? 'last_3_months') === 'this_year' ? 'selected' : ''; ?>>
                            Năm nay
                        </option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="typeFilter" class="form-label">
                        <i class="fas fa-filter me-1"></i>
                        Loại giao dịch
                    </label>
                    <select id="typeFilter" class="form-select">
                        <option value="expense" <?php echo ($current_type ?? 'expense') === 'expense' ? 'selected' : ''; ?>>
                            Chi tiêu
                        </option>
                        <option value="income" <?php echo ($current_type ?? 'expense') === 'income' ? 'selected' : ''; ?>>
                            Thu nhập
                        </option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="text-muted small">
                        <i class="fas fa-info-circle me-1"></i>
                        Biểu đồ sẽ cập nhật tự động
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Summary Stats (Optional) -->
    <div class="row g-4 mt-2">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="text-muted mb-2">
                        <i class="fas fa-wallet"></i> Tổng thu nhập
                    </div>
                    <h4 class="text-success mb-0" id="totalIncome">-</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="text-muted mb-2">
                        <i class="fas fa-shopping-cart"></i> Tổng chi tiêu
                    </div>
                    <h4 class="text-danger mb-0" id="totalExpense">-</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="text-muted mb-2">
                        <i class="fas fa-balance-scale"></i> Chênh lệch
                    </div>
                    <h4 class="mb-0" id="balance">-</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="text-muted mb-2">
                        <i class="fas fa-piggy-bank"></i> Tỷ lệ tiết kiệm
                    </div>
                    <h4 class="text-info mb-0" id="savingsRate">-</h4>
                </div>
            </div>
        </div>
    </div>
    <!-- Filters Card -->

    <!-- Charts Row -->
    <div class="row g-4">
        <!-- Line Chart -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header bg-transparent border-0">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2 text-primary"></i>
                        Thu nhập và Chi tiêu theo Thời gian
                    </h5>
                </div>
                <div class="card-body">
                    <div style="height: 350px; position: relative;">
                        <canvas id="lineChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pie Chart -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header bg-transparent border-0">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2 text-success"></i>
                        Phân bổ theo Danh mục
                    </h5>
                </div>
                <div class="card-body">
                    <div style="height: 350px; position: relative;">
                        <canvas id="pieChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

</section>

<?php $this->partial('footer'); ?>

<script src="<?php echo BASE_URL; ?>/js/reports.js"></script>