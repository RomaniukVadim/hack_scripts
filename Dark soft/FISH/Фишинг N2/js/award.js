function adjustGal() {
    var gal = jQuery('.nomination-wrapper');
    var related = jQuery('.nominee-related-articles');
    if (!gal.length) {
        return false;
    }

    //var h = jQuery(window).height() / 2 - gal.height() / 2 - 36;
    //gal.css('padding-top', h > 0 ? h + 'px' : '30px');

    var contentHeight = related.outerHeight();
    gal.children().each(function(){
        contentHeight += jQuery(this).outerHeight();
    });
    var gap = (window.innerHeight - contentHeight) / 2;

    gal.css('padding-top', gap > 0 ? gap + 'px' : '30px');
    related.css('margin-top', gap > 0 ? gap + 'px' : '30px');
}

function animateNomTabs() {
    if (jQuery(window).width() > 1024) {
        return false;
    }
    var bound = jQuery(window).scrollTop() + jQuery(window).height() / 2;

    jQuery('.nomination-tab').each(function () {
        var top = jQuery(this).offset().top, bottom = top + jQuery(this).height();
        if (top < bound && bound < bottom) {
            jQuery(this).addClass('hover');
        } else {
            jQuery(this).removeClass('hover');
        }
    });
}

function adjustPartnersContent() {
    var pc = jQuery('.award-partners-content');
    var sb = jQuery('.col-sidebar > .container-fluid > .row > .col-xs-12');

    if (jQuery(window).width() < 992) {
        pc.height('auto');
    } else {
        pc.height((sb.height() - 80) + 'px');
    }
}

function loadRelatedArticles(slick, currentSlide) {
    var id = $(slick.$slides.get(currentSlide)).attr('id');
    if(!id) {
        return false;
    }

    var gal = jQuery('.nominees-gallery');
    var related = jQuery('.nominee-related-articles');

    related.addClass('hide');
    related.find('.related-articles').addClass('hide');

    var sid = id.replace(/\D+/g, '');
    var ra = related.find('#related-articles-' + sid);

    if (!ra.length) {
        related.append('<div id="related-articles-' + sid + '" class="related-articles col-xs-12"></div>');
        ra = related.find('#related-articles-' + sid);
        jQuery.post('/award/related-articles/' + sid, function (r) {
            if (r.result === 'success' && r.data.articles) {
                ra.html(r.data.articles);
                showRelatedArticles(related, ra);
            }
        }, 'json');
    }

    showRelatedArticles(related, ra);
}

function showRelatedArticles(relatedContainer, relatedBlock) {
    if (relatedBlock.html()) {
        relatedBlock.removeClass('hide');
        relatedContainer.removeClass('hide');
        adjustGal();
    }
}

jQuery(function () {
    var gal = jQuery('.nominees-gallery');
    var related = jQuery('.nominee-related-articles');

    if (gal.length) {

        gal.slick({
            infinite: true,
            slidesToShow: 5,
            slidesToScroll: 1,
            speed: 200,
            dots: true,
            centerMode: true,
            variableWidth: true,
            focusOnSelect: true,
            //mobileFirst: true,
            arrows: true,
            responsive: [
                {
                    breakpoint: 480,
                    settings: {
                        arrows: false
                    }
                }
            ]
        });

        gal.on('swipe', function () {
            jQuery('.voting-block').addClass('hidden');
        });

        gal.on('afterChange', function (event, slick, currentSlide, nextSlide) {
            loadRelatedArticles(slick, currentSlide);
        });

        jQuery('.slick-current').focus();
    }


    //jQuery('.voting-block button[type=submit]').click(function () {
    //    var f = jQuery(this).closest('form');
    //    var inp = f.find('textarea');
    //    if (inp.val().trim() == '') {
    //        f.append('<div class="text-danger text-center">Необходимо заполнить поле</div>');
    //        inp.addClass('has-error');
    //        return false;
    //    }
    //
    //    f.find('.text-danger').remove();
    //    inp.removeClass('has-error');
    //    f.addClass('form-disabled');
    //
    //    jQuery.post(f.attr('action'), f.serialize(), function (r) {
    //        if (r.result === 'success') {
    //            f.html('<div class="text-success text-center">' + r.descr + '</div>');
    //        } else {
    //            f.append('<div class="text-danger text-center">Произошла ошибка, повторите попытку позже</div>');
    //        }
    //        f.removeClass('form-disabled');
    //    }, 'json');
    //
    //    return false;
    //});
    //
    //jQuery('.nomination-content, .voting-block-closer').on('click', function () {
    //        jQuery('.voting-block').addClass('hidden');
    //    })
    //    .find('.label-name, .label-action, .voting-block form').click(function (e) {
    //    jQuery(this).closest('.nominees-gallery-item').find('.voting-block').removeClass('hidden');
    //    return false;
    //});

    jQuery('.nominee-photo[data-target]').click(function () {
        if (!jQuery(this).closest('.nominees-gallery-item').hasClass('slick-current')) {
            return true;
        }
        window.location.href = jQuery(this).data('target');
    });

    adjustGal();
});

jQuery(window).on('resize', function () {
    adjustGal();
    adjustPartnersContent();
});

jQuery(document).on('scroll', function () {
    animateNomTabs();
});

jQuery(window).on('load', function () {
    adjustPartnersContent();
});
