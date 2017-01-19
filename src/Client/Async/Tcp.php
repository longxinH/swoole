<?php

namespace Swoole\Client\Async;

use Swoole\Client\TcpInterface;
use Swoole\Tool;

/**
 * Class Tcp
 * @package Swoole\Client\Async
 */
class Tcp implements TcpInterface {

    /**
     * @var \swoole_client
     */
    protected $client;

    protected $timeout = 2;

    protected $host;

    protected $port;

    protected $send_data;

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

        $this->client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
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
        $this->callbacks['error'] = $callback;
        return $this;
    }

    /**
     * @param $data
     */
    public function send($data)
    {
        $this->client->on('connect', [$this, 'onConnect']);
        $this->client->on('receive', [$this, 'onReceive']);
        $this->client->on('error', [$this, 'onError']);
        $this->client->on('close', [$this, 'onClose']);

        $this->send_data = $data;

        if ($this->client->connect($this->host, $this->port, $this->timeout) === false) {
            trigger_error('server is not connected [' . $this->host . ':' . $this->port . ']', E_USER_ERROR);
        }
    }



    /**
     * @param $callback
     * @return $this
     */
    public function close($callback)
    {
        $this->callbacks['close'] = $callback;

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

    /**
     * @param \swoole_client $client
     */
    final public function onConnect(\swoole_client $client)
    {
        $client->send($this->send_data);

        if (isset($this->callbacks['connect']) && is_callable($this->callbacks['connect'])) {
            $this->callbacks['connect']($client);
        }
    }

    /**
     * @param \swoole_client $client
     * @param $data
     */
    final public function onReceive(\swoole_client $client, $data)
    {
        if (isset($this->callbacks['recv']) && is_callable($this->callbacks['recv'])) {
            $this->callbacks['recv']($client, $data);
        }
    }

    /**
     * @param \swoole_client $client
     */
    final public function onError(\swoole_client $client)
    {
        if (isset($this->callbacks['error']) && is_callable($this->callbacks['error'])) {
            $this->callbacks['error']($client);
        }
    }

    /**
     * @param \swoole_client $client
     */
    final public function onClose(\swoole_client $client)
    {
        if (isset($this->callbacks['close']) && is_callable($this->callbacks['close'])) {
            $this->callbacks['close']();
        }
    }

}