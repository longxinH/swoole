<?php

namespace Swoole\Client;

/**
 * Interface ClientInterface
 *
 * @package FastD\Swoole\Client
 */
interface ClientInterface {

    /**
     * @param $data
     * @return mixed
     */
    public function send($data);

    /**
     * @param      $host
     * @param      $port
     * @param int  $timeout
     * @return mixed
     */
    public function connect($host, $port, $timeout = 5);

    /**
     * @return mixed
     */
    public function receive();

    /**
     * @return mixed
     */
    public function close();
}