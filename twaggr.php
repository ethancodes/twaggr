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
 * Much borrowed from Paul Robinson's Twitter Stream http://return-true.com/
 *
 * Your important functions are twagger_fetch() and twagger_display()
 */


date_default_timezone_set('America/New_York');


//Setup oAuth data such as Twitter Streams Consumer Key etc.
if($keys = get_option('twaggr_keys')) {
	define('CONSUMER_KEY', $keys['consumer_key']);
	define('CONSUMER_SECRET', $keys['consumer_secret']);
	define('OAUTH_CALLBACK', 'http://' . $_SERVER['HTTP_HOST'] . preg_replace('/&wptwit-page=[^&]*/', '', $_SERVER['REQUEST_URI']) . '&wptwit-page=callback');
}

//include TwitteroAuthFile
require_once('twitteroauth/twitteroauth.php');

//If we are authenticating execute the redirection to Twitter...
if(isset($_GET['wptwit-page']) && $_GET['wptwit-page'] == 'redirect') {
	require_once('redirect.php'); //Load redirect to auth
} elseif(isset($_GET['wptwit-page']) && $_GET['wptwit-page'] == 'callback') {
	require_once('callback.php'); //Load callback to create tokens
} elseif(isset($_GET['wptwit-page']) && $_GET['wptwit-page'] == 'deletekeys') {
	delete_option('twaggr_keys');
	//Delete oAuth token if it is set too.
	delete_option('twaggr_token');
	//redirect user to the entry page to enter new keys
	header('Location: ' . preg_replace('/&wptwit-page=[^&]*/', '', $_SERVER['REQUEST_URI']));
} elseif(isset($_GET['wptwit-page']) && $_GET['wptwit-page'] == 'deletecache') {
	//run cache deletion function
	twaggr_delete_cache();
} elseif(isset($_POST['consumerkey']) && isset($_POST['consumersecret'])) {
	//check if keys have been sent via POST & save them here.
	update_option('twaggr_keys', array('consumer_key' => trim($_POST['consumerkey']), 'consumer_secret' => trim($_POST['consumersecret'])));
	//redirect user to this page now that the keys have been saved. Remove the extra url param or we will endless loop.
	header('Location: ' . preg_replace('/&wptwit-page=[^&]*/', '', $_SERVER['REQUEST_URI']));
} elseif(isset($_POST['twaggr_usernames']) && isset($_POST['twaggr_number'])) {
	update_option('twaggr_usernames', trim(strip_tags($_POST['twaggr_usernames'])));
	update_option('twaggr_number', trim(strip_tags($_POST['twaggr_number'])));
	header('Location: ' . $_SERVER['REQUEST_URI'] . '&updated=1');
}

//Add our new page so users can authorize the plugin with Twitter... Yes, a page for 1 button...
add_action('admin_menu', 'twaggr_add_options');
//add our page to the settings sub menu
function twaggr_add_options() {
	add_options_page('Twaggr Authorize Page', 'Twaggr', 8, 'twaggrauth', 'twaggr_options_page');	
}

//Create the page...
function twaggr_options_page() {
?>
<div class="wrap">
   	<h2><?php _e( 'Twaggr Authorize Page', 'twit_sream' ); ?></h2>
   	
	<?php
	if(isset($_GET['wptwit-page']) && $_GET['wptwit-page'] == 'cachedeleted') {
	?>
		<div id="message" class="updated fade">
			<p><strong>
				<?php _e('Cache Deleted!', 'twit_stream' ); ?>
			</strong></p>
		</div>
	<?php
	} elseif(isset($_GET['wptwit-page']) && $_GET['wptwit-page'] == 'cachefailed') {
	?>
		<div id="message" class="error fade">
			<p><strong>
				<?php _e('Cache Deletion Failed!', 'twit_stream' ); ?>
			</strong></p>
		</div>
	<?php
	}
	//Have we already been authorized?
	$token = get_option('twaggr_token');
	if(!defined('CONSUMER_KEY') && !defined('CONSUMER_SECRET')) {
	?>
		<p style="font-weight:bold; color:#666;">oAuth is no longer optional. Due to changes to the Twitter API you must authorize Twaggr with your Twitter account by following the instructions below.</p>
		<h3>Create A Twitter App</h3>
		<p>To sign into Twitter via Twaggr you will need to register for a Twitter App. The process is fairly quick and can be done by clicking the 'Get your consumer keys' button below (opens in new window/tab), please read the following to find out what to enter.</p>
		<div style="margin: 15px 0 15px 0;"><a href="http://dev.twitter.com/apps/new/" target="_blank"><img src="<?php echo WP_PLUGIN_URL; ?>/twitter-stream/twitter-oauth-button.png" alt="Get your consumer keys"/></a></div>
		<ul>
			<li><strong>App Name &amp; Description:</strong> Any name to identify your blog (e.g. My Stream), it cannot contain the word 'Twitter'. You don't have to fill in description.</li>
			<li><strong>Website:</strong> Generally the URL to the home page of your blog.</li>
			<li><strong>Callback URL:</strong> Enter the following: <strong>http://<?php echo $_SERVER['HTTP_HOST'] . preg_replace('/&wptwit-page=[^&]*/', '', $_SERVER['REQUEST_URI']) . '&wptwit-page=callback'; ?></strong></li>
		</ul>
		<p>Once you have completed the registration you will be given a page with two very important keys <em style="color: #666;">(Consumer Key &amp; Consumer Secret)</em>. Please enter them in the boxes below &amp; hit save.</p>
		<p>When you are entering your Consumer Key &amp; Consumer Secret please make sure you do not copy any extraneous characters such as spaces. While we try our best to remove them for you, sometimes we cannot &amp; this will cause the dreaded 401 error when trying to authorize the plugin.</p>
		<p><strong>N.B:</strong> For those who are curious Twaggr does not need the app to have write access so if you are want to make sure security is tight you can make sure 'read-only' is picked on your <a href="http://dev.twitter.com/apps/">App's Settings page</a>.</p>
		<h3>Enter Key Information</h3>
		<form action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>" method="post">
			<label for="consumerkey" style="font-weight:bold;display:block;width:150px;">Consumer Key:</label> <input type="text" value="" id="consumerkey" name="consumerkey" />
			<label for="consumersecret" style="font-weight:bold;display:block;width:150px;margin-top:5px;">Consumer Secret:</label> <input type="text" value="" id="consumersecret" name="consumersecret" />
			<input type="submit" value="Save" style="display:block;margin-top:10px;" />
		</form>
	<?php
	} elseif(!is_array($token) && !isset($token['oauth_token'])) {
	?>
		<h3>Sign In With Twitter</h3>
		<p>Now you have registered an Twitter App and the keys have been saved, we can now sign you into Twitter &amp; finally get Twaggr up and running. To sign in simply click the 'sign in with Twitter' button below, check the details on the page that follows match that of the Twitter App you created, and finally press the 'allow' button.</p>
		<div style="margin: 15px 0 15px 0;"><a href="<?php echo preg_replace('/&wptwit-page=[^&]*/', '', $_SERVER['REQUEST_URI']) . '&wptwit-page=redirect'; ?>"><img src="<?php echo WP_PLUGIN_URL; ?>/twitter-stream/darker.png" alt="Sign in with Twitter"/></a></div>
		<h3>I'm Getting A 401 Error! What Do I Do?</h3>
		<p>This error is generally caused by one of the keys you provided either being incorrect or having unneeded characters in it, such as spaces or tabs at the start. This can sometimes happen when coping the keys from Twitter's website. Please make sure this is not the case. If you are still having trouble please get in touch via http://return-true.com.</p>
		<h3>What If I Made A Mistake Entering The Keys?</h3>
		<p>If you made a mistake entering the keys please click <a href="<?php echo preg_replace('/&wptwit-page=[^&]*/', '', $_SERVER['REQUEST_URI']) . '&wptwit-page=deletekeys'; ?>" style="color: #aa0000;">delete</a> to remove them.</p>
	<?php
	} else {
	?>
		<h3>Twaggr Authorized!</h3>
		<p>If you ever wish to revoke Twaggr's access to your twitter account just go to <a href="http://dev.twitter.com">Twitter's</a> Development website, login, then hover over your username (top right) and hit <strong>My Applications</strong>. Find the name of the application you created when authorizing Twaggr and click it. Next press the 'Delete' tab. Remember that doing this will obviously stop Twaggr from working. Once you've done that, click <a href="<?php echo preg_replace('/&wptwit-page=[^&]*/', '', $_SERVER['REQUEST_URI']) . '&wptwit-page=deletekeys'; ?>" style="color: #aa0000;">here</a> to revoke your keys from the WordPress database as they are no longer needed.</p>
		
		
		<h3>Twaggr Settings</h3>
		<?php 
			$twaggr_usernames = get_option('twaggr_usernames', ''); 
			$twaggr_un_count = count(explode(chr(10), $twaggr_usernames));
		?>
		<form action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>" method="post">
			<label for="twaggr_usernames">Usernames: (one name per line)</label><br />
			<textarea name="twaggr_usernames" id="twaggr_usernames" rows="20" cols="30" /><?php echo $twaggr_usernames; ?></textarea>
			<br />
			<p>
				Twitter limits API requests to 180 per 15 minutes. 
				Every username listed is a request. 
				You have <strong><?php echo $twaggr_un_count; ?></strong> usernames listed.
				The shortest interval of time you can schedule cron is <strong><?php
					$foo = 180 / $twaggr_un_count;
					$foo = 15 / $foo;
					echo ceil($foo);
				?></strong> minutes.
			</p>
			
			<label for="twaggr_number">Number of tweets to display:</label>
			<input type="text" name="twaggr_number" id="twaggr_number" value="<?php echo get_option('twaggr_number', 10); ?>" />
			<br />

			<input type="submit" value="Save" />
		</form>
		
		
		<h3>What Do I Do Now?</h3>
		<p>The easiest way to use Twaggr is to add it via the widgets. Just go to the widgets page and add the Twaggr widget to one of your widget areas. The alternative is to use the function by including &lt;php twaggr(); ?&gt; in your template somewhere. You can customize it using the parameters shown <a href="http://return-true.com/2009/12/wordpress-plugin-twitter-stream/">here</a>.
		<h3>I Need To Change My Keys!</h3>
		<p>If you ever need to change your consumer keys for whatever reason click <a href="<?php echo preg_replace('/&wptwit-page=[^&]*/', '', $_SERVER['REQUEST_URI']) . '&wptwit-page=deletekeys'; ?>" style="color: #aa0000;">delete</a> to remove them.</p>
	<?php
	}
	?>
	<h3>How Do I delete The Cache?</h3>
	<p>Use the small button below to delete the cache. Use this if there is an error message displaying instead of your Tweets or if you have changed your widget/template function settings.</p>
	<a href="<?php echo preg_replace('/&wptwit-page=[^&]*/', '', $_SERVER['REQUEST_URI']) . '&wptwit-page=deletecache'; ?>" style="display:block;width:95px;text-decoration:none;border:line-height:15px;margin:1px;padding:3px;font-size:11px;-moz-border-radius:4px 4px 4px 4px;-webkit-border-radius:4px 4px 4px 4px;border-radius:4px 4px 4px 4px;border-style:solid;border-width:1px;background-color:#fff0f5;border-color:#BBBBBB;color:#464646;text-align:center;">Delete Cache?</a>
	<p><small>Huge thanks to <a href="http://twitteroauth.labs.poseurtech.com/">Abraham Williams</a> for creating TwitterOAuth which is used to connect Twaggr to Twitter via oAuth.</small></p>
</div>
<?php
}





/*
 * Get this user's tweets from Twitter.
 * @param string $username
 * @param int $num number of tweets to get
 * @return array
 */
function twaggr_get_tweets(&$connection, $username, $num = 10) {
	$tweets = array();
	
	$username = str_replace("@", "", $username);
	
	$args = array(
		'screen_name' => $username,
		'count' => $num
	);
	
	$content = $connection->get('statuses/user_timeline', $args);
	
	echo "<hr /><pre>"; var_dump($content); echo '</pre>';
	
	if ($content === false) return array();
	if ($content->error || $content->errors) return array();
	
	foreach ($content as $result) {
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
		
		$twitter_url = 'http://twitter.com/' . $tweet->user->screen_name;
	
		$foo  = '<div class="twaggr-tweet">';
		
		$foo .= '<div class="twaggr-tweet-profile-image">';
		$foo .= '<a href="' . $twitter_url . '" target="_blank" rel="nofollow">';
		$foo .= '<img src="' . $tweet->user->profile_image_url . '" alt="' . $tweet->user->name . '" />';
		$foo .= '</a>';
		$foo .= '</div>';
		
		$foo .= '<div class="twaggr-tweet-name">';
		$foo .= '<a href="' . $twitter_url . '" target="_blank" rel="nofollow">';
		$foo .= $tweet->user->name;
		$foo .= '</a>';
		$foo .= '</div>';
		
		$foo .= '<div class="twaggr-tweet-user">';
		$foo .= '<a href="' . $twitter_url . '" target="_blank" rel="nofollow">';
		$foo .= '@' . $tweet->user->screen_name;
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
 */
function twaggr_fetch($verbose = false) {

	$all_tweets = array();

	$path = twaggr_path();
	$p = explode("/", $path);
	$upload_path = implode("/", array_slice($p, 0, count($p) - 2)) . '/uploads';

	// load usernames from config	
	$usernames = array();
	$config = explode(chr(10), get_option('twaggr_usernames', ''));
	foreach ($config as $c) {
		if (substr($c, 0, 1) != '@') {
			$c = '@' . $c;
		}
		$usernames[] = trim($c);
	}
	if ($verbose) {
		echo '<pre>'; var_dump($usernames); echo '</pre>';
	}

	
	// load number of tweets to display from config
	$num = get_option('twaggr_number', 10);
	
	if ($verbose) {
		echo '<pre>'; var_dump($num); echo '</pre>';
	}
	
	
	/* Get user access tokens out of the session. */
	$access_token = get_option('twaggr_token');
	if(empty($access_token) || $access_token === FALSE) {
		_e('Authorizing Twaggr with Twitter is no longer optional. You need to go to the Twaggr Authorization page in the WordPress Admin (under settings) before your tweets can be shown.');
		return FALSE;
	}
	/* Create a TwitterOauth object with consumer/user tokens. */
	$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);
	$connection->format = 'json';
	
	foreach ($usernames as $username) {
		$tweets = twaggr_get_tweets($connection, $username, $num);
		foreach ($tweets as $key => $tweet) {
			$all_tweets[$key] = $tweet;
		}
		sleep(1);
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
 */
function twaggr_display() {
	$path = twaggr_path();
	$p = explode("/", $path);
	$upload_path = implode("/", array_slice($p, 0, count($p) - 2)) . '/uploads';
	if (!file_exists($upload_path . '/twaggr_cache.html')) {
		return 'Sorry, no tweets at this time.';
	}
	$tweets = file_get_contents($upload_path . '/twaggr_cache.html');
	if ($tweets == '') return 'Sorry, no tweets at this time.';
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