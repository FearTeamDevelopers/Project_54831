jQuery.noConflict();

jQuery(document).ready(function() {
    jQuery('.close').click(function() {
        jQuery(this).parent().hide(1000)
    });

    jQuery('#announcement').hide();

    jQuery('#navi-select').change(function() {
        jQuery('#temptopdiv:visible').hide(1000);
        jQuery('#main').children('div:visible').hide();

        var id = jQuery(this).children('option:selected').val();
        var n = id.replace('navi_', '');

        if (n == 'news') {
            var target = '#main #' + n + ' .content';
        } else {
            var target = '#main #showaction .content';
        }

        jQuery(target).load('/' + n, function() {

            if (n == 'news') {
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
                });
            } else {
                jQuery('#main #showaction').show(1000, function() {
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
            }

            jQuery('.ajaxLink').click(function() {
                var id = jQuery(this).attr('id');
                var parts = id.split('_');

                if (parts[1] == 'design' || parts[1] == 'styling') {
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
                } else if (parts[1] == 'bio') {
                    jQuery('#showaction .content').load('/bio', function() {
                        jQuery('#main div, #main p, #main iframe, #main img').css({
                            opacity: '0.5',
                            zIndex: '10'
                        });
                        jQuery('#main #showaction').show(1000, function() {
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
                } else if(parts[1] == 'news-detail') {
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

    jQuery('.ajaxLink').click(function() {
        var id = jQuery(this).attr('id');
        var parts = id.split('_');

        if (parts[1] == 'design' || parts[1] == 'styling') {
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
        } else if (parts[1] == 'bio') {
            jQuery('#showaction .content').load('/bio', function() {
                jQuery('#main div, #main p, #main iframe, #main img').css({
                    opacity: '0.5',
                    zIndex: '10'
                });
                jQuery('#main #showaction').show(1000, function() {
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
        } else {
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