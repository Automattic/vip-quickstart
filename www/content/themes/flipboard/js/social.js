  var bindSocialDropDowns = function() {
    // Helpers
    var unbindBodyClick = function() {
      $('body').off('click', hideSocialMedia);
    };
    var hideSocialMedia = function($exception) {
      $('.social-media-dropdown').not($exception).hide();
      unbindBodyClick();
    };


    var delayTimer;

    // Toggle dropdown
    $('.social-media a.icon').on('mouseenter', function(e) {
      e.preventDefault();

      clearTimeout(delayTimer);

      var target = $(this).data('target');
      var $dropdown = $('#'+ target +'-expanded-dropdown');

      // Hide other dropdowns
      hideSocialMedia($dropdown);

      // Show the dropdown
      $dropdown.show();

      // Delay this so that it doesn't immediately hide the menu we just revealed
      setTimeout(function() {
        $('body').on('click', hideSocialMedia);
      }, 100);
    });

  };



  var initializeGooglePlus = function() {
    // http://www.google.com/intl/en/webmasters/+1/button/index.html
    (function() {
      var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
      po.src = 'https://apis.google.com/js/plusone.js';
      var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
    })();
  };

  var initializeTwitter = function() {
    // http://twitter.com/about/resources/buttons#tweet
    !function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");
  };

  var initializeFacebook = function() {
    // http://developers.facebook.com/docs/reference/plugins/like/
    // TODO Update the app id!!!
    (function(d, s, id) {
      var js, fjs = d.getElementsByTagName(s)[0];
      if (d.getElementById(id)) return;
      js = d.createElement(s); js.id = id;
      js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=147435715310362";
      fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));
  };
