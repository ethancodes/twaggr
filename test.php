<?php

require_once 'twaggr.php';

//$tweets = twaggr_get_tweets('@AlbanyTweedRide', 1);

twaggr_fetch(3, true);

/*

what a tweet object looks like, for reference

object(stdClass)#32 (14) {
    ["created_at"]=>
    string(31) "Thu, 07 Mar 2013 02:24:53 +0000"
    ["from_user"]=>
    string(8) "jakevnrc"
    ["from_user_id"]=>
    int(59517460)
    ["from_user_id_str"]=>
    string(8) "59517460"
    ["from_user_name"]=>
    string(10) "jake brown"
    ["geo"]=>
    NULL
    ["id"]=>
    int(309489800065458176)
    ["id_str"]=>
    string(18) "309489800065458176"
    ["iso_language_code"]=>
    string(2) "en"
    ["metadata"]=>
    object(stdClass)#33 (1) {
      ["result_type"]=>
      string(6) "recent"
    }
    ["profile_image_url"]=>
    string(62) "http://a0.twimg.com/profile_images/422466188/trees3_normal.jpg"
    ["profile_image_url_https"]=>
    string(64) "https://si0.twimg.com/profile_images/422466188/trees3_normal.jpg"
    ["source"]=>
    string(79) "&lt;a href=&quot;http://www.handmark.com&quot;&gt;TweetCaster for iOS&lt;/a&gt;"
    ["text"]=>
    string(75) "RT @wcax: Should #Vt regulate shoreline development? http://t.co/yEg9uxo0iB"
  }
  
*/