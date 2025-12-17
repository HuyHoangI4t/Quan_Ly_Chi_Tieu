# SmartSpending - Quản Lý Chi Tiêu Cá Nhân

## Giới thiệu
SmartSpending là hệ thống quản lý chi tiêu cá nhân, hỗ trợ theo dõi thu nhập, chi tiêu, ngân sách, mục tiêu tài chính và báo cáo trực quan.

## Tính năng chính
- Quản lý thu nhập, chi tiêu, ngân sách
- Báo cáo trực quan bằng biểu đồ
- Quản lý mục tiêu tài chính
- Giao dịch định kỳ
- Phân quyền người dùng (admin, user)

## Cài đặt
1. Clone dự án về máy:
	```
	git clone https://github.com/HuyHoangI4t/Quan_Ly_Chi_Tieu.git
	```
2. Cài đặt PHP >= 7.4, MySQL >= 5.7, Composer
3. Cài đặt thư viện:
	```
	# SmartSpending - Quản Lý Chi Tiêu Cá Nhân

	## Giới thiệu
	SmartSpending là hệ thống quản lý chi tiêu cá nhân, hỗ trợ theo dõi thu nhập, chi tiêu, ngân sách, mục tiêu tài chính và báo cáo trực quan.

	## Tính năng chính
	- Quản lý thu nhập, chi tiêu, ngân sách
	- Báo cáo trực quan bằng biểu đồ
	- Quản lý mục tiêu tài chính
	- Giao dịch định kỳ
	- Phân quyền người dùng (admin, user)

	## Cài đặt
	1. Clone dự án về máy:
	```bash
	git clone https://github.com/HuyHoangI4t/Quan_Ly_Chi_Tieu.git
	```
	2. Cài đặt PHP >= 7.4, MySQL >= 5.7, Composer
	3. Cài đặt thư viện:
	```bash
	composer install
	```
	4. Tạo database và import file `database/schema.sql` (và `database/data_test.sql` nếu cần dữ liệu mẫu)
	5. Copy file `.env.example` thành `.env` và chỉnh sửa thông tin kết nối database

	## Cấu hình
	- Thông tin cấu hình lưu ở file `.env`
	- Không commit file `.env` lên git

	## Sử dụng
	Truy cập trang web tại:
	```
	http://localhost/Quan_Ly_Chi_Tieu/public
	```

	## Tài khoản admin
	- Để tạo tài khoản admin, thêm user với trường `role` là `admin` trong bảng `users`

	## Đóng góp
	Mọi ý kiến đóng góp vui lòng gửi về [github issues](https://github.com/HuyHoangI4t/Quan_Ly_Chi_Tieu/issues)

	## License
	MIT

	## Project structure

	```
	composer.json
	LICENSE
	README.md
	app/
		Controllers/
			Admin/
				Categories.php
				Dashboard.php
				Logs.php
				Reports.php
				Settings.php
				System.php
				Transactions.php
				Users.php
			Auth/
				Login.php
			User/
				Budgets.php
				(other user controllers...)
		Core/
			App.php
			AssetBundler.php
			ConnectDB.php
			Container.php
			Controllers.php
			EnvLoader.php
			Request.php
			Response.php
			SessionManager.php
			Views.php
		Middleware/
			AuthCheck.php
			CsrfProtection.php
		Models/
			Budget.php
			Category.php
			Goal.php
			Log.php
			RecurringTransaction.php
			Transaction.php
			User.php
			Wallet.php
		Services/
			DashboardService.php
			FinancialUtils.php
			Validator.php
	config/
		constants.php
		database.php
	database/
		data_test.sql
		schema.sql
	images/
	public/
		index.php
		css/
			admin.css
			budgets.css
			dashboard.css
			goals.css
			login.css
			profile.css
			reports.css
			transactions.css
		js/
			budgets.js
			dashboard.js
			goals.js
			profile.js
			reports.js
			smart-budget.js
			transactions.js
		shared/
			app.js
			input-masking.js
			style.css
	resources/
		views/
			admin/
				partials/
					sidebar.php
				dashboard.php
				reports.php
			auth/
			user/
			partials/
	storage/
		logs/
	vendor/
		autoload.php
		(composer packages...)

	Note: `vendor/` contains third-party libraries installed by Composer. `app/` contains application controllers, models and core classes. `public/` is the webroot.

	If you want a full expanded file list (including every file under `vendor/`), tell me and I will append it or generate a separate `STRUCTURE.md`.

	```
