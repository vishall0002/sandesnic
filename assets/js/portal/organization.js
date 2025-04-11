
var listpath = "";
var newpath = "";

function isValidURL(string) {
    var res = string.match(/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/);
    return res !== null;
}

$(document).ready(function () {
    var eleDBD = $(".btn-portal-o-new");
    listpath = eleDBD.data('list-path');
    // Paras
    if (typeof listpath !== 'string' || !isValidURL(listpath)) {
        console.error('Invalid URL:', listpath);
        return;
    }
    newpath = eleDBD.data('new-path');

    var _lpp = { 'pageURL': listpath, 'cacheTime': new Date().toISOString() };
    localStorage.setItem('lpp', JSON.stringify(_lpp));
});

$('body').on('click', '.btn-portal-o-new', function (e) {
    $.ajax({
        url: newpath,
        beforeSend: function () {
            $("#base_modal").modal('show');
            $("#base_modal .loading").append('<div class="overlay"><i class="fa fa-refresh fa-spin"></i></div>');
        },
        success: function (form) {
//            $("#base_modal_content").html(form);
            $("#base_modal_body").html(form);
        },
        complete: function () {
            $("#base_modal .loading").find(".overlay, .loading-img").remove();
        }
    });
    e.preventDefault();
});

$('body').on('click', '.btn-portal-o-edit', function (e) {
    var path = this.dataset.editPath;
    var objId = $(this).data('guid');
    $.ajax({
        url: path,
        data: objId,
        type: "POST",
        beforeSend: function () {
            $("#base_modal").modal('show');
            $("#base_modal .loading").append('<div class="overlay"><i class="fa fa-refresh fa-spin"></i></div>');
        },
        success: function (form) {
//            $("#base_modal_content").html(form);
            $("#base_modal_body").html(form);
        },
        complete: function () {
            $("#base_modal .loading").find(".overlay, .loading-img").remove();
        }
    });
    e.preventDefault();
});
$('body').on('click', '.btn-portal-o-delete', function (e) {
    var path = this.dataset.deletePath;
    var objId = $(this).data('guid');
    $.ajax({
        url: path,
        data: objId,
        type: "POST",
        success: function () {
            $("tr[data-id='" + objId + "']").remove();
            flashMessage("success", 'Deleted successfully');
        }
    });
    e.preventDefault();
});
//123
$('body').on('click', '.btn-portal-o-submit', function (e) {
    var theForm = $("#frmPortalO");
    var path = theForm.attr("action");
    var formData = theForm.serialize();
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
                flashMessage("success", result.message)
                $("#base_modal").modal('hide');
                refreshPagination();
            } else {
                $("#base_modal_body").html(result.form);
            }
        },
        complete: function () {
            $(".itsloading").fadeOut();
        }
    });
    e.preventDefault();
});
