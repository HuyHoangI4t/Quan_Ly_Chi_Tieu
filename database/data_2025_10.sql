-- SmartSpending - Sample Data for October 2025
USE `quan_ly_chi_tieu`;

-- Sample transactions for October 2025
INSERT INTO `transactions` (user_id, category_id, amount, date, description, type) VALUES
(2, 2, -200000, '2025-10-01', 'Ăn sáng', 'expense'),
(2, 2, -500000, '2025-10-02', 'Ăn trưa', 'expense'),
(2, 2, -300000, '2025-10-03', 'Ăn tối', 'expense'),
(2, 6, -400000, '2025-10-04', 'Tiền điện', 'expense'),
(2, 5, -250000, '2025-10-05', 'Tiền nước', 'expense'),
(2, 9, -150000, '2025-10-06', 'Tiền internet', 'expense'),
(2, 12, -1000000, '2025-10-07', 'Mua quần áo', 'expense'),
(2, 14, -800000, '2025-10-08', 'Mua đồ gia dụng', 'expense'),
(2, 25, -300000, '2025-10-09', 'Vé xem phim', 'expense'),
(2, 25, -200000, '2025-10-10', 'Đi chơi bowling', 'expense'),
(2, 36, 9000000, '2025-10-25', 'Lương tháng 10', 'income'),
(2, 37, 500000, '2025-10-26', 'Thu nhập khác', 'income'),
(2, 20, -150000, '2025-10-11', 'Tiền xăng', 'expense'),
(2, 23, -200000, '2025-10-12', 'Khám sức khỏe', 'expense'),
(2, 28, -400000, '2025-10-13', 'Học phí', 'expense'),
(2, 29, -300000, '2025-10-14', 'Quà sinh nhật', 'expense'),
(2, 30, -250000, '2025-10-15', 'Bảo hiểm xe', 'expense'),
(2, 31, -1000000, '2025-10-16', 'Đầu tư chứng khoán', 'expense'),
(2, 32, -50000, '2025-10-17', 'Chi phí khác', 'expense');