$("body").on("change", "#scanFileUpload", function (e) {
    e.preventDefault();
    var guid = $(this).data('guid');
    var uPath = $(this).data('upath');
    var mode = $(this).data('mode');
    var action = $(this).data('action');

    uPath = uPath.replace("____", guid);
    //    var fileinfo=$('#fileinfo');
    if (!guid) {
        flashMessage('danger', 'Save the credentials and then upload file');
        return false;
    }
    var fileData = $('#scanFileUpload').prop('files')[0];
    if (fileData == undefined) {
        flashMessage('danger', 'Select a file to upload');
        return false;
    }
    var fileType = fileData.name.split('.').pop(),
        allowedtypes = 'apk,ipa';
            //    allowedtypes = 'pdf';
    if (allowedtypes.indexOf(fileType.toLowerCase()) < 0) {
        flashMessage('danger', 'File Type should be Apk/Ipa');
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
            refreshPagination();
            if(action !== 'edit'){
                if (mode === 'android') {
                    setTimeout(function () {
                        $("#base_modal").modal('hide');
                        // window.location.reload();
                    }, 1000);
                    //            refreshPagination();
                } else {
                    setTimeout(function () {
                        $("#base_modal").modal('hide');
                        //            refreshPagination();
                        // window.location.reload();
                    }, 1000);
                    //                $('#form3').removeClass('invisible');
                    //                $('#base_modal #manifestFileUpload').attr('data-guid', guid);
                }
            }

        }
    });
    return false;
});
$('body').on('click', '.btn-action-new-multi', function (e) {
    var npath = '';
    var eleDBD = $(this);
    npath = eleDBD.data('new-path');

    $.ajax({
        url: npath,
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

$('body').on('click', '.btn-action-set-status', function (e) {
    var path = this.dataset.viewPath;
    var objid = this.dataset.objid;
    // var token = $("input[name='token']").val();
    $.ajax({
        url: path,
        data: {
            'objid': objid,
        },
        type: "POST",
        success: function (response) {
            flashMessage(response.type, response.message);
            if (token) {
                $("input[name='token']").val(response.token);
            }
            if (response.type === 'success') {
                               refreshPagination();
                // setTimeout(function () {
                //     window.location.reload();
                // }, 1000);
            }
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

$("body").on("change", "#organization_organizationUnit", function (e) {

    $('#form2').removeClass('invisible');
    $('#base_modal #csvFileUpload').attr('data-guid', $(this).val());
});

$("body").on("change", "#csvFileUpload", function (e) {
    e.preventDefault();
    var guid = $(this).data('guid');
    var uPath = $(this).data('upath');
    var mode = $(this).data('mode');
    uPath = uPath.replace("____", guid);
    //    var fileinfo=$('#fileinfo');
    if (!guid) {
        flashMessage('danger', 'Save the credentials and then upload file');
        return false;
    }
    var fileData = $('#csvFileUpload').prop('files')[0];
    if (fileData == undefined) {
        flashMessage('danger', 'Select a file to upload');
        return false;
    }
    var fileType = fileData.name.split('.').pop(),
        allowedtypes = 'csv,xls';
    //            allowedtypes = 'pdf';
    if (allowedtypes.indexOf(fileType.toLowerCase()) < 0) {
        flashMessage('danger', 'File Type should be csv,xls');
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
            if (res.html) {
                $("#base_modal_body #error_list").html(res.html);
            }
        } else {
            flashMessage($type, res.message);
            setTimeout(function () {
                $("#base_modal").modal('hide');
                window.location.reload();
            }, 3000);
            refreshPagination();
        }
    });
    return false;
});

$('body').on('click', '.btn-action-set-status2', function (e) {
    var path = this.dataset.statusPath;
    var objid = $(this).data('objid');
    var token = $("input[name='token']").val();
    $.ajax({
        url: path,
        data: {
            'objid': objid,
            'token': token
        },
        type: "POST",
        success: function (response) {
            flashMessage(response.type, response.message);
            if (token) {
                $("input[name='token']").val(response.token);
            }
            if (response.type === 'success') {
                setTimeout(function () {
                    $("#base_modal").modal('hide');
                    window.location.reload();
                }, 2000);
            }
        }
    });
    e.preventDefault();
});

$('body').on('click', '.begin-download-notif', function (e) {
    flashMessage('info', 'Downloading app in background...');
});

$('body').on('click', '.btn-version-apps', function (e) {
    var path = this.dataset.viewPath;
    var objid = this.dataset.objid;
    var type = $(this).parent().parent().parent().data('type');
    $.ajax({
        url: path,
        data: {
            'objid': objid,
            'type': type,
            'view':'view'
           
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