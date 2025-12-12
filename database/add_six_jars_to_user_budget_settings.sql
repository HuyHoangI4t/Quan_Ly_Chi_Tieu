-- 1. Cập nhật dữ liệu cũ sang nhóm mới tương ứng (để tránh lỗi khi đổi kiểu ENUM)
-- Map: needs -> nec, wants -> play, savings -> ffa (hoặc ltss tùy bạn chọn)
UPDATE `categories` SET `group_type` = 'nec' WHERE `group_type` = 'needs';
UPDATE `categories` SET `group_type` = 'play' WHERE `group_type` = 'wants';
UPDATE `categories` SET `group_type` = 'ffa' WHERE `group_type` = 'savings';

-- 2. Sửa cột group_type trong bảng categories
ALTER TABLE `categories` 
MODIFY COLUMN `group_type` ENUM('nec', 'ffa', 'ltss', 'edu', 'play', 'give') NOT NULL DEFAULT 'nec';

-- 3. Sửa bảng cài đặt ngân sách (Xóa 3 cột cũ, thêm 6 cột mới)
ALTER TABLE `user_budget_settings`
DROP COLUMN `needs_percent`,
DROP COLUMN `wants_percent`,
DROP COLUMN `savings_percent`,
ADD COLUMN `nec_percent` INT DEFAULT 55,
ADD COLUMN `ffa_percent` INT DEFAULT 10,
ADD COLUMN `ltss_percent` INT DEFAULT 10,
ADD COLUMN `edu_percent` INT DEFAULT 10,
ADD COLUMN `play_percent` INT DEFAULT 10,
ADD COLUMN `give_percent` INT DEFAULT 5;