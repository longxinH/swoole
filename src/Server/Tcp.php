<?php

namespace Swoole\Server;

abstract class Tcp extends Base implements ServerInterface {

    const HttpServer = false;

    /**
     * Tcp constructor.
     * @param $config
     * @param string $process_name
     */
    public function __construct($config, $process_name = 'swoole')
    {
        parent::__construct($config, $process_name);
    }

    /**
     * 初始化服务
     */
    protected function initServer()
    {
        $this->server = new \swoole_server($this->host, $this->port, SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
        parent::initServer();
    }

    /**
     * @param \swoole_server $server
     * @param $fd
     * @param $from_id
     * @param $data
     * @return mixed
     */
    final public function onReceive(\swoole_server $server, $fd, $from_id, $data)
    {
        try {
            $content = $this->doWork($server, $fd, $from_id, $data);
            $server->send($fd, $content);
        } catch (\Exception $e) {
            $server->send($fd, 'Error : ' . $e->getMessage() . ' Code : ' . $e->getCode());
        }

        return true;
    }

    abstract public function doWork(\swoole_server $server, $fd, $from_id, $data);

}