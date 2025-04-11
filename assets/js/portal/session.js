//session out timer starts
var idleTime = 0;
const timerview = document.querySelector('#__loginExpiresIn');
// const gimsDIV = document.querySelector('#__dvGIMS');
// var gimsDIVPath = gimsDIV.dataset.umursPath;
var sto = timerview.dataset.sto;
var stp = timerview.dataset.stp;
var stlp = timerview.dataset.lp;
var display = '';
if (sto) {
    $(document).ready(function () {
        // Commented for may not 
        // var idleInterval = setInterval(timerIncrement, (sto * 60000) + 60000);
        $(this).mousemove(function (e) {
            idleTime = 0;
        });
        $(this).keypress(function (e) {
            idleTime = 0;
        });
    });

    function timerIncrement() {
        idleTime = idleTime + 1;
        // Paras
        if (idleTime >= sto) {
            // Prevent open redirect
            var safeUrl = new URL(stlp, window.location.origin);
            if (safeUrl.origin === window.location.origin) {
                window.location.replace(safeUrl.href);
            } else {
                alert('Invalid redirect URL');
            }
        }
        // $.ajax({
        //     type: 'POST',
        //     url: stp,
        //     success: function (res) {
        //         if (res === "ko") {
        //             window.location.replace(stlp);
        //         } else {
        //             alert('Your session is about to expire');
        //             startTimer(sto * 60, display);
        //         }
        //     }
        // });
}

    var times = null;

    function startTimer(duration, display) {
        var timer = duration+60,
            minutes,
            seconds;
        if (times !== null) {
            clearInterval(times);
        }
        times = setInterval(function () {
            minutes = parseInt(timer / 60, 10);
            seconds = parseInt(timer % 60, 10);
            minutes = minutes < 10 ?
                "0" + minutes :
                minutes;
            seconds = seconds < 10 ?
                "0" + seconds :
                seconds;
            display.html("Idle timeout in <b>" + minutes + ":" + seconds + "</b>");
            if (timer < 30) {
                display.addClass('text-danger blink_text_timer');
            } else {
                display.removeClass('text-danger blink_text_timer');
            }
            if (--timer < 0) {
                clearInterval(times);
                timerIncrement();
            }
        }, 1000);
    }
    $(document).ajaxComplete(function (event, xhr, settings) {
        // if (settings.url !== gimsDIVPath) {
            display = $('#__loginExpiresIn');
            startTimer(sto * 60, display);
        // }
    });
    jQuery(function ($) {
        display = $('#__loginExpiresIn');
        startTimer(sto * 60, display);
    });
}

$(document).ready(function () {
    // $('.popIcon').show();
    // var umurstime = setInterval(umurs, 5000);
});

$('body').on('click', '.popIcon', function () {
    // umurs();
    // $('#__dvGIMS').toggle();
});

// function umurs() {
//     var gimsB = $(".__dvGIMSBody");
//     var result = '';
//     var tm=0;
//     $.ajax({
//         type: "POST",
//         url: gimsDIVPath,
//         success: function (response) {
//             result += '';
//             if(response)
//             {
//                 $.each(response, function (index, values) {
//                     tm = tm+values.unread;
//                     result += '<div class="col text-left border-bottom p-2"><div class="badge badge-bill badge-success float-right" style="font-size: 1em">' + values.unread + '</div><span class="text-dark">' + values.name + '</span></br><small class="text-muted">' + values.designation + '</small></br> <small  class="text-muted">' + values.ou + '</small>';
//                     result += '</div>';
//                 });
//             }
//             // gimsB.show();
//             gimsB.html(result);
//             $('.gimsOV').html(tm);
//         },
//     });
// }

$('body').on('click', '.popup-close', function () {
    $('.__dvGIMS').hide();
});