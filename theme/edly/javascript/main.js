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

    // On ne fait tout ça que sur les pages de quiz
    if (!document.body.classList.contains('path-mod-quiz')) {
        return;
    }

    // --- 1. Grandes réponses : transformer l'input en textarea auto-height ---
    (function enhanceLongShortAnswers() {
        var inputs = document.querySelectorAll(
            '.que.shortanswer .answer input[type="text"].form-control'
        );

        inputs.forEach(function (input) {
            if (input.dataset.enhanced === '1') {
                return;
            }
            input.dataset.enhanced = '1';

            // Créer le textarea visible
            var ta = document.createElement('textarea');
            ta.className = 'form-control quiz-longanswer';
            ta.id = input.id + '_visible';
            ta.name = input.name + '_visible';
            ta.rows = 1;
            ta.value = input.value || '';

            var init = input.getAttribute('data-initial-value');
            if (init !== null && !ta.value) {
                ta.value = init;
            }

            function autoResize() {
                ta.style.height = 'auto';
                ta.style.overflowY = 'hidden';
                ta.style.height = ta.scrollHeight + 'px';
            }

            ta.addEventListener('input', function () {
                input.value = ta.value; // on alimente l’input caché pour Moodle
                autoResize();
            });

            // Insérer le textarea dans le DOM
            input.parentNode.insertBefore(ta, input);

            // Premier sizing
            autoResize();

            // Cacher l’input original mais le garder dans le formulaire
            input.style.position = 'absolute';
            input.style.left = '-9999px';
            input.style.width = '0';
            input.style.height = '0';
            input.setAttribute('aria-hidden', 'true');
            input.tabIndex = -1;

            // Mettre à jour le label pour qu'il pointe vers le textarea
            var label = input.closest('label');
            if (label && label.getAttribute('for') === input.id) {
                label.setAttribute('for', ta.id);
            }
        });
    })();

    // --- 2. Trous dans la phrase : input qui s'élargit/rétrécit horizontalement ---
    (function enhanceGapInputs() {
        var gaps = document.querySelectorAll(
            '.qtext input[type="text"].form-control.d-inline'
        );

        gaps.forEach(function (input) {
            // Classe pour pouvoir le styler
            input.classList.add('quiz-gap');

            // Base : l'attribut size si présent, sinon 10 caractères
            var sizeAttr = parseInt(input.getAttribute('size') || '0', 10);
            var minCh = sizeAttr > 0 ? sizeAttr : 10;  // plus large par défaut
            var maxCh = 24;                            // limite max

            function autoWidth() {
                var len = input.value.length;
                var widthCh = len + 1; // petite marge

                if (widthCh < minCh) {
                    widthCh = minCh;
                } else if (widthCh > maxCh) {
                    widthCh = maxCh;
                }

                input.style.width = widthCh + 'ch';
            }

            // Largeur initiale
            autoWidth();

            // Recalcul à chaque frappe (ajout ou suppression)
            input.addEventListener('input', autoWidth);
        });
    })();

});


document.addEventListener('DOMContentLoaded', function () {
    // On ne fait ça que s'il y a un menu utilisateur → user connecté
    if (!document.querySelector('.usermenu')) {
        return;
    }

    // Pour chaque fil d'Ariane dans la bannière, on supprime le premier <li>
    document.querySelectorAll('.page-banner-area .breadcrumb').forEach(function (bc) {
        var firstItem = bc.querySelector('.breadcrumb-item:first-child');
        if (firstItem) {
            firstItem.parentNode.removeChild(firstItem);
        }
    });
});