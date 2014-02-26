<?php

namespace Codedistro\Processor;

class Process {

    static public function run($command) {
        $descriptorspec = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w')
        );

        $process = proc_open($command, $descriptorspec, $pipes);

        if (!is_resource($process)) {
            throw new \Exception('Could not proc_open');
        }

        fclose($pipes[0]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $returnValue = proc_close($process);

        if ($returnValue !== 0) {
            throw new \Exception('Process terminated with non-zero exit code. ' . 
                PHP_EOL . 'stdout : ' . $stdout .
                PHP_EOL . 'stderr : ' . $stderr .
                PHP_EOL . 'retcode : ' . $returnValue
            );
        }
        return $stdout;
    }
}


