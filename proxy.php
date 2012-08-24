<?php
	
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
	 * @author William Duyck <wduyck@gmail.com>
	 * @version 2012.08.17
	 */
	class TweetScreen {
		
		private $_OAuth;
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
		 * @param	string	hashtag	the hashtag to track
		 */
		public function __construct($hastag) {
			$this->_OAuth = new tmhOAuth(array());
			
			$this->_params = array(
				'q' => $hastag,
				'result_type' => 'recent',
				'include_entities' => 'true'
			);
		}
		
		/**
		 * set a param for the search
		 *
		 * @param	string	key		the name of the param
		 * @param	string	value	the value of the param
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
		
		public function get_data() {
			return $this->_data;
		}
		
		/**
		 * Generates a qrcode to tweet a specified message out.
		 *
		 * @param	string	msg	the message to encode
		 */
		public function generate_qr($msg) {
			$tweet_url = 'https://twitter.com/home?status=' . urlencode($msg);
			return 'https://chart.googleapis.com/chart?cht=qr&chs=170x170&chl=' . urlencode($tweet_url) . '&chld=H|1';
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
		private	$_expire	= 172800; // 2 days
		
		/**
		 * sets the cache dir
		 *
		 * @param string	dir	location of the cache dir w/ trailing slash
		 */
		public function set_dir($dir) {
			$this->_dir = $dir;
		}
		
		/**
		 * sets the expire duration of cache files
		 *
		 * @param	int	expire	how long to keep cache for
		 */
		public function set_expire($expire) {
			$this->_expire = $expire;
		}

		/**
		 * saves string to cache
		 *
		 * @param	string	str		what to store
		 * @param	string	name 	name of cache file to store in
		 * @return	boolean	TRUE on success
		 */
		public function save($str, $name){
			// write the cache file
			return (file_put_contents($this->_dir . $name, $str) !== FALSE);
		}
		
		/**
		 * gets string from cache
		 *
		 * @param	string	name	name of cache file
		 * @return	mixed	FALSE on fail, string of cache content on success
		 */
		public function get($name){
			// check cache exists and is not expired
			if($this->exists($this->_dir . $name))
			{
				return file_get_contents($this->_dir . $name);
			}
			
			return FALSE;
		}
		
		/**
		 * checks if a cache exists and hasn't expired
		 *
		 * @param	string	name	name of cache file
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
	 should return the url for cached version of the qrcode, and the message that
	 will be tweeted
	 ?qr=message+to+tweet
	 
	 Promos:
	 no id: return the total number of snippets in the snippets dir (id's map
	 to filenames in alphabetical order)
	 ?promo=0
	 with id: return the relative url and ID selector of the snippet for the promo
	 ?promo=3
	*/
	
	parse_str(implode('&', array_slice($argv, 1)), $_GET);
	
	// find out if we need to return tweets
	if($_GET['hashtag'])
	{
		// start request process
		$api = new TweetScreen($_GET['hashtag']);
		
		// check if we need to add a since_id to our query to twitter
		if($_GET['since_id'])
		{
			$api->set_param('since_id', $_GET['since_id']);
		}
		
		// request results from twitter
		$api->request();
		
		print_r($api->get_data());
	}
	elseif($_GET['qr'])
	{
		
	}

// EOF
