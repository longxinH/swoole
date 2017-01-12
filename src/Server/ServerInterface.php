<?php

namespace Swoole\Server;

interface ServerInterface {

    /**
     * @return string
     */
    public function getHost();

    /**
     * @return string
     */
    public function getPort();

    /**
     * @return string
     */
    public function getProcessName();

    /**
     * @return string
     */
    public function getMasterPidFile();

    /**
     * @return string
     */
    public function getManagerPidFile();

    /**
     * @param \swoole_server $server
     * @param int $fd
     * @param int $from_id
     * @param array $data
     * @param array $header
     * @return array
     */
    public function doWork(\swoole_server $server, $fd, $from_id, $data, $header);

    /**
     * @param \swoole_server $server
     * @param int $task_id
     * @param int $from_id
     * @param string $data
     * @return array
     */
    public function doTask(\swoole_server $server, $task_id, $from_id, $data);

    /**
     * @param $fd
     * @param $send_data
     * @param $protocol_mode
     * @param $guid
     * @return mixed
     */
    public function sendMessage($fd, $send_data, $protocol_mode, $guid);

}