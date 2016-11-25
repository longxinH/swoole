<?php

namespace Swoole\Client;

class Client implements ClientInterface {

    /**
     * @var \swoole_client
     */
    protected $client;

    /**
     *
     * @param $mode
     * @param $async
     * @param bool $keep
     */
    public function __construct($mode = SWOOLE_SOCK_TCP, $async = SWOOLE_SOCK_SYNC, $keep = true)
    {
        $mode = intval($mode);
        $async = intval($async);

        if ($mode == SWOOLE_SOCK_TCP && $async = SWOOLE_SOCK_SYNC && $keep) {
            $this->client = new \swoole_client($mode | SWOOLE_KEEP, $async);
        } else {
            $this->client = new \swoole_client($mode, $async);
        }
    }

    /**
     * @param      $host
     * @param      $port
     * @param int  $timeout
     * @return $this
     */
    public function connect($host, $port, $timeout = 2)
    {
        $this->client->connect($host, $port);

        return $this;
    }

    /**
     * @param $data
     * @return mixed
     */
    public function send($data)
    {
        if ($this->client->isConnected()) {
            $this->client->send($data);

            return $this->receive();
        }

        return null;
    }

    /**
     * @return null|string
     */
    public function receive()
    {
        if ($this->client->isConnected()) {
            return $this->client->recv();
        }
        
        return null;
    }

    /**
     * @return mixed
     */
    public function close()
    {
        return $this->client->close();
    }

    /**
     * @param $name
     * @param $callback
     * @return mixed
     */
    public function on($name, $callback)
    {
        $this->client->on($name, $callback);

        return $this;
    }

    /**
     * @param $configure
     * @return $this
     */
    public function configure($configure)
    {
        $this->client->set($configure);

        return $this;
    }

    
}