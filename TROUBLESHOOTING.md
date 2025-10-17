# راهنمای رفع مشکلات

## مشکلات رایج و راه‌حل‌ها

---

## ❌ خطا: "کتابخانه‌های لازم نصب نشده‌اند"

### علت:
کتابخانه‌های PHP (Dompdf و PHPWord) نصب نشده‌اند.

### راه‌حل:

#### روش 1: استفاده از اسکریپت نصب خودکار
```bash
cd wp-content/plugins/honar-contract/
bash install-dependencies.sh
```

#### روش 2: نصب دستی
```bash
cd wp-content/plugins/honar-contract/
composer install
```

#### روش 3: اگر Composer ندارید
```bash
# نصب Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# سپس نصب وابستگی‌ها
composer install
```

---

## ❌ خطا: "خطا در تولید PDF"

### علت‌های احتمالی:

#### 1. مشکل مجوزها (Permissions)

**بررسی:**
```bash
ls -la wp-content/uploads/
```

**راه‌حل:**
```bash
# ایجاد پوشه contracts
mkdir -p wp-content/uploads/contracts

# تنظیم مجوزها
chmod 755 wp-content/uploads/contracts
chown www-data:www-data wp-content/uploads/contracts
```

#### 2. حافظه ناکافی PHP

**بررسی:**
```bash
php -i | grep memory_limit
```

**راه‌حل:**
در `php.ini` یا `.htaccess`:
```
php_value memory_limit 256M
```

#### 3. مشکل در فونت‌ها

**راه‌حل:**
بررسی کنید فونت DejaVu Sans در سیستم نصب باشد:
```bash
fc-list | grep -i dejavu
```

---

## ❌ امضا ذخیره نمی‌شود

### راه‌حل‌ها:

#### 1. پاک کردن Cache مرورگر
- `Ctrl + Shift + Delete`
- پاک کردن Cache و Cookies

#### 2. بررسی Console
- `F12` → Console
- بررسی خطاهای JavaScript

#### 3. غیرفعال کردن افزونه‌های مرورگر
موقتاً AdBlocker و افزونه‌های امنیتی را غیرفعال کنید.

---

## ❌ PDF خالی یا ناقص

### علت‌های احتمالی:

#### 1. مشکل در قالب Word

**بررسی:**
- فایل `قرارداد رسمی.docx` موجود باشد
- فرمت .docx باشد (نه .doc)
- متغیرها درست نوشته شده باشند

**راه‌حل:**
```bash
# بررسی وجود فایل
ls -la wp-content/plugins/honar-contract/قرارداد\ رسمی.docx
```

#### 2. مشکل در رمزگذاری

**راه‌حل:**
در `wp-config.php`:
```php
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', 'utf8mb4_unicode_ci');
```

---

## ❌ ایمیل ارسال نمی‌شود

### راه‌حل‌ها:

#### 1. تست ارسال ایمیل WordPress
```php
// افزودن به functions.php برای تست
wp_mail('test@example.com', 'Test', 'This is a test');
```

#### 2. استفاده از افزونه SMTP
- WP Mail SMTP
- Easy WP SMTP

#### 3. بررسی Spam Folder
ایمیل‌ها ممکن است در پوشه اسپم باشند.

---

## ❌ تاریخ شمسی نمایش داده نمی‌شود

### راه‌حل‌ها:

#### 1. بررسی JavaScript
```javascript
// در Console مرورگر
console.log(typeof formatJalaliDate);
```

#### 2. پاک کردن Cache
- Cache پلاگین
- Cache مرورگر
- Cache CDN

---

## ❌ فرم ارسال نمی‌شود

### بررسی‌ها:

#### 1. Nonce منقضی شده

**راه‌حل:**
صفحه را رفرش کنید (F5)

#### 2. حد زمان اجرا (Timeout)

**راه‌حل:**
در `.htaccess`:
```
php_value max_execution_time 300
```

#### 3. اندازه فایل امضا

**راه‌حل:**
در `php.ini`:
```
upload_max_filesize = 10M
post_max_size = 10M
```

---

## ❌ طراحی خراب است

### راه‌حل‌ها:

#### 1. تداخل CSS

**راه‌حل:**
در تم خود، CSS پلاگین را غیرفعال نکنید.

#### 2. حالت RTL

**راه‌حل:**
مطمئن شوید تم شما از RTL پشتیبانی می‌کند:
```css
body {
    direction: rtl;
}
```

---

## ❌ خطای 500 (Internal Server Error)

### بررسی‌ها:

#### 1. لاگ خطاها

**بررسی:**
```bash
tail -f wp-content/debug.log
```

**فعال‌سازی Debug:**
در `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

#### 2. تداخل با افزونه‌های دیگر

**راه‌حل:**
- موقتاً سایر افزونه‌ها را غیرفعال کنید
- یکی یکی فعال کنید تا افزونه مشکل‌دار پیدا شود

---

## 🔧 دستورات مفید برای Debug

### بررسی نسخه PHP
```bash
php -v
```

### بررسی Composer
```bash
composer --version
```

### بررسی کتابخانه‌های نصب شده
```bash
composer show
```

### بررسی مجوزها
```bash
find wp-content/plugins/honar-contract -type f -exec chmod 644 {} \;
find wp-content/plugins/honar-contract -type d -exec chmod 755 {} \;
```

### پاک کردن فایل‌های موقت
```bash
rm -rf wp-content/uploads/contracts/*.png
rm -rf wp-content/uploads/contracts/*.pdf
```

---

## 📞 دریافت کمک

اگر مشکل شما حل نشد:

### 1. جمع‌آوری اطلاعات
```
- نسخه WordPress: ؟
- نسخه PHP: ؟
- نسخه پلاگین: 2.0
- پیام خطا: ؟
- مراحل بازتولید: ؟
```

### 2. بررسی لاگ‌ها
```bash
# لاگ WordPress
cat wp-content/debug.log

# لاگ امنیتی پلاگین
cat wp-content/uploads/honar-security.log

# لاگ PHP
tail -f /var/log/php-fpm/error.log
```

### 3. تماس با پشتیبانی
**ایمیل:** toopiloopi11@gmail.com

اطلاعات زیر را ارسال کنید:
- لاگ خطاها
- اسکرین‌شات
- مراحل بازتولید مشکل

---

## ✅ چک‌لیست بررسی

قبل از گزارش مشکل، این موارد را بررسی کنید:

- [ ] Composer نصب شده
- [ ] `composer install` اجرا شده
- [ ] پوشه vendor موجود است
- [ ] مجوز نوشتن در uploads/contracts وجود دارد
- [ ] فایل قالب Word موجود است
- [ ] PHP نسخه 7.4+ است
- [ ] WordPress نسخه 5.0+ است
- [ ] Debug mode فعال است
- [ ] Cache پاک شده
- [ ] تداخل با افزونه‌های دیگر بررسی شده

---

**تاریخ بروزرسانی:** 1403/07/25  
**نسخه:** 1.0
