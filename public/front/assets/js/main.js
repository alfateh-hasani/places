/*global $, JQuery */

AOS.init();

$(document).ready(function () {

    // Slides hidden from assistive tech must not contain tabbable links.
    $(document).on("init reInit afterChange setPosition", function (event, slick) {
        if (!slick || !slick.$slider) {
            return;
        }
        var focusable = "a, button, input, select, textarea";
        $(".slick-slider [aria-hidden='true']").find(focusable).attr("tabindex", "-1");
        $(".slick-slider [aria-hidden='false']").find(focusable).filter(function () {
            return !$(this).closest("[aria-hidden='true']").length;
        }).removeAttr("tabindex");
    });

    $(".select2").select2();

    if ($('.js-searchBox').length) {
        $('.js-searchBox').searchBox({ elementWidth: '250'});
    }

    if ($('#allphotos').length) {
        var height = $(".banner-container").height();

        $(".banner-container").css('height', '464px');

        $('#allphotos').click(function () {
            if ($(".banner-container").hasClass("active")) {
                $(".banner-container").animate({height: '464px'}, {duration: 200, easing: 'swing'});
                $(".banner-container").removeClass("active");
            } else {
                $(".banner-container").animate({height: height}, {duration: 200, easing: 'swing'});
                $(".banner-container").addClass("active");
            }
        });
    }

    $("section.profile .reservations .options-button").click(function () {
        $(this).next("ul").toggleClass("active");
        // $("section.profile .reservations ul").toggleClass("active");
    });

    $('[dir="ltr"] section.slider').slick({
        responsive: [
            {
                breakpoint: 640,
                settings: {
                    arrows: false
                }
            }
        ]
    });

    $(".checkout-slider-detail").slick({
        
        rtl: true
    });

    $('[dir="rtl"] section.slider').slick({
        rtl: true,
        responsive: [
            {
                breakpoint: 640,
                settings: {
                    arrows: false
                }
            }
        ]
    });

    $("section.search-button").click(function () {
        $("section.search").addClass("active");
    });

    $()

    $("section.search .close-button").click(function () {
        $("section.search").removeClass("active");
    });

    $(".menu-button").click(function () {
        $("header .right-menu").addClass("active");
    });

    $(".login-button").click(function () {
        $("header .left-menu").addClass("active");
    });

    $("header .right-menu button").click(function () {
        $("header .right-menu").removeClass("active");
    });

    $("header .left-menu button").click(function () {
        $("header .left-menu").removeClass("active");
    });

    $('[dir="ltr"] .comment-list .comment-slider-1').slick({
        centerMode: true,
        slidesToShow: 6,
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 1300,
        centerPadding: '100px',
        arrows: false,
        responsive: [
            {
                breakpoint: 1600,
                settings: {
                    slidesToShow: 4
                }
            },
            {
                breakpoint: 1200,
                settings: {
                    slidesToShow: 3
                }
            },
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 1,
                    centerPadding: '40px'
                }
            }
        ]
    });

    $('[dir="rtl"] .comment-list .comment-slider-1').slick({
        centerMode: true,
        rtl: true,
        slidesToShow: 6,
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 1300,
        centerPadding: '100px',
        arrows: false,
        responsive: [
            {
                breakpoint: 1600,
                settings: {
                    slidesToShow: 4
                }
            },
            {
                breakpoint: 1200,
                settings: {
                    slidesToShow: 3
                }
            },
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 1,
                    centerPadding: '40px'
                }
            }
        ]
    });

    $('[dir="ltr"] .comment-list .comment-slider-2').slick({
        centerMode: true,
        slidesToShow: 6,
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 800,
        centerPadding: '100px',
        arrows: false,
        responsive: [
            {
                breakpoint: 1600,
                settings: {
                    slidesToShow: 4
                }
            },
            {
                breakpoint: 1200,
                settings: {
                    slidesToShow: 3
                }
            },
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 1,
                    centerPadding: '40px'
                }
            }
        ]
    });

    $('[dir="rtl"] .comment-list .comment-slider-2').slick({
        rtl: true,
        centerMode: true,
        slidesToShow: 6,
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 800,
        centerPadding: '100px',
        arrows: false,
        responsive: [
            {
                breakpoint: 1600,
                settings: {
                    slidesToShow: 4
                }
            },
            {
                breakpoint: 1200,
                settings: {
                    slidesToShow: 3
                }
            },
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 1,
                    centerPadding: '40px'
                }
            }
        ]
    });

    $('[dir="ltr"] section.list .slider').slick({
        dots: true
    });

    $('[dir="rtl"] section.list .slider').slick({
        rtl: true,
        dots: true
    });

    $("section.list .favorite").click(function () {
        $(this).toggleClass("favorite-active");
    });

    $('[dir="ltr"] section.properitirs .slider').slick({
        slidesToShow: 4,
        slidesToScroll: 4,
        responsive: [
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1,
                }
            }
        ]
    });

    $('[dir="rtl"] section.properitirs .slider').slick({
        slidesToShow: 4,
        slidesToScroll: 4,
        rtl: true,
        responsive: [
            {
                breakpoint: 768,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1,
                }
            }
        ]
    });

    $('[dir="ltr"] section.properitirs .category').slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        fade: true,
        arrows: false,
        dots: true,
        customPaging: function (slider, i) {
          var title = $(slider.$slides[i]).data("title");
          return title;
        },
    });

    $('[dir="rtl"] section.properitirs .category').slick({
        slidesToShow: 1,
        rtl: true,
        slidesToScroll: 1,
        fade: true,
        arrows: false,
        dots: true,
        customPaging: function (slider, i) {
          var title = $(slider.$slides[i]).data("title");
          return title;
        },
    });

    $(".video-button").click(function () {
        if ($(this).hasClass("active")) {
            $(this).removeClass("active");
            $(".banner-side a").not(".video").show();
            $(".col-span-2").show();
        } else {
            $(this).addClass("active");
            $(".banner-side").height("auto");
            $(".banner-side a").not(".video").hide();
            $(".col-span-2").hide();
        }
    });

    $(".photo-button").click(function () {
        if ($(this).hasClass("active")) {
            $(this).removeClass("active");
        } else {
            $(this).addClass("active");
            $(".banner-side").height("auto");
        }
    });

    $(".showmore").click(function () {
        if ($(this).hasClass("active")) {
            $(this).removeClass("active");
            $(this).text("Read More");
            $(this).prev("p").css({"maxHeight":"72px"});
        } else {
            $(this).addClass("active");
            $(this).text("Read Less");
            $(this).prev("p").css({"maxHeight":"10000px"});
        }
    });

    $(".faq ul li").click(function () {
        if ($(this).hasClass("active")) {
            // $(".faq ul li p").slideUp("slow");
            $(".faq ul li").removeClass("active");
        } else {
            // $(".faq ul li p").slideUp("slow");
            // $(this).children("p").slideToggle('slow');
            $(".faq ul li").removeClass("active");
            $(this).addClass("active");
        }
    });

    $("section.search .persons > p").click(function () {
        $("section.search .persons ul").slideToggle();
    });

    $("section.search .persons ul").click(function () {
        $("section.search .persons .content").text("البالغون: " + $('#counter-input').val() + " الأطفال: " + $('#counter-input1').val());
    });

    $(".blogslider").slick();

    if($("#tabs").length) {
        $("#tabs").tabs();
    }

    if ($(window).width() < 640) {
        $('[dir="ltr"] section.cities .slider').slick({
            centerMode: true
        });
        $('[dir="ltr"]  section.detail .photos').slick({
            arrows: false
        });
    }

    if ($(window).width() < 640) {
        $('[dir="ltr"] section.cities .slider').slick({
            centerMode: true,
            rtl: true
        });
        $('[dir="rtl"] section.detail .photos').slick({
            arrows: false,
            rtl: true
        });
    }

    let isRtl = $("html").attr("dir") === "rtl";
   

    if($(".slider-checkout").length > 0){
        let mainSlider = $(".slider-checkout")
        .slick({
          slidesToShow: 1,
          slidesToScroll: 1,
          autoplay: true,
          autoplaySpeed: 4000,
          loop: true,
          rtl: isRtl ? true : false,
          fade: true,
          dots: true,
          arrows: false,
        })
    }

});



function toggleFavorite(apartmentId) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        $.ajax({
            url: '/customer/toggle-favorite',
            type: 'POST',
            data: {
                apartment_id: apartmentId,
                _token: csrfToken
            },
            success: function(data) {
                if (data.success) {
                    let icon = $('#favorite-icon-' + apartmentId);
                    if (data.action === 'added') {


                        icon.attr('src', '/front/assets/img/favorite-active.svg');
                        // Swal.fire({
                        //     icon: 'success',
                        //     title: data.message,
                        //     timer: 1500,
                        //     showConfirmButton: false
                        // });
                    } else {

                        icon.attr('src', '/front/assets/img/favoritee.svg');
                        // Swal.fire({
                        //     icon: 'info',
                        //     title: data.message,
                        //     text: data.message,
                        //     timer: 1500,
                        //     showConfirmButton: false
                        // });
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: data.message,

                    });
                }
            },
            error: function(xhr, status, error) {
                if (xhr.status === 401) {
                    Swal.fire({
                        icon: 'error',
                        title: 'يجب تسجيل الدخول أولاً',

                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                    });
                }
            }
        });
    }

    $(document).on('click', function(event) {
        if ($('section.search .persons ul').is(':visible')) {
            if (!$(event.target).closest('section.search .persons').length) {
                $('section.search .persons ul').hide();
            }
        }
    });
    