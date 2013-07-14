// Twitter module
//
// Fetches the latest Flipboard tweets, based on specific criteria
//
// Usage:
//   twitter.init({
//     container: $('#flip-tip-tweet .container')
//   }).render();
//
// Expects the 'container' option to have .posted-on and .body elements
//

define(['vendor/jquery', 'vendor/jquery.linkify', 'vendor/moment'], function($, linkify, moment) {
  var options = {};

  // var hashtag = 'fliptip';
  // var tweeter = 'flipboard';

  // var url = 'http://search.twitter.com/search.json?rpp=1&callback=?&q='+ hashtag +'%20from:'+ tweeter;

  var url = '/fliptip';

  var $loadingIndicator = $('<div class="loading-indicator">Loading</div>');


  // TODO This will need to respect i18n
  moment.lang('en');


  var handleResponse = function(data) {
    var results = data.results[0];

    if(results) {
      var body = results.text,
          timestamp = results.created_at;

      // Assign the body text
      // Use linkify to convert hashtags, links, and twitter users to hyperlinks
      $('.body', options.container).html(body).linkify({
        use:['twitterUser', 'twitterHashtag']
      });

      // Set the timestamp
      $('.posted-on', options.container).html(moment(timestamp).fromNow());
    } else {
      $('.body', options.container).text('There are no FlipTips at this time.');
    }

    // Finally, hide the spinner and fade in our info
    $('.loading-indicator', options.container).fadeOut(100);
    $('p,a', options.container).delay(100).fadeIn(300);
  };


  return {
    init: function(opts) {
      _.extend(options, opts);

      $(options.container).append($loadingIndicator);

      return this;
    },


    render: function() {
      $.getJSON(url, handleResponse);
      return options.container;
    }
  };

});
