(function($){               
    jQuery.fn.lightTabs = function(){
        var createTabs = function(){
            var tabs = this;
            var i = 0;
            var showPage = function(i){
                $(tabs).children('.tabs_content').children('.content_descr').hide();
                $(tabs).children('.tabs_content').children('.content_descr').eq(i).show();
                $(tabs).children('.tabs_list').children('.tabs_item').removeClass('active');
                $(tabs).children('.tabs_list').children('.tabs_item').eq(i).addClass('active');
            };
            showPage(0);                
            $(tabs).children('.tabs_list').children('.tabs_item').each(function(index, element){
                $(element).attr('data-page', i);
                i++;                        
            });
            $(tabs).children('.tabs_list').children('.tabs_item').click(function(){
                showPage(parseInt($(this).attr('data-page')));
            });             
        };      
        return this.each(createTabs);
    };
})(jQuery);



 
$(document).ready(function () {
    function initSlider() {
    var model_car_gallery_list = $('.model_car_gallery_list');
    if (model_car_gallery_list.length) {
        var model_car_gallery_list_settings = {
            lazyLoad: 'ondemand',
            infinite: true,
            slidesToShow: 1,
            slidesToScroll: 1,
            adaptiveHeight: true,
            dots: false,
            arrows: true,
            //variableWidth: true,
            prevArrow: '<div class="prev"><i class="fa fa-chevron-left" aria-hidden="true"></i></div>',
            nextArrow: '<div class="next"><i class="fa fa-chevron-right" aria-hidden="true"></i></div>',
            responsive: [
                {
                    breakpoint: 767,
                    settings: {
                        slidesToShow: 2,
                        slidesToScroll: 1,
                        dots: true,
                        variableWidth: true,
                        arrows: false
                    }
                }   
            ]
        }
        model_car_gallery_list.slick(model_car_gallery_list_settings);
        };
    }
    initSlider();

    $('.tabs_item').on('click', function () {

        if ($(this).data('slick')) {
            if(!$('.model_car_gallery_list').hasClass('slider_init_tabs')) {
                $('.model_car_gallery_list').slick('unslick');
                initSlider();
                $('.model_car_gallery_list').addClass('slider_init_tabs');
            }
        }
    });

    $('.tabs').lightTabs();


    
    $(window).resize(function () {
        if($('.similar_model_list .slick-dots li').length < 2 ) {
            $('.similar_model_list .slick-dots').addClass('hidden');
        } else {
            $('.similar_model_list .slick-dots').removeClass('hidden');
        }
    });   

    var car_list = $('.model_car_wrapper .car_model_list');
    if (car_list.length) {
        var car_list_settings = {
            lazyLoad: 'ondemand',
            infinite: true,
            slidesToShow: 4,
            slidesToScroll: 4,
            dots: false,
            arrows: true,
            prevArrow: '<div class="prev"><i class="fa fa-chevron-left" aria-hidden="true"></i></div>',
            nextArrow: '<div class="next"><i class="fa fa-chevron-right" aria-hidden="true"></i></div>',
			responsive: [
			    {
			      	breakpoint: 1024,
			      	settings: {
				        slidesToShow: 3,
				        slidesToScroll: 3
			      	}
			    },
			    {
			      	breakpoint: 850,
			      	settings: {
				        slidesToShow: 2,
				        slidesToScroll: 2
			      	}
			    },
			    {
			      	breakpoint: 767,
			      	settings: {
				        slidesToShow: 2,
				        slidesToScroll: 2,
				        dots: true,
				        arrows: false
			      	}
			    }				    
			]
        }
        car_list.slick(car_list_settings);
    };


    $('.btn_read_more_table.trim_btn').on('click', function (e) {
    	e.preventDefault();
    	$('.model_car_table_container').toggleClass('open');
    	$(this).toggleClass('active');
    	$(this).children('span').html($(this).children('span').html() == 'read less' ? 'read more' : 'read less');
    });
    $(document).on('click', '.btn_read_more_table.trim_btn', function (e) {
    	e.preventDefault();
        var model_car_table_container  = $('.model_car_table_container').offset().top;
        $('body,html').animate({scrollTop: model_car_table_container - 110}, 800);
    });

    $('.btn_read_more_table.trimHilights_btn').on('click', function (e) {
        e.preventDefault();
        $('.equipment_wrapper').toggleClass('open');
        $(this).toggleClass('active');
        $(this).children('span').html($(this).children('span').html() == 'read less' ? 'read more' : 'read less');
    });
    $(document).on('click', '.btn_read_more_table.trimHilights_btn', function (e) {
        e.preventDefault();
        var equipment_wrapper = $('.equipment_wrapper').offset().top;
        $('body,html').animate({scrollTop: equipment_wrapper - 80}, 800);
    });




    var similar_model_list = $('.slider_similar_model_list');
    if (similar_model_list.length) {
        var similar_model_list_Settings = {
            lazyLoad: 'ondemand',
            infinite: true,
            slidesToShow: 4,
            slidesToScroll: 4,
            dots: true,
            arrows: true,
            prevArrow: '<div class="prev"><i class="fa fa-chevron-left" aria-hidden="true"></i></div>',
            nextArrow: '<div class="next"><i class="fa fa-chevron-right" aria-hidden="true"></i></div>',
            responsive: [
			    {
			      	breakpoint: 991,
			      	settings: {
				        slidesToShow: 3,
				        slidesToScroll: 3,
				        dots: true,
				        arrows: false
			      	}
			    },
			    {
			      	breakpoint: 767,
			      	settings: {
				        slidesToShow: 3,
				        slidesToScroll: 3,
				        dots: true,
				        arrows: false,
				        variableWidth: true
			      	}
			    },
			    {
			      	breakpoint: 600,
			      	settings: {
				        slidesToShow: 2,
				        slidesToScroll: 1,
				        dots: true,
				        arrows: false,
				        variableWidth: true
			      	}
			    }		
            ]
        }
        similar_model_list.slick(similar_model_list_Settings);
    };

    $('.absolute_button .btn_get_started').on('click', function (e) {
        e.preventDefault();
        if($('.choose_brand_block').length > 0) {
            var choose_brand_block = $('.choose_brand_block').offset().top;
            $('body,html').animate({scrollTop: choose_brand_block}, 1500);
        }
    });
    $('.absolute_button .state_page_btn').on('click', function (e) {
        e.preventDefault();
        if($('.populars_cars_block').length > 0) {
            var populars_cars_block = $('.populars_cars_block').offset().top;
            $('body,html').animate({scrollTop: populars_cars_block}, 1500);
        }
    });
    $('.wrap_btn .btn_explore').on('click', function (e) {
        e.preventDefault();
        if($('.model_similar_block').length > 0) {
            var model_similar_block = $('.model_similar_block').offset().top - 60;
            $('body,html').animate({scrollTop: model_similar_block}, 1500);
        }
    });
    $('.ankor_moder_block a').on('click', function (e) {
    	e.preventDefault();
    	$('.ankor_moder_block a').removeClass('active');
    	$(this).addClass('active');
    	var id  = $(this).attr('href');
    	var top = $("#" + id).offset().top;
    	$('body,html').animate({scrollTop: top}, 1500);
    });
    $('.specifications_accardion_item .acc_spec_top').on('click', function () {
        var isOpen = $(this).parent().hasClass('open');
        $('.specifications_accardion_item .acc_content').slideUp();
        $('.specifications_accardion_item').removeClass('open');    
        if (!isOpen)  {
            $(this).next().slideDown();
            $(this).parent().addClass('open');      
        }
    });

    $('.trimhighlights_accardion_mobile_item .trimhighlights_acc_top').on('click', function () {
        var isOpen = $(this).parent().hasClass('open');
        $('.trimhighlights_accardion_mobile_item .trimhighlights_acc_content').slideUp();
        $('.trimhighlights_accardion_mobile_item').removeClass('open');    
        if (!isOpen)  {
            $(this).next().slideDown();
            $(this).parent().addClass('open');      
        }
    });

    $(window).on('load', function() {
        if($('.similar_model_list .slick-dots li').length < 2 ) {
            $('.similar_model_list .slick-dots').addClass('hidden');
        } else {
            $('.similar_model_list .slick-dots').removeClass('hidden');
        }
    })



    $('.form_content_seo label').on('focusin', function () {
        $(this).addClass('focus');
    });
    $('.form_content_seo label').on('focusout', function () {
        $(this).removeClass('focus');
    });

    $('.overlay_seo_catalog .close_popup').on('click', function () {
        $('.overlay_seo_catalog').removeClass('open');
        $('body').removeClass('seoCatalog_hidden');
    });
    $(document).click(function(e) {
        if ($(e.target).closest('.seo_catalog_popup, .top_bg .logo_model').length) return; 
        $('.overlay_seo_catalog').removeClass('open');
        $('body').removeClass('seoCatalog_hidden');
        e.stopPropagation();
    });

    $(document).keyup(function(e) {
         if (e.key === "Escape") { 
            $('.overlay_seo_catalog').removeClass('open');
            $('body').removeClass('seoCatalog_hidden');
        }
    });

});


