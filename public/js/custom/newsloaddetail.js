jQuery.noConflict();
jQuery(document).ready(function() {
    jQuery('.ajaxLink').click(function() {
        var id = jQuery(this).attr('id');
        var parts = id.split('_');

        if (parts[1] == 'news-detail') {
            jQuery('#news-detail .content').load('/news/detail/' + parts[2], function() {
                jQuery('#main div, #main p, #main iframe, #main img').css({
                    opacity: '0.5',
                    zIndex: '10'
                });
                jQuery('#main #news-detail').show(1000, function() {
                    jQuery(this).css({
                        opacity: '1',
                        zIndex: '20'
                    });
                    jQuery('div, p, iframe, img', this).css('opacity', '1');

                    jQuery('iframe').each(function() {
                        var a = jQuery(this).attr('src');
                        var b = 'wmode=transparent';
                        if (a.indexOf('?') != -1) {
                            jQuery(this).attr('src', a + '&' + b);
                        } else {
                            jQuery(this).attr('src', a + '?' + b);
                        }
                    });
                });
            });
        }
    });
});