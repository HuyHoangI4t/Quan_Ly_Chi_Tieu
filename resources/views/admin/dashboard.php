<!DOCTYPE html>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="<?php echo BASE_URL; ?>/css/admin.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="admin-wrapper">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    <main class="main-content">
        <div class="top-bar">
            <div class="page-title">
                <h1>Tổng quan</h1>
                <p>Số liệu hoạt động toàn hệ thống</p>
            </div>
            <div class="date-display text-muted fw-bold small">
                <i class="far fa-calendar-alt me-2"></i><?php echo date('d/m/Y'); ?>
            </div>
        </div>

        <div class="row g-3 mb-4 stats-row">
            <div class="col-md-3">
                <div class="card-box stat-card mb-0">
                    <div class="stat-icon bg-primary-subtle text-primary"><i class="fas fa-users"></i></div>
                    <div>
                        <div class="text-muted small fw-bold">Tổng User</div>
                        <div class="fs-4 fw-bold"><?php echo number_format($stats['total_users']); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-box stat-card mb-0">
                    <div class="stat-icon bg-success-subtle text-success"><i class="fas fa-user-check"></i></div>
                    <div>
                        <div class="text-muted small fw-bold">User Active</div>
                        <div class="fs-4 fw-bold"><?php echo number_format($stats['active_users']); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-box stat-card mb-0">
                    <div class="stat-icon bg-warning-subtle text-warning"><i class="fas fa-exchange-alt"></i></div>
                    <div>
                        <div class="text-muted small fw-bold">Giao dịch</div>
                        <div class="fs-4 fw-bold"><?php echo number_format($stats['total_transactions']); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-box stat-card mb-0">
                    <div class="stat-icon bg-danger-subtle text-danger"><i class="fas fa-folder"></i></div>
                    <div>
                        <div class="text-muted small fw-bold">Danh mục</div>
                        <div class="fs-4 fw-bold"><?php echo number_format($stats['total_categories']); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-8">
                <div class="card-box h-100">
                    <h5 class="fw-bold mb-4">Dòng tiền hệ thống (12 tháng)</h5>
                    <div class="chart-container">
                        <canvas id="mainChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-box h-100">
                    <div class="users-header">
                        <h5 class="fw-bold mb-0">Người dùng mới</h5>
                        <a href="<?php echo BASE_URL; ?>/admin/users" class="btn btn-light small users-view-all">Xem tất cả</a>
                    </div>
                    <div class="recent-users-row">
                        <?php foreach($stats['recent_users'] as $u): ?>
                        <div class="user-card">
                            <div class="user-top">
                                <div class="avatar user-avatar bg-light text-primary"><?php echo strtoupper(substr($u['username'],0,1)); ?></div>
                                <span class="user-date small text-muted"><?php echo date('d/m', strtotime($u['created_at'])); ?></span>
                            </div>
                            <div class="user-body">
                                <div class="user-name fw-bold text-dark" title="<?php echo htmlspecialchars($u['username']); ?>"><?php echo htmlspecialchars($u['username']); ?></div>
                                <div class="user-email small text-muted" title="<?php echo htmlspecialchars($u['email']); ?>"><?php echo htmlspecialchars($u['email']); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                   
                </div>
            </div>
        </div>
    </main>
</div>
<script>
    const ctx = document.getElementById('mainChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chart['labels']); ?>,
            datasets: [
                { label: 'Thu nhập', data: <?php echo json_encode($chart['income']); ?>, backgroundColor: '#3b82f6', borderRadius: 4 },
                { label: 'Chi tiêu', data: <?php echo json_encode($chart['expense']); ?>, backgroundColor: '#ef4444', borderRadius: 4 }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true, grid: { borderDash: [2, 4] } }, x: { grid: { display: false } } } }
    });
</script>
</body>
</html>
</html>