<?php
	
	date_default_timezone_set('Europe/Warsaw');
	
	define('ENVIRONMENT', 'Development');
	
	switch(ENVIRONMENT)
	{
		case 'testing':
		case 'production':
			error_reporting(E_ERROR | E_PARSE);
		break;
		case 'development':
		default:
			error_reporting(E_ALL);
		break;
	}
	
	/*
	 get tmh oauth tiwtter utils
	*/
	require('asset/twitterapi/tmhOAuth.php');
	require('asset/twitterapi/tmhUtilities.php');
	
	/**
	 * Tweet Screen
	 * 
	 * Displays tweets, and promo snippets, for use on a large display 
	 * at conferences.
	 *
	 * Currently supports the following photo sharing sites:
	 * * Twitter
	 * * Instagram
	 * 
	 * @author William Duyck <wduyck@gmail.com>
	 * @version 2012.08.17
	 */
	class TweetScreen {
		
		private $_OAuth;
		private $_cache;
		private $_params = array(array());
		private $_data = array();
		private $_response_codes = array(
			304 => 'There was no new data to return.',
			400 => 'The request was invalid.',
			401 => 'Authentication credentials were missing or incorrect.',
			403 => 'The request is understood, but it has been refused.',
			404 => 'The URI requested is invalid or the resource requested does not exists.',
			406 => 'Invalid format is specified in the request.',
			420 => 'Rate limit reached.',
			500 => 'Something is broken.',
			502 => 'Twitter is down or being upgraded.',
			503 => 'Twitter is over capacity.',
			504 => 'Gateway timeout.'
		);
		
		/**
		 * sets up the oauth session
		 *
		 * @param	string	$hashtag	the hashtag to track
		 */
		public function __construct($hastag = '@FuzzyFox') {
			$this->_OAuth = new tmhOAuth(array());
			$this->_cache = new Cache();
			
			$this->_params = array(
				'q' => $hastag,
				'result_type' => 'recent',
				'include_entities' => 'true'
			);
		}
		
		/**
		 * set a param for the search
		 *
		 * @param	string	$key	the name of the param
		 * @param	string	$value	the value of the param
		 */
		public function set_param($key, $value) {
			$this->_params[$key] = $value;
		}
		
		/**
		 * Sends a request to the twitter search api, using params set in object
		 *
		 * @return	boolean	TRUE on success
		 */
		public function request() {
			$this->_OAuth->request('GET', 'http://search.twitter.com/search.json', $this->_params, FALSE);
			
			if($this->_OAuth->response['code'] == 200)
			{
				$this->_data = json_decode($this->_OAuth->response['response']);
				return TRUE;
			}
			else
			{
				header($this->_response_codes[$this->_OAuth->response['code']], TRUE, $this->_OAuth->response['code']);
				return FALSE;
			}
		}
		
		// tmp function
		public function get_data() {
			return $this->_data;
		}
		
		/**
		 * gets the id of the newest tweet returned
		 *
		 * @return	int	the id of the newest tweet
		 */
		private function get_newest_id() {
			return $this->_data->results[0]->id;
		}
		
		/**
		 * gets only the needed content from the twitter response
		 *
		 * gets the users avatar, any imgs, tweet, timestamp, id, name, username
		 *
		 * @return	assoc_array	An assoc' array of tweet objects
		 */
		public function clean_response() {
			// create new var within scope to place trimmed results
			$data = array();
			
			foreach($this->_data->results as $raw_tweet)
			{
				// cache the users avatar
				$this->_cache->save(file_get_contents(str_replace('_normal.', '_reasonably_small.', $raw_tweet->profile_image_url)), 'avatar_' . $raw_tweet->from_user . '.' . pathinfo($raw_tweet->profile_image_url, PATHINFO_EXTENSION));
				// build up new tweet object
				$tweet = (object)array(
					'timestamp'	=> $raw_tweet->created_at,
					'id'		=> $raw_tweet->id,
					'user'		=> $raw_tweet->from_user,
					'user_name'	=> $raw_tweet->from_user_name,
					'avatar'	=> $this->_cache->get_dir() . 'avatar_' . $raw_tweet->from_user . '.' . pathinfo($raw_tweet->profile_image_url, PATHINFO_EXTENSION),
					'text'		=> $this->correct_tweet_urls($raw_tweet),
					'photo'		=> $this->get_tweet_photo($raw_tweet)
				);
				
				$data[] = $tweet;
			}
			$data = array_reverse($data);
			$this->_data = $data;
			return $data;
		}
		
		/**
		 * corrects the urls in the tweet
		 *
		 * corrects the urls in the tweet so they look more like what the user
		 * tweeted and less like twitter has shortened them all (which they have)
		 * and converts them into html links
		 *
		 * @param	object	$tweet	the tweet to correct
		 * @return	string	the tweet with the corrected urls
		 */
		private function correct_tweet_urls($tweet) {
			if(isset($tweet->entities->urls))
			{
				foreach($tweet->entities->urls as $url)
				{
					$tweet->text = str_replace($url->url, '<a href="' . $url->expanded_url . '">' . $url->display_url . '</a>', $tweet->text);
				}
			}
			return $tweet->text;
		}
		
		/**
		 * gets the photo from a tweet if one exists
		 *
		 * @param	object	$tweet	the tweet to look for photos in
		 */
		private function get_tweet_photo($tweet) {
			if(isset($tweet->entities->media))
			{
				$this->_cache->save(file_get_contents($tweet->entities->media[0]->media_url), 'photo_' . $tweet->id . '.' . pathinfo($tweet->entities->media[0]->media_url, PATHINFO_EXTENSION));
				return $this->_cache->get_dir() . 'photo_' . $tweet->id . '.' . pathinfo($tweet->entities->media[0]->media_url, PATHINFO_EXTENSION);
			}
			elseif(isset($tweet->entities->urls))
			{
				foreach($tweet->entities->urls as $url)
				{
					if(preg_match('/http:\/\/instagr\.am\/p\//i', $url->expanded_url))
					{
						$this->_cache->save(file_get_contents($url->expanded_url . '/media/?size=l'), 'photo_' . $tweet->id . '.jpg');
						return $this->_cache->get_dir() . 'photo_' . $tweet->id . '.jpg';
					}
				}
			}
			else
			{
				return FALSE;
			}
		}
		
		/**
		 * Generates a qrcode to tweet a specified message out.
		 *
		 * @param	string	$msg	the message to encode
		 */
		public function generate_qr($msg) {
			$tweet_url = 'http://twitter.com/intent/tweet?text=' . urlencode($msg);
			return 'https://chart.googleapis.com/chart?cht=qr&chs=170x170&chl=' . ($tweet_url) . '&chld=H|0';
		}
	}

	/**
	 * Generic cache class
	 * 
	 * Manages a file based cache
	 *
	 * @author William Duyck <wduyck@gmail.com>
	 * @version 2012.08.16
	 */
	class Cache {
		
		// default values
		private	$_dir		= 'cache/';
		private	$_expire	= 3600; // 2 days
		
		/**
		 * sets the cache dir
		 *
		 * @param string	$dir	location of the cache dir w/ trailing slash
		 */
		public function set_dir($dir) {
			$this->_dir = $dir;
		}
		
		/**
		 * gets the cache dir
		 *
		 * @return	string	the dir where cache is stored
		 */
		public function get_dir() {
			return $this->_dir;
		}
		
		/**
		 * sets the expire duration of cache files
		 *
		 * @param	int	$expire	how long to keep cache for
		 */
		public function set_expire($expire) {
			$this->_expire = $expire;
		}

		/**
		 * saves string to cache
		 *
		 * @param	string	$str	what to store
		 * @param	string	$name 	name of cache file to store in
		 * @return	boolean	TRUE on success
		 */
		public function save($str, $name){
			// write the cache file
			return (file_put_contents($this->_dir . $name, $str) !== FALSE);
		}
		
		/**
		 * gets string from cache
		 *
		 * @param	string	$name	name of cache file
		 * @return	mixed	FALSE on fail, string of cache content on success
		 */
		public function get($name){
			return file_get_contents($this->_dir . $name);
		}
		
		/**
		 * checks if a cache exists and hasn't expired
		 *
		 * @param	string	$name	name of cache file
		 * @return	boolean	TRUE if cache exists
		 */
		public function exists($name){
			// check if cache exists
			if(file_exists($this->_dir . $name))
			{
				// check not expired
				if(filemtime($this->_dir . $name) < time() - $this->_expire)
				{
					//  expired, delete file
					unlink($this->_dir . $name);
					return FALSE;
				}
				
				return TRUE;
			}
			
			return FALSE;
		}
		
		/**
		 * deletes specified cache
		 *
		 * @param	string	$name	name of cache file to remove
		 */
		public function delete($name) {
			if(file_exists($this->_dir . $name))
			{
				unlink($this->_dir . $name);
			}
		}
	}

	// process tweets to sperate those w/ images and those w/o

	// send request to twitter api

	// generate snippets for tweets
	
	// generate snippets for tweet outs

	// generate qrcode for tweet out
	
	/*
	 Tweets:
	 should return tweets in json, with urls replaced for the cached versions
	 ?hashtag=%23mozcamp
	 ?hashtag=%23mozcamp&since_id=12243
	 
	 QR:
	 should return the cached version of the qrcode
	 ?qr=message+to+tweet
	 
	 Promos:
	 no id: return the total number of snippets in the snippets dir (id's map
	 to filenames in alphabetical order)
	 ?promo=0
	 with id: return the relative url and ID selector of the snippet for the promo
	 ?promo=3
	*/
	
	// for commandline-usage
	if(php_sapi_name() == 'cli')
	{
		parse_str(implode('&', array_slice($argv, 1)), $_GET);
	}
	
	// create a cache object
	$cache = new Cache();
	
	// find out if we need to return tweets
	if(isset($_GET['hashtag']))
	{
		// check request not saved somewhere
		if($cache->exists('request_' . md5($_SERVER['REQUEST_URI'])))
		{
			// it is retrieve it and sent back the same results as last time
			header('Content-type: text/json');
			echo $cache->get('request_' . md5($_SERVER['REQUEST_URI']));
		}
		else
		{
			// nope... make a new request
			$api = new TweetScreen($_GET['hashtag']);
			
			if(isset($_GET['since_id']))
			{
				$api->set_param('since_id', $_GET['since_id']);
			}
			
			$api->request();
			
			$data = $api->clean_response();
			
			if(count($data) > 0)
			{
				$cache->save(json_encode($data), 'request_' . md5($_SERVER['REQUEST_URI']));
			
				header('Content-type: text/json');
				echo $cache->get('request_' . md5($_SERVER['REQUEST_URI']));
			}
			else
			{
				header('Content-type: text/json');
				header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
			    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
			    header("Cache-Control: post-check=0, pre-check=0", false);
			    header("Pragma: no-cache");
				echo json_encode($data);
			}
		}
	}
	elseif($_GET['qr'])
	{
		$api = new TweetScreen();
		
		if(! $cache->exists(md5($_GET['qr'])))
		{
			$cache->save(file_get_contents($api->generate_qr($_GET['qr'])), 'qr_' . md5($_GET['qr']) . '.png');
			header('Content-type: image/png');
			echo $cache->get('qr_' . md5($_GET['qr'])  . '.png');
		}
		else
		{
			header('Content-type: image/png');
			echo $cache->get('qr_' . md5($_GET['qr'])  . '.png');
		}
	}
	elseif($_GET['promo'])
	{
		
	}

// EOF
