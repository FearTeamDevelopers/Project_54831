jQuery(document).ready(function() {

//    //Default Starting Page Results
//    jQuery(".pagination li:first a").addClass('current');
//
//    //Pagination Click
//    jQuery(".pagination li a").click(function() {
//        //CSS Styles
//        jQuery(".pagination li a").removeClass('current');
//
//        jQuery(this).addClass('current');
//
//        //Loading Data
//        var pageNum = jQuery(this).attr('id');
//        jQuery("#news .text").load("/news/" + pageNum, function() {
//            jQuery("#news a.close").focus();
//        });
//    });

    /*** pagination ***/
    jQuery('.pagination a').click(function() {
        var p = jQuery(this).parent();
        if (!p.hasClass('previous') && !p.hasClass('first') && !p.hasClass('next') && !p.hasClass('last')) {
            jQuery('.pagination a').each(function() {
                jQuery(this).removeClass('current');
            });
            jQuery(this).addClass('current');

            //disable next and last button when active page is the last page
            if (jQuery(this).parent().next().hasClass('next')) {
                jQuery('.pagination .next a, .pagination .last a').addClass('disable');
            } else {
                jQuery('.pagination .next a, .pagination .last a').removeClass('disable');
            }

            //disable first and previous button when active page is the first page
            if (jQuery(this).parent().prev().hasClass('previous')) {
                jQuery('.pagination .previous a, .pagination .first a').addClass('disable');
            } else {
                jQuery('.pagination .previous a, .pagination .first a').removeClass('disable');
            }
            var pageNum = jQuery(this).attr('id');
            jQuery("#news .text").load("/news/" + pageNum, function() {
                jQuery("#news a.close").focus();
            });
        }
        return false;
    });

    //clicking next button
    jQuery('.pagination li.next a').click(function() {
        if (!jQuery(this).hasClass('disable')) {
            if (!jQuery(this).parent().prev().find('a').hasClass('current')) {
                jQuery('.pagination a.current').removeClass('current').parent().next().find('a').addClass('current');
            }
        }
        if (jQuery('.pagination a.current').parent().next().hasClass('next')) {
            jQuery('.pagination .next a, .pagination .last a').addClass('disable');
        }
        if (!jQuery('.pagination a.current').parent().prev().hasClass('previous')) {
            jQuery('.pagination .previous a, .pagination .first a').removeClass('disable');
        }

    });

    //clicking previous button
    jQuery('.pagination li.previous a').click(function() {
        if (!jQuery(this).hasClass('disable')) {
            if (!jQuery(this).parent().next().find('a').hasClass('current')) {
                jQuery('.pagination a.current').removeClass('current').parent().prev().find('a').addClass('current');
            }
        }
        if (jQuery('.pagination a.current').parent().prev().hasClass('previous')) {
            jQuery('.pagination .first a, .pagination .previous a').addClass('disable');
        }
        if (!jQuery('.pagination a.current').parent().next().hasClass('next')) {
            jQuery('.pagination .next a, .pagination .last a').removeClass('disable');
        }

    });

    //clicking last button
    jQuery('.pagination .last a').click(function() {
        jQuery(this).addClass('disable');
        jQuery('.pagination .next a').addClass('disable');
        jQuery('.pagination .current').removeClass('current');
        jQuery('.pagination .next a').parent().prev().find('a').addClass('current');
        jQuery('.pagination .first a, .pagination .previous a').removeClass('disable');
    });

    //clicking last button
    jQuery('.pagination .first a').click(function() {
        jQuery(this).addClass('disable');
        jQuery('.pagination .previous a').addClass('disable');
        jQuery('.pagination .current').removeClass('current');
        jQuery('.pagination .previous a').parent().next().find('a').addClass('current');
        jQuery('.pagination .last a, .pagination .next a').removeClass('disable');
    });
});