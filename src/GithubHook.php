<?php

namespace Codedistro;

class GithubHook extends Shared {

    private static $log = null;

    public function __construct($log) {
        self::$log = $log;
    }

    public function process($message) {
        self::$log->addDebug('Got message : ' . print_r($message, true));
    }
}

