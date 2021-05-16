(function ($, Drupal) {
  Drupal.behaviors.mtScrollToRevision = {
    attach: function (context, settings) {
      if (context.id && context.id === "comment-form") {
        console.log('BT Body, context', context);
        // link = $('#mt-request', context);
        $('#mt-request', context).once('mtScrollToRevision').click(function () {
          console.log(this);
          // document.getElementById("mt-revision-form").scrollIntoView();
          const href = $(this).attr("href");
          $('html, body').animate({
            scrollTop: $('#mt-revision-form').offset().top
          }, 1000, function () {
            if (history.pushState) {
              history.pushState(null, null, href);
            } else {
              window.location.hash = 'mt-revision-form';
            }
          });
          return false;
        });
      }
      if(context == document) {
        console.log('BT Body2, context', context);
      }
    }
  };
})(jQuery, Drupal);
