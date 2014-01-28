<?php

namespace Codedistro;

class GithubHook extends Shared {

    private static $log = null;
    private static $config = null;

    public function __construct($log, $config) {
        self::$log = $log;
        self::$config = $config;
    }

    public function process($message) {
        $response = new stdClass;
        $response->type = 'error';
        $response->payload = '';
        self::$log->addDebug('processing message : ' . print_r($message, true));
        //Parse the data we need from the message
        try {
            $this->extractValue($message);
        } catch (Exception $e) {
            $response->payload = $e->getMessage();
            return json_encode($response);
        }

        //Clone the repo
        if (!is_writeable(self::$config['temp_directory'])) {
            $response->payload = 'Could not write to ' . self::$config['temp_directory'];
            return json_encode($response);
        }
        
            

        //Get the diff in patch format

        //Send the diff in patch format back to the pub/sub server



    }

    private function extractValue($message) {
        $requiredProperties = array(
            'before' => true,
            'after' => true,
            'repository' => array(
                'url'
            ),
        );
        $this->validateArray($requireProperties, $message);
    }

    private function validateArray($array, $message) {
        self::$log->addDebug('validateArray array input : ' . print_r($array, true));
        self::$log->addDebug('validateArray message input : ' . print_r($message, true));
        foreach($array as $key => $value) {
            if (!property_exists($message, $key)) {
                throw new Exception('$message has no ' . $key);
            }
            if (is_array($value)) {
                $this->validateArray($value, $message->{$key});
            }

        } 
    }


}



