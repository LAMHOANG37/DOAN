@echo off
setlocal enabledelayedexpansion
chcp 65001 >nul
echo ==========================================
echo    Auto Push to GitHub
echo ==========================================
echo.

:: Kiểm tra xem có phải git repository không
git status >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Đây không phải là Git repository!
    echo Vui lòng chạy: git init
    pause
    exit /b 1
)

:: Hiển thị trạng thái hiện tại
echo [1/4] Kiểm tra trạng thái Git...
echo.
git status -s
echo.

:: Kiểm tra có thay đổi nào không
git diff --quiet 2>nul
set HAS_CHANGES=%errorlevel%
git diff --cached --quiet 2>nul
set HAS_STAGED=%errorlevel%

if !HAS_CHANGES! neq 0 (
    echo [2/4] Có thay đổi, đang thêm vào staging...
    git add .
    echo [OK] Đã thêm tất cả thay đổi
    echo.
) else if !HAS_STAGED! neq 0 (
    echo [2/4] Có thay đổi đã staged, tiếp tục...
    echo.
) else (
    echo [INFO] Không có thay đổi nào để commit
    pause
    exit /b 0
)

:: Tạo commit message
echo [3/4] Tạo commit...
set /p COMMIT_MSG="Nhập commit message (hoặc Enter để dùng message mặc định): "
if "!COMMIT_MSG!"=="" (
    for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value') do set datetime=%%I
    set COMMIT_MSG=Auto commit - %datetime:~0,8% %datetime:~8,6%
)

git commit -m "!COMMIT_MSG!"
if errorlevel 1 (
    echo [ERROR] Commit thất bại!
    pause
    exit /b 1
)
echo [OK] Đã commit thành công
echo Commit message: !COMMIT_MSG!
echo.

:: Kiểm tra remote
echo [4/4] Đang push lên GitHub...
git remote -v >nul 2>&1
if errorlevel 1 (
    echo [WARNING] Chưa có remote repository!
    echo Vui lòng thêm remote: git remote add origin ^<url^>
    pause
    exit /b 1
)

:: Lấy tên branch hiện tại
for /f "tokens=*" %%i in ('git rev-parse --abbrev-ref HEAD') do set CURRENT_BRANCH=%%i

:: Push lên GitHub
echo Đang push lên branch: !CURRENT_BRANCH!
git push origin !CURRENT_BRANCH!
if errorlevel 1 (
    echo [ERROR] Push thất bại!
    echo Có thể cần pull trước: git pull origin !CURRENT_BRANCH!
    pause
    exit /b 1
)

echo.
echo ==========================================
echo [SUCCESS] Đã push lên GitHub thành công!
echo Branch: !CURRENT_BRANCH!
echo ==========================================
echo.
pause

