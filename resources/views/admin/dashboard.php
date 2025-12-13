<?php $this->partial('admin_header', ['title' => $title ?? 'Admin Dashboard']); ?>
<style>
        .stat-card.active {
            border-color: var(--warning);
        }

        .quick-action {
            transition: all 0.3s;
        }

        .quick-action:hover {
            background-color: #f8f9fa;
            transform: scale(1.02);
        }
    </style>
</head>

<body>
    <div class="admin-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
                    <p class="mb-0">Xin chào, <?php echo $_SESSION['full_name'] ?? 'Admin'; ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card users">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-0"><?php echo $stats['total_users']; ?></h3>
                                <small class="text-muted">Tổng Users</small>
                            </div>
                            <div class="text-primary" style="font-size: 2.5rem;">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card active">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-0"><?php echo $stats['active_users']; ?></h3>
                                <small class="text-muted">Users Hoạt động</small>
                            </div>
                            <div class="text-warning" style="font-size: 2.5rem;">
                                <i class="fas fa-user-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card transactions">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-0"><?php echo number_format($stats['total_transactions']); ?></h3>
                                <small class="text-muted">Giao dịch</small>
                            </div>
                            <div class="text-success" style="font-size: 2.5rem;">
                                <i class="fas fa-exchange-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card categories">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-0"><?php echo $stats['total_categories']; ?></h3>
                                <small class="text-muted">Danh mục</small>
                            </div>
                            <div class="text-danger" style="font-size: 2.5rem;">
                                <i class="fas fa-tags"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Quick Actions -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    
                    <div class="card-body p-0">
                        <a href="<?php echo BASE_URL; ?>/admin/users" class="d-block p-3 text-decoration-none text-dark quick-action border-bottom">
                            <i class="fas fa-users-cog"></i> Quản lý Users
                        </a>
                        <a href="<?php echo BASE_URL; ?>/admin/categories" class="d-block p-3 text-decoration-none text-dark quick-action border-bottom">
                            <i class="fas fa-tags"></i> Quản lý Danh mục Gốc
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="col-md-8 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-user-plus"></i> Users đăng ký gần đây</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Vai trò</th>
                                        <th>Ngày tạo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($stats['recent_users'])): ?>
                                        <?php foreach ($stats['recent_users'] as $user): ?>
                                            <tr>
                                                <td><?php echo $user['id']; ?></td>
                                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $user['role'] === 'admin' ? 'bg-danger' : 'bg-secondary'; ?>">
                                                        <?php echo ucfirst($user['role']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">Chưa có users nào</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Activity -->
        <?php if (!empty($stats['system_activity'])): ?>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Hoạt động hệ thống (7 ngày gần nhất)</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Ngày</th>
                                            <th>Số giao dịch</th>
                                            <th>Thu nhập</th>
                                            <th>Chi tiêu</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($stats['system_activity'] as $activity): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y', strtotime($activity['activity_date'])); ?></td>
                                                <td><?php echo $activity['transaction_count']; ?></td>
                                                <td class="text-success">+<?php echo number_format($activity['total_income'], 0, ',', '.'); ?>đ</td>
                                                <td class="text-danger">-<?php echo number_format($activity['total_expense'], 0, ',', '.'); ?>đ</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

            <!-- Charts -->
            <div class="row mt-4">
                <div class="col-md-8 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-chart-line"></i> Thu/Chi theo tháng (12 tháng)</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="monthlyChart" height="120"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Phân bố theo danh mục (30 ngày)</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="categoryChart" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                (function(){
                    const chartData = <?php echo json_encode($chart ?? ['labels'=>[],'income'=>[],'expense'=>[]]); ?>;
                    const catData = <?php echo json_encode($category_breakdown ?? ['labels'=>[], 'data'=>[]]); ?>;

                    const ctx = document.getElementById('monthlyChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: chartData.labels,
                            datasets: [
                                { label: 'Thu nhập', data: chartData.income, borderColor: 'var(--success)', backgroundColor: 'rgba(39,174,96,0.08)', tension:0.2 },
                                { label: 'Chi tiêu', data: chartData.expense, borderColor: 'var(--danger)', backgroundColor: 'rgba(231,76,60,0.08)', tension:0.2 }
                            ]
                        },
                        options: { responsive: true, maintainAspectRatio: false }
                    });

                    const cctx = document.getElementById('categoryChart').getContext('2d');
                    new Chart(cctx, {
                        type: 'pie',
                        data: {
                            labels: catData.labels,
                            datasets: [{ data: catData.data, backgroundColor: ['#10b981','#6366f1','#f59e0b','#ef4444','#3b82f6','#f97316'] }]
                        },
                        options: { responsive: true, maintainAspectRatio: false }
                    });
                })();
            </script>
    </div>

<?php $this->partial('admin_footer'); ?>