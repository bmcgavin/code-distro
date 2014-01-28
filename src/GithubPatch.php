<?php

namespace Codedistro;

class GithubPatch extends Shared {

    protected static $log = null;
    protected static $config = null;

    public $data = null;

    public function __construct($log, $config) {
        self::$log = $log;
        self::$config = $config;
    }

    public function process($message) {
        $response = new \stdClass;
        $response->type = 'complete';
        $response->status = 'error';
        $response->payload = 'processing error';
        self::$log->addDebug('processing message : ' . print_r($message, true));

        try {
            $requiredProperties = array(
                'patch' => true,
                'before' => true,
                'after' => true,
                'user' => true,
                'repo' => true,
            );
            $this->validateArray($requiredProperties, $message);
        } catch (\Exception $e) {
            $response->payload = $e->getMessage();
            return json_encode($response);
        }
        self::$log->addDebug('data : ' . print_r($this->data, true));

        //Find where the working copy is
        if (!array_key_exists('repo_' . $this->data['user'] . '_' . $this->data['repo'], self::$config)) {
            $response->payload = 'No location for ' . $this->data['user'] . ':' . $this->data['repo'];
            return json_encode($response);
        }
        $target_dir = self::$config['repo_' . $this->data['user'] . '_' . $this->data['repo']];
        if (!is_dir($target_dir)) {
            $response->payload = $target_dir . ' does not exist';
            return json_encode($response);
        }
        if (!is_dir($target_dir . '/.git')) {
            $response->payload = $target_dir . ' is not a git repo';
            return json_encode($response);
        }

        //Check the current revision
        if (file_exists($target_dir . '/.gitrevision')) {
            $revision = file_get_contents($target_dir . '/.gitrevision');
        } else {
            $command = '/usr/bin/git --git-dir=' . $target_dir . '/.git --work-tree=' . $target_dir . ' log -n 1 --pretty=format:%H';
            self::$log->addDebug($command);
            $revision = trim(exec($command));
        }
        self::$log->addDebug($revision);

        //Check that before == current
        if ($revision !== $this->data['before']) {
            $response->payload = 'Not at correct patch level : wc @ ' . $revision . ', patch starts @ ' . $this->data['before'];
            return json_encode($response);
        }

        //Write patch to temp file
        $filename = tempnam(self::$config['repo_' . $this->data['user'] . '_' . $this->data['repo']], 'patch_');
        self::$log->addDebug($filename);
        file_put_contents($filename, $this->data['patch']);

        //Try to process as a github patch
        $oldDir = getcwd();
        chdir($target_dir);
        $command = '/usr/bin/git apply < ' . $filename;
        self::$log->addDebug($command);
        $output = exec($command);
        self::$log->addDebug($output);
        chdir($oldDir);
        unlink($filename);

        //Store new revision
        file_put_contents($target_dir . '/.gitrevision', $this->data['after']);

        $response->status = 'success';
        $payload = array(
            'message' => 'done - new revision ' . $this->data['after']
        );
        $response->payload = json_encode($payload);
        return json_encode($response);

    }


}




