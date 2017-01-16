<?php

namespace Swoole\CallBack;

use Swoole\Console\Process;
use Swoole\Server\ServerInterface;

class SwooleCallback {

    /**
     * @var \Swoole\Server\Tcp|\Swoole\Server\Http
     */
    private $server;

    public function __construct(ServerInterface $server)
    {
        $this->server = $server;
    }

    public function initCallback()
    {
        $swoole = $this->server->getSwoole();

        $handles = get_class_methods($this);

        foreach ($handles as $value) {
            if ('on' == substr($value, 0, 2)) {
                $swoole->on(lcfirst(substr($value, 2)), [$this, $value]);
            }
        }

        if (is_callable([$this->server, 'onConnect'])) {
            $swoole->on('Connect', [$this->server, 'onConnect']);
        }

        if (is_callable([$this->server, 'onClose'])) {
            $swoole->on('Close', [$this->server, 'onClose']);
        }

        $_s = $this->server;
        if ($_s::HttpServer) {
            $swoole->on('Request', [$this->server, 'onRequest']);
        } else {
            $swoole->on('Receive', [$this->server, 'onReceive']);
        }

        if (is_callable([$this->server, 'WorkerStop'])) {
            $swoole->on('WorkerStop', [$this->server, 'WorkerStop']);
        }

        if (is_callable([$this->server, 'onTask'])) {
            $swoole->on('Task', [$this->server, 'onTask']);
            $swoole->on('Finish', [$this->server, 'onFinish']);
        }

        if (is_callable([$this->server, 'onShutdown'])) {
            $swoole->on('Shutdown', [$this->server, 'onShutdown']);
        }

        if (is_callable([$this->server, 'onWorkerError'])) {
            $swoole->on('WorkerError', [$this->server, 'onWorkerError']);
        }
    }

    public function onStart(\swoole_server $server)
    {
        Process::setProcessName('swoole_' . $this->server->getProcessName() . ': master');

        file_put_contents($this->server->getMasterPidFile(), $server->master_pid);
        file_put_contents($this->server->getManagerPidFile(), $server->manager_pid);

        if (is_callable([$this->server, 'onStart'])) {
            $this->server->onStart($server);
        }
    }

    public function onManagerStart(\swoole_server $server)
    {
        Process::setProcessName('swoole_' . $this->server->getProcessName() . ': manager');

        if (is_callable([$this->server, 'onManagerStart'])) {
            $this->server->onManagerStart($server);
        }
    }

    public function onWorkerStart(\swoole_server $server, $workerId)
    {
        $istask = $server->taskworker;
        if (!$istask) {
            //worker
            Process::setProcessName('swoole_' . $this->server->getProcessName() . ': worker ' . $workerId);
        } else {
            //task
            Process::setProcessName('swoole_' . $this->server->getProcessName() . ': task ' . $workerId);
        }

        if (is_callable([$this->server, 'onWorkerStart'])) {
            $this->server->onWorkerStart($server, $workerId);
        }
    }

    public function onManagerStop(\swoole_server $server)
    {
        $server->shutdown();

        if (is_callable([$this->server, 'onManagerStop'])) {
            $this->server->onManagerStop($server);
        }
    }

}