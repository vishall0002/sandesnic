import 'cropit';
import './file-validator.js';

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


$('body').on('click', '.btn-grp-remove-member', function (e) {
    var path = this.dataset.setPath;
    var data = {};
    data['token'] = $("input[name='token']").val();
    data['eobjid'] = $(this).data('eobjid');
    data['gobjid'] = $(this).data('gobjid');
    $.ajax({
        url: path,
        data: data,
        type: "POST",
        beforeSend: function () {
            $("#base_modal").modal('show');
        },
        success: function (retdata) {
            flashMessageObj(retdata.result);
            $("#base_modal_body").html(retdata.form);
        }
       
    });
    e.preventDefault();
});

$('body').on('click', '.btn-grp-manage', function (e) {
    var path = this.dataset.setPath;
    var data = {};
    data['token'] = $("input[name='token']").val();
    data['migid'] = $(this).data('migid');
    data['migtype'] = $(this).data('migtype');
    $.ajax({
        url: path,
        data: data,
        type: "POST",
        beforeSend: function () {
            $("#base_modal").modal('show');
        },
        success: function (retdata) {
            flashMessageObj(retdata.result);
            $("#base_modal_body").html(retdata.form);
        }
       
    });
    e.preventDefault();
});

$('body').on('click', '.btn-grp-add', function (e) {
    var path = this.dataset.setPath;
    var data = {};
    data['token'] = $("input[name='token']").val();
    data['eobjid'] = $(this).data('eobjid');
    data['gobjid'] = $(this).data('gobjid');
    data['migtype'] = $(this).data('migtype');
    $.ajax({
        url: path,
        data: data,
        type: "POST",
        success: function (retdata) {
            flashMessageObj(retdata.result);
            $('#__listContainer').html(result.form);
        }
       
    });
    e.preventDefault();
});

$('body').on('click', '.btn-any-member-add', function (e) {
    var path = this.dataset.setPath;
    var data = {};
    data['token'] = $("input[name='token']").val();
    data['eemail'] = $("#any-member-email").val();
    data['gobjid'] = $(this).data('gobjid');
    data['migtype'] = $(this).data('migtype');
    $.ajax({
        url: path,
        data: data,
        type: "POST",
        beforeSend: function () {
            $("#base_modal").modal('show');
        },
        success: function (retdata) {
            flashMessageObj(retdata.result);
            $("#base_modal_body").html(retdata.form);
        }
       
    });
    e.preventDefault();
});

$('body').on('click', '.btn-grp-xmppga', function (e) {
    var path = this.dataset.xmppgaPath;
    var objid = $(this).data('objid');
    $.ajax({
        url: path,
        data: objid,
        type: "POST",
        success: function (results) {
            if (results) {
                flashMessage("success", 'Member addition into default group success');
            } else {
                flashMessage("danger", 'Member addition into default group failed');
            }
        }
    });
    e.preventDefault();
});

$('body').on('click', '.btn-grp-xmppgr', function (e) {
    var path = this.dataset.xmppgrPath;
    var objid = $(this).data('objid');
    $.ajax({
        url: path,
        data: objid,
        type: "POST",
        success: function (results) {
            if (results) {
                flashMessage("success", 'Member removal from default group success');
            } else {
                flashMessage("danger", 'Member removal from default group failed');
            }
        }
    });
    e.preventDefault();
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

$('body').on('click', '.btn-grp-photo', function (e) {
    // var path = $(this).data('photo-path');
    // var objid = $(this).data('objid');
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
        },
        success: function (form) {
            $("#base_modal_body").html(form);
            initCrop();
        },
       
    });
    e.preventDefault();
});
var groupGuId = null;
var defaultProfilePic = null;

function initCrop() {
    var groupPhotoDet = $("#groupPhoto");
    var groupPhoto = groupPhotoDet.val();
    defaultProfilePic = groupPhotoDet.data('blank-image-path');

    groupGuId = $("#groupGuId").val();
    var imageSrc = '';
    if (groupPhoto)
        imageSrc = 'data:image/jpeg;base64,' + groupPhoto;

    var $imageCropper = $('.image-editor').cropit({
        exportZoom: 1.25,
        imageBackground: false,
        allowDragNDrop: false,
        imageBackgroundBorderWidth: 30,
        smallImage: 'stretch',
        width: 200,
        height: 200,
        imageState: {
            src: imageSrc,
        },
        onFileChange: function () {
            addBtn('upload');
        },
        onImageError: function () {
            $('.cropit-preview-image-container').hide();
            $imageCropper.cropit('imageSrc', imageSrc);
            $('.btns').find('.cancelAction,.upload2Server').remove();
            flashMessage('danger', 'Invalid File type/file size exceeds the allowed limit(10kb)');
            return false;
        }

    });
}
$('.rotate-cw').click(function () {
    $imageCropper.cropit('rotateCW');
});
$('.rotate-ccw').click(function () {
    $imageCropper.cropit('rotateCCW');
});

$(".cropit-image-input").fileValidator({
    onValidation: function (files) {},
    onInvalid: function (validationType, file) {
        $imageCropper.cropit('imageSrc', imageSrc);
        $('.btns').find('.cancelAction,.upload2Server').remove();
        $('.btns').append($removeAction);
        flashMessage('danger', 'Invalid File type/file size exceeds the allowed limit(10kb)');
    },
    maxSize: '10k',
    type: 'image'
});

$('body').on('click', ' .upload2Server', function () {
    var path = $('#groupGuId').data('photo-upload-path');
    var imageData = $('.image-editor').cropit('export', {
        type: 'image/jpeg',
        quality: .8
    });
    if (typeof (imageData) === 'undefined') {
        flashMessage('danger', 'Invalid File type/file size exceeds the allowed limit(10kb)');
        return false;
    }
    uploadImageData(imageData, path, groupGuId);
});
var btnsEl = $('.btns');

function addBtn(btn) {
    if (btn === 'upload' && $('.upload2Server').length === 0) {
        $('.btns').append('<button class="upload2Server btn btn-xs btn-warning">Upload</button>');
    }
}

function uploadImageData(imageData, path, groupGuId) {
    var data = {};
    data['img'] = imageData;
    data['groupGuId'] = groupGuId;
    data['token'] = $("input[name='token']").val();
    $.ajax({
        url: path,
        method: "POST",
        data: data,
        success: function (status) {
            var result = JSON.parse(status.result);
            if (result.status == "success") {
                flashMessage("success", result.message)
                $('#groupPhoto').css({
                    'background-image': 'url(' + imageData + ')'
                }).addClass('col-md-3 photo-thumbnail');
                $("#base_modal").modal('hide');
            } else {
                flashMessage("danger", result.message)
            }
        }
    });
}
$('.btns').on('click', '.cancelAction', function () {
    $imageCropper.cropit('imageSrc', imageSrc);
    $('.btns').find('.cancelAction,.upload2Server').remove();
    $('.btns').append($removeAction);
    var role = ($('.options').data('role'));
    if (role == "1" && groupPhoto) {
        $('.btns').append($verifyAction);
    } else if (role == "2" && groupPhoto) {
        $('.btns').append($approveAction);

    }
});

$('body .sbox-ministry').val(0);
$('body').on('change', '.sbox-ministry', function () {
    var mVal = $(this).val();
    var path = this.dataset.path;
    if (mVal === '') {
        $('body .sbox-o').html($("<option/>").val('').text('------- Select --------'));
    } else {
        $.ajax({
            type: 'POST',
            url: path,
            data: {'mVal': mVal},
            dataType: "json",
            beforeSend: function () {
                $(".box").prepend('<div class="overlay"><i class="fa fa-refresh fa-spin"></i></div>');
            },
            success: function (data) {
                $('body .sbox-o').html($("<option/>").val('').text(' -- Select Organization -- '));
                $.each(data.result, function (index, values) {
                    $('body .sbox-o').append($("<option/>").val(index).text(values));
                });
                $('body .sbox-o').trigger('chosen:updated');
            },
            complete: function () {
                $(".box").find(".overlay, .loading-img").remove();
            }
        });
    }
});

$('body .sbox-o').val(0);
$('body').on('change', '.sbox-o', function () {
    var oVal = $(this).val();
    var path = this.dataset.path;
    if (oVal === '') {
        $('body .sbox-parentou').html($("<option/>").val('').text('------- Select --------'));
        $('body .sbox-dg').html($("<option/>").val('').text('------- Select --------'));
        $('body .sbox-lv').html($("<option/>").val('').text('------- Select --------'));
    } else {
        $.ajax({
            type: 'POST',
            url: path,
            data: {'oVal': oVal},
            dataType: "json",
            beforeSend: function () {
                $(".box").prepend('<div class="overlay"><i class="fa fa-refresh fa-spin"></i></div>');
            },
            success: function (data) {
                $('body .sbox-parentou').html($("<option/>").val('').text(' -- Select -- '));
                $.each(data.ou, function (index, values) {
                    $('body .sbox-parentou').append($("<option/>").val(index).text(values));
                });
                $('body .sbox-parentou').trigger('chosen:updated');
                $('body .sbox-dg').html($("<option/>").val('').text(' -- Select -- '));
                $.each(data.dg, function (index, values) {
                    $('body .sbox-dg').append($("<option/>").val(index).text(values));
                });
                $('body .sbox-dg').trigger('chosen:updated');
                $('body .sbox-lv').html($("<option/>").val('').text(' -- Select -- '));
                $.each(data.lv, function (index, values) {
                    $('body .sbox-lv').append($("<option/>").val(index).text(values));
                });
                $('body .sbox-lv').trigger('chosen:updated');
            },
            complete: function () {
                $(".box").find(".overlay, .loading-img").remove();
            }
        });
    }
});