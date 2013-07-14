// Top-level application controller
jQuery(function($) {
  $(window).load(function() {
    $('nav a.download').bind('click', function(e) {
      e.stopPropagation();
      e.preventDefault();
      $('#download-wrap').toggleClass('opened');
    });
    
    $(".background-cover").css({backgroundSize: "cover"});
  });
});