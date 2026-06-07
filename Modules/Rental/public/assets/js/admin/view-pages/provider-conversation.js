'use strict';
$('.view-dm-conv').on('click', function (){
    let url = $(this).data('url');
    let id_to_active = $(this).data('active-id');
    let conv_id = $(this).data('conv-id');
    let sender_id = $(this).data('sender-id');
    viewConvs(url, id_to_active, conv_id, sender_id);
})


function viewConvs(url, id_to_active, conv_id, sender_id) {
$('.customer-list').removeClass('conv-active');
$('#' + id_to_active).addClass('conv-active');

const dataSet = document.getElementById("data-set");
let newUrl = dataSet.getAttribute("data-view-conv-url") + '?conversation=' + conv_id + '&user=' + sender_id;

$.get({
    url: url,
    success: function(data) {
        window.history.pushState('', 'New Page Title', newUrl);
        $('#vendor-view-conversation').html(data.view);
    }
});
}


let page = 1;
let user_id =  $('#vendor_id').val();
$('#vendor-conversation-list').scroll(function() {
    if ($('#vendor-conversation-list').scrollTop() + $('#vendor-conversation-list').height() >= $('#vendor-conversation-list')
        .height()) {
        page++;
        loadMoreData(page);
    }
});

function loadMoreData(page) {
    $.ajax({
            url: $('#data-set').data('message-list-url') + '?page=' + page,
            type: "get",
            data:{"user_id":user_id},
            beforeSend: function() {

            }
        })
        .done(function(data) {
            if (data.html == " ") {
                return;
            }
            $("#vendor-conversation-list").append(data.html);
        })
        .fail(function(jqXHR, ajaxOptions, thrownError) {
            console.log('server not responding...');
        });
};

function fetch_data(page, query) {
        $.ajax({
            url: $('#data-set').data('message-list-url') + '?page=' + page + "&key=" + query,
            type: "get",
            data:{"user_id":user_id},
            success: function(data) {
                $('#vendor-conversation-list').empty();
                $("#vendor-conversation-list").append(data.html);
            }
        })
    };

    $(document).on('keyup', '#serach', function() {
        let query = $('#serach').val();
        fetch_data(page, query);
    });
