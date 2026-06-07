'use strict';
$('#add-your-note').on('input', function () {
    const maxLength = 60;
    const currentLength = $(this).val().length;

    $('#char-count').text(`${currentLength}/${maxLength}`);
});
