#!/usr/bin/php
<?php

require_once('vendor/autoload.php');

use Codedistro\Server;

if (count($argv) < 2) {
    echo "Where's the config file?";
    die(1);
}

$s = new Server($argv[1]);