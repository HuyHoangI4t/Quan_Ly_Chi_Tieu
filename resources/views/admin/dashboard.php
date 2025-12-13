<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Dashboard | SmartAdmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="<?php echo BASE_URL; ?>/css/admin.css" rel="stylesheet">
</head>

<body>

    <div class="admin-wrapper">

        <?php require __DIR__ . '/partials/sidebar.php'; ?>
        <main class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <h1>Tổng quan hệ thống</h1>
                    <p>Số liệu cập nhật ngày <?php echo date('d/m/Y'); ?></p>
                </div>
                <button class="btn-primary"><i class="fas fa-download me-2"></i> Xuất báo cáo</button>
            </div>

            <div class="stat-grid">
                <div class="card-box stat-card">
                    <div class="stat-icon bg-indigo"><i class="fas fa-users"></i></div>
                    <div>
                        <div style="font-size: 1.5rem; font-weight: 700;"><?php echo $stats['total_users']; ?></div>
                        <div style="color: #64748b; font-size: 0.85rem;">Tổng thành viên</div>
                    </div>
                </div>
                <div class="card-box stat-card">
                    <div class="stat-icon bg-emerald"><i class="fas fa-user-check"></i></div>
                    <div>
                        <div style="font-size: 1.5rem; font-weight: 700;"><?php echo $stats['active_users']; ?></div>
                        <div style="color: #64748b; font-size: 0.85rem;">Đang hoạt động</div>
                    </div>
                </div>
                <div class="card-box stat-card">
                    <div class="stat-icon bg-amber"><i class="fas fa-exchange-alt"></i></div>
                    <div>
                        <div style="font-size: 1.5rem; font-weight: 700;"><?php echo number_format($stats['total_transactions']); ?></div>
                        <div style="color: #64748b; font-size: 0.85rem;">Giao dịch</div>
                    </div>
                </div>
                <div class="card-box stat-card">
                    <div class="stat-icon bg-rose"><i class="fas fa-tags"></i></div>
                    <div>
                        <div style="font-size: 1.5rem; font-weight: 700;"><?php echo $stats['total_categories']; ?></div>
                        <div style="color: #64748b; font-size: 0.85rem;">Danh mục</div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card-box h-100">
                        <h5 class="fw-bold mb-4">Biểu đồ dòng tiền (30 ngày)</h5>

                        <div style="position: relative; height: 350px; width: 100%;">
                            <canvas id="mainChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card-box h-100 p-0 overflow-hidden">
                        <div class="p-3 border-bottom bg-light">
                            <h6 class="fw-bold mb-0">Thành viên mới</h6>
                        </div>
                        <div>
                            <?php foreach ($stats['recent_users'] as $u): ?>
                                <div class="d-flex align-items-center p-3 border-bottom">
                                    <div class="avatar me-3"><?php echo strtoupper(substr($u['username'], 0, 1)); ?></div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold small"><?php echo htmlspecialchars($u['username']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($u['email']); ?></div>
                                    </div>
                                    <span class="badge bg-light text-dark border"><?php echo date('d/m', strtotime($u['created_at'])); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('mainChart').getContext('2d');

        
        const activityData = <?php echo json_encode($stats['system_activity'] ?? []); ?>;

        
        const labels = activityData.map(item => {
            const d = new Date(item.activity_date);
            return d.getDate() + '/' + (d.getMonth() + 1); 
        }).reverse(); 

        const incomeData = activityData.map(item => item.total_income).reverse();
        const expenseData = activityData.map(item => item.total_expense).reverse();

        new Chart(ctx, {
            type: 'bar', 
            data: {
                labels: labels.length ? labels : ['Không có dữ liệu'],
                datasets: [{
                        label: 'Thu nhập',
                        data: incomeData,
                        backgroundColor: '#4f46e5', 
                        borderRadius: 4,
                        barPercentage: 0.6
                    },
                    {
                        label: 'Chi tiêu',
                        data: expenseData,
                        backgroundColor: '#ef4444', 
                        borderRadius: 4,
                        barPercentage: 0.6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, 
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            borderDash: [2, 4],
                            color: '#f1f5f9'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>

</body>

</html>