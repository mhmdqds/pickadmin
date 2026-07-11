function hexToRgba(hex, opacity) {
  let r = parseInt(hex.substr(1, 2), 16);
  let g = parseInt(hex.substr(3, 2), 16);
  let b = parseInt(hex.substr(5, 2), 16);
  return `rgba(${r}, ${g}, ${b}, ${opacity})`;
}

$(document).ready(function () {
  $('[data-bg-color]').each(function () {
    let bgColor = $(this).data('bg-color');
    let opacity = 1;

    let classList = $(this).attr('class').split(/\s+/);
    classList.forEach(function (className) {
      if (className.startsWith('bg-opacity-')) {
        let value = parseInt(className.replace('bg-opacity-', '')) || 100;
        opacity = value / 100;
      }
    });

    $(this).css('background-color', hexToRgba(bgColor, opacity));
  });

  $('[data-color]').each(function () {
    let textColor = $(this).data('color');
    $(this).css('color', textColor);
  });

   // --- Changing svg color ---
    $("img.svg").each(function() {
        var $img = jQuery(this);
        var imgID = $img.attr("id");
        var imgClass = $img.attr("class");
        var imgURL = $img.attr("src");

        jQuery.get(
            imgURL,
            function(data) {
                var $svg = jQuery(data).find("svg");

                if (typeof imgID !== "undefined") {
                    $svg = $svg.attr("id", imgID);
                }
                if (typeof imgClass !== "undefined") {
                    $svg = $svg.attr("class", imgClass + " replaced-svg");
                }

                $svg = $svg.removeAttr("xmlns:a");

                if (
                    !$svg.attr("viewBox") &&
                    $svg.attr("height") &&
                    $svg.attr("width")
                ) {
                    $svg.attr(
                        "viewBox",
                        "0 0 " + $svg.attr("height") + " " + $svg.attr("width")
                    );
                }
                $img.replaceWith($svg);
            },
            "xml"
        );
    });

    // ----Daterangepicker Initialization----
    $('.date-range-picker').daterangepicker({
      ranges: {
      'Today': [moment(), moment()],
      'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
      'Last 7 Days': [moment().subtract(6, 'days'), moment()],
      'Last 30 Days': [moment().subtract(29, 'days'), moment()],
      'This Month': [moment().startOf('month'), moment().endOf('month')],
      'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
      },
      showCustomRangeLabel: true,
      startDate: $(this).data("startDate"),
      endDate: $(this).data("endDate"),
      autoUpdateInput: false,
      locale: {
      cancelLabel: 'Clear'
      },
      "alwaysShowCalendars": true,
    });
    
    $('.date-range-picker').each(function (){
      $(this).attr('placeholder', $(this).data('placeholder') || 'Select Date Range');
    });

    $('.date-range-picker').on('apply.daterangepicker', function (ev, picker) {
      $(this).val(picker.startDate.format('DD MMM, YYYY') + ' - ' + picker.endDate.format('DD MMM, YYYY'));
    });

    $('.date-range-picker').on('cancel.daterangepicker', function (ev, picker) {
      $(this).val('');
    });


   // ---- single image upload ----
    $(document).on('change', '.single_file_input', function (event) {
        let file = event.target.files[0];
        let $card = $(event.target).closest('.upload-file');
        let $textbox = $card.find('.upload-file-textbox');
        let $imgElement = $card.find('.upload-file-img');
        let $removeBtn = $card.find('.remove-btn');

        if (file) {
            let reader = new FileReader();
            reader.onload = function (e) {
                $textbox.hide();
                $imgElement.attr('src', e.target.result).removeClass('d-none');
                $removeBtn.css('opacity', 1);
            };
            reader.readAsDataURL(file);
        } else {
            $textbox.show();
            $imgElement.addClass('d-none').attr('src', '');
            $removeBtn.css('opacity', 0);
        }
    });

    $(document).on('click', '.remove-btn', function () {
        let $card = $(this).closest('.upload-file');
        $card.find('.single_file_input').val('');
        $card.find('.upload-file-textbox').show();
        $card.find('.upload-file-img').addClass('d-none').attr('src', '');
        $(this).css('opacity', 0);
    });

    $('#reset_btn').click(function () {
        $('#banner_type').trigger('change');
        $('#store_id').val(null).trigger('change');

        $('.upload-file').each(function () {
            $(this).find('.single_file_input').val('');
            $(this).find('.upload-file-textbox').show();
            $(this).find('.upload-file-img').addClass('d-none').attr('src', '');
            $(this).find('.remove-btn').css('opacity', 0);
        });
    });
   // ---- single image upload ends ----

    // --- Textarea/Input Max Length ---
    function updateCharCount(el) {
        let maxLength = el.data("maxlength") || 0;
        if(maxLength == 0) return;
        let currentLength = el.val().length;

        if (currentLength > maxLength) {
            el.val(el.val().substring(0, maxLength));
            currentLength = maxLength;
        }

        el
            .closest(".form-group")
            .find(".text_count")
            .text(currentLength + "/" + maxLength);
    }

    // Initialize on page load
    $("textarea.form-control, input.form-control").each(function () {
        updateCharCount($(this));
    });

    // Update on typing
    $("textarea.form-control, input.form-control").on("input", function () {
        updateCharCount($(this));
    });
    // --- Textarea/Input Max Length ends ---

     
});
