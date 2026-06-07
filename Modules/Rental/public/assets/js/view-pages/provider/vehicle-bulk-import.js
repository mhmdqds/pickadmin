"use strict";

$('.update_or_import').on("click", function () {
    var upload_type = $('input[name="upload_type"]:checked').val();
    var title = $(this).data('title');
    var desc = $(this).data('desc');
    var text = $(this).data('text');
    var no = $(this).data('no');
    var yes = $(this).data('yes');

    myFunction(upload_type, title, desc, text, no, yes)
});

$('#reset_btn').click(function(){
    var alert = $(this).data('alert');
    $('#products_file').val('');
    $('.filename').text(alert);
})

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


function myFunction(data, title, desc, text, no, yes) {
    Swal.fire({
        title: title ,
        text: desc + data + text,
        type: 'warning',
        showCancelButton: true,
        cancelButtonColor: 'default',
        confirmButtonColor: '#FC6A57',
        cancelButtonText: no,
        confirmButtonText: yes,
        reverseButtons: true
    }).then((result) => {
        if (result.value) {
            $('#btn_value').val(data);
            $("#import_form").submit();
        }
    })
}
