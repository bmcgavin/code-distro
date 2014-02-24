<?php

namespace Codedistro;

class Config {

    private $data = array();
    private $prefix = '';

    public function __construct($configFile, $prefix = '') {
        try {
            //Read config
            if (!file_exists($configFile)) {
                throw new \Exception('Cannot find file ' . $configFile);
            }
            #include_once($configFile);
            $config = parse_ini_file($configFile, true);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            die($e->getMessage());
        }
        $this->data = $config;
        if ($prefix !== '') {
            $this->prefix = $prefix;
        }
        return true;
    }

    public function __get($key) {
        if ($this->prefix !== ''
            && array_key_exists($this->prefix, $this->data)
            && array_key_exists($key, $this->data[$this->prefix])
        ) {
            return $this->data[$this->prefix][$key];
        }
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }
        if (array_key_exists($key, $this->data['common'])) {
            return $this->data['common'][$key];
        }
        return false;
    }

}
