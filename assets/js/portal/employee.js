import 'cropit';
import './file-validator.js';

$(".dropdownSearch").chosen({width: "100%"});

$('body').on('click', '.btn-emp-roles', function (e) {
    var path = this.dataset.rolesPath;
    var objid = this.dataset.objid;
    $.ajax({
        url: path,
        data: {'objid': objid},
        type: "POST",
        success: function (form) {
            $("#base_modal_body").html(form);
        }
    });
    e.preventDefault();
});

$('body').on('click', '.btn-emp-apps', function (e) {
    var path = this.dataset.appsPath;
    var objid = this.dataset.objid;
    $.ajax({
        url: path,
        data: {'objid': objid},
        type: "POST",
        success: function (form) {
            $("#base_modal_body").html(form);
        }
    });
    e.preventDefault();
});

$('body').on('click', '.btn-emp-roles-add', function (e) {
    var path = this.dataset.roleAddPath;
    var data = {};
    data['token'] = $("input[name='token']").val();
    data['objid'] = this.dataset.objid;
    data['role'] = this.dataset.role;
    data['m'] = $('#organization_ministry').val();
    data['o'] = $('#organization_organization').val();
    data['ou'] = $('#organization_organizationUnit').val();
    $.ajax({
        url: path,
        data: data,
        type: "POST",
        success: function (form) {
            var res1 = $(form).find('#rolesAvailable').html();
            var res2 = $(form).find('#rolesAssigned').html();
            $("#base_modal_body #rolesAvailable").html(res1);
            $("#base_modal_body #rolesAssigned").html(res2);
        }
    });
    e.preventDefault();
});

$('body').on('click', '.btn-emp-roles-remove', function (e) {
    var path = this.dataset.roleRemovePath;
    var data = {};
    data['token'] = $("input[name='token']").val();
    data['objid'] = this.dataset.objid;
    data['role'] = this.dataset.role;
    var ou = this.dataset.ou;
    var curOu = this.dataset.curOu;
    var selectedOu=$('#organization_organizationUnit').val();
    data['ou'] = ou;
    $.ajax({
        url: path,
        data: data,
        type: "POST",
        success: function (form) {
            if ((ou===curOu && !selectedOu) || (Number(selectedOu)===ou)) {
                var res1 = $(form).find('#rolesAvailable').html();
                $("#base_modal_body #rolesAvailable").html(res1);
            }
            var res2 = $(form).find('#rolesAssigned').html();
            $("#base_modal_body #rolesAssigned").html(res2);
        }
    });
    e.preventDefault();
});
$('body').on('click', '.btn-emp-apps-remove', function (e) {
    var path = this.dataset.appsRemovePath;
    var data = {};
    data['token'] = $("input[name='token']").val();
    data['objid'] = this.dataset.objid;
    
    $.ajax({
        url: path,
        data: data,
        type: "POST",
        success: function (form) {
           
            $("#base_modal_body").html(form);
        }
    });
    e.preventDefault();
});

$("body").on("change", "#organization_organizationUnit", function (e) {
    var path = this.dataset.path;
    var data = {};
    data['token'] = $("input[name='token']").val();
    data['objid'] = this.dataset.objid;
    data['ou'] = $(this).val();
    $.ajax({
        url: path,
        data: data,
        type: "POST",
        beforeSend: function () {
            $("#base_modal .loading").append('<div class="overlay"><i class="fa fa-refresh fa-spin"></i></div>');
        },
        success: function (form) {
            var res = $(form).find('#rolesAvailable').html();
            $("#base_modal_body #rolesAvailable").html(res);
//            $("#base_modal_body").html(form);
        }
    });
    e.preventDefault();
});


$('body').on('click', '.btn-emp-groups', function (e) {
    var path = this.dataset.groupsPath;
    var data = {};
    data['objid'] = this.dataset.objid;
    $.ajax({
        url: path,
        data: data,
        type: "POST",
        success: function (form) {
            $("#base_modal_body").html(form);
        }
    });
    e.preventDefault();
});

$('body').on('click', '.btn-emp-groups-add', function (e) {
    var path = this.dataset.groupAddPath;
    var data = {};
    data['token'] = $("input[name='token']").val();
    data['objid'] = this.dataset.objid;
    data['group'] = this.dataset.group;
    $.ajax({
        url: path,
        data: data,
        type: "POST",
        success: function (retdata) {
            flashMessageObj(retdata.result);
            $("#base_modal").show();
            $("#base_modal_body").html(retdata.form);
        }
    });
    e.preventDefault();
});
$('body').on('click', '.btn-emp-groups-remove', function (e) {
    var path = this.dataset.groupRemovePath;
    var data = {};
    data['token'] = $("input[name='token']").val();
    data['objid'] = this.dataset.objid;
    data['group'] = this.dataset.group;
    $.ajax({
        url: path,
        data: data,
        type: "POST",
        success: function (retdata) {
            flashMessageObj(retdata.result);            
             $("#base_modal").show();
            $("#base_modal_body").html(retdata.form);
        }
    });
    e.preventDefault();
});

$("body").on('blur', '.txt-member-email', function (e) {
    var txtEmail = $(this);
    var emailVal = txtEmail.val();
    var objid = $(this).data('objid');
    var emailValO = "";
    if (emailVal != emailValO) {
        var path = this.dataset.verifyPath;
        path = path.replace("____", emailVal);
        if (emailVal != '') {
            emailValO = emailVal;
            $.ajax({
                type: "POST",
                url: path,
                data: {'objid':objid},
                success: function (data) {
                    if (data === '1') {
                        flashMessage('danger', 'This email already exists, Please try other Email');
                        txtEmail.addClass('is-invalid').removeClass('is-valid');
                        txtEmail.val('');
                    } else {
                        txtEmail.addClass('is-valid').removeClass('is-invalid');

                    }
                }
            });
        }
    }
});


$('body').on('click', '.btn-emp-photo', function (e) {
    var path = this.dataset.pup;
    var objid = this.dataset.objid;
    $.ajax({
        url: path,
        data: {'objid': objid},
        type: "POST",
        success: function (form) {
            $("#base_modal").modal('show');
            $("#base_modal_body").html(form);
            initCrop();
        }
    });
    e.preventDefault();
});
var empGuId = null;
var defaultProfilePic = null;
function initCrop() {
    var userPhotoDet = $("#userPhoto");
    var userPhoto = userPhotoDet.val();
    defaultProfilePic = userPhotoDet.data('blank-image-path');

    empGuId = $("#empGuId").val();
    var imageSrc = '';
    if (userPhoto)
        imageSrc = 'data:image/jpeg;base64,' + userPhoto;

    var $imageCropper = $('.image-editor').cropit({
        exportZoom: 1.25,
        imageBackground: false,
        allowDragNDrop: false,
        imageBackgroundBorderWidth: 30,
        smallImage: 'stretch',
        width: 150,
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
    onValidation: function (files) {
    },
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
    var path = $('#empGuId').data('path');
    var imageData = $('.image-editor').cropit('export', {
        type: 'image/jpeg',
        quality: .8
    });
    if (typeof (imageData) === 'undefined') {
        flashMessage('danger', 'Invalid File type/file size exceeds the allowed limit(10kb)');
        return false;
    }
    uploadImageData(imageData, path, empGuId);
});
var btnsEl = $('.btns');
function addBtn(btn) {
    if (btn === 'upload' && $('.upload2Server').length === 0) {
        $('.btns').append('<button class="upload2Server btn btn-xs btn-warning">Upload</button>');
    }
}
function uploadImageData(imageData, path, empGuId) {
    var data = {};
    data['img'] = imageData;
    data['empGuId'] = empGuId;
    data['token'] = $("input[name='token']").val();
    $.ajax({
        url: path,
        method: "POST",
        data: data,
        success: function (status) {
            var result = status;
            // var result = JSON.parse(status);
            if (result.status == "success") {
                flashMessage("success", result.message)
                $('#userPhoto').css({
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
    if (role == "1" && userPhoto) {
        $('.btns').append($verifyAction);
    } else if (role == "2" && userPhoto) {
        $('.btns').append($approveAction);

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

$('body').on('change', '.sbox-tro', function () {
    var oVal = $(this).val();
    var path = this.dataset.path;
    if (oVal === '') {
        $('body .sbox-parentou').html($("<option/>").val('').text('------- Select --------'));
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
                $.each(data.result, function (index, values) {
                    $('body .sbox-parentou').append($("<option/>").val(index).text(values));
                });
                $('body .sbox-parentou').trigger('chosen:updated');
            },
            complete: function () {
                $(".box").find(".overlay, .loading-img").remove();
            }
        });
    }
});

$('body .sbox-trministry').val(0);
$('body').on('change', '.sbox-trministry', function () {
    var mVal = $(this).val();
    var path = this.dataset.path;
    if (mVal === '') {
        $('body .sbox-tro').html($("<option/>").val('').text('------- Select --------'));
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
                $('body .sbox-tro').html($("<option/>").val('').text(' -- Select Organization -- '));
                $.each(data.result, function (index, values) {
                    $('body .sbox-tro').append($("<option/>").val(index).text(values));
                });
                $('body .sbox-tro').trigger('chosen:updated');
            },
            complete: function () {
                $(".box").find(".overlay, .loading-img").remove();
            }
        });
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

$('body').on('click', '.btn-emp-delete-confirm', function (e) {
    var path = this.dataset.path;
    var objid = this.dataset.objid;
    $.ajax({
        url: path,
        type: "POST",
        data: {'objid': objid, 'delreason': $('.user-del-reason').val()},
        success: function (status) {
            var result = JSON.parse(status);
            if (result.status == "success") {
                flashMessage("success", result.message);
                $("#base_modal").modal('hide');
                refreshPagination();
            } else {
                flashMessage("danger", result.message);
                $("#base_modal_body").html(result.form);
            }
            $("#base_modal").modal('hide');
        }
    });
    e.preventDefault();
});

$('body').on('click', '.btn-emp-verify-confirm', function (e) {
    var path = this.dataset.path;
    var objid = this.dataset.objid;
    var rem = $("#verify-remarks").val();
    $.ajax({
        url: path,
        type: "POST",
        data: {'objid': objid, 'remarks' : rem},
        success: function (status) {
            var result = JSON.parse(status);
            if (result.status == "success") {
                flashMessage("success", result.message)
                $("#base_modal").modal('hide');
                refreshPagination();
            } else {
                $("#base_modal_body").html(result.form);
            }
        }
    });
    e.preventDefault();
});
$('body').on('click', '.btn-emp-reject-confirm', function (e) {
    var path = this.dataset.path;
    var objid = this.dataset.objid;
    var rem = $("#verify-remarks").val();
    $.ajax({
        url: path,
        type: "POST",
        data: {'objid': objid, 'remarks' : rem},
        success: function (status) {
            var result = JSON.parse(status);
            if (result.status == "success") {
                flashMessage("success", result.message)
                $("#base_modal").modal('hide');
                refreshPagination();
            } else {
                $("#base_modal_body").html(result.form);
            }
        }
    });
    e.preventDefault();
});
$('body').on('click', '.btn-emp-migrate-confirm', function (e) {
    var path = this.dataset.path;
    var objid = this.dataset.objid;
    $.ajax({
        url: path,
        type: "POST",
        data: {'objid': objid},
        success: function (status) {
            var result = JSON.parse(status);
            if (result.status == "success") {
                flashMessage("success", result.message)
                $("#base_modal").modal('hide');
                refreshPagination();
            } else {
                $("#base_modal_body").html(result.form);
            }
        }
    });
    e.preventDefault();
});

$('body').on('click', '.btn-action-transfer', function (e) {
    var path = this.dataset.path;
    var data = {};
    data['objid'] = this.dataset.objid;
    $.ajax({
        url: path,
        data: data,
        type: "POST",
        success: function (form) {
            $("#base_modal_body").html(form);
        }
    });
    e.preventDefault();
});
$('body').on('click', '.btn-emp-trgroups-remove', function (e) {
    var path = this.dataset.groupRemovePath;
    var selGroup = this;
    var data = {};
    data['token'] = $("input[name='token']").val();
    data['objid'] = this.dataset.objid;
    data['group'] = this.dataset.group;
    $.ajax({
        url: path,
        data: data,
        type: "POST",
        success: function (status) {
            selGroup.remove();
            flashMessage("success", "Group Removed successfully")
        }
    });
    e.preventDefault();
});
$('body').on('click', '.btn-emp-transfer-update', function (e) {
    var path = this.dataset.path;
    var objid = this.dataset.objid;
    var ouid = $('.sbox-parentou').val();
    $.ajax({
        url: path,
        type: "POST",
        data: {'objid': objid, 'ou': ouid},
        success: function (result) {
            if (result.status == "success") {
                flashMessage("success", result.message)
                $("#base_modal").modal('hide');
                refreshPagination();
            }else if (result.status == "danger") {
                    flashMessage("danger", result.message)
            } else {
                $("#base_modal_body").html(result.form);
            }
        }
    });
    e.preventDefault();
});

$('body').on('click', '.btn-emp-apps-update', function (e) {
    var path = this.dataset.path;
    var objid = this.dataset.objid;
    var ouid = $('.externalApp').val();
    if(!ouid){
        flashMessage('error', "An error has been occured ");
        return false; 
    }
    $.ajax({
        url: path,
        type: "POST",
        data: {'objid': objid, 'ou': ouid},
        beforeSend: function () {
            $(".itsloading").show();
        },
        success: function (status) {
            var result = JSON.parse(status);
            flashMessage(result.status, result.message)
            if (result.status == "success") {
               
                $("#base_modal").modal('hide');
               
            }
        },
        complete: function () {
            $(".itsloading").fadeOut();
        }
    });
    e.preventDefault();
});

$('body').on('click', '.btn-action-offboard', function (e) {
    var path = this.dataset.path;
    var data = {};
    data['objid'] = this.dataset.objid;
    $.ajax({
        url: path,
        data: data,
        type: "POST",
        success: function (form) {
            $("#base_modal_body").html(form);
        }
    });
    e.preventDefault();
});
$('body').on('click', '.btn-emp-offboard-update', function (e) {
    var path = this.dataset.path;
    var objid = this.dataset.objid;
    var reason = this.dataset.reason;
    $.ajax({
        url: path,
        type: "POST",
        data: {'objid': objid, 'reason': reason},
        success: function (status) {
            var result = JSON.parse(status);
            if (result.status == "success") {
                flashMessage("success", result.message)
                $("#base_modal").modal('hide');
                refreshPagination();
            } else {
                flashMessage("danger", result.message)
                $("#base_modal_body").html(result.form);
            }
        }
    });
    e.preventDefault();
});

$('body').on('click', '.btn-action-otl', function (e) {
    var path = this.dataset.viewPath;
    var data = {};
    data['objid'] = this.dataset.objid;
    $.ajax({
        url: path,
        data: data,
        type: "POST",
        success: function (result) {
            flashMessage(result.status, result.message);
            if (result.type === 'success') {
                refreshPagination();
            }
        }
    });
    e.preventDefault();
});

$('body').on('click', '.btn-action-csv', function (e) {
    var path = this.dataset.newPath;
    var filterValue = $("input[name='filterValue']").val();
    var filterField = $("select[name='filterField']").val();
    path = path.replace('_field_', filterField);
    path = path.replace('_value_', filterValue);
    window.location = path;
});

$('body').on('click', '.btn-emp-beta-confirm', function (e) {
    var path = this.dataset.path;
    var objid = this.dataset.objid;
    $.ajax({
        url: path,
        type: "POST",
        data: {'objid': objid},
        success: function (status) {
            var result = JSON.parse(status);
            if (result.status == "success") {
                flashMessage("success", result.message)
                $("#base_modal").modal('hide');
                refreshPagination();
            } else {
                $("#base_modal_body").html(result.form);
            }
        }
    });
    e.preventDefault();
});

$('body').on('click', '.gims_message', function (e) {
    e.stopPropagation();
    var gimspath = this.dataset.gimPath;   
    var path=this.dataset.path;
    var type = this.dataset.type;
    var data = {};
    var msgData = [];
    var pageFilters = $("#filterArray").val();
    var cust_field_val = $(".cust_field_val").val();
    $.ajax({
        type: 'POST',
        url: gimspath,
        data: {'custom_filter_param': pageFilters, 'cust_field_val': cust_field_val},
        success: function (data) {
            data = JSON.parse(data);
            if(data.status=='error'){
                flashMessage("error", data.message)
            }else{
                data['objid'] = data.objid;
                data['objname'] ="Job#"+data.msgId;
                msgData.push(data);
                var jdata = JSON.stringify(msgData);
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
            }
        }
    });
    
});

$("body").on('blur', '.txt-member-mobile', function (e) {
    var txtMobile = $(this);
    var objid = $(this).data('objid');
    var mobileVal = txtMobile.val();
    var mobileValO = "";
    if (mobileVal != mobileValO) {
        var path = this.dataset.verifyPath;
        path = path.replace("____", mobileVal);
        if (mobileVal != '') {
            mobileValO = mobileVal;
            $.ajax({
                type: "POST",
                url: path,
                data: {'objid':objid},
                success: function (data) {
                    if (data === '1') {
                        flashMessage('danger', 'This mobile number already exists, Please try other');
                        txtMobile.addClass('is-invalid').removeClass('is-valid');
                        txtMobile.val('');
                    } else {
                        txtMobile.addClass('is-valid').removeClass('is-invalid');

                    }
                }
            });
        }
    }
});

$('body').on('click', '.btn-member-search', function (e) {
    var path = this.dataset.path;
    $.ajax({
        type: "POST",
        url: path,
        data: {
            'mobileno': $(".__membersearch").val()
        },
        success: function (form) {
            $("#__listContainer").html(form);
        }
    });
    e.preventDefault();
});