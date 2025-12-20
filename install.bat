
@echo off
REM Script tự động cài đặt SmartSpending
REM Tác giả: Hỗ trợ tự động hóa cài đặt cho người dùng Windows

echo === SMARTSPENDING AUTO INSTALL ===

REM Hướng dẫn tải các phần mềm cần thiết
echo Nếu bạn chưa cài PHP, MySQL, Composer, hãy tải và cài đặt:
echo - PHP: https://windows.php.net/download
echo - MySQL: https://dev.mysql.com/downloads/installer/
echo - Composer: https://getcomposer.org/download/
echo -----------------------------------
pause

REM Hỏi người dùng có muốn clone lại repo không
set /p CLONE=Bạn đã clone mã nguồn về chưa? (y/n): 
if /I "%CLONE%"=="n" (
    set /p REPO=Nhập đường dẫn repo (mặc định: https://github.com/HuyHoangI4t/Quan_Ly_Chi_Tieu.git): 
    if "%REPO%"=="" set REPO=https://github.com/HuyHoangI4t/Quan_Ly_Chi_Tieu.git
    git clone "%REPO%"
    echo Đã clone xong mã nguồn.
    cd Quan_Ly_Chi_Tieu
)

REM Kiểm tra Composer
where composer >nul 2>nul
if errorlevel 1 (
    echo Composer chưa được cài đặt. Vui lòng cài Composer trước khi chạy script này.
    exit /b 1
)

REM Cài đặt thư viện PHP
composer install

REM Hỏi thông tin kết nối MySQL
set /p MYSQL_USER=Nhập MySQL user (ví dụ: root): 
set /p MYSQL_PASS=Nhập MySQL password: 
set /p MYSQL_DB=Nhập tên database muốn tạo (ví dụ: smartspending): 

REM Tạo database
echo Tạo database %MYSQL_DB% ...
mysql -u%MYSQL_USER% -p%MYSQL_PASS% -e "CREATE DATABASE IF NOT EXISTS %MYSQL_DB% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if errorlevel 1 (
    echo Lỗi khi tạo database. Kiểm tra lại thông tin kết nối!
    pause
    exit /b 1
)

REM Import schema.sql
echo Import cấu trúc bảng từ database/schema.sql ...
mysql -u%MYSQL_USER% -p%MYSQL_PASS% %MYSQL_DB% < database\schema.sql
if errorlevel 1 (
    echo Lỗi khi import schema.sql!
    pause
    exit /b 1
)

REM Hỏi có import dữ liệu mẫu không
set /p IMPORT_SAMPLE=Bạn có muốn import dữ liệu mẫu (data_test.sql)? (y/n): 
if /I "%IMPORT_SAMPLE%"=="y" (
    mysql -u%MYSQL_USER% -p%MYSQL_PASS% %MYSQL_DB% < database\data_test.sql
    if errorlevel 1 (
        echo Lỗi khi import data_test.sql!
        pause
        exit /b 1
    )
)

echo -----------------------------------
echo Hoàn tất cài đặt mã nguồn và database!
echo -----------------------------------
echo Tiếp theo:
echo 1. Copy file .env.example thành .env
echo 2. Sửa thông tin kết nối DB trong file .env cho đúng với thông tin bạn vừa nhập
echo 3. Truy cập http://localhost/Quan_Ly_Chi_Tieu/public trên trình duyệt
echo 4. Đăng nhập hoặc tạo tài khoản admin
echo -----------------------------------
pause
