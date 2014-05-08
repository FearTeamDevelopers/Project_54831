jQuery.noConflict();

jQuery(document).ready(function() {
    jQuery('input[name=title]').blur(function() {
        var t = jQuery('input[name=title]').val();
        var val = t.replace(/\s/g, '-');
        jQuery('input[name=urlkey]').val(val);
    });
});