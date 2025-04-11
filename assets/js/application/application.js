import '../../vendors/chosen/chosen.jquery.min.js';
import '../../vendors/sweetalert/sweetalert.min.js';
import '../../vendors/popConfirm/jquery.popconfirm.js';
import 'eonasdan-bootstrap-datetimepicker';
import './ouOnboarding.js';


$(".dropdownSearch").chosen({width: "100%"});

//$('#btn-submit').on('click',function(e){
//    e.preventDefault();
//    var form = $('body form');
//    swal({
//        title: "Are you sure?",
//        type: "warning",
//        showCancelButton: true,
//        confirmButtonColor: "#DD6B55",
//        confirmButtonText: "Yes, submit it!",
//        closeOnConfirm: false
//    }, function(isConfirm){
//        if (isConfirm) {
//            form.submit();
////            $('body .submt').submit();  
////           $('#btn-submit').trigger('submit');  
//           
////        form.submit();
//        }
//    });
//});
// $('[data-toggle=confirmation]').popConfirm();
