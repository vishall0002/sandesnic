import './global';
var sjcl = require('./sjcl');

$(function () {
    var peru = $(".__selfperu");
    var thakol = $("#_thakol");
    var uppu = $("#_uppu");
    var thisForm = $("#user");
    var verifyPath = $("#_verifyUser").val();
    $('input[type="submit"]').attr('disabled', 'disabled');
    peru.focusout(function (e) {
        var userData = $(e.target).val();
        var path = verifyPath.replace('__objid__', userData);
        if (userData !== "") {
            $.ajax({
                type: 'POST',
                url: path,
                success: function (result) {
                    uppu.val(result.uppu);
                    if (result.status == 'kshamikku'){
                        peru.val('');
                        flashMessage('danger','This authentication method is applicable for authorised government officials');
                    }
                    $('input[type="submit"]').removeAttr('disabled');
                }
            });
        } else {
            $('input[type="submit"]').attr('disabled', 'disabled');
        }
    });
    thisForm.submit(function () {
        var baS1 = sjcl.hash.sha256.hash(thakol.val());
        var digestS1 = sjcl.codec.hex.fromBits(baS1);
        var baS2 = sjcl.hash.sha256.hash(digestS1 + '{' + uppu.val() + '}');
        var digestS2 = sjcl.codec.hex.fromBits(baS2);
        thakol.val(digestS2);
    });

});
