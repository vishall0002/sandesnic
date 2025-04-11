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
            data: { 'stateVal': stateVal },
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

$('body .sbox-o').val(0);
$('body').on('change', '.sbox-o', function () {
    var oVal = $(this).val();
    var path = $(this).data('path');
    if (oVal === '') {
        $('body .sbox-parentou').html($("<option/>").val('').text('------- Select --------'));
    } else {
        $.ajax({
            type: 'POST',
            url: path,
            data: { 'oVal': oVal },
            dataType: "json",
            beforeSend: function () {
                $(".box").prepend('<div class="overlay"><i class="fa fa-refresh fa-spin"></i></div>');
            },
            success: function (data) {
                $('body .sbox-parentou').html($("<option/>").val('').text(' -- Select Parent OU -- '));
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

$('body .sbox-ministry').val(0);
$('body').on('change', '.sbox-ministry', function () {
    var mVal = $(this).val();
    var path = $(this).data('path');
    if (mVal === '') {
        $('body .sbox-o').html($("<option/>").val('').text('------- Select --------'));
    } else {
        $.ajax({
            type: 'POST',
            url: path,
            data: { 'mVal': mVal },
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