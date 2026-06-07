"use strict";

$('#reset_btn').click(function(){
    $('#bulk__import').val(null);
})

$('#reset_btn').click(function(){
$('#products_file').val('');
$('.filename').text($('.filename').data('text'));
})


$(document).on("click", ".update_or_import", function(e){
e.preventDefault();
let upload_type = $('input[name="upload_type"]:checked').val();
myFunction(upload_type)
});

$(".action-upload-section-dot-area").on("change", function () {
if (this.files && this.files[0]) {
    let reader = new FileReader();
    reader.onload = () => {
        let imgName = this.files[0].name;
        $(this).closest(".uploadDnD").find('.filename').text(imgName);
    };
    reader.readAsDataURL(this.files[0]);
}
});

function myFunction(data) {
Swal.fire({
title: $('.update_or_import').data('title'),
text: $('.update_or_import').data('massage') + data,
type: 'warning',
showCancelButton: true,
cancelButtonColor: 'default',
confirmButtonColor: '#FC6A57',
cancelButtonText: $('.update_or_import').data('no'),
confirmButtonText: $('.update_or_import').data('yes'),
reverseButtons: true
}).then((result) => {
    if (result.value) {
        $('#btn_value').val(data);
        $("#import_form").submit();
    }
})
}
