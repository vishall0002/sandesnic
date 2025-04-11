// $(document).ready(function () {
//     var eleDBD = $("#btnMemberisedRefresh");
//     var path = eleDBD.data('api-path');
//     var objid = eleDBD.data('objid');
//     pullList(path, objid);
// });

$("#btnMemberisedRefresh").click(function () {
    var path = this.dataset.apiPath;
    var objid = this.dataset.objid;
    pullList(path, objid);
})

$('body').on('click', '.btn-memberised-stats', function (e) {
    var apiPath = this.dataset.apiPath;
    var objid = this.dataset.objid;
    pullList(apiPath, objid);
    e.preventDefault();
});

$('body').on('click', '.btn-drill', function (e) {
    var apiPath = this.dataset.pullPath;
    var objid = this.dataset.objid;
    pullList(apiPath, objid);
    e.preventDefault();
});


function pullList(apiPath, objid) {
    $.ajax({
        method: "POST",
        url: apiPath,
        data: {'objid': objid},
        beforeSend: function () {
            $('.progress-barm').addClass('d-block').addClass('progress-bar-animated');
        },
        success: function (dbdata) {
            $("#__listContainer").html(dbdata);
        },
        complete: function () {
            $('.progress-barm').removeClass('d-block').addClass('d-none').removeClass('progress-bar-animated');
        }
    });
}

function isValidURL(string) {
    var res = string.match(/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/);
    return res !== null;
}

$('body').on('click', '.pagination-navigator-dashboard', function (e) {
    var pageURL = $(this).attr('href');
    // Paras
    if (typeof pageURL !== 'string' || !isValidURL(pageURL)) {
        console.error('Invalid URL:', pageURL);
        return;
    }
    var _lpp = {'pageURL': pageURL, 'pageFilters': 'NA', 'cacheTime': new Date().toISOString()
    };
    localStorage.setItem('lpp', JSON.stringify(_lpp));
    var objid = $("#btnMemberisedRefresh").data('objid');
    $.ajax({
        type: "POST",
        data: {'objid': objid},
        url: pageURL,
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
$('body').on('click', '.paginator-filter-dashboard-button', function (e) {
    var objid = $("#btnMemberisedRefresh").data('objid');
    var form = $("#pagination-filterform");
    var pageURL = form.attr('action');
    // Paras
    if (typeof pageURL !== 'string' || !isValidURL(pageURL)) {
        console.error('Invalid URL:', pageURL);
        return;
    }
    var pageFilters = form.serialize() + '&objid=' + objid;
    var _lpp = {'pageURL': pageURL, 'pageFilters': pageFilters, 'cacheTime': new Date().toISOString()};
    localStorage.setItem('lpp', JSON.stringify(_lpp));
    $.ajax({
        type: "POST",
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
    e.preventDefault();
});
