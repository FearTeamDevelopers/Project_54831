jQuery.noConflict();

jQuery(document).ready(function() {
    jQuery('.close, .close-announcement').click(function() {
        jQuery(this).parent().hide(1000)
    });

    if (jQuery(window).width() > 1000) {
        jQuery('#bio,#design,#styling,#contact,#partners,#news-detail,#provas').draggable();
        jQuery('#news').draggable({
            scroll: true,
            handle: 'div.news-wrapper'
        });
        jQuery('#portfolio, #collection').draggable({
            scroll: true,
            handle: 'div.content'
        });
    }

    jQuery('#navi li a').click(function() {
        jQuery('#temptopdiv:visible').hide(1000);

        if (jQuery(window).width() < 1000) {
            jQuery('#main').children('div:visible').hide();
        }

        jQuery('#main div, #main p, #main iframe, #main img').css({
            opacity: '0.5',
            zIndex: '10'
        });

        var id = jQuery(this).parent().attr('id');
        var n = id.replace('navi_', '');
        var target = '#main #' + n + ' .content';

        jQuery(target).load('/' + n, function() {
            jQuery('#main #' + n).show(1000, function() {
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

                jQuery('.ajaxLink').click(function() {
                    var id = jQuery(this).attr('id');
                    var parts = id.split('_');

                    if (parts[1] == 'styling') {
                        jQuery('#portfolio .content').load('/collection/show/' + parts[2], function() {
                            jQuery('#main div, #main p, #main iframe, #main img').css({
                                opacity: '0.5',
                                zIndex: '10'
                            });
                            jQuery('#main #portfolio').show(1000, function() {
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
                    if (parts[1] == 'design') {
                        jQuery('#collection .content').load('/collection/show/' + parts[2], function() {
                            jQuery('#main div, #main p, #main iframe, #main img').css({
                                opacity: '0.5',
                                zIndex: '10'
                            });
                            jQuery('#main #collection').show(1000, function() {
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
                    if (parts[1] == 'bio') {
                        jQuery('#bio .content').load('/bio', function() {
                            jQuery('#main div, #main p, #main iframe, #main img').css({
                                opacity: '0.5',
                                zIndex: '10'
                            });
                            jQuery('#main #bio').show(1000, function() {
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
        });
    });

    jQuery('#main div').mousedown(function() {
        jQuery('#main div, #main p, #main iframe, #main img').css({
            opacity: '0.5',
            zIndex: '10'
        });

        jQuery(this).css({
            opacity: '1',
            zIndex: '80'
        });

        jQuery('div, p, iframe, img', this).css('opacity', '1');
    });

    jQuery('#announcement').click(function() {
        jQuery('#main div, #main p, #main iframe, #main img').css({
            opacity: '0.5',
            zIndex: '10'
        });
        jQuery(this).css({
            opacity: '1',
            zIndex: '40'
        });
        jQuery('.text', this).css('opacity', '1');
    });

});