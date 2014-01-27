#!/usr/bin/php
<?php

require_once('vendor/autoload.php');

use Codedistro\Client;

if (count($argv) < 2) {
    echo "Where's the config file?";
    die(1);
}

$c = new Client($argv[1]);