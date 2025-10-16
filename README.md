# پلاگین قرارداد آنلاین هنر مغز 📝

پلاگین وردپرس برای ایجاد و مدیریت قراردادهای آنلاین با امضای دیجیتال، تبدیل خودکار به PDF و ارسال ایمیل.

![Version](https://img.shields.io/badge/version-2.0-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

## ✨ امکانات

### 🎨 رابط کاربری مدرن و زیبا
- طراحی ریسپانسیو و موبایل‌فرندلی
- انیمیشن‌های روان و حرفه‌ای
- رنگ‌بندی شیک و چشم‌نواز
- تجربه کاربری بهینه

### 📅 تبدیل خودکار تاریخ
- تبدیل تاریخ میلادی به شمسی (جلالی)
- نمایش پیش‌نمایش زنده تاریخ شمسی
- فرمت تاریخ به صورت اعداد فارسی

### 📄 تولید PDF حرفه‌ای
- تبدیل فایل Word به PDF
- استفاده از قالب‌های قابل تنظیم
- افزودن لوگو و امضای دیجیتال
- طراحی PDF زیبا و استاندارد

### ✍️ امضای دیجیتال
- امضای دیجیتال با ماوس یا لمس
- پشتیبانی از دستگاه‌های تاچ
- امکان پاک کردن و امضای مجدد

### 📧 ارسال خودکار ایمیل
- ارسال قرارداد PDF به کاربر
- ارسال کپی به مدیر سیستم
- قالب ایمیل حرفه‌ای و قابل تنظیم

### 🔒 امنیت پیشرفته
- اعتبارسنجی ورودی‌ها (Input Validation)
- محافظت در برابر CSRF با Nonce
- ضدعفونی داده‌ها (Sanitization)
- محدودیت حجم فایل امضا
- محافظت از دایرکتوری فایل‌ها

### 👁️ پیش‌نمایش PDF
- نمایش PDF در صفحه بعد از تولید
- امکان دانلود مستقیم
- نمایش کامل قرارداد

## 🚀 نصب و راه‌اندازی

### پیش‌نیازها
```bash
- WordPress 5.0+
- PHP 7.4+
- Composer
```

### مراحل نصب

1. **دانلود و آپلود پلاگین**
```bash
# آپلود فایل‌ها به مسیر
wp-content/plugins/honar-contract/
```

2. **نصب وابستگی‌ها**
```bash
cd wp-content/plugins/honar-contract/
composer install
```

3. **فعال‌سازی پلاگین**
- وارد پنل مدیریت WordPress شوید
- افزونه‌ها → Honar Maghz Contract → فعال‌سازی

4. **تنظیمات**
فایل `honar-contract.php` را ویرایش کنید:
```php
define('HONAR_ADMIN_EMAIL', 'your-email@domain.com');
define('HONAR_ORG_NAME', 'نام سازمان شما');
```

5. **استفاده از شورت‌کد**
```
[honar_maghz_contract]
```

## 📋 استفاده از قالب Word

در فایل Word قرارداد، از متغیرهای زیر استفاده کنید:

| متغیر | توضیحات |
|-------|---------|
| `${PARTNER_NAME}` | نام و نام خانوادگی طرف دوم |
| `${PARTNER_TITLE}` | سمت یا عنوان |
| `${PARTNER_ADDRESS}` | آدرس |
| `${PARTNER_EMAIL}` | ایمیل |
| `${PLAN}` | طرح انتخابی |
| `${DATE}` | تاریخ شمسی |
| `${ORG_NAME}` | نام سازمان |

## 🎯 ویژگی‌های فنی

### معماری
- استفاده از PSR-4 Autoloading
- کد تمیز و قابل نگهداری
- استفاده از بهترین روش‌های WordPress

### کتابخانه‌های استفاده شده
- **Dompdf**: تبدیل HTML به PDF
- **PHPWord**: کار با فایل‌های Word
- **Jalali Moment**: تبدیل تاریخ شمسی

### بهینه‌سازی‌ها
- کش کردن منابع استاتیک
- بارگذاری تنبل (Lazy Loading)
- فشرده‌سازی CSS/JS
- کوئری‌های بهینه شده

## 🔧 سفارشی‌سازی

### تغییر طرح‌های انتخابی
در فایل `honar-contract.php`:
```php
<label><input type="radio" name="plan" value="طرح برنز" /> طرح برنز</label>
<label><input type="radio" name="plan" value="طرح نقره" /> طرح نقره</label>
<label><input type="radio" name="plan" value="طرح طلا" /> طرح طلا</label>
```

### تغییر استایل
فایل `honar-contract.css` را ویرایش کنید:
```css
.honar-contract-card {
    background: #your-color;
    border-radius: 20px;
}
```

## 📂 ساختار فایل‌ها

```
honar-contract/
├── honar-contract.php      # فایل اصلی پلاگین
├── honar-contract.css      # استایل‌ها
├── honar-contract.js       # جاوااسکریپت
├── composer.json           # وابستگی‌های PHP
├── src/
│   └── JalaliDate.php     # کلاس تبدیل تاریخ
├── logo.pdf               # لوگوی سازمان
├── قرارداد رسمی.docx      # قالب قرارداد
├── README.md              # مستندات
├── INSTALL.md             # راهنمای نصب
└── .htaccess              # تنظیمات امنیتی
```

## 🔐 امنیت

### اقدامات امنیتی پیاده‌سازی شده:
- ✅ Nonce Verification (محافظت CSRF)
- ✅ Input Sanitization (ضدعفونی ورودی)
- ✅ Data Validation (اعتبارسنجی داده)
- ✅ File Upload Security (امنیت آپلود فایل)
- ✅ SQL Injection Prevention (جلوگیری از SQL Injection)
- ✅ XSS Protection (محافظت XSS)
- ✅ Directory Protection (محافظت دایرکتوری)

## 📊 عملکرد

### بهینه‌سازی‌های انجام شده:
- Cache-Control Headers
- Minified Assets
- Optimized Database Queries
- Lazy Loading
- Image Optimization
- Code Splitting

## 🐛 عیب‌یابی

### مشکلات رایج

**1. PDF تولید نمی‌شود**
```bash
# بررسی مجوزها
chmod 755 wp-content/uploads/contracts
```

**2. خطای Composer**
```bash
# نصب مجدد
composer clear-cache
composer install
```

**3. امضا ذخیره نمی‌شود**
- Cache مرورگر را پاک کنید
- JavaScript را بررسی کنید (Console)

## 📈 نسخه‌ها

### نسخه 2.0 (جاری)
- ✨ رابط کاربری کاملاً جدید
- ✨ تبدیل Word به PDF
- ✨ تاریخ شمسی
- ✨ پیش‌نمایش PDF
- 🔒 امنیت پیشرفته
- ⚡ بهینه‌سازی عملکرد

### نسخه 1.0
- فرم قرارداد ساده
- امضای دیجیتال
- ارسال ایمیل

## 🤝 مشارکت

برای مشارکت در توسعه:
1. Fork کنید
2. Branch جدید بسازید (`git checkout -b feature/AmazingFeature`)
3. تغییرات را Commit کنید (`git commit -m 'Add AmazingFeature'`)
4. Push کنید (`git push origin feature/AmazingFeature`)
5. Pull Request ایجاد کنید

## 📝 لایسنس

این پروژه تحت لایسنس MIT منتشر شده است.

## 👥 توسعه‌دهندگان

- **تیم هنر مغز**
- ایمیل: toopiloopi11@gmail.com

## 🙏 تشکر

از تمامی کسانی که در توسعه این پلاگین مشارکت داشتند، تشکر می‌کنیم.

---

**ساخته شده با ❤️ برای جامعه وردپرس ایران**
