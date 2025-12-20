@echo off
REM Script tu dong cai dat SmartSpending
REM Tac gia: Ho tro tu dong hoa cai dat cho nguoi dung Windows

echo === SMARTSPENDING AUTO INSTALL ===

REM Huong dan tai cac phan mem can thiet
echo Neu ban chua cai PHP, MySQL, Composer, hay tai va cai dat:
echo - PHP: https://windows.php.net/download
echo - MySQL: https://dev.mysql.com/downloads/installer/
echo - Composer: https://getcomposer.org/download/
echo -----------------------------------
pause

REM Kiem tra PHP
where php >nul 2>nul
if errorlevel 1 (
    echo PHP chua duoc cai dat. Vui long cai PHP truoc khi chay script nay.
    exit /b 1
)

REM Kiem tra Composer
where composer >nul 2>nul
if errorlevel 1 (
    echo Composer chua duoc cai dat. Vui long cai Composer truoc khi chay script nay.
    exit /b 1
)

REM Cai dat thu vien PHP
composer install

REM Hoi thong tin ket noi MySQL
set /p MYSQL_USER=Nhap MySQL user (vi du: root): 
set /p MYSQL_PASS=Nhap MySQL password: 
set /p MYSQL_DB=Nhap ten database muon tao (vi du: smartspending): 

REM Tao database
echo Tao database %MYSQL_DB% ...
mysql -u%MYSQL_USER% -p%MYSQL_PASS% -e "CREATE DATABASE IF NOT EXISTS %MYSQL_DB% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if errorlevel 1 (
    echo Loi khi tao database. Kiem tra lai thong tin ket noi!
    pause
    exit /b 1
)

REM Import schema.sql
echo Import cau truc bang tu database/schema.sql ...
mysql -u%MYSQL_USER% -p%MYSQL_PASS% %MYSQL_DB% < database\schema.sql
if errorlevel 1 (
    echo Loi khi import schema.sql!
    pause
    exit /b 1
)

REM Hoi co import du lieu mau khong
set /p IMPORT_SAMPLE=Ban co muon import du lieu mau (data_test.sql)? (y/n): 
if /I "%IMPORT_SAMPLE%"=="y" (
    mysql -u%MYSQL_USER% -p%MYSQL_PASS% %MYSQL_DB% < database\data_test.sql
    if errorlevel 1 (
        echo Loi khi import data_test.sql!
        pause
        exit /b 1
    )
)

echo -----------------------------------
echo Hoan tat cai dat ma nguon va database!
echo -----------------------------------
echo Tiep theo:
echo 1. Copy file .env.example thanh .env
echo 2. Sua thong tin ket noi DB trong file .env cho dung voi thong tin ban vua nhap
echo 3. Truy cap http://localhost/Quan_Ly_Chi_Tieu/public tren trinh duyet
echo 4. Dang nhap hoac tao tai khoan admin
echo -----------------------------------
pause
exit /b 0
echo === END ===
echo -----------------------------------
echo Hoan tat cai dat ma nguon va database!
echo -----------------------------------        