<?php

require('../vendor/autoload.php');

$p = new \Codedistro\Processor\Process('/usr/bin/git status');
echo $p->run();