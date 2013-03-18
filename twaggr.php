<?php

/*
Plugin Name: Twaggr
Description: Aggregate twitter streams. Store formatted HTML.
Version: 1.1
Author: Ethan
Author URI: http://www.ethancodes.com

Your important functions are twagger_fetch() and twagger_display()
*/

date_default_timezone_set('America/New_York');

/*
 * Load usernames from config file.
 * @param string $f the name of the config file, if you've called it something other than config.txt
 * @return array
 */
function twaggr_load_config($f = 'config.txt') {
	$usernames = array();
	
	if (file_exists($f)) {
		$config = explode(chr(10), file_get_contents($f));
		foreach ($config as $c) {
			if (substr($c, 0, 1) != '@') {
				$c = '@' . $c;
			}
			$usernames[] = trim($c);
		}
	}
	
	return $usernames;
}


/*
 * Get this user's tweets from Twitter.
 * @param string $username
 * @param int $num number of tweets to get
 * @return array
 */
function twaggr_get_tweets($username, $num = 10) {
	$tweets = array();
	
	$username = str_replace("@", "", $username);
	$twapi = 'http://search.twitter.com/search.json?q=from:' . $username . '&rpp=' . $num;
	
	$ch = curl_init($twapi);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$json = curl_exec($ch);
	curl_close($ch);
	
	if ($json == '') return array();
	
	$decoded = json_decode($json);
	
	if (!$decoded) return array();
	
	foreach ($decoded->results as $result) {
		$key = strtotime($result->created_at) . '_' . $result->id_str;
		$tweets[$key] = $result;
	}
	
	return $tweets;	
}


/*
 * Format tweets as HTML.
 * @param array $tweets
 * @return string html
 */
function twaggr_format_html($tweets = array()) {
	$html = '';
	
	foreach ($tweets as $tweet) {
		
		$twitter_url = 'http://twitter.com/' . $tweet->from_user;
	
		$foo  = '<div class="twaggr-tweet">';
		
		$foo .= '<div class="twaggr-tweet-profile-image">';
		$foo .= '<a href="' . $twitter_url . '" target="_blank" rel="nofollow">';
		$foo .= '<img src="' . $tweet->profile_image_url . '" alt="' . $tweet->from_user_name . '" />';
		$foo .= '</a>';
		$foo .= '</div>';
		
		$foo .= '<div class="twaggr-tweet-name">';
		$foo .= '<a href="' . $twitter_url . '" target="_blank" rel="nofollow">';
		$foo .= $tweet->from_user_name;
		$foo .= '</a>';
		$foo .= '</div>';
		
		$foo .= '<div class="twaggr-tweet-user">';
		$foo .= '<a href="' . $twitter_url . '" target="_blank" rel="nofollow">';
		$foo .= '@' . $tweet->from_user;
		$foo .= '</a>';
		$foo .= '</div>';
		
		$foo .= '<div class="twaggr-tweet-text">';
		$foo .= preg_replace( '@(?<![.*">])\b(?:(?:https?|ftp|file)://|[a-z]\.)[-A-Z0-9+&#/%=~_|$?!:,.]*[A-Z0-9+&#/%=~_|$]@i', '<a href="\0" target="_blank" rel="nofollow">\0</a>', $tweet->text );
		$foo .= '</div>';
		
		$dtstamp = strtotime($tweet->created_at);
		$foo .= '<div class="twaggr-tweet-date">' . date('g:ia - F jS, Y', $dtstamp) . '</div>';
		
		$foo .= '</div>' . chr(10);
		
		$html .= $foo;
	}
	
	return $html;
}


/*
 * Fetch all tweets for configured users, format as html, store in a file.
 * @param int $num the number of tweets to include in the html.
 */
function twaggr_fetch($num = 10, $verbose = false) {

	$all_tweets = array();

	$path = twaggr_path();
	$p = explode("/", $path);
	$upload_path = implode("/", array_slice($p, 0, count($p) - 2)) . '/uploads';

	$usernames = twaggr_load_config($path . '/config.txt');
	
	if ($verbose) {
		echo '<pre>'; var_dump($usernames); echo '</pre>';
	}
	
	foreach ($usernames as $username) {
		$tweets = twaggr_get_tweets($username, $num);
		foreach ($tweets as $key => $tweet) {
			$all_tweets[$key] = $tweet;
		}
		sleep(1); // let's not piss off twitter
	}
	
	if (count($all_tweets) == 0) {
		if ($verbose) echo 'No tweets.' . chr(10);
		return false;
	}
	
	krsort($all_tweets);
	if ($verbose) echo 'Got ' . count($all_tweets) . ' tweets... ';
	
	if ($verbose) {
		echo '<pre>';
		var_dump($all_tweets);
		echo '</pre>';
	}
	
	$tweets = array_slice($all_tweets, 0, $num);
	if ($verbose) echo 'Keeping ' . count($tweets) . ' of them... ';
	
	$html = twaggr_format_html($tweets);
	if ($verbose) echo 'Outputting as HTML... ';
	
	file_put_contents($upload_path . '/twaggr_cache.html', $html);
	if ($verbose) echo chr(10) . 'Saved to ' . $upload_path . '/twaggr_cache.html' . chr(10);
}


/*
 * Load the cache HTML.
 * @param string $no_tweets_msg
 */
function twaggr_display($no_tweets_msg = 'Sorry, no tweets at this time.') {
	$path = twaggr_path();
	$p = explode("/", $path);
	$upload_path = implode("/", array_slice($p, 0, count($p) - 2)) . '/uploads';
	if (!file_exists($upload_path . '/twaggr_cache.html')) return $no_tweets_msg;

	$tweets = file_get_contents($upload_path . '/twaggr_cache.html');
	if ($tweets == '') return $no_tweets_msg;
	return $tweets;
}


function twaggr_path() {
	return dirname(__FILE__);
}


if (function_exists('add_shortcode')) {
function twaggr_shortcode( $atts ){
 return twaggr_display();
}
add_shortcode( 'twaggr', 'twaggr_shortcode' );
}