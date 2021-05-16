(function ($, Drupal) {
  Drupal.behaviors.mtBody = {
    attach: function (context, settings) {
      if(context == document) {
        console.log('BT Body, context', context);
        $('.mt-body-reveal button', context).once('myCustomBehavior').click(function () {
          // hot fix
          // release the height without the effect.
          $('.field--name-body').css('max-height', 'none');
          $('.mt-body-reveal.mt-read-more').css('display', 'none');      
          return false;

          totalHeight = 0

          $el = $(this);
          $p  = $el.parent();
          $up = $p.parent();
          $ps = $up.find("p:not('.mt-read-more')");
          
          // measure how tall inside should be by adding together heights of all inside paragraphs (except read-more paragraph)
          $ps.each(function() {
            totalHeight += $(this).outerHeight();
          });
                
          $up
            .css({
              // Set height to prevent instant jumpdown when max height is removed
              "height": $up.height(),
              "max-height": 9999
            })
            .animate({
              "height": totalHeight
            });
          
          // fade out read-more
          $p.fadeOut();
          
          // prevent jump-down
          return false;
        });
      }
    }
  };
})(jQuery, Drupal);