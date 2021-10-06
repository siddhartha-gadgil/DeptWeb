/*!
    * Start Bootstrap - Creative v6.0.4 (https://startbootstrap.com/theme/creative)
    * Copyright 2013-2020 Start Bootstrap
    * Licensed under MIT (https://github.com/StartBootstrap/startbootstrap-creative/blob/master/LICENSE)
    */
(function($) {
  "use strict"; // Start of use strict

  // Smooth scrolling using jQuery easing
  $('a.js-scroll-trigger[href*="#"]:not([href="#"])').click(function() {
    if (location.pathname.replace(/^\//, '') == this.pathname.replace(/^\//, '') && location.hostname == this.hostname) {
      var target = $(this.hash);
      target = target.length ? target : $('[name=' + this.hash.slice(1) + ']');
      if (target.length) {
      /* $('html').animate({
          scrollTop: (target.offset().top - 72)
          }, 1000, "easeInOutExpo");*/
	  window.scrollBy({ 
	      top: target.offset().top - 72 - window.scrollY,
	      left: 0, 
	      behavior: 'smooth' 
	  });
        return false;
      }
    }
  });

  // Closes responsive menu when a scroll trigger link is clicked
	$('.js-scroll-trigger').click(function() {
    $('.navbar-collapse').collapse('hide');
  });

  // Activate scrollspy to add active class to navbar items on scroll
  $('body').scrollspy({
    target: '#mainNav',
    offset: 75
  });

    var collapseDropdown = function() {
      var scroll = $(window).scrollTop();
        if(scroll > 200){
            $("div.dropdown-menu").removeClass("show");
        }
    };
    $(window).scroll(collapseDropdown);

  // Magnific popup calls
  $('#portfolio').magnificPopup({
    delegate: 'a',
    type: 'image',
    tLoading: 'Loading image #%curr%...',
    mainClass: 'mfp-img-mobile',
    gallery: {
      enabled: true,
      navigateByImgClick: true,
      preload: [0, 1]
    },
    image: {
      tError: '<a href="%url%">The image #%curr%</a> could not be loaded.'
    }
  });

  // For positioning submenu correctly on click
  $("a.dropdown-item").click(function() {
    $(".navbar-collapse").collapse("hide");
  });

})(jQuery); // End of use strict
