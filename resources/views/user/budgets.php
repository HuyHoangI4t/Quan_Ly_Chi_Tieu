<?php
use App\Middleware\CsrfProtection;
$this->partial('header');
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/budgets.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

<style>
    .jar-card {
        transition: all 0.3s ease;
        border: 1px solid rgba(0,0,0,0.05);
        border-radius: 16px;
        background: #fff;
        overflow: hidden;
    }
    .jar-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 24px rgba(0,0,0,0.08) !important;
    }
    .jar-icon-box {
        width: 48px; height: 48px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 12px;
        font-size: 1.25rem;
    }
    /* Màu sắc thương hiệu cho từng hũ */
    .bg-nec-subtle { background-color: #fee2e2; color: #dc3545; }
    .bg-ffa-subtle { background-color: #fef3c7; color: #d97706; }
    .bg-ltss-subtle { background-color: #dbeafe; color: #0d6efd; }
    .bg-edu-subtle { background-color: #cffafe; color: #0891b2; }
    .bg-play-subtle { background-color: #fce7f3; color: #db2777; }
    .bg-give-subtle { background-color: #dcfce7; color: #16a34a; }
    
    .bg-nec { background-color: #dc3545 !important; }
    .bg-ffa { background-color: #f59e0b !important; }
    .bg-ltss { background-color: #0d6efd !important; }
    .bg-edu { background-color: #06b6d4 !important; }
    .bg-play { background-color: #d63384 !important; }
    .bg-give { background-color: #16a34a !important; }
</style>

<?php echo CsrfProtection::getTokenMeta(); ?>

<main class="container budgets-page py-4">
    <div class="budgets-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <div class="header-icon-box" style="background: white; padding: 12px; border-radius: 50%; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                    <i class="fas fa-chart-pie fa-lg text-primary"></i>
                </div>
                <div>
                    <h2 class="page-title mb-0 fw-bold">Hệ thống JARS</h2>
                    <p class="page-subtitle text-muted mb-0 small">Quản lý tài chính theo phương pháp 6 chiếc hũ</p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button id="editSmartRatiosBtn" class="btn btn-outline-primary bg-white shadow-sm fw-medium" data-bs-toggle="modal" data-bs-target="#smartBudgetModal">
                    <i class="fas fa-sliders-h me-2"></i>Cấu hình
                </button>
                <button id="openCreateBudget" class="btn btn-primary shadow-sm fw-medium px-3">
                    <i class="fas fa-plus me-2"></i>Thêm Ngân Sách
                </button>
            </div>
        </div>
    </div>

    <div id="jarsContainer" class="row g-3 mb-4">
        <?php
        $jarConfig = [
            'nec'  => ['name' => 'Thiết yếu (NEC)',  'style' => 'nec',  'icon' => 'fa-utensils'],
            'ffa'  => ['name' => 'Tự do TC (FFA)',   'style' => 'ffa',  'icon' => 'fa-chart-line'],
            'ltss' => ['name' => 'Tiết kiệm dài hạn', 'style' => 'ltss', 'icon' => 'fa-piggy-bank'],
            'edu'  => ['name' => 'Giáo dục (EDU)',   'style' => 'edu',  'icon' => 'fa-graduation-cap'],
            'play' => ['name' => 'Hưởng thụ (PLAY)', 'style' => 'play', 'icon' => 'fa-gamepad'],
            'give' => ['name' => 'Cho đi (GIVE)',    'style' => 'give', 'icon' => 'fa-hand-holding-heart']
        ];

        // Biến $wallets được truyền từ Controller
        $walletsData = $wallets ?? []; 
        $settingsData = $settings ?? [];

        if (!empty($walletsData)): 
            foreach ($walletsData as $wallet):
                $code = $wallet['jar_code'];
                $conf = $jarConfig[$code] ?? ['name' => strtoupper($code), 'style' => 'secondary', 'icon' => 'fa-wallet'];
                $balance = $wallet['balance'];
                $percent = $settingsData[$code . '_percent'] ?? 0;
        ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card jar-card h-100 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="jar-icon-box bg-<?= $conf['style'] ?>-subtle">
                                <i class="fas <?= $conf['icon'] ?>"></i>
                            </div>
                            <span class="badge bg-light text-dark border rounded-pill px-3 py-2 fw-normal">
                                Tỷ lệ: <b><?= $percent ?>%</b>
                            </span>
                        </div>
                        
                        <h6 class="text-muted text-uppercase fw-bold small mb-1"><?= $conf['name'] ?></h6>
                        <h3 class="fw-bold mb-3 text-dark"><?= number_format($balance, 0, ',', '.') ?> <small class="text-muted fs-6">₫</small></h3>
                        
                        <div class="progress" style="height: 6px; border-radius: 3px; background-color: #f1f5f9;">
                            <div class="progress-bar bg-<?= $conf['style'] ?>" role="progressbar" 
                                 style="width: <?= $percent ?>%" 
                                 aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; else: ?>
            <div class="col-12 text-center py-5">
                <p class="text-muted mb-0">Chưa có dữ liệu ví. Hãy thực hiện giao dịch thu nhập để khởi tạo.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="row g-4">
        <div class="col-lg-12">
            <div class="card list-card border-0 shadow-sm h-100 rounded-4">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-dark mb-0">Chi tiết Ngân sách chi tiêu</h5>
                    <div class="d-flex align-items-center">
                        <select class="form-select form-select-sm w-auto border-0 bg-light fw-bold" id="periodFilter">
                             <option value="monthly">Tháng này</option>
                             <option value="weekly">Tuần này</option>
                             <option value="yearly">Năm nay</option>
                        </select>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="budgetsTable" class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small text-uppercase">
                                <tr>
                                    <th class="ps-4 py-3 fw-bold" style="width: 35%;">Danh mục</th>
                                    <th class="text-end py-3 fw-bold" style="width: 20%;">Đã chi / Hạn mức</th>
                                    <th class="py-3 fw-bold ps-4" style="width: 35%;">Tiến độ</th>
                                    <th class="text-end py-3 pe-4" style="width: 10%;"></th>
                                </tr>
                            </thead>
                            <tbody id="budgetsList"></tbody>
                        </table>
                    </div>
                    <div id="emptyState" class="text-center py-5" style="display:none;">
                        <h6 class="text-dark fw-bold">Chưa có ngân sách nào</h6>
                    </div>
                    <div id="budgetsPagination" class="d-flex justify-content-center my-3"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="charts-row mt-4" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        <div class="card chart-card border-0 shadow-sm mb-4 rounded-4">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-4">Xu hướng chi tiêu</h6>
                <div class="chart-container"><canvas id="budgetTrend"></canvas></div>
            </div>
        </div>
        <div class="card chart-card border-0 shadow-sm mb-4 rounded-4">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-4">Phân bổ danh mục</h6>
                <div class="chart-container"><canvas id="budgetPie"></canvas></div>
            </div>
        </div>
    </div>

    <?php $this->partial('modal_create_budget'); ?>

    <div class="modal fade" id="smartBudgetModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 pb-0 px-4 pt-4">
                    <h5 class="modal-title fw-bold text-primary"><i class="fas fa-sliders-h me-2"></i>Cấu hình Tỷ lệ JARS</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 py-4">
                    <p class="text-muted small mb-4">Điều chỉnh tỷ lệ phân bổ thu nhập (Tổng phải là 100%).</p>
                    <?php
                    // Render input range từ settings PHP (Để hiển thị đúng giá trị hiện tại)
                    $jarInputs = [
                        ['id'=>'nec', 'label'=>'NEC - Thiết yếu', 'color'=>'danger', 'def'=>55],
                        ['id'=>'ffa', 'label'=>'FFA - Tự do TC', 'color'=>'warning', 'def'=>10],
                        ['id'=>'ltss', 'label'=>'LTSS - TK dài hạn', 'color'=>'primary', 'def'=>10],
                        ['id'=>'edu', 'label'=>'EDU - Giáo dục', 'color'=>'info', 'def'=>10],
                        ['id'=>'play', 'label'=>'PLAY - Hưởng thụ', 'color'=>'pink', 'def'=>10],
                        ['id'=>'give', 'label'=>'GIVE - Cho đi', 'color'=>'success', 'def'=>5]
                    ];
                    foreach($jarInputs as $j): 
                        $val = $settings[$j['id'].'_percent'] ?? $j['def'];
                        $badgeStyle = ($j['color'] === 'pink') ? 'background-color: #d63384; color: #fff;' : '';
                        $badgeClass = ($j['color'] !== 'pink') ? 'bg-' . $j['color'] : '';
                    ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <label class="fw-bold text-dark small"><?= $j['label'] ?></label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="range" class="form-range jar-input" id="<?= $j['id'] ?>Input" 
                                       min="0" max="100" step="1" value="<?= $val ?>" data-key="<?= $j['id'] ?>">
                                <span class="badge <?= $badgeClass ?>" style="width: 50px; <?= $badgeStyle ?>">
                                    <span id="<?= $j['id'] ?>Percent"><?= $val ?></span>%
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="alert alert-light border d-flex justify-content-between align-items-center py-2 mt-4" role="alert">
                        <span class="small fw-bold">Tổng tỷ lệ:</span>
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
</main>

<script>
    window.BASE_URL = "<?php echo BASE_URL; ?>";
    function formatInputMoney(input) {
        let value = input.value.replace(/\D/g, '');
        input.value = new Intl.NumberFormat('vi-VN').format(value);
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?php echo BASE_URL; ?>/js/budgets.js"></script>
<script src="<?php echo BASE_URL; ?>/js/smart-budget.js"></script>

<?php $this->partial('footer'); ?>