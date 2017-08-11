jQuery(document).ready(function($) {
    var untilDay = new Date(nextTweetTime.tweetTime * 1000);
    $('#defaultCountdown').countdown({until: untilDay , format: 'HMS'});
});