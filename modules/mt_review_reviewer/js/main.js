const awardsBoardMemberRole = 'mt_awards_board_member';

(function ($, Drupal) {
  Drupal.behaviors.mtReviewerStartButton = {
    attach: function (context, settings) {
      console.log('BT Body, context', context);
      let url = settings.path.baseUrl + 'user/' + settings.user.uid + '/abstracts';
      // Button goes to /award-abstracts when user is Awards Board member
      if (settings.user.roles.includes(awardsBoardMemberRole)) {
        url = settings.path.baseUrl + 'user/' + settings.user.uid + '/award-abstracts';
      }
      if(context == document) {
        $('#mt-button-start-review', context).once('mt-button-start-review').click(function (e) {
          window.location.href = url;
          // prevent jump-down
          return false;
        });
      }
      if (context.id && (context.id === "comment-form" || context.id === "comment-form--3")) {
        $('#mt-button-continue-review', context).once('mt-button-continue-review').click(function (e) {
          window.location.href = url;
          // prevent jump-down
          return false;
        });
      }
    }
  };
})(jQuery, Drupal);
