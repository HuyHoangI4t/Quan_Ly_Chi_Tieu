<?php $this->partial('header'); ?>

<!-- Reports Specific Styles -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/reports.css">

<section>
    <h3 class="mb-3">Báo cáo</h3>

    <div class="card p-3">
        <div class="row">
            <div class="col-md-8">
                <div class="chart-container">
                    <h6>Thu Nhập và Chi Tiêu theo Thời Gian</h6>
                    <canvas id="reportsLine" style="height:200px;"></canvas>
                </div>
            </div>
            <div class="col-md-4">
                <div class="chart-container">
                    <h6>Phân Bổ Theo Danh Mục</h6>
                    <canvas id="reportsPie" style="height:200px;"></canvas>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Move page JS into a registered script so it runs after footer (which loads Chart.js)
$lineJson = json_encode($reportLine ?? []);
$pieJson = json_encode($reportPie ?? []);

$script = "<script>\n" .
    "document.addEventListener('DOMContentLoaded', function(){\n" .
    "    const reportLine = $lineJson;\n" .
    "    const reportPie = $pieJson;\n" .
    "    if (reportLine && reportLine.labels) {\n" .
    "        const ctx = document.getElementById('reportsLine').getContext('2d');\n" .
    "        new Chart(ctx, { type: 'line', data: { labels: reportLine.labels, datasets: [ { label: 'Thu nhập', data: reportLine.income, borderColor: '#00b083', backgroundColor: 'transparent', tension: 0.4 }, { label: 'Chi tiêu', data: reportLine.expense, borderColor: '#ff6b6b', backgroundColor: 'transparent', tension: 0.4 } ] }, options: { responsive:true, maintainAspectRatio:false } });\n" .
    "    }\n" .
    "    if (reportPie && reportPie.labels) {\n" .
    "        const ctx2 = document.getElementById('reportsPie').getContext('2d');\n" .
    "        new Chart(ctx2, { type: 'pie', data: { labels: reportPie.labels, datasets:[{ data: reportPie.data, backgroundColor:['#2ecc71','#3498db','#f1c40f','#ff6b6b','#95a5a6'] }] }, options: { responsive:true, maintainAspectRatio:false } });\n" .
    "    }\n" .
    "});\n" .
    "<\/script>";

$this->set('pageScripts', $script);

$this->partial('footer');
?>
