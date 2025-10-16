(function($){
    // Canvas signature
    var canvas = null;
    var ctx = null;
    var drawing = false;
    var lastPos = {x:0,y:0};

    function initCanvas(){
        canvas = document.getElementById('honar-signature-pad');
        if(!canvas) return;
        ctx = canvas.getContext('2d');
        ctx.fillStyle = "#fff";
        ctx.fillRect(0,0,canvas.width, canvas.height);
        ctx.strokeStyle = "#000";
        ctx.lineWidth = 2;
        // mouse
        canvas.addEventListener('mousedown', function(e){ drawing = true; lastPos = getMousePos(canvas,e); });
        canvas.addEventListener('mousemove', function(e){ if(drawing){ var p = getMousePos(canvas,e); drawLine(lastPos.x,lastPos.y,p.x,p.y); lastPos = p; }});
        canvas.addEventListener('mouseup', function(e){ drawing=false; });
        canvas.addEventListener('mouseleave', function(e){ drawing=false; });

        // touch
        canvas.addEventListener('touchstart', function(e){ e.preventDefault(); drawing=true; lastPos = getTouchPos(canvas,e); });
        canvas.addEventListener('touchmove', function(e){ e.preventDefault(); if(drawing){ var p = getTouchPos(canvas,e); drawLine(lastPos.x,lastPos.y,p.x,p.y); lastPos = p; }});
        canvas.addEventListener('touchend', function(e){ drawing=false; });

        document.getElementById('honar-clear-sign').addEventListener('click', function(e){
            e.preventDefault();
            ctx.clearRect(0,0,canvas.width,canvas.height);
            ctx.fillStyle = "#fff";
            ctx.fillRect(0,0,canvas.width,canvas.height);
        });
    }
    function getMousePos(c, evt){
        var rect = c.getBoundingClientRect();
        return { x: evt.clientX - rect.left, y: evt.clientY - rect.top };
    }
    function getTouchPos(c, evt){
        var rect = c.getBoundingClientRect();
        var touch = evt.touches[0];
        return { x: touch.clientX - rect.left, y: touch.clientY - rect.top };
    }
    function drawLine(x1,y1,x2,y2){
        ctx.beginPath();
        ctx.moveTo(x1,y1);
        ctx.lineTo(x2,y2);
        ctx.stroke();
        ctx.closePath();
    }

    $(document).ready(function(){
        initCanvas();

        $('#honar-contract-form').on('submit', function(e){
            e.preventDefault();
            // encode signature
            var dataURL = canvas.toDataURL('image/png');
            $('#signature_data').val(dataURL);

            var form = $(this);
            var formData = form.serializeArray();
            // add nonce for AJAX (from localized script)
            formData.push({name:'action', value:'honar_contract_submit'});
            formData.push({name:'nonce', value: HONAR_AJAX.nonce});

            $('#honar-submit-btn').attr('disabled',true).text('در حال پردازش...');

            $.ajax({
                url: HONAR_AJAX.ajax_url,
                method: 'POST',
                data: formData,
                dataType: 'json'
            }).done(function(resp){
                if(resp.success){
                    $('#honar-result').html('<div class="honar-success">'+ resp.data.msg +'<br/><a href="'+ resp.data.pdf_url +'" target="_blank">دانلود قرارداد (نسخه PDF)</a></div>').show();
                    $('#honar-contract-form').hide();
                } else {
                    $('#honar-result').html('<div class="honar-error">'+ (resp.data && resp.data.msg ? resp.data.msg : 'خطا رخ داد') +'</div>').show();
                }
            }).fail(function(){
                $('#honar-result').html('<div class="honar-error">خطا در ارسال داده. لطفاً دوباره تلاش کنید.</div>').show();
            }).always(function(){
                $('#honar-submit-btn').attr('disabled',false).text('تایید و تولید قرارداد');
            });
        });
    });
})(jQuery);