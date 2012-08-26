window.since_id = undefined;
var process_tweets = function(tweets) {
	window.since_id = tweets[0].id;
	
	//avatar
	for(i = 0, j = 3; i < j; i++)
	{
		tweet = tweets[i];
		$('#tweets').append('<div class="row">\
								<article class="span6 offset3 tweet-card">\
									<img src="' + tweet.avatar + '" alt="avatar" class="avatar" />\
									<strong class="fullname">' + tweet.user_name + '</strong>\
									<span class="username">@' + tweet.user + '</span>\
									<small class="time"></small>\
									<p class="tweet">' + tweet.text + '</p>\
								</article>\
							</div>');
		console.log(tweet);
	}
},
fetch_tweets = function(){
	if(typeof window.since_id != 'number')
	{
		$.getJSON('proxy.php?hashtag=%23mozcamp', process_tweets);
	}
	else
	{
		$.getJSON('proxy.php?hashtag=%23mozcamp&since_id=' + window.since_id, process_tweets);
	}
};

// for demo purposes
fetch_tweets();