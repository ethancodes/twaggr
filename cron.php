<?php

// execute something like
// /usr/bin/php /path/to/twaggr/cron.php

error_reporting(E_ALL);

$twaggr_path = dirname(__FILE__);
require_once $twaggr_path . '/twaggr.php';
twaggr_fetch();

?>