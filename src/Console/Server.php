<?php

namespace Swoole\Console;

class Server {

    static function reload($pid_file)
    {
        $pid = self::getPidFromFile($pid_file);

        if (!$pid) {
            self::log('can not find manager pid file');
            self::log('reload [FAIL]');

            return false;

        //SIGUSR1  10
        } else if (!posix_kill($pid, SIGUSR1)) {
            self::log('send signal to manager failed');
            self::log('stop [FAIL]');

            return false;
        }

        self::log('reload [OK]');

        return true;
    }

    /**
     * 关闭服务器
     * @param $pid_file
     * @param $process_name
     * @return bool
     */
    static function shutdown($pid_file, $process_name)
    {
        $pid = self::getPidFromFile($pid_file);

        if (!$pid) {
            self::log($process_name . ': can not find master pid file');
            self::log($process_name . ': stop [FAIL]');

            return false;

        //SIGTERM  15  SIGKILL 9
        } else if (!posix_kill($pid, SIGKILL)) {
            self::log($process_name . ': send signal to master failed');
            self::log($process_name . ': stop [FAIL]');

            return false;
        }

        self::log($process_name . ": stop [OK]");

        return true;
    }

    static public function getPidFromFile($file)
    {
        $pid = false;
        if (file_exists($file)) {
            $pid = file_get_contents($file);
        }

        return $pid;
    }

    static public function log($msg)
    {
        echo sprintf("[%s]\t" . $msg . PHP_EOL, date('Y-m-d H:i:s'));
    }

}