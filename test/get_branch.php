<?php

require('vendor/autoload.php');

$target_dir = '.';

$command = '/usr/bin/git --git-dir=' . $target_dir . '/.git --work-tree=' . $target_dir . ' status --porcelain -b';
print($command . PHP_EOL);
try {
    $output = \Codedistro\Processor\Process::getInstance($command)->run();
} catch (\Exception $e) {
    print_r($e->getMessage());
}
print($output . PHP_EOL);
$branch = preg_match('|^\#\# (.*)(\.\.\.)?|', $output, $matches);
print('Got branch : ' . print_r($matches, true) . PHP_EOL);