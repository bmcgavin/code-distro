<?php

namespace Codedistro;

class GithubPatch extends Shared {

    private static $log = null;
    private static $config = null;

    public $data = null;

    public function __construct($log, $config) {
        self::$log = $log;
        self::$config = $config;
    }

    public function process($message) {
        $response = new \stdClass;
        $response->type = 'github_patch';
        $response->status = 'error';
        $response->payload = 'processing error';
        self::$log->addDebug('processing message : ' . print_r($message, true));

        $response->status = 'success';
        $response->payload = $patch;
        return json_encode($response);

    }


}




