#!/bin/bash
# =====================================================================
# Campus Services Booking System - Auto Deployment Script
# =====================================================================

# Cấu hình các thông số
PROJECT_DIR="/var/www/campus-booking"
BRANCH="main"
PHP_FPM_SERVICE="php8.2-fpm" # Thay đổi thành php8.3-fpm nếu sử dụng PHP 8.3

echo "=================================================="
echo "  Bắt đầu cập nhật và triển khai ứng dụng (Deploy) "
echo "=================================================="

# 1. Truy cập vào thư mục dự án
if [ -d "$PROJECT_DIR" ]; then
    cd "$PROJECT_DIR" || exit 1
else
    echo "Lỗi: Thư mục dự án $PROJECT_DIR không tồn tại!"
    exit 1
fi

# 2. Reset các thay đổi local không mong muốn (nếu có) và kéo code mới nhất
echo "--> Đang lấy mã nguồn mới nhất từ nhánh $BRANCH..."
git fetch origin
git checkout "$BRANCH"
git reset --hard origin/"$BRANCH"

# 3. Đảm bảo thư mục upload tồn tại
echo "--> Đảm bảo cấu trúc thư mục..."
mkdir -p public/uploads

# 4. Phân lại quyền sở hữu cho Nginx (www-data)
echo "--> Cập nhật phân quyền thư mục..."
sudo chown -R www-data:www-data public/uploads
sudo chown -R www-data:www-data app
sudo chmod -R 775 public/uploads

# 5. Reload PHP-FPM để xoá OPcache (đảm bảo code PHP mới được áp dụng ngay lập tức)
echo "--> Khởi động lại dịch vụ PHP..."
if systemctl is-active --quiet "$PHP_FPM_SERVICE"; then
    sudo systemctl reload "$PHP_FPM_SERVICE"
    echo "--> Đã reload dịch vụ $PHP_FPM_SERVICE thành công."
else
    echo "Cảnh báo: Dịch vụ $PHP_FPM_SERVICE không chạy hoặc không khả dụng."
fi

echo "=================================================="
echo "          DEPLOY HOÀN TẤT THÀNH CÔNG!             "
echo "=================================================="
