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
     * @param \swoole_server $server
     * @param int $fd
     * @param int $from_id
     * @param array $data
     * @param array $header
     * @return mixed
     */
    public function doWork(\swoole_server $server, $fd, $from_id, $data, $header);

    /**
     * @param \swoole_server $server
     * @param int $task_id
     * @param int $from_id
     * @param string $data
     * @return mixed
     */
    //public function doTask(\swoole_server $server, $task_id, $from_id, $data);

    /**
     * @param int $fd
     * @param string $send_data
     * @param int $protocol_mode
     * @param int $guid
     * @return mixed
     */
    public function sendMessage($fd, $send_data, $protocol_mode, $guid);
    
}