<?php

namespace Swoole\Client\Sync;

use Swoole\Client\TcpInterface;
use Swoole\Tool;

/**
 * Class Tcp
 * @package Swoole\Client\Sync
 */
class Tcp implements TcpInterface {

    /**
     * @var \swoole_client
     */
    protected $client;

    protected $timeout = 2;

    protected $host;

    protected $port;

    /**
     * @var array
     */
    protected $callbacks = [];

    /**
     * Tcp constructor.
     * @param $address
     */
    public function __construct($address)
    {
        $info = Tool::parse_address($address);

        $this->host = $info['host'];
        $this->port = $info['port'];

        $this->client = new \swoole_client(SWOOLE_SOCK_TCP);
    }

    /**
     * @param $callback
     * @param int $timeout
     * @return $this
     */
    public function connect($callback, $timeout = 2)
    {
        $this->callbacks['connect'] = $callback;

        $this->timeout = $timeout;

        return $this;
    }

    /**
     * @param $callback
     * @return $this
     */
    public function recv($callback)
    {
        $this->callbacks['recv'] = $callback;

        return $this;
    }

    /**
     * @param $callback
     * @return $this
     */
    public function error($callback)
    {
        return $this;
    }

    /**
     * @param $data
     */
    public function send($data)
    {
        if ($this->client->connect($this->host, $this->port, $this->timeout) === false) {
            trigger_error('server is not connected [' . $this->host . ':' . $this->port . ']', E_USER_ERROR);
        }

        if (isset($this->callbacks['connect']) && is_callable($this->callbacks['connect'])) {
            $this->callbacks['connect']($this->client);
        }

        $this->client->send($data);
        $res = $this->client->recv();
        if (isset($this->callbacks['recv']) && is_callable($this->callbacks['recv'])) {
            $this->callbacks['recv']($this->client, $res);
        }
    }

    /**
     * @param null $callback
     * @return $this
     */
    public function close($callback = null)
    {
        $this->client->close();

        return $this;
    }

    /**
     * @param array $configure
     * @return $this
     */
    public function configure(array $configure)
    {
        $this->client->set($configure);

        return $this;
    }

    /**
     * @return bool
     */
    public function isConnected()
    {
        return $this->client->isConnected();
    }

}