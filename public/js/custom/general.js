jQuery.noConflict();

jQuery(document).ready(function() {

    jQuery(window).load(function() {
        jQuery('#loader, .loader').hide();

        jQuery.post('/admin/system/showprofiler/', function(msg) {
            jQuery('body').append(msg);
        });
    });

    jQuery('a.view').lightBox();
    jQuery('#tabs').tabs();

    jQuery('.datepicker').datepicker({
        changeMonth: true,
        changeYear: true,
        dateFormat: 'yy-mm-dd',
        firstDay: 1
    });

    jQuery('button.dialog, a.dialog').click(function() {
        var href = jQuery(this).attr('href');
        var val = jQuery(this).attr('value');

        jQuery('#dialog p').load(href);

        jQuery('#dialog').dialog({
            title: val,
            width: 600,
            modal: true,
            position: {my: 'center', at: 'top', of: window},
            buttons: {
                Close: function() {
                    jQuery(this).dialog('close');
                }
            }
        });
        return false;
    });

// DATA TABLES
    jQuery.fn.dataTableExt.oApi.fnPagingInfo = function(oSettings)
    {
        return {
            "iStart": oSettings._iDisplayStart,
            "iEnd": oSettings.fnDisplayEnd(),
            "iLength": oSettings._iDisplayLength,
            "iTotal": oSettings.fnRecordsTotal(),
            "iFilteredTotal": oSettings.fnRecordsDisplay(),
            "iPage": Math.ceil(oSettings._iDisplayStart / oSettings._iDisplayLength),
            "iTotalPages": Math.ceil(oSettings.fnRecordsDisplay() / oSettings._iDisplayLength)
        };
    };


    jQuery('.stdtable').DataTable({
        'aaSorting': [],
        'iDisplayLength': 25,
        'sPaginationType': 'full_numbers'
    });

    var selected = [];

    var table = jQuery('.stdtable2').DataTable({
        'aaSorting': [],
        'iDisplayLength': 50,
        'sPaginationType': 'full_numbers',
        "serverSide": true,
        "bProcessing": true,
        "sServerMethod": "POST",
        "sAjaxDataProp": "data",
        "sAjaxSource": "/admin/photo/load/",
        "fnServerParams": function(aoData) {
            aoData.push({"name": "page", "value": this.fnPagingInfo().iPage});
        },
        "rowCallback": function(row, data, displayIndex) {
            if (jQuery.inArray(data[0], selected) !== -1) {
                jQuery(row).addClass('togglerow');
            }
        },
        "aoColumns": [
            null,
            {"bSortable": false},
            null,
            null,
            null,
            null,
            {"bSortable": false},
            null,
            null,
            {"bSortable": false}
        ]
    });

    jQuery('.stdtable2 tbody').on('click', 'tr', function() {
        var id = jQuery(this).find('td:first').text();
        var index = jQuery.inArray(id, selected);

        if (index === -1) {
            selected.push(id);
        } else {
            selected.splice(index, 1);
        }

        jQuery(this).toggleClass('togglerow');
    });

    jQuery('.tableoptions select').change(function() {
        var val = jQuery(this).children('option:selected').val();
        var name = jQuery(this).attr('name');

        jQuery('.tableoptions select[name=' + name + ']').val(val);

        if (val == 2) {
            var tr = jQuery('.stdtable2 tbody tr');

            tr.each(function() {
                var id = jQuery(this).find('td:first').text();
                var index = jQuery.inArray(id, selected);

                if (index === -1) {
                    selected.push(id);
                } 
                jQuery(this).addClass('togglerow');
            });
        } else if (val == 1) {
            jQuery('.stdtable2 tbody tr.togglerow').removeClass('togglerow');
            selected = [];
        }
    });

    jQuery('a.ajax-massaction').click(function(event) {
        event.preventDefault();

        var url = jQuery(this).attr('href');
        var action = jQuery('.tableoptions select[name=action]').children('option:selected').val();
        var csrf = jQuery('#csrf').val();

        jQuery.post(url, {csrf: csrf, action: action, photoids: selected}, function(msg) {
            jQuery('#dialog p').text(msg);

            jQuery('#dialog').dialog({
                title: 'Výsledek',
                width: 600,
                modal: true,
                position: {my: 'center', at: 'top', of: window},
                buttons: {
                    Close: function() {
                        jQuery(this).dialog('close');
                        jQuery('.stdtable2 tbody tr.togglerow').removeClass('togglerow');
                        jQuery('.tableoptions select[name=selection]').val('1');
                        selected = [];
                        table.ajax.reload();
                    }
                }
            });
        });

        return false;
    });

    //userinfo
    jQuery('.userinfo').click(function() {
        if (!jQuery(this).hasClass('userinfodrop')) {
            var t = jQuery(this);
            jQuery('.userdrop').width(t.width() + 30);
            jQuery('.userdrop').slideDown('fast');
            t.addClass('userinfodrop');

        } else {
            jQuery(this).removeClass('userinfodrop');
            jQuery('.userdrop').hide();
        }

        //remove notification box if visible
        jQuery('.notialert').removeClass('notiactive');
        jQuery('.notibox').hide();

        return false;
    });


    //notification onclick
    jQuery('.notialert').click(function() {
        var t = jQuery(this);
        var url = t.attr('href');
        if (!t.hasClass('notiactive')) {
            jQuery('.notibox').slideDown('fast');
            jQuery('.noticontent').empty();
            jQuery('.notibox .tabmenu li').each(function() {
                jQuery(this).removeClass('current');
            });
            //make first li as default active menu
            jQuery('.notibox .tabmenu li:first-child').addClass('current');

            t.addClass('notiactive');

            jQuery('.notibox .loader').show();
            jQuery.post(url, function(data) {
                jQuery('.notibox .loader').hide();
                jQuery('.noticontent').append(data);
            });
        } else {
            t.removeClass('notiactive');
            jQuery('.notibox').hide();
        }

        //this will hide user info drop down when visible
        jQuery('.userinfo').removeClass('userinfodrop');
        jQuery('.userdrop').hide();

        return false;
    });


    jQuery(document).click(function(event) {
        var ud = jQuery('.userdrop');
        var nb = jQuery('.notibox');

        //hide user drop menu when clicked outside of this element
        if (!jQuery(event.target).is('.userdrop') && ud.is(':visible')) {
            ud.hide();
            jQuery('.userinfo').removeClass('userinfodrop');
        }

        //hide notification box when clicked outside of this element
        if (!jQuery(event.target).is('.notibox') && nb.is(':visible')) {
            nb.hide();
            jQuery('.notialert').removeClass('notiactive');
        }
    });

    //notification box tab menu
    jQuery('.tabmenu a').click(function() {
        var url = jQuery(this).attr('href');

        //reset active menu
        jQuery('.tabmenu li').each(function() {
            jQuery(this).removeClass('current');
        });

        jQuery('.noticontent').empty();
        jQuery('.notibox .loader').show();
        jQuery(this).parent().addClass('current');
        jQuery.post(url, function(data) {
            jQuery('.notibox .loader').hide();
            jQuery('.noticontent').append(data);
        });
        return false;
    });

    // Widget Box Title on Hover event
    // show arrow image in the right side of the title upon hover
    jQuery('.widgetbox .title').hover(function() {
        if (!jQuery(this).parent().hasClass('uncollapsible'))
            jQuery(this).addClass('titlehover');
    }, function() {
        jQuery(this).removeClass('titlehover');
    });

    //show/hide widget content when widget title is clicked
    jQuery('.widgetbox .title').click(function() {
        if (!jQuery(this).parent().hasClass('uncollapsible')) {
            if (jQuery(this).next().is(':visible')) {
                jQuery(this).next().slideUp('fast');
                jQuery(this).addClass('widgettoggle');
            } else {
                jQuery(this).next().slideDown('fast');
                jQuery(this).removeClass('widgettoggle');
            }
        }
    });

    //wrap menu to em when click will return to true
    //this code is required in order the code (next below this code) to work.
    jQuery('.leftmenu a span').each(function() {
        jQuery(this).wrapInner('<em />');
    });

    jQuery('.leftmenu a').click(function(e) {
        var t = jQuery(this);
        var p = t.parent();
        var ul = p.find('ul');
        var li = jQuery(this).parents('.lefticon');

        //check if menu have sub menu
        if (jQuery(this).hasClass('menudrop')) {

            //check if menu is collapsed
            if (!li.length > 0) {

                //check if sub menu is available
                if (ul.length > 0) {

                    //check if menu is visible
                    if (ul.is(':visible')) {
                        ul.slideUp('fast');
                        p.next().css({borderTop: '0'});
                        t.removeClass('active');
                    } else {
                        ul.slideDown('fast');
                        p.next().css({borderTop: '1px solid #ddd'});
                        t.addClass('active');
                    }
                }

                if (jQuery(e.target).is('em'))
                    return true;
                else
                    return false;
            } else {
                return true;
            }

            //redirect to assigned link when menu does not have a sub menu
        } else {
            return true;
        }
    });

    //show tooltip menu when left menu is collapsed
    jQuery('.leftmenu a').hover(function() {
        if (jQuery(this).parents('.lefticon').length > 0) {
            jQuery(this).next().stop(true, true).fadeIn();
        }
    }, function() {
        if (jQuery(this).parents('.lefticon').length > 0) {
            jQuery(this).next().stop(true, true).fadeOut();
        }
    });

    //show/hide left menu to switch into full/icon only menu
    jQuery('#togglemenuleft a').click(function() {
        if (jQuery('.mainwrapper').hasClass('lefticon')) {
            jQuery('.mainwrapper').removeClass('lefticon');
            jQuery(this).removeClass('toggle');

            //remove all tooltip element upon switching to full menu view
            jQuery('.leftmenu a').each(function() {
                jQuery(this).next().remove();
            });

        } else {
            jQuery('.mainwrapper').addClass('lefticon');
            jQuery(this).addClass('toggle');

            showTooltipLeftMenu();
        }
    });

    function showTooltipLeftMenu() {
        //create tooltip menu upon switching to icon only menu view
        jQuery('.leftmenu a').each(function() {
            var text = jQuery(this).text();
            jQuery(this).removeClass('active');
            jQuery(this).parent().attr('style', '');
            jQuery(this).parent().find('ul').hide();
            jQuery(this).after('<div class="menutip">' + text + '</div>');
        });
    }

    /** FLOAT LEFT SIDEBAR **/
    jQuery(document).scroll(function() {
        var pos = jQuery(document).scrollTop();
        if (pos > 50) {
            jQuery('.floatleft').css({position: 'fixed', top: '10px', right: '10px'});
        } else {
            jQuery('.floatleft').css({position: 'absolute', top: 0, right: 0});
        }
    });

    /** FLOAT RIGHT SIDEBAR **/
    jQuery(document).scroll(function() {
        if (jQuery(this).width() > 580) {
            var pos = jQuery(document).scrollTop();
            if (pos > 50) {
                jQuery('.floatright').css({position: 'fixed', top: '10px', right: '10px'});
            } else {
                jQuery('.floatright').css({position: 'absolute', top: 0, right: 0});
            }
        }
    });

    //NOTIFICATION CLOSE BUTTON
    jQuery('.notification .close').click(function() {
        jQuery(this).parent().fadeOut();
    });

    //buttons in error page
    jQuery('.errorWrapper a').hover(function() {
        jQuery(this).switchClass('default', 'hover');
    }, function() {
        jQuery(this).switchClass('hover', 'default');
    });

    //screen resize
    var TO = false;
    jQuery(window).resize(function() {
        if (jQuery(this).width() < 1024) {
            jQuery('.mainwrapper').addClass('lefticon');
            jQuery('#togglemenuleft').hide();
            jQuery('.mainright').insertBefore('.footer');

            showTooltipLeftMenu();

            if (jQuery(this).width() <= 580) {
                jQuery('.stdtable').wrap('<div class="tablewrapper"></div>');

                if (jQuery('.headerinner2').length == 0)
                    insertHeaderInner2();
            } else {
                removeHeaderInner2();
            }

        } else {
            toggleLeftMenu();
            removeHeaderInner2();
        }

    });

    if (jQuery(window).width() < 1024) {
        jQuery('.mainwrapper').addClass('lefticon');
        jQuery('#togglemenuleft').hide();
        jQuery('.mainright').insertBefore('.footer');

        showTooltipLeftMenu();

        if (jQuery(window).width() <= 580) {
            jQuery('table').wrap('<div class="tablewrapper"></div>');
            insertHeaderInner2();
        }

    } else {
        toggleLeftMenu();
    }

    function toggleLeftMenu() {
        if (!jQuery('.mainwrapper').hasClass('lefticon')) {
            jQuery('.mainwrapper').removeClass('lefticon');
            jQuery('#togglemenuleft').show();
        } else {
            jQuery('#togglemenuleft').show();
            jQuery('#togglemenuleft a').addClass('toggle');
        }
    }

    function insertHeaderInner2() {
        jQuery('.headerinner').after('<div class="headerinner2"></div>');
        jQuery('#searchPanel').appendTo('.headerinner2');
        jQuery('#userPanel').appendTo('.headerinner2');
        jQuery('#userPanel').addClass('userinfomenu');
    }

    function removeHeaderInner2() {
        jQuery('#searchPanel').insertBefore('#notiPanel');
        jQuery('#userPanel').insertAfter('#notiPanel');
        jQuery('#userPanel').removeClass('userinfomenu');
        jQuery('.headerinner2').remove();
    }

    /* ---------------------- CUSTOM SCRIPTS --------------------------------*/
    //Collection
    jQuery('.uploadPhotoForm .multi_upload').click(function() {
        if (jQuery('.uploadPhotoForm .file_inputs input[type=file]').length < 7) {
            jQuery('.uploadPhotoForm .file_inputs input[type=file]')
                    .last()
                    .after('<input type="file" name="photos[]" />');
        }
    });

    jQuery('.uploadPhotoForm .multi_upload_dec').click(function() {
        if (jQuery('.uploadPhotoForm .file_inputs input[type=file]').length > 1) {
            jQuery('.uploadPhotoForm .file_inputs input[type=file]').last().remove();
        }
    });

    jQuery('.uploadCollectionPhotoForm .multi_upload').click(function() {
        if (jQuery('.uploadCollectionPhotoForm .file_inputs input[type=file]').length < 7) {
            jQuery('.uploadCollectionPhotoForm .file_inputs input[type=file]')
                    .last()
                    .after('<input type="file" name="photos[]" />');
        }
    });

    jQuery('.uploadCollectionPhotoForm .multi_upload_dec').click(function() {
        if (jQuery('.uploadCollectionPhotoForm .file_inputs input[type=file]').length > 1) {
            jQuery('.uploadCollectionPhotoForm .file_inputs input[type=file]').last().remove();
        }
    });

    jQuery('.uploadPhotoForm, .uploadCollectionPhotoForm').submit(function() {
        jQuery('#loader').show();
    });

    //Partner
    jQuery('#delLogo').click(function() {
        var id = jQuery(this).attr('value');

        jQuery.post('/admin/partner/deleteLogo/' + id, function(msg) {
            if (msg == 'ok') {
                jQuery('#currentLogo').hide(500);
                jQuery('#uploadLogo').removeClass('nodisplay');
            } else {
                jQuery('#currentLogo').append("<label class='error'>An error occured while deleting logo: " + msg + "</label>")
            }
        });
    });

    /* ------------ MEDIA ---------------*/
    //a little image effectts
    jQuery('.imagelist img').hover(function() {
        jQuery(this).stop().animate({opacity: 0.75});
    }, function() {
        jQuery(this).stop().animate({opacity: 1});
    });

    //delete image in grid list
    jQuery('.imagelist a.delete').click(function(event) {
        event.preventDefault();
        var parent = jQuery(this).parents('li');
        var c = confirm('Delete this image?');
        if (c) {
            var url = jQuery(this).attr('href');

            jQuery.post(url, function(msg) {
                if (msg == 'ok') {
                    parent.hide('explode', 500);
                } else {
                    alert(msg);
                }
            });
        }
        return false;
    });

    //activate/deactivate image in grid list
    jQuery('.imagelist a.activate').click(function(event) {
        event.preventDefault();
        var parent = jQuery(this).parents('li');
        var url = jQuery(this).attr('href');

        jQuery.post(url, function(msg) {
            if (msg == 'active') {
                parent.removeClass('photoinactive').addClass('photoactive');
            } else if (msg == 'inactive') {
                parent.removeClass('photoactive').addClass('photoinactive');
            } else {
                alert(msg);
            }
        });

        return false;
    });

    //delete image in table list
    jQuery('.mediatable a.btn_trash').click(function() {
        var c = confirm('Continue delete?');
        var parentTr = jQuery(this).parents('tr');

        if (c) {
            var url = jQuery(this).attr('href');
            var token = jQuery('#csrf').val();

            jQuery.post(url, {csrf: token}, function(msg) {
                if (msg == 'ok') {
                    parentTr.fadeOut();
                } else {
                    alert(msg);
                }
            });
        }
        return false;
    });

    /* ------------- BUTTONS --------------- */

    //button hover
    jQuery('.btn').hover(function() {
        jQuery(this).stop().animate({backgroundColor: '#eee'});
    }, function() {
        jQuery(this).stop().animate({backgroundColor: '#f7f7f7'});
    });

    //standard button hover
    jQuery('.stdbtn').hover(function() {
        jQuery(this).stop().animate({opacity: 0.75});
    }, function() {
        jQuery(this).stop().animate({opacity: 1});
    });

    jQuery('.button-edit').button({
        icons: {
            primary: 'ui-icon-pencil'
        },
        text: false
    });
    jQuery('.button-delete').button({
        icons: {
            primary: 'ui-icon-trash'
        },
        text: false
    });
    jQuery('.button-detail').button({
        icons: {
            primary: 'ui-icon-search'
        },
        text: false
    });
    jQuery('.button-comment').button({
        icons: {
            primary: 'ui-icon-comment'
        },
        text: false
    });
    jQuery('.button-person').button({
        icons: {
            primary: 'ui-icon-person'
        },
        text: false
    });

    /* ---------------- TABLES ---------------------- */
    jQuery('.stdtable .checkall').click(function() {
        var parentTable = jQuery(this).parents('table');
        var ch = parentTable.find('tbody input[type=checkbox]');
        if (jQuery(this).is(':checked')) {

            //check all rows in table
            ch.each(function() {
                jQuery(this).attr('checked', true);
                jQuery(this).parent().addClass('checked');
                jQuery(this).parents('tr').addClass('selected');
            });

            //check both table header and footer
            parentTable.find('.checkall').each(function() {
                jQuery(this).attr('checked', true);
            });

        } else {
            //uncheck all rows in table
            ch.each(function() {
                jQuery(this).attr('checked', false);
                jQuery(this).parent().removeClass('checked');
                jQuery(this).parents('tr').removeClass('selected');
            });

            //uncheck both table header and footer
            parentTable.find('.checkall').each(function() {
                jQuery(this).attr('checked', false);
            });
        }
    });

    jQuery('.stdtable tbody input[type=checkbox]').click(function() {
        if (jQuery(this).is(':checked')) {
            jQuery(this).parents('tr').addClass('selected');
        } else {
            jQuery(this).parents('tr').removeClass('selected');
        }
    });

    //delete individual row
    jQuery('.stdtable a.deleteRow').click(function() {
        var c = confirm('Continue delete?');
        var parentTr = jQuery(this).parents('tr');

        if (c) {
            var url = jQuery(this).attr('href');
            var csrf = jQuery('#csrf').val();

            jQuery.post(url, {csrf: csrf}, function(msg) {
                if (msg == 'ok') {
                    parentTr.fadeOut();
                } else {
                    alert(msg);
                }
            });
        }
        return false;
    });

    //check if there is/are selected row in table
    jQuery('.massActionForm').submit(function() {
        var sel = false;
        var ch = jQuery(this).find('tbody input[type=checkbox]');

        ch.each(function() {
            if (jQuery(this).is(':checked')) {
                sel = true;
            }
        });

        if (!sel) {
            alert('No data selected');
            return false;
        } else {
            return true;
        }
    });

});

CKEDITOR.replace('ckeditor');
CKEDITOR.replace('ckeditor2');