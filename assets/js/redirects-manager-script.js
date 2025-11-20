(function($) {
    "use strict";
    jQuery(document).on('ready', function() {
        
        // Add new redirect
        $('#add-redirect-form').on('submit', function(e) {
            e.preventDefault();
            $(this).find('.button').attr('disabled', true);

            var redirect_from = $(this).find('input[name="redirect_from"]').val();
            var redirect_to = $(this).find('input[name="redirect_to"]').val();
            var redirect_type = $(this).find('select[name="redirect_type"]').val();
            var key = $(this).find('select[name="redirect_type"]').data('key');

            // if($(this).find('input[type="checkbox"]').prop('checked') == true){
            //     var status = 1;
            // } else {
            //     var status = 0;
            // }

            if ( (redirect_type !== '301') && (key == '') ) {
                alert('This is a pro feature');
                redirect_type = 301;
                $(this).find('select[name="redirect_type"]').val(301);
            }

            var status = 1;
            
            var redData = {'redirect_from' : redirect_from, 'redirect_to' : redirect_to, 'redirect_type' : redirect_type, 'status' : status};


            $.ajax({
                type: 'POST',
                url: object_kcgred.ajax_url,
                data: {
                    action: 'kcgred_save_redirect',
                    nonce: object_kcgred.nonce,
                    'form-data' : redData,
                }
            }).done(function(response) {
                // console.log(response);
                if(response['status'] == false) {
                    alert(response['message']);
                    $('#add-redirect-form').find('.button').attr('disabled', false);
                } else {
                    location.reload();
                }
            }).fail(function(response) {
                console.log(response); 
            });
        });
        
        // Delete redirect
        $(document).on('click', '.delete-redirect', function() {
            if (!confirm('Are you sure you want to delete this redirect?')) {
                return;
            }
            
            const id = $(this).data('id');
            const row = $(this).closest('tr');
            
            if(id) {
                console.log(object_kcgred.nonce);
                $.ajax({
                    type: 'POST',
                    url: object_kcgred.ajax_url,
                    data: {
                        action: 'kcgred_delete_redirect',
                        nonce: object_kcgred.nonce,
                        id: id
                    }
                }).done(function(response) {
                    console.log(response);
                    if(response['status'] == true) {
                        row.fadeOut(300, function() {
                            $(this).remove();
                        });
                    }
                }).fail(function(response) {
                    console.log(response); 
                });
            }
        });
        
        // Toggle status
        $(document).on('change', '.toggle-status', function() {
            const id = $(this).data('id');
            const status = $(this).is(':checked') ? 1 : 0;
            const row = $(this).closest('tr');
            
            if(id) {
                $.ajax({
                    type: 'POST',
                    url: object_kcgred.ajax_url,
                    data: {
                        action: 'kcgred_toggle_redirect',
                        nonce: object_kcgred.nonce,
                        id: id,
                        status: status
                    }
                }).done(function(response) {
                    // console.log(response);
                    if (response['status'] == true) {
                        row.removeClass('inactive').addClass('active');
                    } else {
                        row.removeClass('active').addClass('inactive');
                    }
                }).fail(function(response) {
                    console.log(response); 
                });
            }
        });
        
        // Edit redirect - open modal
        $(document).on('click', '.edit-redirect', function() {
            const id = $(this).data('id');
            const row = $(this).closest('tr');
            
            // Get current values from the row
            const redirectFrom = row.find('td.kcgred-redirect-from code').text();
            const redirectTo = row.find('td.kcgred-redirect-to code').text();
            const redirectType = row.find('.redirect-type').text().trim();
            // console.log(redirectFrom);
            
            // Populate form
            $('#edit_id').val(id);
            $('#edit_redirect_from').val(redirectFrom);
            $('#edit_redirect_to').val(redirectTo);
            $('#edit_redirect_type').val(redirectType);
            
            // Show modal
            $('#edit-modal').fadeIn();
        });
        
        // Close modal
        $('.close-modal').on('click', function() {
            $('#edit-modal').fadeOut();
        });
        
        // Close modal on outside click
        $(window).on('click', function(e) {
            if ($(e.target).is('#edit-modal')) {
                $('#edit-modal').fadeOut();
            }
        });
        
        // Update redirect
        $('#edit-redirect-form').on('submit', function(e) {
            e.preventDefault();
            $(this).find('.button').attr('disabled', true);
            
            var id = $('#edit_id').val();
            var redirect_from = $(this).find('input[name="redirect_from"]').val();
            var redirect_to = $(this).find('input[name="redirect_to"]').val();
            var redirect_type = $(this).find('select[name="redirect_type"]').val();

            
            var redData = {'id': id,'redirect_from' : redirect_from, 'redirect_to' : redirect_to, 'redirect_type' : redirect_type};

            if(id) {
                $.ajax({
                    type: 'POST',
                    url: object_kcgred.ajax_url,
                    data: {
                        action: 'kcgred_save_redirect',
                        nonce: object_kcgred.nonce,
                        'form-data' : redData,
                    }
                }).done(function(response) {
                    // console.log(response);
                    if(response['status'] == false) {
                        alert(response['message']);
                        $('#edit-redirect-form').find('.button').attr('disabled', false);
                    } else {
                        location.reload();
                    }
                }).fail(function(response) {
                    console.log(response); 
                });
            } else {
                alert('Redirect ID missing');
            }
        });
        

        // Import/Export buttons
        $('#import-btn').on('click', function() {
            $('#import-form').slideToggle();
        });
        
        $('#export-btn').on('click', function() {
            window.location.href = object_kcgred.ajax_url + '?action=kcgred_export_redirects&nonce=' + object_kcgred.nonce;
        });
        
        // CSV Import
        $('#csv-import-form').on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('action', 'kcgred_import_redirects');
            formData.append('nonce', object_kcgred.nonce);
            formData.append('csv_file', $('#csv_file')[0].files[0]);
            
            $.ajax({
                url: object_kcgred.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        alert(response.data);
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                }
            });
        });
        
        
        // Real-time hit counter update (every 30 seconds)
        setInterval(function() {
            $.ajax({
                type: 'POST',
                url: object_kcgred.ajax_url,
                data: {
                    action: 'kcgred_get_redirect_stats',
                    nonce: object_kcgred.nonce,
                }
            }).done(function(response) {
                // console.log(response);
                if(response['status'] == true) {
                    $('.hits-count').find('.stat-number').text(response['total']);
                }
            }).fail(function(response) {
                console.log(response); 
            });
        }, 30000);


        // Select all redirects for bulk actions
        $('input[name="kcgred_select_all"]').on('change', function() {
            $('.redirect-list-container input[name="kcgred_redirect_ids[]"]').prop('checked', this.checked);
        });

        
        
        // Bulk actions delete selected redirects
        $('.bulkactions form').on('submit', function(e) {
            e.preventDefault();
            
            var redData = [];
            var action = $(this).find('select[name="action"]').val();
            $('.redirect-list-container input[name="kcgred_redirect_ids[]"]').each(function() {
                if($(this).prop('checked')) {
                    redData.push($(this).val());
                }
            });

            
            if(action == 'delete') {
                $.ajax({
                    type: 'POST',
                    url: object_kcgred.ajax_url,
                    data: {
                        action: 'kcgred_delete_selected_redirects_init',
                        nonce: object_kcgred.nonce,
                        'form-data' : redData,
                    }
                }).done(function(response) {
                    // console.log(response);
                    if(response['status'] == false) {
                        alert(response['message']);
                    } else {
                        location.reload();
                    }
                }).fail(function(response) {
                    console.log(response); 
                });
            }
        });

        
    });

}(jQuery));


