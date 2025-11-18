<?php $this->partial('header'); ?>

<!-- Profile Specific Styles -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/profile.css">

<section>
    <div class="col-12 mb-3">
        <h3>Hồ sơ của tôi</h3>
    </div>

    <div class="card p-3">
        <div class="row">
            <div class="col-md-4">
                <div class="card p-4">
                    <div class="profile-box">
                        <div class="avatar"></div>
                        <div class="user-info">
                            <h5><?php echo $this->escape($user['name'] ?? 'Người dùng'); ?></h5>
                            <small><?php echo $this->escape($user['email'] ?? 'user@example.com'); ?></small>
                        </div>
                    </div>

                    <div class="actions-inline">
                        <a href="/profile/edit" class="btn btn-success">Chỉnh sửa hồ sơ</a>
                        <a href="/profile/change_password" class="btn btn-outline-primary">Đổi mật khẩu</a>
                    </div>
                </div>
            </div>

            <div class="col-md-7 offset-md-1">
                <div class="card p-4 settings-box">
                    <h5 class="mb-3">Cài đặt tài khoản</h5>

                    <div class="row">
                        <div class="col-6">
                            <p><strong>Đơn vị tiền tệ:</strong> INR (₹)</p>
                            <p><strong>Ngôn ngữ:</strong> Tiếng Việt</p>
                            <p><strong>Bắt đầu tháng:</strong> 1st of every month</p>
                        </div>
                        <div class="col-6">
                            <p><strong>Thông báo:</strong></p>
                            <ul>
                                <li>Thông báo giới hạn ngân sách</li>
                                <li>Nhắc nhở mục tiêu</li>
                                <li>Email tóm tắt hàng tuần</li>
                            </ul>
                            <div class="actions-inline">
                                <a href="/profile/export" class="btn btn-success">Xuất dữ liệu</a>
                                <a href="/profile/clear" class="btn btn-danger">Xoá tất cả dữ liệu</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php $this->partial('footer'); ?>
