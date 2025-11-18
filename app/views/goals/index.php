<?php $this->partial('header'); ?>

<!-- Goals Specific Styles -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/goals.css">

<section>
    <h3 class="mb-3">Mục tiêu</h3>

    <div class="card p-3">
        <div class="row">
            <?php if (!empty($goals)): ?>
                <?php foreach ($goals as $g): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card p-3">
                            <h6><?php echo $this->escape($g['title']); ?></h6>
                            <p class="text-muted">Mục tiêu <?php echo '₫ ' . number_format($g['target'],0,',','.'); ?> | Đã tiết kiệm <?php echo '₫ ' . number_format($g['saved'],0,',','.'); ?></p>
                            <div class="progress-small mt-3"><span class="fill" style="width:<?php echo $g['progress']; ?>%"></span></div>
                            <small class="text-muted"><?php echo $g['progress']; ?>% hoàn thành</small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 center-muted">Không có mục tiêu nào.</div>
            <?php endif; ?>

            <div class="col-md-8">
                <div class="chart-container">
                    <h6>Goal Progress</h6>
                    <canvas id="goalsBar" style="height:160px;"></canvas>
                </div>
            </div>
        </div>
    </div>
</section>

<?php $this->partial('footer'); ?>
