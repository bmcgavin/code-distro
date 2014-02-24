<?php

namespace Codedistro\Processor;

use Codedistro\Processor;
use Codedistro\Message;

class GithubPatch extends Processor {

    public function __construct($log, $config) {
        parent::__construct($log, $config);
        $this->type = 'github_patch';
        $this->next_type = 'complete';
        $this->response->type = $this->next_type;
        
        $this->requiredProperties = array(
            'patch' => true,
            'before' => true,
            'after' => true,
            'user' => true,
            'repo' => true,
            'ref' => true,
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


        //Find where the working copy is
        $target_dir_key = 'repo_' . $this->data['user'] . '_' . $this->data['repo'];
        $target_dir = $this->config->{$target_dir_key};
        if (!is_dir($target_dir)) {
            $this->response->payload = $target_dir . ' does not exist';
            return json_encode($this->response);
        }
        if (!is_dir($target_dir . '/.git')) {
            $this->response->payload = $target_dir . ' is not a git repo';
            return json_encode($this->response);
        }

        //Check the current revision
        if (file_exists($target_dir . '/.gitrevision')) {
            $revision = file_get_contents($target_dir . '/.gitrevision');
        } else {
            $command = '/usr/bin/git --git-dir=' . $target_dir . '/.git --work-tree=' . $target_dir . ' log -n 1 --pretty=format:%H';
            $this->logger->addDebug($command);
            try {
                $revision = trim(Process::getInstance($command)->run());
            } catch (\Exception $e) {
                $this->response->payload = $e->getMessage();
                return json_encode($this->response);
            }
        }
        $this->logger->addDebug($revision);

        //Check that before == current
        if ($revision !== $this->data['before']) {
            $this->response->payload = 'Not at correct patch level : wc @ ' . $revision . ', patch starts @ ' . $this->data['before'];
            return json_encode($this->response);
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
            $output = Process::getInstance($command)->run();
        } catch (\Exception $e) {
            $this->response->payload = $e->getMessage();
            return json_encode($this->response);
        }
        $this->logger->addDebug($output);
        chdir($oldDir);
        unlink($filename);

        //Store new revision
        file_put_contents($target_dir . '/.gitrevision', $this->data['after']);

        $this->response->status = 'success';
        $payload = array(
            'message' => 'done - new revision ' . $this->data['after']
        );
        $this->response->payload = json_encode($payload);
        return json_encode($this->response);

    }


}




