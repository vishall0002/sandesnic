import 'bootstrap/';
import 'jgrowl/';
import 'eonasdan-bootstrap-datetimepicker';
import 'chosen-js';
import 'sweetalert2';
import 'jquery-charactercounter-plugin';
import './bootstrap-confirmation.min.js';
import 'bootstrap/js/dist/tooltip';
import './jquery.upload.js';
var moment = require('moment');
require('daterangepicker');
var gobjid;
var start = moment().subtract(7, 'days');
var end = moment();
import NProgress from 'nprogress';

function isValidURL(string) {
    var res = string.match(/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/);
    return res !== null;
}

$('.input-daterange').daterangepicker({
    "showDropdowns": true,
    ranges: {
        'Today': [moment(), moment()],
        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
        'This Month': [moment().startOf('month'), moment().endOf('month')],
        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
    },
    "locale": {
        "format": "DD/MM/YYYY"
    },
    "startDate": start,
    "endDate": end,
    "minDate": "DD/MM/YYYY",
    "maxDate": "DD/MM/YYYY"
}, function (start, end, label) {
    console.log('New date range selected: ' + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD') + ' (predefined range: ' + label + ')');
});

window.flashMessage = flashMessage;
window.flashMessageObj = flashMessageObj;
window.refreshPagination = refreshPagination;
$(document).ready(function () {
    localStorage.setItem('lpp', null);
});
document.addEventListener("DOMContentLoaded", function () {
    var flashes = JSON.parse($('#server-flash-messages').val());
    $.each(flashes, function (type, message) {
        flashMessage(type, message);
    });

});

$(document).keyup(function (e) {
    if (e.key === "Escape") {
        $('.popper').fadeOut();
    }
});

$(function () {
    $('[data-toggle="tooltip"]').tooltip('toggleEnabled')
    $(document).on('click touchend', function (e) {
        var popover = $('.popper');
        var target = $(e.target);
        var row = target.closest('tr');
        if (row.hasClass('btn-action-trmenu') || row.hasClass('btn-action-rowmenu')) {

        } else {
            var targetP = target.closest('.popper');
            if (targetP.length == 0) {
                popover.hide();
            }
        }
    });
});

function genToolTips(){
    $('[data-toggle="tooltip"]').tooltip('toggleEnabled');
}
function flashMessage(type, msg) {
    var sticky = false;
    var life = 7000;
    var theme = '';
    switch (type) {
        case 'danger':
            theme = 'alert alert-danger h6 strong';
            life = 10000;
            break;
        case 'success':
            theme = 'alert alert-success h6';
            break;
        case 'warning':
            theme = 'alert alert-warning h6';
            break;
        case 'info':
            theme = 'alert alert-primary h6';
            break;
        default:
            theme = 'alert alert-danger h6';

    }
    $.jGrowl(msg, {
        sticky: sticky,
        position: "center",
        theme: theme,
        life: life,
        beforeOpen: function (e, m, o) {
            $(e).width("400px").height("50px");
        }
    });
}
function flashMessageObj(obj) {
    var fd = null;
    fd = JSON.parse(obj);
    var type = fd.status;
    var msg = fd.message;
    var sticky = false;
    var life = 7000;
    var theme = '';
    switch (type) {
        case 'danger':
            theme = 'alert alert-danger h6 strong';
            life = 10000;
            break;
        case 'success':
            theme = 'alert alert-success h6';
            break;
        case 'warning':
            theme = 'alert alert-warning h6';
            break;
        case 'info':
            theme = 'alert alert-primary h6';
            break;
        default:
            theme = 'alert alert-danger h6';

    }
    $.jGrowl(msg, {
        sticky: sticky,
        position: "center",
        theme: theme,
        life: life,
        beforeOpen: function (e, m, o) {
            $(e).width("400px").height("50px");
        }
    });
}

$("body .searchable").chosen({
    width: "100%"
});

$('[data-toggle=confirmation]').confirmation({
    rootSelector: '[data-toggle=confirmation]',
});

$(document).ajaxComplete(function () {
    $('.searchable').chosen({
        width: "100%"
    });
    $('[data-toggle=confirmation]').confirmation({
        rootSelector: '[data-toggle=confirmation]',
    });
});

$('body').on('click', '.pagination-navigator', function (e) {    
    var pageURL = $(this).attr('href');
    // Paras
    if (typeof pageURL !== 'string' || !isValidURL(pageURL)) {
        console.error('Invalid URL:', pageURL);
        return;
    }
    var _lpp = {
        'pageURL': pageURL,
        'pageFilters': 'NA',
        'cacheTime': new Date().toISOString()
    };
    localStorage.setItem('lpp', JSON.stringify(_lpp));
    var customData = $('#custom_filter_param').data('param-value');
    $.ajax({
        type: "GET",
        url: pageURL,
        data: {'custom_filter_param': customData},
        beforeSend: function () {
            $("#base_modal .loading").append('<div class="overlay"><i class="fa fa-refresh fa-spin"></i></div>');
        },
        success: function (form) {
            $("#__listContainer").html(form);
        },
        complete: function () {
            $("#base_modal .loading").find(".overlay, .loading-img").remove();
        }
    });
    e.preventDefault();
});

$('body').on('click', '.paginator-goto-button', function (e) {
    var gPage = $('#__KNP_goto_page').val();
    var pageURL = this.dataset.paginatorUrl;
    pageURL = pageURL.replace('__gPage__', gPage);
    if (typeof pageURL !== 'string' || !isValidURL(pageURL)) {
        console.error('Invalid URL:', pageURL);
        return;
    }
    var _lpp = {
        'pageURL': pageURL,
        'pageFilters': 'NA',
        'cacheTime': new Date().toISOString()
    };
    localStorage.setItem('lpp', JSON.stringify(_lpp));
    $.ajax({
        type: "GET",
        url: pageURL,
        beforeSend: function () {
            $("#itsloading").append('<div class="overlay"><i class="fa fa-refresh fa-spin"></i></div>');
        },
        success: function (form) {
            $("#__listContainer").html(form);
        },
        complete: function () {
            $("#base_modal .loading").find(".overlay, .loading-img").remove();
        }
    });
    e.preventDefault();
});

$('body').on('click', '.paginator-filter-button', function (e) {
    var form = $("#pagination-filterform");
    var pageURL = form.attr('action');
    //Paras
    if (typeof pageURL !== 'string' || !isValidURL(pageURL)) {
        console.error('Invalid URL:', pageURL);
        return;
    }
    var pageFilters = form.serialize();
    var custom_filter_param = $('#custom_filter_param').data('param-value');
    var _lpp = {
        'pageURL': pageURL,
        'pageFilters': pageFilters,
        'cacheTime': new Date().toISOString()
    };
    localStorage.setItem('lpp', JSON.stringify(_lpp));
    $.ajax({
        type: "GET",
        url: pageURL,
        data: pageFilters + "&custom_filter_param=" + custom_filter_param,
        beforeSend: function () {
            $("#base_modal .loading").append('<div class="overlay"><i class="fa fa-refresh fa-spin"></i></div>');
        },
        success: function (form) {
            // var data = $(form).find('#__listContainer').html();
            // $("#__listContainer").html(data);
            $("#__listContainer").html(form);
        },
        complete: function () {
            $("#base_modal .loading").find(".overlay, .loading-img").remove();
        }
    });
    e.preventDefault();
});

function refreshPagination() {
    var _lpp = JSON.parse(localStorage.getItem('lpp'));
    // proceed only if its within 30 minutes
    var nowTime = Date.now();
    var pageFilters = "";
    var cacheTime = new Date(_lpp.cacheTime);
    if ((nowTime - cacheTime.getTime()) / 1000 < 1800) {
        var pageURL = _lpp.pageURL;
        if (_lpp.pageFilters) {
            pageFilters = _lpp.pageFilters;
        }
        $.ajax({
            type: "GET",
            url: pageURL,
            data: pageFilters,
            beforeSend: function () {
                $("#base_modal .loading").append('<div class="overlay"><i class="fa fa-refresh fa-spin"></i></div>');
            },
            success: function (form) {
                // var data = $(form).find('#__listContainer').html();
                // $("#__listContainer").html(data);
                $("#__listContainer").html(form);
            },
            complete: function () {
                $("#base_modal .loading").find(".overlay, .loading-img").remove();
            }
        });
    } else {
        localStorage.setItem('lpp', null);
    }
    return true;
}

$('[data-toggle="datetimepicker"]').datetimepicker({
    //    icons: {
    //        time: 'fa fa-clock-o',
    //        date: 'fa fa-calendar',
    //        up: 'fa fa-chevron-up',
    //        down: 'fa fa-chevron-down',
    //        previous: 'fa fa-chevron-left',
    //        next: 'fa fa-chevron-right',
    //        today: 'fa fa-check-circle-o',
    //        clear: 'fa fa-trash',
    //        close: 'fa fa-remove'
    //    },
    //    format: 'LT'
    format: 'DD/MM/YYYY',
});
$('#gifPoper').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var imgSrc = button.data('href');
    var gifTitle = button.data('title');
    var modal = $(this);
    var img = $("<img />").attr('src', imgSrc).on('load', function () {
        if (!this.complete || typeof this.naturalWidth == "undefined" || this.naturalWidth == 0) {
            alert('broken image!');
        } else {
            modal.find('.modal-body ').html(img);
            modal.find('.modal-title ').text(gifTitle);
        }
    });

});

$('#base_modal').on('click', '.pagination-navigator-modal', function (e) {
    var pageURL = $(this).attr('href');
    // Paras
    if (typeof pageURL !== 'string' || !isValidURL(pageURL)) {
        console.error('Invalid URL:', pageURL);
        return;
    }
    var _lpp = {
        'pageURL': pageURL,
        'pageFilters': 'NA',
        'cacheTime': new Date().toISOString()
    };
    localStorage.setItem('lpp', JSON.stringify(_lpp));
    var customData = $('#custom_filter_param_modal').data('param-value');
    $.ajax({
        type: "POST",
        url: pageURL,
        data: {
            'custom_filter_param': customData
        },
        success: function (response) {
            //            $("#base_modal_body").html('');
            $("#base_modal_content").find('.pagination_content').html($(response.form).find('.pagination_content').html());
            paginationModal();
        }
    });
    e.preventDefault();
});

$('#base_modal').on('click', '.paginator-goto-button-modal', function (e) {
    var gPage = $('#__KNP_goto_page_modal').val();
    var pageURL = this.dataset.paginatorUrl;
    // Paras
    if (typeof pageURL !== 'string' || !isValidURL(pageURL)) {
        console.error('Invalid URL:', pageURL);
        return;
    }
    var customData = $('#custom_filter_param_modal').data('param-value');
    pageURL = pageURL.replace('__gPage__', gPage);
    var _lpp = {
        'pageURL': pageURL,
        'pageFilters': 'NA',
        'cacheTime': new Date().toISOString()
    };
    localStorage.setItem('lpp', JSON.stringify(_lpp));
    $.ajax({
        type: "POST",
        url: pageURL,
        data: {
            'custom_filter_param': customData
        },
        success: function (response) {
            $("#base_modal_content").find('.pagination_content').html($(response.form).find('.pagination_content').html());
            paginationModal();
        }
    });
    e.preventDefault();
});

$('#base_modal').on('click', '.paginator-filter-button-modal', function (e) {
    var form = $("#pagination-filterform-modal");
    var pageURL = form.attr('action');
    // Paras
    if (typeof pageURL !== 'string' || !isValidURL(pageURL)) {
        console.error('Invalid URL:', pageURL);
        return;
    }
    var pageFilters = form.serialize();
    var _lpp = {
        'pageURL': pageURL,
        'pageFilters': pageFilters,
        'cacheTime': new Date().toISOString()
    };
    localStorage.setItem('lpp', JSON.stringify(_lpp));
    var customData = $('#custom_filter_param_modal').data('param-value');
    $.ajax({
        type: "GET",
        url: pageURL,
        data: pageFilters + '&custom_filter_param=' + customData,
        success: function (response) {
            $("#base_modal_content").find('.pagination_content').html($(response.form).find('.pagination_content').html());
            paginationModal();
        }
    });
    e.preventDefault();
});

function paginationModal() {
    $("#base_modal").find('.paginator-goto-button').addClass('paginator-goto-button-modal').removeClass('paginator-goto-button');
    $("#base_modal").find('#__KNP_goto_page').attr("id", "__KNP_goto_page_modal");
    $("#base_modal").find('.paginator-filter-button').addClass('paginator-filter-button-modal').removeClass('paginator-filter-button');
    $("#base_modal").find('#pagination-filterform').attr("id", "pagination-filterform-modal");
    $("#base_modal").find('.pagination-navigator').addClass('pagination-navigator-modal').removeClass('pagination-navigator');
    $("#base_modal").find('#custom_filter_param').attr("id", "custom_filter_param_modal");
}

//display servertime 
$(document).ready(function () {
    function checkTime(i) {
        i = ("0" + i).slice(-2);
        return i;
    }
    var serverTime = $('#timeShow').data('time');
    var today = new Date(serverTime * 1000);

    function startTime() {
        var h = today.getHours();
        var m = today.getMinutes();
        var s = today.getSeconds();
        var month = today.getMonth() + 1;
        var day = today.getDate();
        var year = today.getFullYear();
        //converting to 12 hour format
        var ampm = h >= 12 ? 'pm' : 'am';
        h = h % 12;
        h = h ? h : 12;
        // add a zero in front of numbers<10
        m = checkTime(m);
        s = checkTime(s);
        h = checkTime(h);
        month = checkTime(month);
        day = checkTime(day);
        var dd = document.getElementById('timeShow');
        if (dd) {
            dd.innerHTML = day + "-" + month + "-" + year + " " + h + ":" + m + ":" + s + " " + ampm;
        }
        setTimeout(function () {
            today = new Date(today.getTime() + 1000 * 1);
            startTime();
        }, 1000);
    }
    startTime();
});


$('.switch_role').on('click', function (e) {
    var objId = this.dataset.objid;
    var path = this.dataset.path;
    $.ajax({
        url: path,
        data: {
            'objid': objId
        },
        type: "POST",
        success: function (resp) {
            if (resp.status === 'success') {
                // Paras
                var safeUrl = new URL(resp.path, window.location.origin);
                if (safeUrl.origin === window.location.origin) {
                    window.location.href = safeUrl.href;
                } else {
                    flashMessage('danger', 'Invalid redirect URL');
                }
            } else {
                flashMessage('danger', 'Something went wrong');
            }
        }
    });
    e.preventDefault();
});

$('body').on('click', '.sendMessage', function (e) {
    e.stopPropagation();
    var type = this.dataset.type;
    var data = {};
    var msgData = [];
    data['objid'] = this.dataset.objid;
    data['objname'] = this.dataset.dname;
    msgData.push(data);
    var jdata = JSON.stringify(msgData);
    var path = this.dataset.path;
    $.ajax({
        type: 'POST',
        url: path,
        data: {'type':type,'data':jdata},
        dataType: "json",
        success: function (data) {
            $("#base_modal").modal('show');
            $("#base_modal_body").html(data);
        }
    });

    $("#smMessage").characterCounter({
        maxChars: 1000,
        postCountMessage: "characters left",
        postCountMessageSingular: "character left",
        zeroRemainingMessage: "No characters left",
        overrunPreCountMessage: "Please remove",
        overrunPostCountMessage: "characters",
        overrunPostCountMessageSingular: "character",
        positiveOverruns: true
    });
});

$('#base_modal').on('click', '.smSend', function (e) {
    var path = this.dataset.path;
    var form = $("#smForm");
    var serializedForm = form.serialize();
   
    if ($('#smMessage').val() === '') {
        flashMessage('danger', 'Message Is Mandatory!!');
        return false;
    }
    $.ajax({
        type: 'POST',
        url: path,
        data: serializedForm,
        dataType: "json",
        success: function (data) {
            flashMessage(data.status, data.message);
            $("#base_modal").modal('hide');
        }
    });

});

$('body').on('click', '.selectmember', function (e) {
    var guid =this.dataset.guid;
    var objname = this.dataset.objname;
    var existingData = $('body .sendMessage').attr('data-data');
    var data = {};
    if ($(this).is(':checked')) {
        if (existingData === '') {
            existingData = [];
            data['objid'] = guid;
            data['objname'] = objname;
            existingData.push(data);
        } else {
            existingData = JSON.parse(existingData);
            if (existingData.guid !== guid) {
                data['objid'] = guid;
                data['objname'] = objname;
                existingData.push(data);
            }
        }
    } else {
        existingData = JSON.parse(existingData);
        $.each(existingData, function (k, val) {
            if (val.guid === guid) {
                existingData.splice(k, 1);
                return false;
            }
        });
    }
    $('body .sendMessage').attr('data-data', JSON.stringify(existingData));
});

$('body').on('click', '#search-list', function (e) {
   
    var path = this.dataset.path;
    var objid = $('.hdn-data-objid').val();
    var dateRange = $('.input-daterange').val();
    $.ajax({
        url: path,
        type: "POST",
        data: {'objid': objid, 'input-daterange': dateRange},
        success: function (resp) {
            var table = $(resp).find('#replace_table_val').parent().html();
            if (table) {
                $("#replace-tbl").html(table);
            } else {
                $("#replace-tbl").html($(resp).find('.no_data_msg').html());
            }
        }
    });
    e.preventDefault();
});

$('body').on('click', '.btn_top_bottom_statistics', function (e) {
    var path = this.dataset.path;
    var objid = $('#hdn-data-objid-tbr').val();
    var dateRange = $('.input-daterange').val();
    var record = $('.text_records').val();
    var type=this.dataset.type;
    $.ajax({
        url: path,
        type: "POST",
        data: {'objid': objid, 'input-daterange': dateRange, 'record' :record, 'type': type },
        success: function (data) {
            var response = JSON.parse(data);
            if(response.status == "success")
            {
                $("#states_list").html(response.res1);
                $("#hog_list").html(response.res2);
                $("#usres_list").html(response.res3);
            }
            }
    });
    e.preventDefault();
});

$('body').on('click', '.btn-version', function (e) {
    var path = this.dataset.versionPath;
    var objid = $(this).data('guid');
    var type = $(this).data('type');    
    $.ajax({
        url: path,
        data: {
            'objid': objid,
            'type': type,
        },
        type: "POST",
        success: function (response) {            
            $('#version').html(response);
        }
    });
    e.preventDefault();
});


$('body').on('click', '.csv_download', function (e) {
    var path = this.dataset.path;
    var actionPath = this.dataset.actionPath;
    var pageFilters = $("#filterArray").val();
    var cust_field_val = $(".cust_field_val").val();
    $.ajax({
        type: 'POST',
        url: path,
        data: {'actionPath': actionPath, 'custom_filter_param': pageFilters, 'cust_field_val': cust_field_val},
        success: function (form) {
            $("#mini_base_modal").modal('show');
            $("#mini_base_modal_body").html(form);
        }
    });
    e.preventDefault();
});


$('body').on('click', '.csv_download_generic_submit', function (e) {
    e.preventDefault();
    var np = $('#csvDownload_new_password').val();
    var cp = $('#csvDownload_confirm_password').val();
    var type = 'error';
    var msg = '';
    if (!np && !cp) {
        msg = 'New Password cannot ne left blank!';
        showErrorAndSuccess($('#csvDownload_new_password'), type, msg, 'csvDownload_new_password');

        msg = 'Confirm Password cannot ne left blank!';
        showErrorAndSuccess($('#csvDownload_confirm_password'), type, msg, 'csvDownload_confirm_password');
        return false;
    }
    if (!np) {
        msg = 'New Password cannot ne left blank!!';
        showErrorAndSuccess($('#csvDownload_new_password'), type, msg, 'csvDownload_new_password');
        return false;
    }
    if (!cp) {
        msg = 'Confirm Password cannot ne left blank!!';
        showErrorAndSuccess($('#csvDownload_confirm_password'), type, msg, 'csvDownload_confirm_password');
        return false;
    }
    if (np !== cp) {
        msg = 'New password and confirm password doesnot match!';
        showErrorAndSuccess($('#csvDownload_confirm_password'), type, msg, 'csvDownload_confirm_password');
        return false;
    }
    document.getElementById("frmCsvDownload").submit();
    $("#mini_base_modal").modal('hide');
});

function showErrorAndSuccess(ob, type, msg, fieldId) {
    if (!msg) {
        msg = null;
    }
      ob.siblings(".help-block").remove();
    if (type === 'error') {
        ob.siblings("#"+fieldId+"-error").remove();
        ob.closest(".form-group").removeClass("has-success has-success").addClass("has-error");
        ob.val('');
        ob.closest("div").append('<span id="' + fieldId + '-error" class="text-danger">' + msg + '</span>');
    } else {
        ob.closest(".form-group").removeClass("has-error has-success").addClass("has-success");
        ob.siblings(".help-block").remove();
        ob.closest("div").append('<span id="' + fieldId + '-error" class="text-success">' + msg + '</span>');
    }
}

$("body").on("change", "#msgFileUpload", function (e) {
    e.preventDefault();
    var guid = $(this).data('guid');
    var uPath = $(this).data('upath');
    var mode = $(this).data('mode');
    var fileData = $('#msgFileUpload').prop('files')[0];
    if (fileData == undefined) {
        flashMessage('danger', 'Select a file to upload');
        return false;
    }
    var fileType = fileData.name.split('.').pop(),
    allowedtypes = 'png,jpg,pdf,jpeg';
    if (allowedtypes.indexOf(fileType.toLowerCase()) < 0) {
        flashMessage('danger', 'File type is not allowed [png,pdf,jpg are supported]');
        return false;
    }
    $('.progress-bar').css('width', '0%').attr('aria-valuenow', 0);
    $.upload(uPath, new FormData(fileinfo)).progress(function (progressEvent, upload) {
        if (progressEvent.lengthComputable) {
            var percent = Math.round(progressEvent.loaded * 100 / progressEvent.total);
            $('.progress-bar').css('width', percent + '%').attr('aria-valuenow', percent);
            $('.progress-bar').html(percent + '%');
            if (upload) {
                console.log(percent + ' uploaded');
            } else {
                console.log(percent + ' downloaded');
            }
        }
    }).done(function (res) {
        var $type = 'success';
        if (res.error) {
            $type = 'danger';
            flashMessage($type, res.message);
        } else {
            flashMessage($type, res.message);
            $("#frf").val(res.frf);
        }
    });
    return false;
});

$(document).ajaxStart(function () {
   NProgress.start();
}).ajaxComplete(function () {
   if ($('.invalid-feedback').is(':visible')) {
       var error_block = $('.invalid-feedback');
       error_block.parents('.form-group').find('input,textarea,select').val('');
       $('.is-invalid').val('');
   }
   NProgress.done();
});

$("body").on("keypress", ".preview_textarea", function (e) {
    var data = $(this).val();
    $('.preview_view').html(data);
});

// Paras
function printMessage() {
    alert("You are not allowed to use developer tools");
}
document.addEventListener("contextmenu", function(e) {
    printMessage();
    e.preventDefault();  // Disable right-click context menu
});
document.addEventListener("keydown", function(e) {
// Block F12
if (e.keyCode == 123) {
    printMessage();
    e.preventDefault();
}
// Block Ctrl + Shift + I (Inspect Element)
if (e.ctrlKey && e.shiftKey && e.keyCode == 73) {
    printMessage();
    e.preventDefault();
}
// Block Ctrl + Shift + J (Developer Tools console)
if (e.ctrlKey && e.shiftKey && e.keyCode == 74) {
    printMessage();
    e.preventDefault();
}
});
