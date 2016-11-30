<?php

namespace Swoole\Client;

use Swoole\Packet\Format;

class Client implements ClientInterface {

    const ERR_UNPACK     = 8006; //解包失败了
    const ERR_HEADER     = 8007; //错误的协议头
    const ERR_LENGTH     = 8008; //错误的长度

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
            $this->client->send(Format::packEncode($data));

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
            $result = $this->client->recv();
            $header = Format::packDecodeHeader($result);

            //错误的包头
            if ($header == false) {
                return $this->resultError('ERR_HEADER', self::ERR_HEADER);
            }

            if (Format::checkHeaderLength($header, $result) == false) {
                return $this->resultError('ERR_LENGTH', self::ERR_LENGTH);
            }

            $data = Format::packDecode($result, $header['type']);

            //解包失败
            if ($data === false) {
                return $this->resultError('ERR_UNPACK', self::ERR_UNPACK);
            }

            return $data;
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

    /**
     * @return bool
     */
    public function isConnected()
    {
        return $this->client->isConnected();
    }

    private function resultError($message, $erron)
    {
        return json_encode(
            [
                'code'  => $erron,
                'msg'   => $message,
                'data'  => ''
            ]
        );
    }

}