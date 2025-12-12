<?php

use App\Middleware\CsrfProtection;

$this->partial('header');
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<?php echo CsrfProtection::getTokenMeta(); ?>

<main class="container budgets-page py-4">
    <div class="budgets-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <div class="header-icon-box">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <div>
                    <h2 class="page-title">Hệ thống JARS (6 Chiếc Hũ)</h2>
                    <p class="page-subtitle">Quản lý tài chính theo phương pháp T. Harv Eker</p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button id="editSmartRatiosBtn" class="btn btn-outline-primary bg-white shadow-sm" data-bs-toggle="modal" data-bs-target="#smartBudgetModal">
                    <i class="fas fa-sliders-h me-2"></i>Cấu hình Hũ
                </button>
                <button id="openCreateBudget" class="btn btn-primary custom-btn-add shadow-sm">
                    <i class="fas fa-plus me-2"></i>Thêm Khoản Chi
                </button>
            </div>
        </div>
    </div>

    <div id="jarsContainer" class="row g-3 mb-4">
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="text-muted mt-2">Đang tải dữ liệu 6 hũ...</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-12">
            <div class="card list-card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-4 pb-2 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-dark mb-0">Chi tiết các khoản chi</h5>
                    <div class="badge bg-light text-muted border fw-normal px-3 py-2 rounded-pill">
                        <i class="fas fa-filter me-1"></i> Tháng này
                    </div>
                </div>
                <div class="card-body px-0">
                    <div class="table-responsive">
                        <table id="budgetsTable" class="table table-hover align-middle custom-table mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4" style="width: 35%;">Danh mục</th>
                                    <th class="text-end" style="width: 20%;">Còn lại</th>
                                    <th style="width: 35%;">Tiến độ</th>
                                    <th class="text-end pe-4" style="width: 10%;"></th>
                                </tr>
                            </thead>
                            <tbody id="budgetsList"></tbody>
                        </table>
                    </div>
                    <div id="emptyState" class="text-center py-5" style="display:none;">
                        <div class="empty-icon-wrapper mb-3"><i class="fas fa-inbox"></i></div>
                        <h6 class="text-dark fw-bold">Chưa có dữ liệu</h6>
                        <p class="text-muted small">Hãy thêm ngân sách để bắt đầu theo dõi.</p>
                    </div>
                    <div id="budgetsPagination" class="d-flex justify-content-center my-3"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="charts-row mt-4" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        <div class="card chart-card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-4">Xu hướng chi tiêu</h6>
                <div class="chart-container"><canvas id="budgetTrend"></canvas></div>
            </div>
        </div>
        <div class="card chart-card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-4">Phân bổ theo danh mục</h6>
                <div class="chart-container"><canvas id="budgetPie"></canvas></div>
            </div>
        </div>
    </div>

    <?php $this->partial('modal_create_budget'); // load modal create budget from partials ?>

    <div class="modal fade" id="categoryChooserModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content rounded-4 border-0">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">Chọn danh mục</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="categoryList" class="list-group list-group-flush"></div>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="smartBudgetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-bold text-primary"><i class="fas fa-sliders-h me-2"></i>Cấu hình Tỷ lệ JARS</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-4">
                <p class="text-muted small mb-4">Phương pháp 6 chiếc hũ (JARS). Tổng tỷ lệ phải là 100%.</p>

                <?php
                $jars = [
                    ['id' => 'nec', 'label' => 'NEC - Thiết yếu', 'color' => 'danger', 'def' => 55],
                    ['id' => 'ffa', 'label' => 'FFA - Tự do TC', 'color' => 'warning', 'def' => 10],
                    ['id' => 'ltss', 'label' => 'LTSS - TK dài hạn', 'color' => 'primary', 'def' => 10],
                    ['id' => 'edu', 'label' => 'EDU - Giáo dục', 'color' => 'info', 'def' => 10],
                    ['id' => 'play', 'label' => 'PLAY - Hưởng thụ', 'color' => 'pink', 'def' => 10], // Custom color class needed or use simple bootstrap
                    ['id' => 'give', 'label' => 'GIVE - Cho đi', 'color' => 'success', 'def' => 5]
                ];
                foreach ($jars as $j): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <label class="fw-bold text-<?= $j['color'] ?> small"><?= $j['label'] ?></label>
                            <span id="<?= $j['id'] ?>Amount" class="fw-bold text-dark small">0 ₫</span>
                        </div>
                            <div class="d-flex align-items-center gap-2">
                                    <input type="range" class="form-range jar-input" id="<?= $j['id'] ?>Input" min="0" max="100" step="5" value="<?= $j['def'] ?>" data-key="<?= $j['id'] ?>">
                                    <?php
                                        $badgeClass = '';
                                        $badgeStyle = '';
                                        if ($j['color'] === 'pink') {
                                            $badgeStyle = 'background-color: #d63384; color: #fff;';
                                        } else {
                                            $badgeClass = 'bg-' . $j['color'];
                                        }
                                    ?>
                                    <span class="badge <?= $badgeClass ?>" style="width: 45px; <?= $badgeStyle ?>"><span id="<?= $j['id'] ?>Percent">0</span>%</span>
                                </div>
                    </div>
                <?php endforeach; ?>

                <div class="alert alert-light border d-flex justify-content-between align-items-center py-2" role="alert">
                    <span class="small"><i class="fas fa-info-circle me-1"></i>Tổng tỷ lệ:</span>
                    <span id="totalPercent" class="fw-bold text-success">100%</span>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4">
                <button type="button" class="btn btn-light" id="resetRatiosBtn">Khôi phục mặc định</button>
                <button type="button" class="btn btn-primary px-4" id="saveRatiosBtn">Lưu Cấu Hình</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.BASE_URL = "<?php echo BASE_URL; ?>";

    function formatInputMoney(input) {
        let value = input.value.replace(/\D/g, '');
        document.getElementById('budget_amount').value = value;
        input.value = new Intl.NumberFormat('vi-VN').format(value);
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?php echo BASE_URL; ?>/js/smart-budget.js"></script>

<?php $this->partial('footer'); ?>