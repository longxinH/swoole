<?php

namespace Swoole\Server;

interface ServerCallbackInterface {

    /**
     *
     * @param \swoole_server $server
     * @return void
     */
    public function onStart(\swoole_server $server);

    /**
     *
     * @param \swoole_server $server
     * @param int $fd
     * @param int $from_id
     * @return mixed
     */
    public function onConnect(\swoole_server $server, $fd, $from_id);

    /**
     *
     * @param \swoole_server $server
     * @param int $fd
     * @param int $from_id
     * @return mixed
     */
    public function onClose(\swoole_server $server, $fd, $from_id);

    /**
     *
     * @param \swoole_server $server
     * @return void
     */
    public function onShutdown(\swoole_server $server);

    /**
     *
     * @param \swoole_server $server
     * @return void
     */
    public function onManagerStart(\swoole_server $server);

    /**
     *
     * @param \swoole_server $server
     * @return void
     */
    public function onManagerStop(\swoole_server $server);

    /**
     *
     * @param \swoole_server $server
     * @param int $worker_id
     * @return void
     */
    public function onWorkerStart(\swoole_server $server, $worker_id);

    /**
     *
     * @param \swoole_server $server
     * @param int $worker_id
     * @return void
     */
    public function onWorkerStop(\swoole_server $server, $worker_id);

    /**
     *
     * @param \swoole_server $serv
     * @param int $worker_id
     * @param int $worker_pid
     * @param int $exit_code
     * @return void
     */
    public function onWorkerError(\swoole_server $serv, $worker_id, $worker_pid, $exit_code);

    /**
     * @param \swoole_server $server
     * @param $task_id
     * @param $from_id
     * @param $data
     * @return mixed
     */
    public function onTask(\swoole_server $server, $task_id, $from_id, $data);

    /**
     * @param \swoole_server $server
     * @param $task_id
     * @param $data
     * @return mixed
     */
    public function onFinish(\swoole_server $server, $task_id, $data);

    /**
     * @param \swoole_server $server
     * @param $fd
     * @param $from_id
     * @param $data
     * @return mixed
     */
    public function onReceive(\swoole_server $server, $fd, $from_id, $data);

}