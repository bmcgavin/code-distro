<?php

namespace Codedistro;

class GithubHook {

    public static function process($message) {
        self::$log->addDebug('Got message : ' . print_r($message, true));
    }
}

