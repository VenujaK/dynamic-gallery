jQuery(document).ready(function($) {
    $('#art-filter-form').on('submit', function(e) {
        e.preventDefault(); 
        var formData = $(this).serialize();
        // Send AJ request
        $.ajax({
            type: 'POST',
            url: dag_ajax_object.ajax_url,
            data: formData + '&action=dag_filter_gallery', 
            success: function(response) {
                $('#art-gallery').html(response);
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
            }
        });
    });
});
