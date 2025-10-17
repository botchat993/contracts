#!/bin/bash

# Honar Contract Plugin - Dependency Installer
# نصب خودکار وابستگی‌های پلاگین

echo "========================================="
echo "نصب وابستگی‌های پلاگین قرارداد آنلاین"
echo "========================================="
echo ""

# بررسی نصب Composer
if ! command -v composer &> /dev/null
then
    echo "❌ Composer نصب نیست!"
    echo ""
    echo "برای نصب Composer این دستورات را اجرا کنید:"
    echo ""
    echo "curl -sS https://getcomposer.org/installer | php"
    echo "sudo mv composer.phar /usr/local/bin/composer"
    echo ""
    exit 1
fi

echo "✅ Composer یافت شد"
echo ""

# نصب وابستگی‌ها
echo "📦 در حال نصب کتابخانه‌ها..."
echo ""

composer install --no-dev --optimize-autoloader

if [ $? -eq 0 ]; then
    echo ""
    echo "========================================="
    echo "✅ نصب با موفقیت انجام شد!"
    echo "========================================="
    echo ""
    echo "کتابخانه‌های نصب شده:"
    echo "  - dompdf/dompdf (تبدیل HTML به PDF)"
    echo "  - phpoffice/phpword (کار با فایل Word)"
    echo ""
    echo "حالا می‌توانید پلاگین را فعال کنید."
    echo ""
else
    echo ""
    echo "========================================="
    echo "❌ خطا در نصب!"
    echo "========================================="
    echo ""
    echo "لطفاً به صورت دستی دستور زیر را اجرا کنید:"
    echo "composer install"
    echo ""
    exit 1
fi
