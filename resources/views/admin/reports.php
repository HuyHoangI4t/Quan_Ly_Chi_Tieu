<?php $this->partial('admin_header', ['title' => $title ?? 'Báo cáo']); ?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Báo cáo chi tiêu</h3>
        <div>
            <a class="btn btn-outline-secondary" href="?export=csv&start=<?php echo urlencode($range['start']); ?>&end=<?php echo urlencode($range['end']); ?>">Export CSV</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form class="row g-2" method="get">
                <div class="col-md-3"><input type="date" name="start" value="<?php echo htmlspecialchars($range['start']); ?>" class="form-control"></div>
                <div class="col-md-3"><input type="date" name="end" value="<?php echo htmlspecialchars($range['end']); ?>" class="form-control"></div>
                <div class="col-md-2"><button class="btn btn-primary">Áp dụng</button></div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">Phân bố theo danh mục</div>
                <div class="card-body"><canvas id="reportCategoryChart" height="240"></canvas></div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">Xu hướng thu/chi (12 tháng)</div>
                <div class="card-body"><canvas id="reportTrendChart" height="240"></canvas></div>
            </div>
        </div>
    </div>

    <div class="card table-card">
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead><tr><th>Danh mục</th><th class="text-end">Tổng chi</th></tr></thead>
                <tbody>
                    <?php if (!empty($category_breakdown)): foreach ($category_breakdown as $c): ?>
                        <tr><td><?php echo htmlspecialchars($c['name']); ?></td><td class="text-end"><?php echo number_format($c['total'],0,',','.'); ?>đ</td></tr>
                    <?php endforeach; else: ?><tr><td colspan="2" class="text-center">Không có dữ liệu</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php $this->partial('admin_footer'); ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function(){
        const catRows = <?php echo json_encode($category_breakdown); ?>;
        const labels = catRows.map(r => r.name);
        const data = catRows.map(r => parseFloat(r.total));

        const ctx = document.getElementById('reportCategoryChart').getContext('2d');
        new Chart(ctx, { type: 'doughnut', data: { labels, datasets: [{ data, backgroundColor: ['#34d399','#6366f1','#f59e0b','#ef4444','#3b82f6','#f97316'] }] }, options: { responsive:true } });

        const trend = <?php echo json_encode($trend); ?>;
        const tctx = document.getElementById('reportTrendChart').getContext('2d');
        new Chart(tctx, { type: 'line', data: { labels: trend.labels, datasets: [{label: 'Thu', data: trend.income, borderColor: 'var(--success)', backgroundColor: 'rgba(16,185,129,0.06)'},{label:'Chi', data: trend.expense, borderColor: 'var(--danger)', backgroundColor:'rgba(239,68,68,0.06)'}] }, options: { responsive:true } });
    })();
</script>
