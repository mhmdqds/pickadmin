
    $(document).on('click', '.data-info-show', function() {
            let id = $(this).data('id');
            let url = $(this).data('url');
            fetch_data(id, url)
        })

        function fetch_data(id, url) {
            $.ajax({
                url: url,
                type: "get",
                beforeSend: function() {
                    $('#data-view').empty();
                    $('#loading').show()
                },
                success: function(data) {
                    $("#data-view").append(data.view);

                    initSelect2Dropdowns();

                },
                complete: function() {
                    $('#loading').hide()
                }
            })
        }

        function initSelect2Dropdowns() {
             $('.offcanvas-close, #offcanvasOverlay').on('click', function () {
                $('.custom-offcanvas').removeClass('open');
                $('#offcanvasOverlay').removeClass('show');
            });
        }
