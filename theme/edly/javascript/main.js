(function($){
    (function($) {
        "use strict";

		$(document).ready(function() {

            var current_site_url = $(".navbar-area .navbar .navbar-brand").attr("href");
			if (current_site_url) {
				var local_urls = [
					'http://localhost/moodle/edly/',
					'http://localhost/moodle/edly/',
					'http://localhost:8888/moodle/edly/',
					'http://localhost:8888/moodle/edly-4.4/',
					'http://localhost:8888/moodle/edly-4.3/'
				];

				// Function to replace old URLs with the current site URL
				function replaceUrls(element, attribute) {
					$(element).each(function () {
						var url = $(this).attr(attribute);
						if (url) {
							local_urls.forEach(function (local_url) {
								if (url.includes(local_url)) {
									url = url.replace(local_url, current_site_url);
									$(this).attr(attribute, url);
								}
							}, this);
						}
					});
				}

				// Replace URLs for 'a' and 'img' elements
				replaceUrls('a', 'href');
				replaceUrls('img', 'src');
			}

			// Gallery
			$('.single-gallery-item a').magnificPopup({
				type: 'image',
				gallery:{
				  enabled:true
				}
			  });


			// Header Sticky
			$(window).on('scroll',function() {
				if ($(this).scrollTop() > 30){
					$('.navbar-area').addClass("is-sticky");
				}
				else{
					$('.navbar-area').removeClass("is-sticky");
				}
			});

            $("body.role-standard:not(.path-contentbank):not(#page-contentbank) .bottom-region-main-box").each(function() {
                if (!$(this).find(".block").length && !$(this).find(".edly-main").text().trim().length) {
                $(".bottom-region-main-box, .bottom-region-main-box #page-content").css({
                    'padding-top': '0',
                    'margin-top': '0',
                    'padding-bottom': '0px !important',
                });
                $(".edly-main").remove();
                }
            });

            $(".dashbord_nav_list > a:first-child").prepend("<i class='bx bxs-dashboard' ></i>");
            $(".dashbord_nav_list > a:nth-child(2)").prepend("<i class='bx bx-user' ></i>");
            $(".dashbord_nav_list > a:nth-child(3)").prepend("<i class='bx bxs-graduation' ></i>");
            $(".dashbord_nav_list > a:nth-child(4)").prepend("<i class='bx bx-chat' ></i>");
            $(".dashbord_nav_list > a:nth-child(5)").prepend("<i class='bx bx-cog' ></i>");
            $(".dashbord_nav_list > a:nth-child(6)").prepend("<i class='bx bx-log-out' ></i>");
            $(".dashbord_nav_list > a:nth-child(7)").prepend("<i class='bx bx-user-plus' ></i>");
            $(".dashbord_nav_list > a:nth-child(8)").prepend("<i class='bx bx-log-out'></i>");
            $(".dashbord_nav_list > a").each(function() {
            $(this).removeClass("dropdown-item").wrap("<li></li>");
            });
            $(".dashbord_nav_list > li").wrapAll("<ul></ul>");

			$('#message-user-button .icon.iconsmall').replaceWith("<i class='bx bx-envelope'></i>");
			$('#toggle-contact-button .icon.iconsmall').replaceWith("<i class='bx bx-add-to-queue'></i>");

            // Popup Video
			$('.popup-video').magnificPopup({
				disableOn: 320,
				type: 'iframe',
				mainClass: 'mfp-fade',
				removalDelay: 160,
				preloader: false,
				fixedContentPos: false
			});

			// Click Event JS
			$('.go-top').on('click', function() {
				$("html, body").animate({ scrollTop: "0" }, 100);
			});

			// Mean Menu
			$('.mean-menu').meanmenu({
				meanScreenWidth: "1199"
			});

			$(".popover-region-notifications").click(function() {
				$(".popover-region-notifications").toggleClass('collapsed');
			});

			// Others Option For Responsive JS
			$(".others-option-for-responsive .dot-menu").on("click", function(){
				if ($(".meanmenu-reveal.meanclose")[0]){
					$('.meanmenu-reveal').click();
				}
				$(".others-option-for-responsive .container .container").toggleClass("active");
				$(".others-option-for-responsive .dot-menu").toggleClass("dot-menu-active");
			});

			$(".main-responsive-menu .meanmenu-reveal").on("click", function(){
				$(".others-option-for-responsive .container .container").removeClass("active");
			});

        });

		// Partner Slides
		$('.partner-slides').owlCarousel({
			loop: true,
			nav: false,
			dots: false,
			smartSpeed: 500,
			margin: 25,
			autoplayHoverPause: true,
			autoplay: true,

			responsive: {
				0: {
					items: 2
				},
				576: {
					items: 3
				},
				768: {
					items: 3
				},
				1024: {
					items: 4
				},
				1200: {
					items: 7
				}
			}
		});
		$('.partner-wrap-slides').owlCarousel({
			loop: true,
			nav: false,
			dots: false,
			smartSpeed: 500,
			margin: 25,
			autoplayHoverPause: true,
			autoplay: true,

			responsive: {
				0: {
					items: 2
				},
				576: {
					items: 3
				},
				768: {
					items: 3
				},
				1024: {
					items: 4
				},
				1200: {
					items: 6
				}
			}
		});

		// Review Slides
		$('.review-slides').owlCarousel({
			loop: true,
			nav: false,
			dots: false,
			smartSpeed: 500,
			margin: 25,
			autoplayHoverPause: true,
			autoplay: true,
			autoHeight:true,

			responsive: {
				0: {
					items: 1
				},
				576: {
					items: 1
				},
				768: {
					items: 2
				},
				1024: {
					items: 3
				},
				1200: {
					items: 4
				}
			}
		});

		// Count Time
		function makeTimer() {
			var endTime = new Date("September 20, 2025 17:00:00 PDT");
			var endTime = (Date.parse(endTime)) / 1000;
			var now = new Date();
			var now = (Date.parse(now) / 1000);
			var timeLeft = endTime - now;
			var days = Math.floor(timeLeft / 86400);
			var hours = Math.floor((timeLeft - (days * 86400)) / 3600);
			var minutes = Math.floor((timeLeft - (days * 86400) - (hours * 3600 )) / 60);
			var seconds = Math.floor((timeLeft - (days * 86400) - (hours * 3600) - (minutes * 60)));
			if (hours < "10") { hours = "0" + hours; }
			if (minutes < "10") { minutes = "0" + minutes; }
			if (seconds < "10") { seconds = "0" + seconds; }
			$("#days").html(days + "<span>Days</span>");
			$("#hours").html(hours + "<span>Hours</span>");
			$("#minutes").html(minutes + "<span>Minutes</span>");
			$("#seconds").html(seconds + "<span>Seconds</span>");
		}
		setInterval(function() { makeTimer(); }, 0);

		// Odometer JS
		$('.odometer').appear(function(e) {
			var odo = $(".odometer");
			odo.each(function() {
				var countNumber = $(this).attr("data-count");
				$(this).html(countNumber);
			});
		});

		// WOW Animation JS
		if($('.wow').length){
			var wow = new WOW({
				mobile: false
			});
			wow.init();
		}

		// AOS JS
		AOS.init();

		// Masonry PACKAGED Js
		$('.review-grid').masonry({
			// options
			itemSelector: '.grid-item',
		});

		// Tabs
		(function ($) {
			$('.tab ul.tabs').addClass('active').find('> li:eq(0)').addClass('current');
			$('.tab ul.tabs li a').on('click', function (g) {
				var tab = $(this).closest('.tab'),
				index = $(this).closest('li').index();
				tab.find('ul.tabs > li').removeClass('current');
				$(this).closest('li').addClass('current');
				tab.find('.tab-content').find('div.tabs-item').not('div.tabs-item:eq(' + index + ')').slideUp();
				tab.find('.tab-content').find('div.tabs-item:eq(' + index + ')').slideDown();
				g.preventDefault();
			});
		})(jQuery);

		// FAQ Accordion
		$(function() {
			$('.accordion').find('.accordion-title').on('click', function(){
				// Adds Active Class
				$(this).toggleClass('active');
				// Expand or Collapse This Panel
				$(this).next().slideToggle('fast');
				// Hide The Other Panels
				$('.accordion-content').not($(this).next()).slideUp('fast');
				// Removes Active Class From Other Titles
				$('.accordion-title').not($(this)).removeClass('active');
			});
		});

		// Go to Top
		$(window).on('scroll', function(){
			var scrolled = $(window).scrollTop();
			if (scrolled > 600) $('.go-top').addClass('active');
			if (scrolled < 600) $('.go-top').removeClass('active');
		});
		$('.go-top').on('click', function() {
			$("html, body").animate({ scrollTop: "0" },  500);
		});

		// Preloader JS
		jQuery(window).on('load',function(){
			jQuery(".preloader").fadeOut(500);
		});
	})(window.jQuery);
}(jQuery));


$(function () {
	var langValue = $("html").attr("lang");
	$('.multilang').each(function(){
		var currentLangValue = $(this).attr("lang");
		if(langValue !== currentLangValue) {
			$(this).addClass('d-none');
		}
		console.log(currentLangValue);
	});
});


// ==== CAMPUS – Améliorations des champs de réponse dans les quiz ====
document.addEventListener('DOMContentLoaded', function () {
    // Edly historically removed the first breadcrumb item for connected users
    // because it was normally the Moodle home node. CampusFR now deliberately
    // uses "Mon Campus" as the first customer breadcrumb entry, so only remove
    // a genuine home node and preserve every custom first item.
    if (!document.querySelector('.usermenu')) {
        return;
    }

    document.querySelectorAll('.page-banner-area .breadcrumb').forEach(function (breadcrumb) {
        var firstItem = breadcrumb.querySelector('.breadcrumb-item:first-child');
        if (!firstItem) {
            return;
        }

        var link = firstItem.querySelector('a');
        var href = link ? (link.getAttribute('href') || '') : '';
        var label = (firstItem.textContent || '').trim().toLowerCase();
        var pathname = '';

        if (href !== '') {
            try {
                pathname = new URL(href, window.location.origin).pathname;
            } catch (error) {
                pathname = href.split(/[?#]/, 1)[0];
            }
        }

        // Only remove the genuine Moodle site home. A friendly route such as
        // /mon-campus/ may legitimately end with a slash and must be kept.
        var normalisedPath = pathname.replace(/\/+$/, '') || '/';
        var isSiteHome = normalisedPath === '/'
            || normalisedPath === '/index.php'
            || ['accueil', 'home', 'главная'].indexOf(label) !== -1;

        if (isSiteHome) {
            firstItem.remove();
        }
    });
});
