<?php

namespace Codedistro\Processor;

use Codedistro\Processor;
use Codedistro\Message;

class GithubHook extends Processor {

    public function __construct(\Codedistro\Logger $log, \Codedistro\Config $config) {
        parent::__construct($log, $config);
        $this->type = 'GitPatch';
        $this->payload = null;
        $this->requiredProperties = array(
            'ref' => true,
            'before' => true,
            'after' => true,
            'repository' => array(
                'url' => true,
            ),
        );
    }

    public function process(\Codedistro\Message $message) {
        $this->logger->addDebug('validating message : ' . $message);
        try {
            $this->validate($message);
        } catch (\Exception $e) {
            $this->type = 'complete';
            $this->payload = $e->getMessage();
            return $this->output();
        }
        
        //Clone the repo
        if (!is_writeable($this->config->tempDirectory)) {
            $this->payload = 'Could not write to ' . $this->config->tempDirectory;
            $this->type = 'complete';
            return $this->output();
        }
        if (!is_dir($this->config->tempDirectory)) {
            mkdir($this->config->tempDirectory);
        }

        $user = basename(dirname($this->data['url']));

        //Support other git providers
        $server = "git@github.com"
        if (preg_match('/^(.+)@(.+):(.+)$/', $user, $matches)) {
            $this->logger->addDebug('Regex match : ' . print_r($matches));
            $user = $matches[3];
            $server = $matches[1] . '@' . $matches[2];
        }

        $this->logger->addDebug('User : ' . $user);
        if (!is_dir($this->config->tempDirectory . DIRECTORY_SEPARATOR . $user)) {
            mkdir($this->config->tempDirectory . DIRECTORY_SEPARATOR . $user);
        }

        $repo = basename($this->data['url']);
        $this->logger->addDebug('Repo : ' . $repo);
        $target_dir = $this->config->tempDirectory . DIRECTORY_SEPARATOR . $user . DIRECTORY_SEPARATOR . $repo;
        $this->logger->addDebug('TargetDir : ' . $target_dir);

        if (!is_dir($target_dir)) {
            mkdir($target_dir);
            $command = '/usr/bin/git clone ' . $server . ':' . $user . '/' . $repo . ' ' . $target_dir;
        } else {
            $command = '/usr/bin/git --git-dir=' . $target_dir . '/.git --work-tree=' . $target_dir . ' fetch';
        }

        $this->logger->addDebug($command);
        try {
            $output = Process::run($command);
        } catch (\Exception $e) {
            $this->payload = $e->getMessage();
            $this->type = 'complete';
            return $this->output();
        }
        $this->logger->addDebug($output);

        //Get the diff in patch format
        $filename = tempnam($this->config->tempDirectory, $user . $repo);
        $this->logger->addDebug($filename);
        $command = '/usr/bin/git --git-dir=' . $target_dir . '/.git --work-tree=' . $target_dir . ' format-patch ' . $this->data['before'] . '..' . $this->data['after'] . ' --stdout > ' . $filename;
        $this->logger->addDebug($command);
        try {
            $output = Process::run($command);
        } catch (\Exception $e) {
            $this->payload = $e->getMessage();
            $this->type = 'complete';
            return $this->output();
        }
        $this->logger->addDebug($output);

        //Send the diff in patch format back to the pub/sub server
        $patch = file_get_contents($filename);
        $this->logger->addDebug($patch);
        unlink($filename);
        $this->status = 'success';
        $payload = array(
            'patch' => $patch,
            'before' => $this->data['before'],
            'after' => $this->data['after'],
            'user' => $user,
            'repo' => $repo,
            'ref' => $this->data['ref'],
        );
        $this->payload = $payload;
        return $this->output();

    }
}



