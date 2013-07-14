// Advanced Excerpt options script
jQuery(function($)
{
    var plugin_prefix = 'advancedexcerpt';
    var tag_cols = $('#' + plugin_prefix + '_tags_table tr:eq(1) td').length;
    
    var tag_list = new Array();
    $('#' + plugin_prefix + '_tags_table input').each(function(i, el)
    {
       tag_list.push($(el).val()); 
    });
    
    // Add a tag to the checkbox table
    $('#' + plugin_prefix + '_add_tag').click(function(event)
    {
        event.preventDefault();
        
        var tag = $('#' + plugin_prefix + '_more_tags option:selected').val();
        
        // No duplicate tags in the table
        if($.inArray(tag, tag_list) > -1)
        {
            return;
        }
        tag_list.push(tag);
        
        var last_row = $('#' + plugin_prefix + '_tags_table tr:last-child');
        var tag_count = last_row.find('input').length;
        var tag_cell = $(
        '<td>' +
            '<input name="' + plugin_prefix + '_allowed_tags[]" type="checkbox" value="' + tag + '" checked="checked" />' +
            '<code>' + tag + '</code>' +
        '</td>'
        );
        
        if(tag_count < tag_cols)
        {
            // Add to last row
            var span = last_row.find('td[colspan]');
            if(span.attr('colspan') > 1)
            {
                span.attr('colspan', span.attr('colspan') - 1);
                tag_cell.insertBefore(span);
            }
            else
            {
                span.replaceWith(tag_cell);
            }
        }
        else
        {
            // New row
            $('<tr><td colspan="' + (tag_cols - 1) + '">&nbsp;</td></tr>').insertAfter(last_row).prepend(tag_cell);
        }
    });
    
    // Check all boxes
    $('#' + plugin_prefix + '_select_all').click(function(event)
    {
        event.preventDefault();
        $('input[name="' + plugin_prefix + '_allowed_tags[]"]:gt(0)').attr('checked', 'checked');
    });
    
    // Uncheck all boxes
    $('#' + plugin_prefix + '_select_none').click(function(event)
    {
        event.preventDefault();
        $('input[name="' + plugin_prefix + '_allowed_tags[]"]:gt(0)').removeAttr('checked');
    });
});