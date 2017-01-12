<?php

namespace Swoole\Server;

class ServerCallback implements ServerCallbackInterface {

    private $server;

    private $sw;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function initCallback(\swoole_server $sw)
    {
        $this->sw = $sw;

        $handles = get_class_methods($this);

        foreach ($handles as $value) {
            if ('on' == substr($value, 0, 2)) {
                $this->sw->on(lcfirst(substr($value, 2)), [$this, $value]);
            }
        }
    }

    public function onStart(\swoole_server $server)
    {
        swoole_set_process_name('swoole_' . $this->server->getProcessName() . ': master');

        file_put_contents($this->server->getMasterPidFile(), $server->master_pid);
        file_put_contents($this->server->getManagerPidFile(), $server->manager_pid);

        if (method_exists($this->server, 'onStart')) {
            $this->server->onStart($server);
        }
    }

    public function onManagerStart(\swoole_server $server)
    {
        swoole_set_process_name('swoole_' . $this->server->getProcessName() . ': manager');

        if (method_exists($this->server, 'onManagerStart')) {
            $this->server->onManagerStart($server);
        }
    }

    public function onWorkerStart(\swoole_server $server, $workerId)
    {
        $istask = $server->taskworker;
        if (!$istask) {
            //worker
            swoole_set_process_name('swoole_' . $this->server->getProcessName() . ': worker ' . $workerId);
        } else {
            //task
            swoole_set_process_name('swoole_' . $this->server->getProcessName() . ': task ' . $workerId);
        }

        if (method_exists($this->server, 'onWorkerStart')) {
            $this->server->onWorkerStart($server, $workerId);
        }
    }

    public function onManagerStop(\swoole_server $server)
    {
        $server->shutdown();

        if (method_exists($this->server, 'onManagerStop')) {
            $this->server->onManagerStop($server);
        }
    }

    public function onConnect(\swoole_server $server, $fd, $from_id)
    {
        if (method_exists($this->server, 'onConnect')) {
            $this->server->onConnect($server, $fd, $from_id);
        }
    }

    public function onClose(\swoole_server $server, $fd, $from_id)
    {
        if (method_exists($this->server, 'onClose')) {
            $this->server->onClose($server, $fd, $from_id);
        }
    }

    public function onShutdown(\swoole_server $server)
    {
        if (method_exists($this->server, 'onShutdown')) {
            $this->server->onShutdown($server);
        }
    }

    public function onWorkerStop(\swoole_server $server, $worker_id)
    {
        if (method_exists($this->server, 'onWorkerStop')) {
            $this->server->onWorkerStop($server, $worker_id);
        }
    }

    public function onWorkerError(\swoole_server $server, $worker_id, $worker_pid, $exit_code)
    {
        if (method_exists($this->server, 'onWorkerError')) {
            $this->server->onWorkerError($server, $worker_id, $worker_pid, $exit_code);
        }
    }

    public function onTask(\swoole_server $server, $task_id, $from_id, $data)
    {
        if (method_exists($this->server, 'onTask')) {
            return $this->server->onTask($server, $task_id, $from_id, $data);
        }
    }

    public function onFinish(\swoole_server $server, $task_id, $data)
    {
        if (method_exists($this->server, 'onFinish')) {
            $this->server->onFinish($server, $task_id, $data);
        }
    }

    public function onReceive(\swoole_server $server, $fd, $from_id, $data)
    {
        if (method_exists($this->server, 'onReceive')) {
            $this->server->onReceive($server, $fd, $from_id, $data);
        }

        return true;
    }

}