jQuery.noConflict();

jQuery(document).ready(function() {
    jQuery('input[name=title]').blur(function(){
        var t = jQuery('input[name=title]').val();
        var val = t.replace(/\s/g, '-');
        jQuery('input[name=urlkey]').val(val);
    });
    jQuery('#news-text-to-teaser').click(function(event){
        event.preventDefault();
        var value = CKEDITOR.instances['ckeditor'].getData();
        var short = value.substr(0,240);
        CKEDITOR.instances['ckeditor2'].setData(short);
    });
    jQuery('#news-teaser-to-meta').click(function(event){
        event.preventDefault();
        var value = CKEDITOR.instances['ckeditor2'].getData();
        var short = value.substr(0,250);
        jQuery('textarea[name=metadescription]').val(short);
    });
    jQuery('#news-clear-text').click(function(event){
        event.preventDefault();
        CKEDITOR.instances['ckeditor'].setData('');
    });
    jQuery('#news-clear-teaser').click(function(event){
        event.preventDefault();
        CKEDITOR.instances['ckeditor2'].setData('');
    });
    jQuery('#news-readmore-link').click(function(event){
        event.preventDefault();
        CKEDITOR.instances['ckeditor2'].insertText('(!read_more!)');
    });
    jQuery('.img-to-text').click(function(event){
        event.preventDefault();
        var id = jQuery(this).attr('value');
        CKEDITOR.instances['ckeditor'].insertText('(!photo_'+id+'!)');
    });
    jQuery('.img-to-teaser').click(function(event){
        event.preventDefault();
        var id = jQuery(this).attr('value');
        CKEDITOR.instances['ckeditor2'].insertText('(!photo_'+id+'!)');
    });
    jQuery('.img-to-meta').click(function(event){
        event.preventDefault();
        var path = jQuery(this).attr('value');
        jQuery('input[name=metaimage]').val(path);
    });
    jQuery('.video-to-text').click(function(event){
        event.preventDefault();
        var id = jQuery(this).attr('value');
        CKEDITOR.instances['ckeditor'].insertText('(!video_'+id+'!)');
    });
    jQuery('.video-to-teaser').click(function(event){
        event.preventDefault();
        var id = jQuery(this).attr('value');
        CKEDITOR.instances['ckeditor2'].insertText('(!video_'+id+'!)');
    });
});