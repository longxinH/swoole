<?php

namespace Swoole\Server;

abstract class ServerCallback implements ServerCallbackInterface {

    public function initCallback(\swoole_server $server)
    {
        $handles = get_class_methods($this);

        foreach ($handles as $value) {
            if ('on' == substr($value, 0, 2)) {
                $server->on(lcfirst(substr($value, 2)), [$this, $value]);
            }
        }
    }
    
    final public function onStart(\swoole_server $server)
    {
        swoole_set_process_name('swoole_' . $this->processName . ': master');

        file_put_contents($this->masterPidFile, $server->master_pid);
        file_put_contents($this->managerPidFile, $server->manager_pid);
    }

    final public function onManagerStart(\swoole_server $server) {
        swoole_set_process_name('swoole_' . $this->processName . ': manager');
    }

    final public function onWorkerStart(\swoole_server $server, $workerId) {
        $istask = $server->taskworker;
        if (!$istask) {
            //worker
            swoole_set_process_name('swoole_' . $this->processName . ': worker ' . $workerId);
        } else {
            //task
            swoole_set_process_name('swoole_' . $this->processName . ': task ' . $workerId);
        }
    }

    final public function onManagerStop(\swoole_server $server) {
        $server->shutdown();
    }

    public function onConnect(\swoole_server $server, $fd, $from_id) {}

    public function onClose(\swoole_server $server, $fd, $from_id) {}
    
    public function onShutdown(\swoole_server $server) {}
    
    public function onWorkerStop(\swoole_server $server, $worker_id) {}
    
    public function onWorkerError(\swoole_server $serv, $worker_id, $worker_pid, $exit_code) {}

    public function onTask(\swoole_server $server, $task_id, $from_id, $data) {}

    public function onFinish(\swoole_server $server, $task_id, $data) {}
}