var listpath = "";
var newpath = "";

function isValidURL(string) {
    var res = string.match(/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/);
    return res !== null;
}

$(document).ready(function () {
    var eleDBD = $(".btn-action-new");
    listpath = eleDBD.data('list-path');
    // Paras
    if (typeof listpath !== 'string' || !isValidURL(listpath)) {
        console.error('Invalid URL:', listpath);
        return;
    }
    newpath = eleDBD.data('new-path');

    var _lpp = {
        'pageURL': listpath,
        'cacheTime': new Date().toISOString()
    };
    localStorage.setItem('lpp', JSON.stringify(_lpp));
});

$('body').on('click', '.btn-action-new', function (e) {
    $.ajax({
        url: newpath,
        beforeSend: function () {
            $("#base_modal").modal('show');
            $("#base_modal .loading").append('<div class="overlay"><i class="fa fa-refresh fa-spin"></i></div>');
        },
        success: function (form) {
            $("#base_modal_body").html(form);
        },
        complete: function () {
            $("#base_modal .loading").find(".overlay, .loading-img").remove();
        }
    });
    e.preventDefault();
});

$('body').on('click', '.btn-action-edit', function (e) {
    var path = this.dataset.editPath;
    var objid = this.dataset.objid;
    $.ajax({
        url: path,
        data: {
            'objid': objid
        },
        type: "POST",
        beforeSend: function () {
            $("#base_modal").modal('show');
            $("#base_modal .loading").append('<div class="overlay"><i class="fa fa-refresh fa-spin"></i></div>');
        },
        success: function (form) {
            $("#base_modal_body").html(form);
        },
        complete: function () {
            $("#base_modal .loading").find(".overlay, .loading-img").remove();
        }
    });
    e.preventDefault();
});

$('body').on('click', '.btn-action-view', function (e) {
    var path = this.dataset.viewPath;
    var objid = this.dataset.objid;
    $.ajax({
        url: path,
        data: {
            'objid': objid
        },
        type: "POST",
        beforeSend: function () {
            $("#base_modal").modal('show');
            $("#base_modal .loading").append('<div class="overlay"><i class="fa fa-refresh fa-spin"></i></div>');
        },
        success: function (form) {
            $("#base_modal_body").html(form);
        },
        complete: function () {
            $("#base_modal .loading").find(".overlay, .loading-img").remove();
        }
    });
    e.preventDefault();
});

$('body').on('click', '.btn-action-delete', function (e) {
    var path = this.dataset.deletePath;
    var objid = this.dataset.objid;
    $.ajax({
        url: path,
        data: {'objid': objid},
        type: "POST",
        success: function (status) {
            var result = JSON.parse(status);
            flashMessage(result.status, result.message);
            $("#base_modal").modal('hide');
            refreshPagination();
        },
        complete: function () {
            $("#base_modal .loading").find(".overlay, .loading-img").remove();
        }
    });
    e.preventDefault();
});

$('body').on('click', '.btn-action-submit', function (e) {
    var theForm = $("#frmBaseModal");
    var path = theForm.attr("action");
    var objid = this.dataset.objid;
    var formData = "";
    if (objid == null) {
        formData = theForm.serialize();
    } else {
        formData = theForm.serialize() + '&objid=' + objid;
    }
    if (validateForm()) {
        $.ajax({
            url: path,
            type: "POST",
            data: formData,
            beforeSend: function () {
                $(".itsloading").show();
            },
            success: function (status) {
                var result = JSON.parse(status);
                if (result.status == "success") {
                    flashMessage("success", result.message);
                    $("#base_modal").modal('hide');
                    refreshPagination();
                } else {
                    $("#base_modal_body").html(result.form);
                    flashMessage("danger", result.message);
                }
            },
            complete: function () {
                $(".itsloading").fadeOut();
            }
        });
        e.preventDefault();
    }
});
$('body').on('click', '.btn-action-nfsubmit', function (e) {
    var path = this.dataset.actionPath;
    var objid = this.dataset.objid;
    if (validateForm()) {
        $.ajax({
            url: path,
            type: "POST",
            data: {
                'objid': objid
            },
            success: function (retdata) {
                if (retdata.status == "success") {
                    flashMessage("success", retdata.message)
                    $("#base_modal").modal('hide');
                    refreshPagination();
                } else {
                    $("#base_modal_body").html(retdata.form);
                }
            }
        });
        e.preventDefault();
    }
});

function validateForm() {
    var isValid = true;
    //$('input,textarea,select').filter('[required]:visible').each(function () {
    $('input,textarea,select').filter('[required]').each(function () {
        if ($(this).prop('required')) {
            form_error_clear(this);
            if ($(this).val() === '') {
                $("#" + this.id + "").parent().find('.chosen-container').addClass('error_box');
                $("#" + this.id + "").addClass('error_box');
                var labelname = $("label[for='" + this.id + "']").text();
                //                $("#" + this.id + "").parent().append("<span class='d-block'><span class='form-error-icon badge badge-danger text-uppercase'>Error</span> <span class='form-error-message'>" + labelname + "</span></span>");
                $("#" + this.id + "").parent().append("<div class='validation' style='color:#ff5b5b;  font-size: 12px; font-size: 12px;padding: 2px;'><span class='form-error-icon badge badge-danger text-uppercase'>Error</span><span class='form-error-message'> " + labelname + " is required </span></div>");
                isValid = false;
            }
            $("#" + this.id + "").on('blur, change', function () {
                form_error_clear(this);
                if ($(this).val() === '') {
                    $("#" + this.id + "").parent().find('.chosen-container').addClass('error_box');
                    var labelname = $("label[for='" + this.id + "']").text();
                    //                    $("#" + this.id + "").parent().append("<div class='col-sm-12 validation' style='color:#ff5b5b; text-transform: uppercase; font-size: 12px; font-size: 11px;padding: 2px;'><i class='fa fa-exclamation-triangle'></i>" + labelname + " is  required </div>");
                    $("#" + this.id + "").parent().append("<div class='validation' style='color:#ff5b5b;  font-size: 12px; font-size: 12px;padding: 2px;'><span class='form-error-icon badge badge-danger text-uppercase'>Error</span><span class='form-error-message'> " + labelname + " is required </span></div>");
                    isValid = false;
                } else {
                    form_error_clear(this);
                }
            });

        }
    });
    return isValid;
}

function form_error_clear(object) {
    $("#" + object.id + "").parent().find('.validation').remove();
    $("#" + object.id + "").parent().find('.chosen-container').removeClass('error_box');
    $("#" + object.id + "").removeClass('error_box');
}

$('#base_modal').on('click', '.btn-save-details', function (e) {
    var theForm = $("#frmBaseModal");
    var path = theForm.attr("action");
    var objid = this.dataset.objid;
    var formData = "";
    if (objid == null) {
        formData = theForm.serialize();
    } else {
        formData = theForm.serialize() + '&objid=' + objid;
    }
    if (validateForm()) {
        $.ajax({
            url: path,
            type: "POST",
            data: formData,
            beforeSend: function () {
                $(".itsloading").show();
            },
            success: function (status) {
                var result = JSON.parse(status);
                if (result.status === "success") {
                    flashMessage("success", result.message);
                    //                $("#base_modal").modal('hide');
                    refreshPagination();
                    $('#form1').find('.btn-save-details').remove();
                    $('#form2').removeClass('invisible');
                    $('#base_modal #scanFileUpload').attr('data-guid', result.objId);
                } else {
                    $("#base_modal_body").html(result.form);
                }
            },
            complete: function () {
                $(".itsloading").fadeOut();
            }
        });
    }
    e.preventDefault();
});

$('body').on('click', '.btn-action-submit-new', function (e) {
    var theForm = $("#frmBaseModal");
    var path = theForm.attr("action");
    var objid = this.dataset.objid;
    var formData = "";
    if (objid == null) {
        formData = theForm.serialize();
    } else {
        formData = theForm.serialize() + '&objid=' + objid;
    }
    if (validateForm()) {
        $.ajax({
            url: path,
            type: "POST",
            data: formData,
            beforeSend: function () {
                $(".itsloading").show();
            },
            success: function (status) {
                var result = JSON.parse(status);
                if (result.status == "success") {
                    flashMessage("success", result.message);
                    $("#base_modal").modal('hide');
                    //                    refreshPagination();
                    window.location.reload();
                } else {
                    $("#base_modal_body").html(result.form);
                }
            },
            complete: function () {
                $(".itsloading").fadeOut();
            }
        });
    }
    e.preventDefault();
});
$('body').on('click', '.btn-action-view-with-pagination', function (e) {
    var path = this.dataset.viewPath;
    var objid = this.dataset.objid;
    $.ajax({
        url: path,
        data: {
            'custom_filter_param': objid
        },
        type: "POST",
        beforeSend: function () {
            $("#base_modal").modal('show');
            $("#base_modal .loading").append('<div class="overlay"><i class="fa fa-refresh fa-spin"></i></div>');
        },
        success: function (form) {
            $("#base_modal_body").html('');
            $("#base_modal_body").html(form.form);
            $("#base_modal").find('.paginator-goto-button').addClass('paginator-goto-button-modal').removeClass('paginator-goto-button');
            $("#base_modal").find('#__KNP_goto_page').attr("id", "__KNP_goto_page_modal");
            $("#base_modal").find('.paginator-filter-button').addClass('paginator-filter-button-modal').removeClass('paginator-filter-button');
            $("#base_modal").find('#pagination-filterform').attr("id", "pagination-filterform-modal");
            $("#base_modal").find('.pagination-navigator').addClass('pagination-navigator-modal').removeClass('pagination-navigator');
            $("#base_modal").find('#custom_filter_param').attr("id", "custom_filter_param_modal");
        },
        complete: function () {
            $("#base_modal .loading").find(".overlay, .loading-img").remove();
        }
    });
    e.preventDefault();
});
$('body').on('click', '.btn-action-view-with-pagination-csv', function (e) {
    var path = this.dataset.viewPath;
    var objid = this.dataset.objid;
    $.ajax({
        url: path,
        data: {
            'objid': objid
        },
        type: "POST",
        beforeSend: function () {
            $("#base_modal").modal('show');
            $("#base_modal .loading").append('<div class="overlay"><i class="fa fa-refresh fa-spin"></i></div>');
        },
        success: function (response) {
            $("#base_modal_body").html('');
            $("#base_modal_body").html(response.form);
            $("#base_modal").find('.paginator-goto-button').addClass('paginator-goto-button-modal').removeClass('paginator-goto-button');
            $("#base_modal").find('#__KNP_goto_page').attr("id", "__KNP_goto_page_modal");
            $("#base_modal").find('.paginator-filter-button').addClass('paginator-filter-button-modal').removeClass('paginator-filter-button');
            $("#base_modal").find('#pagination-filterform').attr("id", "pagination-filterform-modal");
            $("#base_modal").find('.pagination-navigator').addClass('pagination-navigator-modal').removeClass('pagination-navigator');
            $("#base_modal").find('#custom_filter_param').attr("id", "custom_filter_param_modal");
        },
        complete: function () {
            $("#base_modal .loading").find(".overlay, .loading-img").remove();
        }
    });
    e.preventDefault();
});


$("body").on("change", ".optionChange", function (e) {
    e.preventDefault();
    var objid = $(this).val();
    var token = $("input[name='token']").val();
    var path = $(this).data('path');

    var nxtField1 = $(this).data('nxt-field1');
    var nxtField2 = $(this).data('nxt-field2');

    var nextEl1 = $('#' + nxtField1);
    var nextEl2 = $('#' + nxtField2);

    var labelname1 = $("label[for='" + nxtField1 + "']").text();
    var labelname2 = $("label[for='" + nxtField2 + "']").text();
    var val = $(this).val();
    $.ajax({
        url: path,
        data: {
            'obj': objid,
            'token': token
        },
        type: "POST",
        success: function (response) {
            nextEl1.html($("<option/>").val('').text('Select ' + labelname1 + ''));
            nextEl2.html($("<option/>").val('').text('Select ' + labelname2 + ''));
            nextEl2.trigger('chosen:updated');
            if (response.status === 'success') {
                $.each(response.data, function (index, values) {
                    nextEl1.append($("<option/>").val(index).text(values));
                });
                nextEl1.trigger('chosen:updated');
                $("input[name='token']").val(response.token);
            } else {
                flashMessage(response.status, response.message);
            }
        }
    });
});

$('body').on('click', '.btn-action-beta', function (e) {
    var path = this.dataset.viewPath;
    var objid = this.dataset.objid;
    $.ajax({
        url: path,
        data: {
            'objid': objid
        },
        type: "POST",
        beforeSend: function () {
            $("#base_modal").modal('show');
            $("#base_modal .loading").append('<div class="overlay"><i class="fa fa-refresh fa-spin"></i></div>');
        },
        success: function (form) {
            $("#base_modal_body").html(form);
        },
        complete: function () {
            $("#base_modal .loading").find(".overlay, .loading-img").remove();
        }
    });
    e.preventDefault();
});

$('body').on('change', '.listfilter', function (el) {
    var path = this.dataset.listFilterPath;
    var filter = $('#form_getOrganizationMinistry').val();
    var data = {
        'custom_filter_param': filter
    };
    $.ajax({
        url: path,
        data: data,
        type: "POST",
        //        context: this,
        success: function (data) {
            var table = $(data).find('#redirectPath').parent().html();
            if (table) {
                $("#replc_div").html(table);
            } else {
                $("#replc_div").html(data.filter);
                $('#redirectPath').html('<button class=" btn btn-block btn-social btn-info text-center col-md-12">No More Items</button>');
            }

        }
    });
});
//////////Dynamic Filter System///////////
$('#dfSelect').on('change', function () {
    if ($(this).val() === '') {
        $('#df-content').fadeOut();
    } else {
        $('#df-content').html($('#' + $(this).val()).html()).fadeIn();
        $('#value-' + $(this).val()).attr('required', true);
        datepicker();
    }
});
$('body').on('click', '.addApplyFilter', function (e) {
//    alert('clicked');
    var identifier = $(this).data('alias');
    if (addFilter(identifier)) {
        filterAction();
        $('.df-filter-button').addClass('invisible');
    }
    e.preventDefault();
    // $('#dfSelect').popover('hide');

});
$('body').on('click', '.addFilter', function () {
    var identifier = $(this).data('alias');
    addFilter(identifier);
});
function addFilter(identifier) {
    var existingFilterArray = $("#filterArray").val();
    if (!existingFilterArray) {
        dfJsonObj = {};
    } else {
        var dfJsonObj = JSON.parse(atob(existingFilterArray));
    }
    var filter = $('#dfSelect option:selected').text();
    var operatorText = $('#df-content #operator-' + identifier + ' option:selected').text();
    var operator = $('#df-content #operator-' + identifier).val();
    // var fvalue = $('#df-content #value-' + identifier).val();
    var fvalue = $('#df-content #value-' + identifier).hasClass('codefinder') ? $('#df-content #value-' + identifier).data('id') : $('#df-content #value-' + identifier).val();
    var fvalueText = $('#df-content #value-' + identifier).is('.choice, .boolean, .sboolean') ? $('#df-content #value-' + identifier + ' option:selected').text() : $('#df-content #value-' + identifier).val();
    var isValid = false;
    var fdv =  filter + " " + operatorText + " " + fvalueText ;
    if (validateForm()) {
        var tag = "<span id='f-tag-" + identifier + "' data-alias='" + identifier + "' class='badge badge-pill bg-white border p-2' style='font-size: 14px;'><b ><span id='__fdv" + identifier + "'></span></b><i class='fa fa-times badge-close text-danger pointer' aria-hidden='true'></i></span>";
        $("#f-tag-" + identifier).remove();
        $("#dfTags").append(tag);
        $("#__fdv"+ identifier).text(fdv);
        innerArray = {};
        filterArray = {};
        innerArray['operator'] = operator;
        innerArray['fvalue'] = fvalue;
        dfJsonObj[identifier] = innerArray;
        $("#filterArray").val(btoa(JSON.stringify(dfJsonObj)));
        $('#df-content').fadeOut();
        $('.df-filter-button').removeClass('invisible');
        $('#dfSelect').prop('selectedIndex', 0);
        isValid = true;
    }
    return isValid;
}

$('body').on('click', '.badge-close', function () {
    var existingFilterArray = $("#filterArray").val();
    var identifier = $(this).parent().data('alias');
    if (existingFilterArray) {
        var dfJsonObj = JSON.parse(atob(existingFilterArray));
    }
    delete dfJsonObj[identifier];
    $("#filterArray").val(btoa(JSON.stringify(dfJsonObj)));
    $(this).parent().remove();
    if (Object.keys(dfJsonObj).length === 0) {
        $('.df-filter-button').addClass('invisible');
    }
});

$('body').on('click', '.df-filter-button', function (e) {
    filterAction();
    e.preventDefault();
});
function filterAction() {
    $('#df-content').fadeOut();
    var pageURL = $("#filterArray").data('path');
    // Paras
    if (typeof pageURL !== 'string' || !isValidURL(pageURL)) {
        console.error('Invalid URL:', pageURL);
        return;
    }
    var pageFilters = $("#filterArray").val();
    var _lpp = {
        'pageURL': pageURL,
        'pageFilters': pageFilters,
        'cacheTime': new Date().toISOString()
    };
    localStorage.setItem('lpp', JSON.stringify(_lpp));
    $.ajax({
        type: "GET",
        url: pageURL,
        data: {'custom_filter_param': pageFilters},
        success: function (form) {
            $("#__listContainer").html(form);
        }
    });
}
///////////////////////////////////////////
//    CODE FINDER 

$('body').on('click', '.codeFinderAction', function (e) {
    e.preventDefault();
    var data = {};
    data['usage'] = this.dataset.use;
    path = document.querySelector('#cfPath').dataset.path;
    data['custfunction'] = this.dataset.custfunction;
    codeFinderAction(path, data);
});


function codeFinderAction(path, data, func) {
    //    var path = $('.options').data('codefinder');

    $.ajax({
        url: path,
        data: data,
        type: "POST",
        beforeSend: function () {
            $("#cf_modal").modal('show');
        },
        success: function (result) {
            if (result.type === 'success') {
                $('#cf_modal_content').html('');
                $("#cf_modal_content").html(result.form);
                $('#cf_modal_content').find('.modal-header').remove();
                $('#cf_modal_content').find('.modal-footer').remove();
                $('#cf_modal_content').find('.modal-body').css({
                    'padding-bottom': '0px'
                });
                if (result.title) {
                    $('#cf_modalLabel').html('Choose ' + result.title);
                } else {
                    $('#cf_modalLabel').html('Code Finder of ' + result.finderFor);
                }
                $('#cf_modal').modal({
                    show: true,
                    keyboard: false
                });
            } else {
                flashMessage(result.type, result.msg);
            }
        }
    });
}
//    CODE FINDER 

$('body').on('click', '.btn_codefinder_submit', function (e) {
    var theForm = $("#frmBaseModal");
    var codefinderId = $("#codefinder-id").data('id');
    var objid = this.dataset.objid;
    var path = theForm.attr("action");
    var formData = $(theForm).serialize() + '&codefinderId=' + codefinderId + '&objid=' + objid;
    if (validateForm()) {
        $.ajax({
            url: path,
            type: "POST",
            data: formData,
            beforeSend: function () {
                $(".itsloading").show();
            },
            success: function (status) {
                var result = JSON.parse(status);
                if (result.status === "success") {

                    flashMessage("success", result.message);
                    $("#base_modal").modal('hide');
                    refreshPagination();
                } else {
                    flashMessage("danger", result.message);
                    $("#base_modal_body").html('');
                    $("#base_modal_body").html(result.form);
                }
            },
            complete: function () {
                $(".itsloading").fadeOut();
            }
        });
        e.preventDefault();
    }
});

$('body .ou_state').val(0);
$('body').on('change', '.ou_state', function () {
    var stateVal = $(this).val();
    var path = $(this).data('path');
    if (stateVal === '') {
        $('body .ou_district').html($("<option/>").val('').text('------- Select --------'));
    } else {
        $.ajax({
            type: 'POST',
            url: path,
            data: {'stateVal': stateVal},
            dataType: "json",
            beforeSend: function () {
                $(".box").prepend('<div class="overlay"><i class="fa fa-refresh fa-spin"></i></div>');
            },
            success: function (data) {
                $('body .ou_district').html($("<option/>").val('').text('---Select District---'));
                $.each(data.result, function (index, values) {
                    $('body .ou_district').append($("<option/>").val(index).text(values));
                });
                $('body .ou_district').trigger('chosen:updated');
            },
            complete: function () {
                $(".box").find(".overlay, .loading-img").remove();
            }
        });
    }
});


function datepicker() {
    $('body .datePicker').datetimepicker({
        icons: {
            time: 'fa fa-clock-o',
            date: 'fa fa-calendar',
            up: 'fa fa-chevron-up',
            down: 'fa fa-chevron-down',
            previous: 'fa fa-chevron-left',
            next: 'fa fa-chevron-right',
            today: 'fa fa-check-circle-o',
            clear: 'fa fa-trash',
            close: 'fa fa-remove'
        },
        format: 'DD/MM/YYYY',
        minDate: $('.datePicker').attr('data-start-date')
    });
}

$('body').on('click', '.btn-edit', function (e) {
    var path = this.dataset.editPath;
    var id = this.dataset.id;
    $.ajax({
        url: path,
        data: {
            'id': id
        },
        type: "POST",
        beforeSend: function () {
            $("#base_modal").modal('show');
            $("#base_modal .loading").append('<div class="overlay"><i class="fa fa-refresh fa-spin"></i></div>');
        },
        success: function (form) {
            $("#base_modal_body").html(form);
        },
        complete: function () {
            $("#base_modal .loading").find(".overlay, .loading-img").remove();
        }
    });
    e.preventDefault();
});
