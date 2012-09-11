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
};

$(function(){
		config = {
			'hashtag' : 'mozcamp',
			'tweetouts' : [
				'Welcome to #MozCamp! We\'re going to have an awesome weekend.',
				
				//'What is your #MozCamp #Mission? Add yours at mzl.la/missionmozcampeu',
				
				'Who do you want to meet at #MozCamp? Tweet it out!',
				
				'What do you want to learn at #MozCamp this weekend?',
				
				'I saw The Fox at #MozCamp, here\'s my photo!',
				
				'Who is your #MozCamp buddy? Have you met them yet?',
				
				//'Wake-up and eat Breakfast. Breakfast is located in the Lilla Weneda Restaurant on floor 2 and opens at 6:30 am.',
				
				//'Tomorrow morning take a tram to Fabryka. We are starting at 9:00 am, so get on a tram by 8:30 am.',
				
				'Which #MozCamp sessions are you going to tomorrow?',
				
				'My favorite #MozCamp track? Desktop and mobile, Apps and B2G, and Grow Mozilla',
				
				//'Are you leading a #MozCamp session? Be sure to let others know!'
			]
		}
		
		
		
		window.hashtag = config.hashtag;
		window.tweetouts = config.tweetouts;
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
		
		function urlencode (str) {
		    // http://kevin.vanzonneveld.net
		    // +   original by: Philip Peterson
		    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
		    // +      input by: AJ
		    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
		    // +   improved by: Brett Zamir (http://brett-zamir.me)
		    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
		    // +      input by: travc
		    // +      input by: Brett Zamir (http://brett-zamir.me)
		    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
		    // +   improved by: Lars Fischer
		    // +      input by: Ratheous
		    // +      reimplemented by: Brett Zamir (http://brett-zamir.me)
		    // +   bugfixed by: Joris
		    // +      reimplemented by: Brett Zamir (http://brett-zamir.me)
		    // %          note 1: This reflects PHP 5.3/6.0+ behavior
		    // %        note 2: Please be aware that this function expects to encode into UTF-8 encoded strings, as found on
		    // %        note 2: pages served as UTF-8
		    // *     example 1: urlencode('Kevin van Zonneveld!');
		    // *     returns 1: 'Kevin+van+Zonneveld%21'
		    // *     example 2: urlencode('http://kevin.vanzonneveld.net/');
		    // *     returns 2: 'http%3A%2F%2Fkevin.vanzonneveld.net%2F'
		    // *     example 3: urlencode('http://www.google.nl/search?q=php.js&ie=utf-8&oe=utf-8&aq=t&rls=com.ubuntu:en-US:unofficial&client=firefox-a');
		    // *     returns 3: 'http%3A%2F%2Fwww.google.nl%2Fsearch%3Fq%3Dphp.js%26ie%3Dutf-8%26oe%3Dutf-8%26aq%3Dt%26rls%3Dcom.ubuntu%3Aen-US%3Aunofficial%26client%3Dfirefox-a'
		    str = (str + '').toString().replace(/\#/g, '%23');
		
		    // Tilde should be allowed unescaped in future versions of PHP (as reflected below), but if you want to reflect current
		    // PHP behavior, you would need to add ".replace(/~/g, '%7E');" to the following.
		    return encodeURIComponent(str).replace(/!/g, '%21').replace(/'/g, '%27').replace(/\(/g, '%28').
		    replace(/\)/g, '%29').replace(/\*/g, '%2A').replace(/%20/g, '+');
		}
		
		
		// tweetouts
		$('.promo p').html(window.tweetouts[0]);
		$('.qr img').attr('src', 'proxy.php?qr=' + urlencode(window.tweetouts[0]));
		window.tweetouts.push(window.tweetouts[0]);
		window.tweetouts.shift();
		setInterval(function(){
			$('.promo p').html(window.tweetouts[0]);
			$('.qr img').attr('src', 'proxy.php?qr=' + urlencode(window.tweetouts[0]));
			window.tweetouts.push(window.tweetouts[0]);
			window.tweetouts.shift();
		}, 60000);
		
		$('.hashtag').html(config.hashtag);
		$('.qr img:first')[0].style.bottom = -($('.promo').height()) + 'px';
});