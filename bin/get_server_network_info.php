#!/usr/bin/env php
<?php
include __DIR__.'/../../include/functions.inc.php';

if (!isset($_SERVER['argv'][1])) {
    die('Missing Server ID Parameter!

Syntax: '.$_SERVER['argv'][0].' <id>
 where <id> is a server id');
}
$info = get_server_network_info($_SERVER['argv'][1]);
echo str_replace('\\/', '/', json_encode($info, JSON_PRETTY_PRINT)).PHP_EOL;
