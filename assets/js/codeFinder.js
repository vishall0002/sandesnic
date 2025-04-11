$("#cf_modal .dropdownSearch").chosen({width: "100%"});
var data = {};
//if (cfDepth !== 0) {

$('body #cf_modal').on('click', '.selectCode', function (e) {
    var cfDepth = $('#depthCount').val();
    var count = cfDepth;
    e.preventDefault();
    var jsonPrefix = $('#jsonFilePrefixName').val();
    var selectType = $('#selectType').val();
    var selectFor = $('#selectFor').val();
    var custFunction = $('#custFunctionName').val();
    if (selectType === 'multiple') {
        var selectedText = $('#sbParent' + count + ' option:selected').map(function () {
            return $(this).text();
        }).get().join(',');
        var selectedValue = $('#sbParent' + count).val();
    } else {
        var selectedText = $('#sbParent' + count + ' option:selected').text();
        var selectedValue = $('#sbParent' + count + ' option:selected').val();
    }
    if (selectFor) {
        $('body').find('.' + selectFor + jsonPrefix).val(selectedText);
        $('body').find('.' + selectFor + jsonPrefix).attr('data-id', selectedValue);
    } else {
        $('body').find('.' + jsonPrefix + ', .codefinder').val(selectedText);
        $('body').find('.' + jsonPrefix + ', .codefinder').attr('data-id', selectedValue);
        $('body').find('.codefinder_' + jsonPrefix).val(selectedText);
        $('body').find('.cfValue_' + jsonPrefix).val(selectedValue);
        $('body').find('.codefinder_' + jsonPrefix).attr('data-id', selectedValue);
    }
    $('body').find('#cfSelectedValue').val(selectedValue);
    // Paras
    if (custFunction && typeof window[custFunction] === 'function') {
        window[custFunction]();
    }
    if (typeof __trigger_update == 'function') {
        __trigger_update($('body').find('.codefinder_' + jsonPrefix), selectedValue);
    }
    $('#cf_modal').modal('hide');
});
$('body').on('change', '.cfParent', function (e) {
    e.preventDefault();
    var parentLevel = $(this).data('parentlevel');
    var parent2 = $('#sbParent' + parentLevel);
    var postParam = $(parent2).data('postparam');
    var parent2Path = $(parent2).data('path');
    var param = $(parent2).data('param');
    if (parent2Path) {
        data[postParam] = $(this).val();
        if ($(this) === "") {
            parent2.val([]).attr('disabled', 'disabled');
        } else {
            ajaxResult(parent2Path, data, param, parent2, parentLevel);
        }
    }
});


function ajaxResult(path, data, param, sbParent, sbParentCount) {
    sbParent.empty();
    sbParent.append($("<option />").val('').text('Loading...'));
    $.ajax({
        type: "POST",
        url: path,
        dataType: 'json',
        data: data,
        success: function (rData) {
            var rData = isJson(rData);
            $.each(rData, function (key, value) {
                sbParent.append($("<option />").val(this.id).text(value[param]));
            });
            $('#sbParent' + sbParentCount + ' option[value=""]').text('-- Select --');
        },
        complete: function () {
            $(".dropdownSearch").trigger('chosen:updated');
        }
    }).fail(function (jqXHR, textStatus) {
        alert(textStatus);
    });
}

function isJson(str) {
    try {
        return JSON.parse(str);
    } catch (e) {
        return str;
    }
}

function change_cd_codefinder() {
    var pssb_id = $("#codefinder_id").data('id');
    var path = $('.block_change_path').data('blockchange-path');
    var student_guid = $('.student_guid').val();
    var class_division = $('.class_division');
    var reg_type = $('.reg_type').val();
    var data = {};
    data['pssb_id'] = pssb_id;
    data['student_guid'] = student_guid;
    data['reg_type'] = reg_type;
    class_division.empty();
    class_division.append($("<option />").val('').text('Loading...'));
    $.ajax({
        type: "POST",
        url: path,
        dataType: 'json',
        data: data,
        success: function (result) {
            class_division.empty();
            class_division.append($("<option />").val('').text('----Select Class Division--------'));
            if (result.status == "success") {
                var rData = result.classDivisions;
                $.each(rData, function () {
                    class_division.append($("<option />").val(this.id).text(this.code));
                });
            } else {
                flashMessage("danger", result.msg);
            }
            class_division.trigger('chosen:updated');
        }
    }).fail(function (jqXHR, textStatus) {
        alert(textStatus);
    });
}

//}

function cust_codefinder()
{
    var selectedVal = $('body').find('#cfSelectedValue').val();
    var cfFor = $('body').find('#jsonFilePrefixName').val();
    var objid = $('body').find('.formGuId').val();
    var path = $(".codeFinderAction").data('custpath');
    var data = {"objid": objid, "cfFor": cfFor, "selectedVal": selectedVal};
    $.ajax({
        url: path,
        data: data,
        type: "POST",
        success: function (response)
        {
            var res = JSON.parse(response);
            flashMessage(res.status, res.message);
            if ($('#' + cfFor + ' tr').length > 0) {
                $('#' + cfFor + ' tr:last').after(res.tr);
            } else {
                var table = "<h5 id='" + cfFor + "Head'>" + res.subscriberType + "</h5><table id='" + cfFor + "' class='table table-striped table-bordered table-hover table-sm'><thead><tr><th width='5%'>SlNo.</th>" + res.th + "<th width='10%'>Action</th></tr><tbody>" + res.tr + "</tbody></table>";
                $('#listSubscribers').append(table);
            }
            $('#' + cfFor + ' tbody tr').each(function (index) {
                $(this).find('td:first').html(index + 1);
            });
        }
    });
}
