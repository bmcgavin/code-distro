<?php

namespace Codedistro;

class Config {

    private $data = array();

    public function __construct($configFile) {
        try {
            //Read config
            if (!file_exists($configFile)) {
                throw new \Exception('Cannot find file ' . $configFile);
            }
            include_once($configFile);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            die($e->getMessage());
        }
        $this->data = $config;
        return true;
    } 

    public function __get($key) {
        return $this->data[$key];
    }

}
