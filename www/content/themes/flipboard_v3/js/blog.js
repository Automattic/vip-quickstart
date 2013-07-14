jQuery(document).ready(function($) {

  $('nav a.download').bind('click', function(e) {
    e.stopPropagation();
    e.preventDefault();

    $('#download-wrap').toggleClass('opened').removeClass('success');
  });

  $('.close-download').bind('click', function(e) {
    e.stopPropagation();
    e.preventDefault();

    $('#download-wrap').toggleClass('opened').removeClass('success');
  });

});
