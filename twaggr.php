<?php

/*
Plugin Name: Twaggr
Description: Aggregate twitter streams. Store formatted HTML.
Version: 1.0
Author: Ethan
Author URI: http://www.overit.com
*/

/*
 * Twaggr
 *
 * Aggregate twitter streams. Store formatted HTML.
 *
 * Your important functions are twagger_fetch() and twagger_display()
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
			$usernames[] = $c;
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
	
	$decoded = json_decode($json);
	
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

	$usernames = twaggr_load_config($path . '/config.txt');
	foreach ($usernames as $username) {
		$tweets = twaggr_get_tweets($username, $num);
		foreach ($tweets as $key => $tweet) {
			$all_tweets[$key] = $tweet;
		}
	}
	
	if (count($all_tweets) == 0) {
		if ($verbose) echo 'No tweets.' . chr(10);
		return false;
	}
	
	ksort($all_tweets);
	if ($verbose) echo 'Got ' . count($all_tweets) . ' tweets... ';
	
	$tweets = array_slice($all_tweets, 0, $num);
	if ($verbose) echo 'Keeping ' . count($tweets) . ' of them... ';
	
	$html = twaggr_format_html($tweets);
	if ($verbose) echo 'Outputting as HTML... ';
	
	file_put_contents($path . '/cache.html', $html);
	if ($verbose) echo chr(10) . 'Saved to ' . $path . '/cache.html' . chr(10);
}


/*
 * Load the cache HTML.
 */
function twaggr_display() {
	$path = twaggr_path();
	return file_get_contents($path . '/cache.html');
}


function twaggr_path() {
	return dirname(__FILE__);
}


function twaggr_shortcode( $atts ){
 return twaggr_display();
}
add_shortcode( 'twaggr', 'twaggr_shortcode' );
