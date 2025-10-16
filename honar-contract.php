<?php
/**
 * Plugin Name: Honar Maghz Contract
 * Description: قرارداد آنلاین با امضای دیجیتال — فرم، تولید PDF، ذخیره در uploads و ارسال ایمیل (برای مدیریت و کاربر).
 * Version: 1.0
 * Author: ChatGPT Helper
 */

// ------------------------
// === تنظیمات اولیه ===
// ------------------------
// لطفا آدرس لوگوی آپلود شده در کتابخانه وردپرس را در این متغیر قرار دهید.
// مثال: https://example.com/wp-content/uploads/2025/10/logo.png
if ( ! defined( 'HONAR_LOGO_URL' ) ) {
    define('HONAR_LOGO_URL', 'YOUR_LOGO_URL_HERE');
}
// ایمیل مدیریت (از شما) — از همان که فرستادید
if ( ! defined( 'HONAR_ADMIN_EMAIL' ) ) {
    define('HONAR_ADMIN_EMAIL', 'toopiloopi11@gmail.com');
}
// نام آموزشگاه
if ( ! defined( 'HONAR_ORG_NAME' ) ) {
    define('HONAR_ORG_NAME', 'آموزشگاه هنر مغز');
}
// پوشه ذخیره قراردادها (برحسب uploads)
if ( ! defined( 'HONAR_CONTRACT_DIR' ) ) {
    define('HONAR_CONTRACT_DIR', 'contracts');
}

// ------------------------
// === Enqueue scripts ===
// ------------------------
add_action('wp_enqueue_scripts', function(){
    wp_register_script('honar-contract-js', plugin_dir_url(__FILE__) . 'honar-contract.js', array('jquery'), '1.0', true);
    wp_localize_script('honar-contract-js', 'HONAR_AJAX', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('honar_contract_nonce'),
    ));
    wp_enqueue_script('honar-contract-js');

    wp_register_style('honar-contract-css', plugin_dir_url(__FILE__) . 'honar-contract.css');
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
                    <label>تاریخ</label>
                    <input type="date" name="contract_date" required />
                </div>

                <div class="field">
                    <label>امضا (با ماوس یا لمس)</label>
                    <div class="signature-wrap">
                        <canvas id="honar-signature-pad" width="600" height="160" style="border:1px solid #ddd;background:#fff"></canvas>
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
    // nonce
    if ( empty($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce'], 'honar_contract_nonce') ) {
        wp_send_json_error(['msg' => 'خطا: اعتبارسنجی ناموفق.']);
    }

    // دریافت و پاکسازی ورودی‌ها
    $name = isset($_POST['partner_name']) ? sanitize_text_field($_POST['partner_name']) : '';
    $title = isset($_POST['partner_title']) ? sanitize_text_field($_POST['partner_title']) : '';
    $address = isset($_POST['partner_address']) ? sanitize_text_field($_POST['partner_address']) : '';
    $email = isset($_POST['partner_email']) ? sanitize_email($_POST['partner_email']) : '';
    $plan = isset($_POST['plan']) ? sanitize_text_field($_POST['plan']) : '';
    $date = isset($_POST['contract_date']) ? sanitize_text_field($_POST['contract_date']) : '';
    $signature_data = isset($_POST['signature_data']) ? $_POST['signature_data'] : '';

    if ( empty($name) || empty($email) || empty($signature_data) ) {
        wp_send_json_error(['msg' => 'لطفاً نام، ایمیل و امضا را وارد کنید.']);
    }

    // پوشه ذخیره در uploads
    $upload_dir = wp_upload_dir();
    $contracts_dir = trailingslashit($upload_dir['basedir']) . HONAR_CONTRACT_DIR;
    if ( ! file_exists($contracts_dir) ) {
        wp_mkdir_p($contracts_dir);
    }

    // ذخیره تصویر امضا (base64 -> png)
    if ( preg_match('/^data:image\/png;base64,/', $signature_data) ) {
        $data = substr($signature_data, strpos($signature_data, ',') + 1);
        $data = base64_decode($data);
        $sig_filename = 'signature_' . time() . '_' . wp_rand(1000,9999) . '.png';
        $sig_path = trailingslashit($contracts_dir) . $sig_filename;
        file_put_contents($sig_path, $data);
    } else {
        wp_send_json_error(['msg' => 'امضای دریافتی نامعتبر است.']);
    }

    // === تولید HTML قرارداد ===
    $html = "
    <html dir='rtl' lang='fa'>
    <head>
        <meta charset='utf-8'>
        <style>
            body { font-family: DejaVu Sans, Tahoma, Arial; direction: rtl; }
            .header { text-align: center; margin-bottom: 20px; }
            .logo { max-height:80px; }
            h2 { margin:5px 0; }
            .section { margin-bottom: 10px; }
            .sig { margin-top:20px; }
            .small { font-size:12px; color:#555; }
            table { width:100%; }
        </style>
    </head>
    <body>
        <div class='header'>
            <img src='" . esc_url(HONAR_LOGO_URL) . "' class='logo' alt='logo' />
            <h2>" . esc_html(HONAR_ORG_NAME) . "</h2>
            <h3>قرارداد همکاری</h3>
        </div>

        <div class='section'>
            <strong>طرف دوم (همکار):</strong> " . esc_html($name) . "<br/>
            <strong>سمت:</strong> " . esc_html($title) . "<br/>
            <strong>آدرس:</strong> " . esc_html($address) . "<br/>
            <strong>ایمیل:</strong> " . esc_html($email) . "<br/>
            <strong>طرح انتخابی:</strong> " . esc_html($plan) . "<br/>
            <strong>تاریخ:</strong> " . esc_html($date) . "<br/>
        </div>

        <div class='section'>
            <p>متن قرارداد بر اساس توافق دو طرف (خلاصه در این PDF درج شده است). با امضای زیر، طرف دوم مفاد قرارداد را می‌پذیرد.</p>
        </div>

        <div class='sig'>
            <strong>امضای طرف دوم:</strong><br/>
            <img src='file://" . $sig_path . "' style='max-width:400px; border:1px solid #eee;' />
        </div>

        <div class='small'>
            این قرارداد به صورت الکترونیکی تولید شده و در آرشیو سیستم ذخیره شده است.
        </div>
    </body>
    </html>
    ";

    // === تولید PDF با Dompdf ===
    $vendor = plugin_dir_path(__FILE__) . 'vendor/autoload.php';
    if ( ! file_exists($vendor) ) {
        // خطا اگر Dompdf نصب نشده
        wp_send_json_error(['msg' => 'کتابخانه تولید PDF (dompdf) یافت نشد. لطفاً پوشه vendor را نصب کنید (composer require dompdf/dompdf) و در مسیر plugin/honar-contract/vendor قرار دهید.']);
    }
    require_once $vendor;
    try {
        $options = new Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdf_output = $dompdf->output();
        $pdf_name = 'contract_' . time() . '_' . wp_rand(1000,9999) . '.pdf';
        $pdf_path = trailingslashit($contracts_dir) . $pdf_name;
        file_put_contents($pdf_path, $pdf_output);

    } catch(Exception $e) {
        wp_send_json_error(['msg' => 'خطا در تولید PDF: ' . $e->getMessage()]);
    }

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
        'pdf_url' => $pdf_url
    ]);
}