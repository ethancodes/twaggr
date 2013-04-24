<?php

// execute something like
// /usr/bin/php /path/to/twaggr/cron.php

error_reporting(E_ALL);

$twaggr_path = dirname(__FILE__);
$wp_path = explode("/", $twaggr_path);
#echo '<pre>'; var_dump($wp_path); echo '</pre>';
$wp_path = array_slice($wp_path, 0, count($wp_path) - 3);
#echo '<pre>'; var_dump($wp_path); echo '</pre>';
$wp_path = implode("/", $wp_path);
#echo '<pre>'; var_dump($wp_path); echo '</pre>';
#exit;

// bootstrap wordpress
$ok = chdir($wp_path);
#echo '<pre>'; var_dump($ok); echo '</pre>';
define('WP_USE_THEMES', false);
require('wp-blog-header.php');

require_once $twaggr_path . '/twaggr.php';
twaggr_fetch(true);

?>