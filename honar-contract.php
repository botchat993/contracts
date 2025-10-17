<?php
/**
 * Plugin Name: Honar Maghz Contract
 * Description: قرارداد آنلاین با امضای دیجیتال — فرم، تبدیل Word به PDF، تاریخ شمسی، ذخیره در uploads و ارسال ایمیل
 * Version: 2.0
 * Author: Honar Maghz Team
 * Text Domain: honar-contract
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// ------------------------
// === تنظیمات اولیه ===
// ------------------------
if (!defined('HONAR_LOGO_URL')) {
    // لوگو از پوشه پلاگین
    define('HONAR_LOGO_URL', plugin_dir_url(__FILE__) . 'logo.pdf');
}

if (!defined('HONAR_ADMIN_EMAIL')) {
    define('HONAR_ADMIN_EMAIL', 'toopiloopi11@gmail.com');
}

if (!defined('HONAR_ORG_NAME')) {
    define('HONAR_ORG_NAME', 'آموزشگاه هنر مغز');
}

if (!defined('HONAR_CONTRACT_DIR')) {
    define('HONAR_CONTRACT_DIR', 'contracts');
}

if (!defined('HONAR_CONTRACT_TEMPLATE')) {
    define('HONAR_CONTRACT_TEMPLATE', plugin_dir_path(__FILE__) . 'قرارداد رسمی.docx');
}

// بارگذاری کتابخانه‌های مورد نیاز
require_once plugin_dir_path(__FILE__) . 'src/JalaliDate.php';

// بارگذاری تنظیمات عملکرد و امنیت
if (file_exists(plugin_dir_path(__FILE__) . 'performance-config.php')) {
    require_once plugin_dir_path(__FILE__) . 'performance-config.php';
}

// ------------------------
// === Enqueue scripts ===
// ------------------------
add_action('wp_enqueue_scripts', function(){
    wp_register_script('honar-contract-js', plugin_dir_url(__FILE__) . 'honar-contract.js', array('jquery'), '2.0', true);
    wp_localize_script('honar-contract-js', 'HONAR_AJAX', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('honar_contract_nonce'),
    ));
    wp_enqueue_script('honar-contract-js');

    wp_register_style('honar-contract-css', plugin_dir_url(__FILE__) . 'honar-contract.css', array(), '2.0');
    wp_enqueue_style('honar-contract-css');
});

// ------------------------
// === Shortcode: form ===
// ------------------------
add_shortcode('honar_maghz_contract', function($atts){
    ob_start();
    ?>
    <div id="honar-contract-wrap" class="honar-contract-wrap">
        <div class="honar-contract-card">
            <div class="honar-contract-header">
                <img src="<?php echo esc_attr(HONAR_LOGO_URL); ?>" alt="logo" class="honar-logo" />
                <h2 class="honar-title"><?php echo esc_html(HONAR_ORG_NAME); ?></h2>
                <h3 class="honar-sub">قرارداد همکاری</h3>
            </div>

            <form id="honar-contract-form" method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('honar_contract_submit', 'honar_contract_nonce_field'); ?>
                <div class="field">
                    <label>نام و نام خانوادگی (طرف دوم)</label>
                    <input type="text" name="partner_name" required />
                </div>
                <div class="field">
                    <label>سمت / عنوان</label>
                    <input type="text" name="partner_title" />
                </div>
                <div class="field">
                    <label>آدرس</label>
                    <input type="text" name="partner_address" />
                </div>
                <div class="field">
                    <label>ایمیل (برای دریافت قرارداد)</label>
                    <input type="email" name="partner_email" required />
                </div>
                <div class="field">
                    <label>طرح انتخابی</label><br/>
                    <label><input type="radio" name="plan" value="طرح سایت" checked /> طرح سایت</label>
                    <label><input type="radio" name="plan" value="طرح گروه" /> طرح گروه</label>
                    <label><input type="radio" name="plan" value="طرح طلایی" /> طرح طلایی</label>
                </div>
                <div class="field">
                    <label>تاریخ قرارداد</label>
                    <input type="date" name="contract_date" required />
                    <small class="date-preview" id="date-preview" style="display:block;margin-top:5px;color:#666;">تاریخ شمسی: در انتظار انتخاب تاریخ</small>
                </div>

                <div class="field">
                    <label>امضا (با ماوس یا لمس)</label>
                    <div class="signature-wrap">
                        <canvas id="honar-signature-pad" width="800" height="300" style="border:2px solid #ddd;background:#fff;border-radius:8px;"></canvas>
                        <div><button id="honar-clear-sign" type="button">پاک کردن امضا</button></div>
                        <input type="hidden" name="signature_data" id="signature_data" />
                    </div>
                </div>

                <div class="field">
                    <button type="submit" id="honar-submit-btn">تایید و تولید قرارداد</button>
                </div>
            </form>

            <div id="honar-result" style="display:none;"></div>
        </div>
    </div>
    <?php
    return ob_get_clean();
});

// ------------------------
// === AJAX handler ===
// ------------------------
add_action('wp_ajax_nopriv_honar_contract_submit', 'honar_contract_submit');
add_action('wp_ajax_honar_contract_submit', 'honar_contract_submit');

function honar_contract_submit() {
    // بررسی nonce برای امنیت
    if (empty($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'honar_contract_nonce')) {
        wp_send_json_error(['msg' => 'خطا: اعتبارسنجی ناموفق.']);
    }

    // دریافت و اعتبارسنجی ورودی‌ها
    $name = isset($_POST['partner_name']) ? sanitize_text_field($_POST['partner_name']) : '';
    $title = isset($_POST['partner_title']) ? sanitize_text_field($_POST['partner_title']) : '';
    $address = isset($_POST['partner_address']) ? sanitize_textarea_field($_POST['partner_address']) : '';
    $email = isset($_POST['partner_email']) ? sanitize_email($_POST['partner_email']) : '';
    $plan = isset($_POST['plan']) ? sanitize_text_field($_POST['plan']) : '';
    $date = isset($_POST['contract_date']) ? sanitize_text_field($_POST['contract_date']) : '';
    $signature_data = isset($_POST['signature_data']) ? $_POST['signature_data'] : '';

    // اعتبارسنجی فیلدهای اجباری
    if (empty($name) || empty($email) || empty($date)) {
        wp_send_json_error(['msg' => 'لطفاً تمام فیلدهای اجباری را پر کنید.']);
    }
    
    // اعتبارسنجی ایمیل
    if (!is_email($email)) {
        wp_send_json_error(['msg' => 'آدرس ایمیل معتبر نیست.']);
    }
    
    // اعتبارسنجی امضا
    if (empty($signature_data) || !preg_match('/^data:image\/png;base64,/', $signature_data)) {
        wp_send_json_error(['msg' => 'لطفاً امضای خود را وارد کنید.']);
    }
    
    // تبدیل تاریخ به شمسی
    $jalali_date = \HonarContract\JalaliDate::toJalali($date);

    // پوشه ذخیره در uploads با امنیت بهتر
    $upload_dir = wp_upload_dir();
    $contracts_dir = trailingslashit($upload_dir['basedir']) . HONAR_CONTRACT_DIR;
    
    if (!file_exists($contracts_dir)) {
        wp_mkdir_p($contracts_dir);
        // ایجاد فایل .htaccess برای محافظت از فایل‌های حساس
        $htaccess = $contracts_dir . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "<Files *.php>\ndeny from all\n</Files>");
        }
    }

    // ذخیره تصویر امضا (base64 -> png) با بررسی امنیتی
    $data = substr($signature_data, strpos($signature_data, ',') + 1);
    $data = base64_decode($data);
    
    // بررسی حجم تصویر امضا (حداکثر 500KB)
    if (strlen($data) > 512000) {
        wp_send_json_error(['msg' => 'حجم امضا بیش از حد مجاز است.']);
    }
    
    $sig_filename = 'signature_' . time() . '_' . wp_rand(1000, 9999) . '.png';
    $sig_path = trailingslashit($contracts_dir) . $sig_filename;
    
    if (!file_put_contents($sig_path, $data)) {
        wp_send_json_error(['msg' => 'خطا در ذخیره امضا.']);
    }

    // === تولید PDF از فایل Word ===
    $pdf_path = honar_generate_contract_pdf($name, $title, $address, $email, $plan, $date, $jalali_date, $sig_path, $contracts_dir);

    if (!$pdf_path) {
        $vendor = plugin_dir_path(__FILE__) . 'vendor/autoload.php';
        if (!file_exists($vendor)) {
            wp_send_json_error([
                'msg' => 'خطا: کتابخانه‌های لازم نصب نشده‌اند. لطفاً دستور زیر را در مسیر پلاگین اجرا کنید:<br><code>composer install</code>',
                'error_type' => 'missing_vendor'
            ]);
        } else {
            wp_send_json_error([
                'msg' => 'خطا در تولید PDF. لطفاً مجوزهای نوشتن در پوشه uploads را بررسی کنید.',
                'error_type' => 'pdf_generation'
            ]);
        }
    }
    
    $pdf_name = basename($pdf_path);

    // آدرس دانلود قابل دسترس (public) — مسیر URL برابر uploads/.../contracts/...
    $pdf_url = trailingslashit($upload_dir['baseurl']) . HONAR_CONTRACT_DIR . '/' . $pdf_name;

    // === ارسال ایمیل با پیوست ===
    $subject_user = "نسخه قرارداد شما - " . HONAR_ORG_NAME;
    $message_user = "سلام " . $name . "\n\nقرارداد شما با " . HONAR_ORG_NAME . " تولید و پیوست شد.\n\nلینک دانلود: " . $pdf_url . "\n\nبا احترام";
    $headers_user = array('Content-Type: text/plain; charset=UTF-8');

    $subject_admin = "قرارداد جدید - " . HONAR_ORG_NAME;
    $message_admin = "یک قرارداد جدید توسط " . $name . " ثبت شد.\n\nلینک دانلود: " . $pdf_url . "\n\nاطلاعات:\nنام: " . $name . "\nایمیل: " . $email . "\nطرح: " . $plan;
    $headers_admin = array('Content-Type: text/plain; charset=UTF-8');

    // ضمیمه‌ها
    $attachments = array( $pdf_path );

    // ارسال به کاربر
    wp_mail( $email, $subject_user, $message_user, $headers_user, $attachments );
    // ارسال به ادمین (شما)
    wp_mail( HONAR_ADMIN_EMAIL, $subject_admin, $message_admin, $headers_admin, $attachments );

    // پاسخ موفق
    wp_send_json_success([
        'msg' => 'قرارداد با موفقیت تولید و ارسال شد.',
        'pdf_url' => $pdf_url,
        'pdf_name' => $pdf_name
    ]);
}

/**
 * تولید PDF قرارداد از فایل Word یا HTML
 */
function honar_generate_contract_pdf($name, $title, $address, $email, $plan, $gregorian_date, $jalali_date, $sig_path, $contracts_dir) {
    $vendor = plugin_dir_path(__FILE__) . 'vendor/autoload.php';
    
    // اگر vendor نصب نیست، مستقیم از HTML استفاده کن
    if (!file_exists($vendor)) {
        error_log('Honar Contract: vendor/autoload.php not found, using HTML method');
        return honar_generate_html_pdf($name, $title, $address, $email, $plan, $jalali_date, $sig_path, $contracts_dir);
    }
    
    require_once $vendor;
    
    // بررسی وجود فایل قالب Word
    $templatePath = HONAR_CONTRACT_TEMPLATE;
    
    if (!file_exists($templatePath)) {
        error_log('Honar Contract: Word template not found at: ' . $templatePath);
        return honar_generate_html_pdf($name, $title, $address, $email, $plan, $jalali_date, $sig_path, $contracts_dir);
    }
    
    // تلاش برای استفاده از Word
    try {
        // بررسی وجود کلاس PHPWord
        if (!class_exists('\PhpOffice\PhpWord\IOFactory')) {
            error_log('Honar Contract: PHPWord class not found');
            return honar_generate_html_pdf($name, $title, $address, $email, $plan, $jalali_date, $sig_path, $contracts_dir);
        }
        
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($templatePath);
        
        // جایگزینی متغیرها در Word
        $variables = [
            '${PARTNER_NAME}' => $name,
            '${PARTNER_TITLE}' => $title,
            '${PARTNER_ADDRESS}' => $address,
            '${PARTNER_EMAIL}' => $email,
            '${PLAN}' => $plan,
            '${DATE}' => $jalali_date,
            '${ORG_NAME}' => HONAR_ORG_NAME,
        ];
        
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text = $element->getText();
                    foreach ($variables as $key => $value) {
                        if (strpos($text, $key) !== false) {
                            $element->setText(str_replace($key, $value, $text));
                        }
                    }
                }
            }
        }
        
        // ذخیره به صورت HTML موقت برای تبدیل به PDF
        $htmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');
        $tempHtml = sys_get_temp_dir() . '/contract_' . time() . '.html';
        $htmlWriter->save($tempHtml);
        
        // خواندن HTML
        $html = file_get_contents($tempHtml);
        unlink($tempHtml);
        
        // اضافه کردن امضا به HTML
        $signatureHtml = "<div style='margin-top:30px;'><strong>امضای طرف دوم:</strong><br/><img src='file://" . $sig_path . "' style='max-width:300px;border:1px solid #ddd;'/></div>";
        $html = str_replace('</body>', $signatureHtml . '</body>', $html);
        
        // تولید PDF با Dompdf
        return honar_html_to_pdf($html, $contracts_dir);
        
    } catch (Exception $e) {
        error_log('Honar Contract Word Error: ' . $e->getMessage());
        // در صورت خطا، از روش HTML استفاده کن
        return honar_generate_html_pdf($name, $title, $address, $email, $plan, $jalali_date, $sig_path, $contracts_dir);
    }
}

/**
 * تولید PDF از HTML (روش جایگزین)
 */
function honar_generate_html_pdf($name, $title, $address, $email, $plan, $jalali_date, $sig_path, $contracts_dir) {
    $html = "
    <!DOCTYPE html>
    <html dir='rtl' lang='fa'>
    <head>
        <meta charset='utf-8'>
        <style>
            @page { margin: 30px; }
            body { 
                font-family: 'DejaVu Sans', Tahoma, Arial, sans-serif; 
                direction: rtl; 
                line-height: 1.8;
                color: #333;
            }
            .header { 
                text-align: center; 
                margin-bottom: 30px; 
                padding-bottom: 20px;
                border-bottom: 3px solid #0066cc;
            }
            .logo { max-height: 100px; margin-bottom: 10px; }
            h1 { 
                color: #0066cc; 
                margin: 10px 0;
                font-size: 24px;
            }
            h2 { 
                margin: 5px 0; 
                color: #555;
                font-size: 18px;
            }
            .section { 
                margin: 20px 0; 
                padding: 15px;
                background: #f9f9f9;
                border-right: 4px solid #0066cc;
            }
            .field-row {
                margin: 8px 0;
                font-size: 14px;
            }
            .field-label {
                display: inline-block;
                min-width: 120px;
                font-weight: bold;
                color: #0066cc;
            }
            .signature-section { 
                margin-top: 40px; 
                padding: 20px;
                background: #fff;
                border: 2px solid #ddd;
                border-radius: 5px;
            }
            .signature-img {
                max-width: 350px;
                border: 1px solid #ddd;
                padding: 5px;
                background: #fff;
            }
            .footer { 
                margin-top: 40px; 
                padding-top: 20px;
                border-top: 1px solid #ddd;
                font-size: 11px; 
                color: #777; 
                text-align: center;
            }
            .contract-body {
                margin: 25px 0;
                padding: 20px;
                line-height: 2;
                text-align: justify;
                background: #fff;
            }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>" . esc_html(HONAR_ORG_NAME) . "</h1>
            <h2>قرارداد همکاری</h2>
        </div>

        <div class='section'>
            <h3 style='margin-top:0;color:#0066cc;'>مشخصات طرف دوم (همکار)</h3>
            <div class='field-row'>
                <span class='field-label'>نام و نام خانوادگی:</span>
                <span>" . esc_html($name) . "</span>
            </div>
            <div class='field-row'>
                <span class='field-label'>سمت:</span>
                <span>" . esc_html($title) . "</span>
            </div>
            <div class='field-row'>
                <span class='field-label'>آدرس:</span>
                <span>" . esc_html($address) . "</span>
            </div>
            <div class='field-row'>
                <span class='field-label'>ایمیل:</span>
                <span>" . esc_html($email) . "</span>
            </div>
            <div class='field-row'>
                <span class='field-label'>طرح انتخابی:</span>
                <span>" . esc_html($plan) . "</span>
            </div>
            <div class='field-row'>
                <span class='field-label'>تاریخ قرارداد:</span>
                <span>" . esc_html($jalali_date) . "</span>
            </div>
        </div>

        <div class='contract-body'>
            <h3 style='color:#0066cc;'>شرایط و مفاد قرارداد</h3>
            <p>
                این قرارداد بین <strong>" . esc_html(HONAR_ORG_NAME) . "</strong> (طرف اول) و 
                <strong>" . esc_html($name) . "</strong> (طرف دوم) منعقد می‌گردد.
            </p>
            <p>
                طرف دوم متعهد می‌شود تمامی شرایط و مقررات مربوط به <strong>" . esc_html($plan) . "</strong> 
                را رعایت نموده و وظایف محوله را در چارچوب قرارداد انجام دهد.
            </p>
            <p>
                این قرارداد در تاریخ <strong>" . esc_html($jalali_date) . "</strong> به صورت الکترونیکی 
                با امضای دیجیتال طرفین منعقد گردیده و دارای اعتبار قانونی می‌باشد.
            </p>
        </div>

        <div class='signature-section'>
            <strong style='color:#0066cc;font-size:16px;'>امضای طرف دوم:</strong><br/><br/>
            <img src='file://" . $sig_path . "' class='signature-img' alt='امضای دیجیتال' />
            <p style='margin-top:15px;font-size:13px;color:#666;'>
                <strong>" . esc_html($name) . "</strong><br/>
                تاریخ: " . esc_html($jalali_date) . "
            </p>
        </div>

        <div class='footer'>
            این قرارداد به صورت الکترونیکی با امضای دیجیتال تولید شده و در سیستم آرشیو " . esc_html(HONAR_ORG_NAME) . " ذخیره گردیده است.<br/>
            تمامی حقوق این قرارداد محفوظ و تابع قوانین جمهوری اسلامی ایران می‌باشد.
        </div>
    </body>
    </html>
    ";
    
    return honar_html_to_pdf($html, $contracts_dir);
}

/**
 * تبدیل HTML به PDF
 */
function honar_html_to_pdf($html, $contracts_dir) {
    $vendor = plugin_dir_path(__FILE__) . 'vendor/autoload.php';
    
    if (!file_exists($vendor)) {
        error_log('Honar Contract: Cannot generate PDF - vendor not installed');
        return false;
    }
    
    require_once $vendor;
    
    try {
        // بررسی وجود کلاس Dompdf
        if (!class_exists('\Dompdf\Dompdf')) {
            error_log('Honar Contract: Dompdf class not found');
            return false;
        }
        
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isFontSubsettingEnabled', true);
        $options->setDefaultFont('DejaVu Sans');
        
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $pdf_output = $dompdf->output();
        $pdf_name = 'contract_' . time() . '_' . wp_rand(1000, 9999) . '.pdf';
        $pdf_path = trailingslashit($contracts_dir) . $pdf_name;
        
        if (!file_put_contents($pdf_path, $pdf_output)) {
            error_log('Honar Contract: Failed to write PDF file');
            return false;
        }
        
        return $pdf_path;
        
    } catch (Exception $e) {
        error_log('Honar PDF Error: ' . $e->getMessage());
        return false;
    }
}