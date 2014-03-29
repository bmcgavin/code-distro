<?php

namespace Codedistro\Processor;

use Codedistro\Processor;
use Codedistro\Message;

class BitbucketHook extends Processor {

    public function __construct(\Codedistro\Logger $log, \Codedistro\Config $config) {
        parent::__construct($log, $config);
        $this->type = 'GitPatch';
        $this->payload = null;
        $this->requiredProperties = array(
            'commits' => array(
                'node' => true,
                'parents' => true,
                'branch' => true,
            ),
            'repository' => array(
                'owner' => true,
                'slug' => true,
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

        $user = $this->data['owner'];
        $this->logger->addDebug('User : ' . $user);
        if (!is_dir($this->config->tempDirectory . DIRECTORY_SEPARATOR . $user)) {
            mkdir($this->config->tempDirectory . DIRECTORY_SEPARATOR . $user);
        }

        $repo = $this->data['slug'];
        $this->logger->addDebug('Repo : ' . $repo);
        $target_dir = $this->config->tempDirectory . DIRECTORY_SEPARATOR . $user . DIRECTORY_SEPARATOR . $repo;
        $this->logger->addDebug('TargetDir : ' . $target_dir);

        if (!is_dir($target_dir)) {
            mkdir($target_dir);
            $command = '/usr/bin/git clone git@bitbucket.org:' . $user . '/' . $repo . '.git ' . $target_dir;
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
        $command = '/usr/bin/git --git-dir=' . $target_dir . '/.git --work-tree=' . $target_dir . ' format-patch ' . $this->data['parents'][0] . '..' . $this->data['node'] . ' --stdout > ' . $filename;
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
            'before' => $this->data['parents'][0],
            'after' => $this->data['node'],
            'user' => $user,
            'repo' => $repo,
            'ref' => $this->data['branch'],
        );
        $this->payload = $payload;
        return $this->output();

    }
}


