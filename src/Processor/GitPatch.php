<?php

namespace Codedistro\Processor;

use Codedistro\Processor;
use Codedistro\Message;

class GitPatch extends Processor {

    public function __construct(\Codedistro\Logger $log, \Codedistro\Config $config) {
        parent::__construct($log, $config);
        $this->type = 'Complete';
        $this->payload = null;
        $this->requiredProperties = array(
            'patch' => true,
            'before' => true,
            'after' => true,
            'user' => true,
            'repo' => true,
            'ref' => true,
        );

    }

    public function process(\Codedistro\Message $message) {
        $this->logger->addDebug('validating message : ' . $message);
        try {
            $this->validate($message);
        } catch (\Exception $e) {
            $this->payload = $e->getMessage();
            return $this->output();
        }

        //Get incoming branch
        $incomingBranch = basename($this->data['ref']);

        //Find where the working copy is
        $target_dir_key = 'repo_' . $this->data['user'] . '_' . $this->data['repo'] . '_' . $incomingBranch;
        $target_dir = $this->config->{$target_dir_key};
        if ($target_dir == null) {
            $target_dir_key = 'repo_' . $this->data['user'] . '_' . $this->data['repo'];
            $target_dir = $this->config->{$target_dir_key};
        }
        if ($target_dir == null) {
            $this->payload= "Cannot find key for this repo";
            return $this->output();
        }
        if (!is_dir($target_dir)) {
            $this->payload = $target_dir . ' does not exist';
            return $this->output();
        }
        if (!is_dir($target_dir . '/.git')) {
            $this->payload = $target_dir . ' is not a git repo';
            return $this->output();
        }

        //Check the current branch
        $command = '/usr/bin/git --git-dir=' . $target_dir . '/.git --work-tree=' . $target_dir . ' status --porcelain -b';
        $this->logger->addDebug($command);
        try {
            $output = Process::run($command);
        } catch (\Exception $e) {
            $this->payload = $e->getMessage();
            return $this->output();
        }
        $this->logger->addDebug($output);
        $branch = '';
        if (preg_match('|^\#\# ([\w]+)|', $output, $matches)) {
            $this->logger->addDebug('Got matches : ' . print_r($matches, true));
            $branch = trim($matches[1]);
        }
        $this->logger->addDebug('Got branch : ' . $branch);

        //Check the current ref
        if ($incomingBranch !== $branch) {
            $this->payload = 'Patch not for our branch (checked out : ' . $branch . ', patch for ' . $incomingBranch . ')';
            return $this->output();
        }

        //Check the current revision
        if (file_exists($target_dir . '/.gitrevision')) {
            $revision = file_get_contents($target_dir . '/.gitrevision');
        } else {
            $command = '/usr/bin/git --git-dir=' . $target_dir . '/.git --work-tree=' . $target_dir . ' log -n 1 --pretty=format:%H';
            $this->logger->addDebug($command);
            try {
                $revision = trim(Process::run($command));
            } catch (\Exception $e) {
                $this->payload = $e->getMessage();
                return $this->output();
            }
        }
        $this->logger->addDebug($revision);

        //Check that before == current
        if ($revision !== $this->data['before']) {
            $this->payload = 'Not at correct patch level : wc @ ' . $revision . ', patch starts @ ' . $this->data['before'];
            return $this->output();
        }

        //Write patch to temp file
        $filename = tempnam($this->config->$target_dir_key, 'patch_');
        $this->logger->addDebug($filename);
        file_put_contents($filename, $this->data['patch']);

        //Try to process as a github patch
        $oldDir = getcwd();
        chdir($target_dir);
        $command = '/usr/bin/git apply < ' . $filename;
        $this->logger->addDebug($command);
        try {
            $output = Process::run($command);
        } catch (\Exception $e) {
            $this->payload = $e->getMessage();
            return $this->output();
        }
        $this->logger->addDebug($output);
        chdir($oldDir);
        unlink($filename);

        //Store new revision
        file_put_contents($target_dir . '/.gitrevision', $this->data['after']);

        $this->status = 'success';
        $payload = array(
            'message' => 'done - new revision ' . $this->data['after']
        );
        $this->payload = $payload;
        return $this->output();
    }
}

