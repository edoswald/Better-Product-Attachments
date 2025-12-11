jQuery(document).ready(function($) {
    
    // Sortable
    if( $("#cw-files-tbody").sortable ) { 
        $("#cw-files-tbody").sortable({ handle: '.cw-row-handle' }); 
    }

    // Init Datepicker on load
    $('.cw-datepicker').datepicker({ dateFormat: 'yy-mm-dd' });

    // Add Row
    $('#cw-add-row').on('click', function() {
        var row = `
        <tr class="cw-file-row">
            <td><span class="dashicons dashicons-menu cw-row-handle"></span></td>
            <td><input type="text" name="cw_custom_name[]" class="cw-file-input" /></td>
            <td><input type="text" name="cw_custom_url[]" class="cw-file-url cw-file-input" /></td>
            <td>
                <select name="cw_file_vis[]">
                    <option value="visible">Visible</option>
                    <option value="hidden">Hidden</option>
                </select>
            </td>
            <td>
                <select name="cw_file_role_restrict[]" style="width: 100px;">
                    <option value="all">Everyone</option>
                    <option value="logged_in">Logged In</option>
                    <option value="guest">Guests</option>
                    <option value="customer">Customer Role</option>
                    <option value="administrator">Admin</option>
                </select>
            </td>
            <td>
                <select name="cw_file_status[]" style="width: 100px;">
                    <option value="any">Immediate</option>
                    <option value="processing">Processing</option>
                    <option value="completed">Completed</option>
                </select>
            </td>
            <td>
                <input type="text" name="cw_file_expiry[]" class="cw-datepicker cw-file-input" placeholder="YYYY-MM-DD" style="width: 90px;" />
            </td>
            <td>
                <button type="button" class="button cw-upload-btn"><span class="dashicons dashicons-upload"></span></button>
                <button type="button" class="button cw-remove-row"><span class="dashicons dashicons-trash"></span></button>
            </td>
        </tr>`;
        
        var $newRow = $(row);
        $('#cw-files-tbody').append($newRow);
        // Init datepicker on new row
        $newRow.find('.cw-datepicker').datepicker({ dateFormat: 'yy-mm-dd' });
    });

    // Remove Row
    $(document).on('click', '.cw-remove-row', function() { $(this).closest('tr').remove(); });

    // Media Uploader
    var file_frame;
    $(document).on('click', '.cw-upload-btn', function(event) {
        event.preventDefault();
        var $button = $(this);
        var $row = $button.closest('tr');

        if ( file_frame ) { file_frame.open(); return; }

        file_frame = wp.media.frames.file_frame = wp.media({ title: 'Select File', button: { text: 'Use' }, multiple: false });
        file_frame.on( 'select', function() {
            var attachment = file_frame.state().get('selection').first().toJSON();
            $row.find('.cw-file-url').val(attachment.id);
            if($row.find('input[name="cw_custom_name[]"]').val() === '') {
                $row.find('input[name="cw_custom_name[]"]').val(attachment.title);
            }
        });
        file_frame.open();
    });
});