/**
 * Honar Contract JavaScript
 * Version: 2.0
 * Enhanced with Persian date preview and PDF viewer
 */

(function($){
    'use strict';
    
    // Canvas signature variables
    var canvas = null;
    var ctx = null;
    var drawing = false;
    var lastPos = {x: 0, y: 0};

    /**
     * Persian Date Converter (Jalali)
     */
    function gregorianToJalali(gy, gm, gd) {
        var g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        
        if (gy > 1600) {
            var jy = 979;
            gy -= 1600;
        } else {
            var jy = 0;
            gy -= 621;
        }
        
        var gy2 = (gm > 2) ? (gy + 1) : gy;
        var days = (365 * gy) + (Math.floor((gy2 + 3) / 4)) - (Math.floor((gy2 + 99) / 100)) + 
                   (Math.floor((gy2 + 399) / 400)) - 80 + gd + g_d_m[gm - 1];
        
        jy += 33 * Math.floor(days / 12053);
        days %= 12053;
        jy += 4 * Math.floor(days / 1461);
        days %= 1461;
        
        if (days > 365) {
            jy += Math.floor((days - 1) / 365);
            days = (days - 1) % 365;
        }
        
        var jm, jd;
        if (days < 186) {
            jm = 1 + Math.floor(days / 31);
            jd = 1 + (days % 31);
        } else {
            jm = 7 + Math.floor((days - 186) / 30);
            jd = 1 + ((days - 186) % 30);
        }
        
        return [jy, jm, jd];
    }

    /**
     * Convert to Persian numerals
     */
    function toPersianNumerals(num) {
        var persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        return String(num).replace(/\d/g, function(digit) {
            return persianDigits[parseInt(digit)];
        });
    }

    /**
     * Format Jalali date
     */
    function formatJalaliDate(dateString) {
        if (!dateString) return 'در انتظار انتخاب تاریخ';
        
        var parts = dateString.split('-');
        if (parts.length !== 3) return dateString;
        
        var gy = parseInt(parts[0]);
        var gm = parseInt(parts[1]);
        var gd = parseInt(parts[2]);
        
        var jalali = gregorianToJalali(gy, gm, gd);
        
        var persianMonths = [
            '', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
            'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'
        ];
        
        var day = toPersianNumerals(jalali[2]);
        var month = persianMonths[jalali[1]];
        var year = toPersianNumerals(jalali[0]);
        
        return day + ' ' + month + ' ' + year;
    }

    /**
     * Initialize signature canvas
     */
    function initCanvas() {
        canvas = document.getElementById('honar-signature-pad');
        if (!canvas) return;
        
        ctx = canvas.getContext('2d');
        
        // Clear canvas with white background
        ctx.fillStyle = "#fff";
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.strokeStyle = "#000";
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        
        // Mouse events
        canvas.addEventListener('mousedown', function(e) {
            drawing = true;
            lastPos = getMousePos(canvas, e);
        });
        
        canvas.addEventListener('mousemove', function(e) {
            if (drawing) {
                var pos = getMousePos(canvas, e);
                drawLine(lastPos.x, lastPos.y, pos.x, pos.y);
                lastPos = pos;
            }
        });
        
        canvas.addEventListener('mouseup', function(e) {
            drawing = false;
        });
        
        canvas.addEventListener('mouseleave', function(e) {
            drawing = false;
        });
        
        // Touch events
        canvas.addEventListener('touchstart', function(e) {
            e.preventDefault();
            drawing = true;
            lastPos = getTouchPos(canvas, e);
        });
        
        canvas.addEventListener('touchmove', function(e) {
            e.preventDefault();
            if (drawing) {
                var pos = getTouchPos(canvas, e);
                drawLine(lastPos.x, lastPos.y, pos.x, pos.y);
                lastPos = pos;
            }
        });
        
        canvas.addEventListener('touchend', function(e) {
            drawing = false;
        });
        
        // Clear button
        var clearBtn = document.getElementById('honar-clear-sign');
        if (clearBtn) {
            clearBtn.addEventListener('click', function(e) {
                e.preventDefault();
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.fillStyle = "#fff";
                ctx.fillRect(0, 0, canvas.width, canvas.height);
            });
        }
    }

    function getMousePos(c, evt) {
        var rect = c.getBoundingClientRect();
        return {
            x: evt.clientX - rect.left,
            y: evt.clientY - rect.top
        };
    }

    function getTouchPos(c, evt) {
        var rect = c.getBoundingClientRect();
        var touch = evt.touches[0];
        return {
            x: touch.clientX - rect.left,
            y: touch.clientY - rect.top
        };
    }

    function drawLine(x1, y1, x2, y2) {
        ctx.beginPath();
        ctx.moveTo(x1, y1);
        ctx.lineTo(x2, y2);
        ctx.stroke();
        ctx.closePath();
    }

    /**
     * Validate signature before submission
     */
    function isCanvasBlank(canvas) {
        var blank = document.createElement('canvas');
        blank.width = canvas.width;
        blank.height = canvas.height;
        var blankCtx = blank.getContext('2d');
        blankCtx.fillStyle = "#fff";
        blankCtx.fillRect(0, 0, blank.width, blank.height);
        
        return canvas.toDataURL() === blank.toDataURL();
    }

    /**
     * Show PDF viewer
     */
    function showPDFViewer(pdfUrl) {
        var viewerHtml = '<div id="pdf-preview-section">' +
            '<h3>قرارداد شما</h3>' +
            '<iframe id="pdf-viewer" src="' + pdfUrl + '#toolbar=1&navpanes=0&scrollbar=1"></iframe>' +
            '<div style="margin-top:15px;text-align:center;">' +
            '<a href="' + pdfUrl + '" download class="download-btn" style="display:inline-block;padding:12px 30px;background:#0066cc;color:white;text-decoration:none;border-radius:8px;font-weight:600;transition:all 0.3s ease;">⬇️ دانلود قرارداد PDF</a>' +
            '</div>' +
            '</div>';
        
        $('#honar-result').after(viewerHtml);
    }

    /**
     * Document ready
     */
    $(document).ready(function() {
        // Initialize canvas
        initCanvas();
        
        // Date preview functionality
        $('input[name="contract_date"]').on('change', function() {
            var selectedDate = $(this).val();
            var jalaliDate = formatJalaliDate(selectedDate);
            $('#date-preview').html('تاریخ شمسی: <strong>' + jalaliDate + '</strong>');
        });
        
        // Form submission with enhanced validation
        $('#honar-contract-form').on('submit', function(e) {
            e.preventDefault();
            
            // Validate signature
            if (!canvas || isCanvasBlank(canvas)) {
                alert('لطفاً ابتدا امضای خود را وارد کنید.');
                return false;
            }
            
            // Encode signature
            var dataURL = canvas.toDataURL('image/png');
            $('#signature_data').val(dataURL);
            
            var form = $(this);
            var formData = form.serializeArray();
            
            // Add AJAX parameters
            formData.push({name: 'action', value: 'honar_contract_submit'});
            formData.push({name: 'nonce', value: HONAR_AJAX.nonce});
            
            var submitBtn = $('#honar-submit-btn');
            submitBtn.attr('disabled', true)
                     .html('<span class="loading-spinner"></span>در حال پردازش...');
            
            $.ajax({
                url: HONAR_AJAX.ajax_url,
                method: 'POST',
                data: formData,
                dataType: 'json'
            }).done(function(resp) {
                if (resp.success) {
                    var successMsg = '<div class="honar-success">' +
                        '<h3 style="margin-top:0;">✅ ' + resp.data.msg + '</h3>' +
                        '<p>قرارداد شما با موفقیت تولید شد و به ایمیل شما ارسال گردید.</p>' +
                        '<p>همچنین می‌توانید قرارداد را در زیر مشاهده و دانلود کنید.</p>' +
                        '</div>';
                    
                    $('#honar-result').html(successMsg).show();
                    $('#honar-contract-form').slideUp(300);
                    
                    // Show PDF viewer
                    setTimeout(function() {
                        showPDFViewer(resp.data.pdf_url);
                        
                        // Scroll to PDF viewer smoothly
                        $('html, body').animate({
                            scrollTop: $('#pdf-preview-section').offset().top - 20
                        }, 800);
                    }, 500);
                    
                } else {
                    var errorMsg = resp.data && resp.data.msg ? resp.data.msg : 'خطای نامشخص رخ داد';
                    $('#honar-result').html('<div class="honar-error">❌ ' + errorMsg + '</div>').show();
                    
                    // Scroll to error message
                    $('html, body').animate({
                        scrollTop: $('#honar-result').offset().top - 20
                    }, 500);
                }
            }).fail(function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                $('#honar-result').html(
                    '<div class="honar-error">❌ خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.</div>'
                ).show();
            }).always(function() {
                submitBtn.attr('disabled', false).text('تایید و تولید قرارداد');
            });
        });
        
        // Add smooth focus effect to inputs
        $('input, textarea').on('focus', function() {
            $(this).parent().addClass('field-focused');
        }).on('blur', function() {
            $(this).parent().removeClass('field-focused');
        });
        
        // Auto-resize canvas on window resize
        var resizeTimer;
        $(window).on('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (canvas) {
                    var parent = $(canvas).parent();
                    var maxWidth = parent.width();
                    if (canvas.width > maxWidth) {
                        $(canvas).css('width', '100%');
                    }
                }
            }, 250);
        });
    });
    
})(jQuery);
