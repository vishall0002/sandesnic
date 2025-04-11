import './file-validator.js';
import './generic.js';

$(".dropdownSearch").chosen({
    width: "100%"
});


$('body').on('change', '.oubox', function (e) {
    buildGN();
});

$('body').on('change', '.gtbox', function (e) {
    buildGN();
});

$('body').on('keyup', '.txtGroupName', function (e) {
    buildGN();
});

$('body').on('click', '.addPublisher', function (e) {
    var newpath = $(this).data('path');
    var objid = this.dataset.objid;

    $.ajax({
        url: newpath,
        data: {'objid': objid},
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

$('body').on('click', '.deleteSubscriber', function (e) {
    var newpath = this.dataset.path;
    var objid = this.parentNode.dataset.objid;
    var type = this.dataset.subtype;

    $.ajax({
        url: newpath,
        data: {'objid': objid, 'type': type},
        type: "POST",
        beforeSend: function () {
        },
        success: function (response) {
            var res = JSON.parse(response);
            if ($('#' + type + ' tbody tr').length > 1) {
                $('#' + type + ' td[data-objid="' + objid + '"]').parent().remove();
            } else {
                $('#' + type).remove();
                $('#' + type + 'Head').remove();
            }
            flashMessage(res.status, res.message);
        },
        complete: function () {
            $('#' + type + ' tbody tr').each(function (index) {
                $(this).find('td:first').html(index + 1);
            });
        }
    });
    e.preventDefault();
});

$('body').on('click', '.publishList', function (e) {
    var newpath = this.dataset.path;
    var objid = this.dataset.objid;
    var action = this.dataset.action;
    var el = this;
    $.ajax({
        url: newpath,
        data: {'objid': objid, 'action': action},
        type: "POST",
        beforeSend: function () {
        },
        success: function (response) {
            var res = JSON.parse(response);
            flashMessage(res.status, res.message);
            if (action === 'PUBLISH') {
                $('.publishList').text('UNPUBLISH');
                $('.publishList').attr('data-action', 'UNPUBLISH');
                $('.publishList').removeClass('btn-success');
                $('.publishList').addClass('btn-warning');
            } else {
                $('.publishList').text('PUBLISH');
                $('.publishList').attr('data-action', 'PUBLISH');
                $('.publishList').removeClass('btn-warning');
                $('.publishList').addClass('btn-success');
            }
        },
        complete: function () {
        }
    });
    e.preventDefault();
});

$('body').on('click', '.addPublisherSubmit', function (e) {
    var theForm = $("#frmPublisher");
    var newpath = this.dataset.path;
    var objid = this.dataset.objid;
    var formData = "";
    if (objid == null)
    {
        formData = theForm.serialize();
    } else {
        formData = theForm.serialize() + '&objid=' + objid;
    }
    if (validateForm()) {
        $.ajax({
            url: newpath,
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

                    if ($('#ListPublisher tr').length > 0) {
                        $('#ListPublisher tr:last').after(result.tr);
                    } else {
                        var table = "<table id='ListPublisher' class='table table-striped table-bordered table-hover table-sm'><thead><tr><th width='5%'>SlNo.</th>" + result.th + "<th width='10%'>Action</th></tr><tbody>" + result.tr + "</tbody></table>";
                        $('#listSubscribers').append(table);
                    }
                    $('#ListPublisher tbody tr').each(function (index) {
                        $(this).find('td:first').html(index + 1);
                    });
                } else {
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
function buildGN() {
    var oubox = $('.oubox');
    var gtbox = $('.gtbox');
    var txtGN = $('.txtGroupName');
    var txtGNRO = $('.txtGroupNameRO');
    var gtboxVal = "";
    var ouboxVal = "";
    if (gtbox.find(':selected').data('suffix')) {
        gtboxVal = gtbox.find(':selected').data('suffix')
    }
    if (oubox.is('select')) {
        if (oubox.find(':selected').data('prefix')) {
            ouboxVal = oubox.find(':selected').data('prefix')
        }
    } else {
        if (oubox.data('prefix')) {
            ouboxVal = oubox.data('prefix')
        }
    }
    if (txtGN.val() === "") {
        txtGNRO.val("");
    } else {
        txtGNRO.val((ouboxVal + '-' + txtGN.val() + gtboxVal).toLowerCase());
    }
}

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
