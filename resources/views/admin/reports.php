<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/css/admin.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="admin-wrapper">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    <main class="main-content">
        <div class="top-bar">
            <div class="page-title">
                <h1>Báo cáo Hệ thống</h1>
            </div>
            <form class="d-flex gap-2 align-items-center bg-white p-2 rounded shadow-sm border">
                <input type="date" name="start" value="<?php echo $range['start']; ?>" class="form-control form-control-sm">
                <span>-</span>
                <input type="date" name="end" value="<?php echo $range['end']; ?>" class="form-control form-control-sm">
                <button type="submit" class="btn btn-sm btn-primary">Xem</button>
                <button type="submit" name="export" value="csv" class="btn btn-sm btn-success"><i class="fas fa-file-csv"></i></button>
            </form>
        </div>

        <div class="row g-4">
            <div class="col-12">
                <div class="card-box">
                    <h5 class="fw-bold mb-3">Xu hướng 12 tháng qua</h5>
                    <canvas id="trendChart" height="80"></canvas>
                </div>
            </div>

            <div class="col-md-12">
                <div class="card-box">
                    <h5 class="fw-bold mb-3">Top Danh mục Chi tiêu (Theo bộ lọc ngày)</h5>
                    <table class="table-pro">
                        <thead>
                            <tr>
                                <th>Danh mục</th>
                                <th class="text-end">Tổng chi</th>
                                <th>Thanh tỷ lệ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $maxTotal = !empty($category_breakdown) ? $category_breakdown[0]['total'] : 1;
                            foreach($category_breakdown as $cat): 
                                $percent = ($cat['total'] / $maxTotal) * 100;
                            ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($cat['name']); ?></td>
                                <td class="text-end text-danger fw-bold"><?php echo number_format($cat['total']); ?> đ</td>
                                <td style="width: 40%;">
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-danger" style="width: <?php echo $percent; ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    const ctx = document.getElementById('trendChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($trend['labels']); ?>,
            datasets: [
                {
                    label: 'Thu nhập',
                    data: <?php echo json_encode($trend['income']); ?>,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4, fill: true
                },
                {
                    label: 'Chi tiêu',
                    data: <?php echo json_encode($trend['expense']); ?>,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.4, fill: true
                }
            ]
        },
        options: { responsive: true }
    });
</script>
</body>
</html>