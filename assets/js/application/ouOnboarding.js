//$('body').on('focusout', '.mobile-check', function (e) {
//    e.preventDefault();
//    var path = $(this).data('path');
//    var method = $(this).data('method');
//    var data = $(this).val();
//    if (data) {
//        $.ajax({
//            url: path,
//            data: {'data': data, 'method': method},
//            type: "POST",
//            beforeSend: function () {
//                $(".page-loader .loading").append('<div class="overlay"><i class="fa fa-refresh fa-spin"></i></div>');
//            },
//            success: function (response) {
//                if (response.type === 'danger') {
//                    flashMessage(response.type, response.msg);
//                    return false;
//                }
//                var ob = $(this);
//                if (response.response === true) {
//                    type = 'error';
//                    msg = 'Mobile number Already Exists.';
////                    $(this).siblings(".help-block").remove();
////                    $(this).closest(".form-group").removeClass("has-success has-success").addClass("has-error");
////                    $(this).val('');
////                    $(this).closest("div").append('<span id="'+$(this)+'-error" class="help-block">This mobile number already exists.</span>');
//                } else if (response.response === false) {
////                    $(this).closest(".form-group").removeClass("has-error has-success").addClass("has-success");
////                    $(this).siblings(".help-block").remove();
//                }
//                showErrorAndSuccess(ob, type, msg);
//            },
//            complete: function () {
//                $(".page-loader .loading").find(".overlay, .loading-img").remove();
//            }
//        });
//    }
//});
//function showErrorAndSuccess(ob, type, msg) {
//    if (!msg) {
//        msg = null;
//    }
//    if (type === 'error') {
//        ob.siblings(".help-block").remove();
//        ob.closest(".form-group").removeClass("has-success has-success").addClass("has-error");
//        ob.val('');
//        ob.closest("div").append('<span id="' + ob.id + '-error" class="help-block">' + msg + '</span>');
//    } else {
//        ob.closest(".form-group").removeClass("has-error has-success").addClass("has-success");
//        ob.siblings(".help-block").remove();
//    }
//}

//import '../../vendors/chosen.jquery.min.js';
$(function () {
    $('body').on('click', '.btn-ou-ob-view', function (e) {
        var path = this.dataset.viewPath;
        $.ajax({
            url: path,
            type: "POST",
            beforeSend: function () {
                // $("#base_modal").modal('show');
                // $("#base_modal .loading").append('<div class="overlay"><i class="fa fa-refresh fa-spin"></i></div>');
            },
            success: function (response) {
                // $("#base_modal").modal('close');
//                $('#base_modal').modal();
                $("#base_modal").modal('show');
                $("#base_modal_content").html(response.form);
            },
            complete: function () {
                // $("#base_modal .loading").find(".overlay, .loading-img").remove();
            }
        });
        e.preventDefault();
    });

});
