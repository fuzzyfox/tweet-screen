window.since_id = undefined;
var process_tweets = function(tweets) {
	
	if(tweets.length > 1) {
		window.since_id = tweets[tweets.length - 1].id;
		
		for(i = 0, j = tweets.length; i < j; i++)
		{
			tweet = tweets[i];
			
			if(tweet.photo) {
				$('#tweets').append('<div class="row tweet has-photo" rel="' + tweet.id + '">\
										<article class="span6 offset3 tweet-card">\
											<img src="' + tweet.avatar + '" alt="avatar" class="avatar" />\
											<strong class="fullname">' + tweet.user_name + '</strong>\
											<span class="username">@' + tweet.user + '</span>\
											<small class="time">\
											    <span class="relative-timestamp">' + new Date(tweet.timestamp).toRelativeTime() + '</span>\
											</small>\
											<p class="tweet-text">' + tweet.text + '</p>\
										</article>\
										<div class="span6 offset3 tweet-photo">\
										    <img src="' + tweet.photo + '">\
										</div>\
									</div>');
			} else {
				$('#tweets').append('<div class="row tweet" rel="' + tweet.id + '">\
										<article class="span6 offset3 tweet-card">\
											<img src="' + tweet.avatar + '" alt="avatar" class="avatar" />\
											<strong class="fullname">' + tweet.user_name + '</strong>\
											<span class="username">@' + tweet.user + '</span>\
											<small class="time">\
											    <span class="relative-timestamp" rel="' + tweet.timestamp + '" >' + new Date(tweet.timestamp).toRelativeTime() + '</span>\
											</small>\
											<p class="tweet-text">' + tweet.text + '</p>\
										</article>\
									</div>');
			}
		}
	} else {
		delete window.since_id;
		window.fetch_tweets();
	}
},
fetch_tweets = function(hashtag){
	if(typeof window.since_id != 'number') {
		$.getJSON('proxy.php?hashtag=%23'+window.hashtag, process_tweets);
	} else {
		$.getJSON('proxy.php?hashtag=%23'+window.hashtag+'&since_id=' + window.since_id, process_tweets);
	}
},
hide_tweets = function() {
	$('.tweet.visible').fadeToggle(1000, function(){
		$(this).remove();
	});
},
show_plain_tweets = function() {
	$tweets = $('.tweet:not(.visible):not(.has-photo)');
	
	if($tweets.size() > 3) {
		for(var i = 0, j = 3; i < j; i++) {
			$tweets.eq(i).delay(1000).fadeToggle(1000).addClass('visible');
		}
	} else {
		 $('.tweet.has-photo:not(.visible):first').delay(1000).fadeToggle(1000).addClass('visible')
	}
},
show_photo_tweets = function() {
	$tweet = $('.tweet.has-photo:not(.visible):first');
	
	clearInterval(show_plain_tweets_timer);
	
	if($tweet.size() === 1) {
		$tweet.delay(1000).addClass('visible').fadeToggle(1000, function(){
			show_plain_tweets_timer = setInterval(function(){
				window.hide_tweets();
				window.show_plain_tweets();
			}, 10000);
		});
	} else {
		window.show_plain_tweets();
		show_plain_tweets_timer = setInterval(function(){
			window.hide_tweets();
			window.show_plain_tweets();
		}, 10000);
	}
},
update_relative_times = function(){
	$tweet = $('.tweet-card .relative-timestamp');
	$tweet.html(new Date($tweet.attr('rel')).toRelativeTime());
},
rotate_tweetout = function(tweetouts) {
	
};

$(function(){
		config = {
			'hashtag' : 'mozcamp',
			'tweetouts' : [
				'tweet out number one',
				'tweet out number two',
				'tweet out etc...'
			]
		}
		
		
		
		window.hashtag = config.hashtag;
		window.fetch_tweets();
		autopoll = setInterval(function(){
			window.fetch_tweets();
			
			// this is here so it does sort of act responsively
			$('.qr img:first')[0].style.bottom = -($('.promo').height()) + 'px';
		}, 24000);
		
		show_plain_tweets_timer = setInterval(function(){
			window.hide_tweets();
			window.show_plain_tweets();
			window.update_relative_times();
		}, 10000);
		
		show_photo_tweets_timer = setInterval(function(){
			window.hide_tweets();
			window.show_photo_tweets();
		}, 29999);
		
		rotate_tweetout_timer = setInterval(rotate_tweetout, 30000);
		
		$('.hashtag').html(config.hashtag);
		$('.qr img:first')[0].style.bottom = -($('.promo').height()) + 'px';
});