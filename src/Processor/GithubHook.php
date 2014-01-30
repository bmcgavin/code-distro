<?php

namespace Codedistro\Processor;

use Codedistro\Processor;
use Codedistro\Message;

class GithubHook extends Processor {

    public function __construct($log, $config) {
        parent::__construct($log, $config);
        $this->type = 'github_hook';
        $this->next_type = 'github_patch';
        $this->response->type = $this->next_type;
        $this->requiredProperties = array(
            'ref' => true,
            'before' => true,
            'after' => true,
            'repository' => array(
                'url' => true,
            ),
        );
    }

    public function process($message) {
        $this->logger->addDebug('validating message : ' . print_r($message, true));
        try {
            $this->validate($message);
        } catch (\Exception $e) {
            $this->response->payload = $e->getMessage();
            return json_encode($this->response);
        }
        
        //Clone the repo
        if (!is_writeable($this->config->temp_directory)) {
            $this->response->payload = 'Could not write to ' . $this->config->temp_directory;
            return json_encode($this->response);
        }
        if (!is_dir($this->config->temp_directory)) {
            mkdir($this->config->temp_directory);
        }

        $user = basename(dirname($this->data['url']));
        $this->logger->addDebug('User : ' . $user);
        if (!is_dir($this->config->temp_directory . DIRECTORY_SEPARATOR . $user)) {
            mkdir($this->config->temp_directory . DIRECTORY_SEPARATOR . $user);
        }

        $repo = basename($this->data['url']);
        $this->logger->addDebug('Repo : ' . $repo);
        $target_dir = $this->config->temp_directory . DIRECTORY_SEPARATOR . $user . DIRECTORY_SEPARATOR . $repo;
        $this->logger->addDebug('TargetDir : ' . $target_dir);

        if (!is_dir($target_dir)) {
            mkdir($target_dir);
            $command = '/usr/bin/git clone git@github.com:' . $user . '/' . $repo . ' ' . $target_dir;
        } else {
            $command = '/usr/bin/git --git-dir=' . $target_dir . '/.git --work-tree=' . $target_dir . ' fetch';
        }

        $this->logger->addDebug($command);
        try {
            $output = Process::getInstance($command)->run();
        } catch (\Exception $e) {
            $this->response->payload = $e->getMessage();
            return json_encode($this->response);
        }
        $this->logger->addDebug($output);

        //Get the diff in patch format
        $filename = tempnam($this->config->temp_directory, $user . $repo);
        $this->logger->addDebug($filename);
        $command = '/usr/bin/git --git-dir=' . $target_dir . '/.git --work-tree=' . $target_dir . ' format-patch ' . $this->data['before'] . '..' . $this->data['after'] . ' --stdout > ' . $filename;
        $this->logger->addDebug($command);
        try {
            $output = Process::getInstance($command)->run();
        } catch (\Exception $e) {
            $this->response->payload = $e->getMessage();
            return json_encode($this->response);
        }
        $this->logger->addDebug($output);

        //Send the diff in patch format back to the pub/sub server
        $patch = file_get_contents($filename);
        $this->logger->addDebug($patch);
        unlink($filename);
        $this->response->status = 'success';
        $payload = array(
            'patch' => $patch,
            'before' => $this->data['before'],
            'after' => $this->data['after'],
            'user' => $user,
            'repo' => $repo,
            'ref' => $this->data['ref'],
        );
        $this->response->payload = json_encode($payload);
        return json_encode($this->response);

    }
}



