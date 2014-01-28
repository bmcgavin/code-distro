<?php

namespace Codedistro;

class GithubHook extends Shared {

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
        //Parse the data we need from the message
        try {
            $this->extractValue($message);
        } catch (\Exception $e) {
            $response->payload = $e->getMessage();
            return json_encode($response);
        }
        self::$log->addDebug('data : ' . print_r($this->data, true));

        //Clone the repo
        if (!is_writeable(self::$config['temp_directory'])) {
            $response->payload = 'Could not write to ' . self::$config['temp_directory'];
            return json_encode($response);
        }
        if (!is_dir(self::$config['temp_directory'])) {
            mkdir(self::$config['temp_directory']);
        }

        $user = basename(dirname($this->data['url']));
        self::$log->addDebug('User : ' . $user);
        if (!is_dir(self::$config['temp_directory'] . DIRECTORY_SEPARATOR . $user)) {
            mkdir(self::$config['temp_directory'] . DIRECTORY_SEPARATOR . $user);
        }

        $repo = basename($this->data['url']);
        self::$log->addDebug('Repo : ' . $repo);
        $target_dir = self::$config['temp_directory'] . DIRECTORY_SEPARATOR . $user . DIRECTORY_SEPARATOR . $repo;
        self::$log->addDebug('TargetDir : ' . $target_dir);

        if (!is_dir($target_dir)) {
            mkdir($target_dir);
            $command = '/usr/bin/git clone git@github.com:' . $user . '/' . $repo . ' ' . $target_dir;
        } else {
            $command = '/usr/bin/git --work-tree=' . $target_dir . ' fetch';
        }

        self::$log->addDebug($command);
        $output = exec($command);
        self::$log->addDebug($output);

        //Get the diff in patch format
        $command = '/usr/bin/git --work-tree=' . $target_dir . ' format-patch ' . $this->data['before'] . '..' . $this->data['after'] . ' --stdout';
        self::$log->addDebug($command);
        $output = exec($command);
        self::$log->addDebug($output);

        //Send the diff in patch format back to the pub/sub server
        $response->status = 'success';
        $response->payload = $output;
        return json_encode($response);

    }

    private function extractValue($message) {
        $requiredProperties = array(
            'before' => true,
            'after' => true,
            'repository' => array(
                'url' => true,
            ),
        );
        $this->validateArray($requiredProperties, $message);
    }

    private function validateArray($array, $message) {
        self::$log->addDebug('validateArray array input : ' . print_r($array, true));
        self::$log->addDebug('validateArray message input : ' . print_r($message, true));
        foreach($array as $key => $value) {
            if (!property_exists($message, $key)) {
                throw new \Exception('$message has no ' . $key);
            }
            if (is_array($value)) {
                $this->validateArray($value, $message->{$key});
            } else {
                $this->data[$key] = $message->{$key};
            }

        } 
    }


}



