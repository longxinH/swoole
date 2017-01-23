<?php

namespace Swoole\Client;

interface TcpInterface {

    /**
     * @param $callback
     * @param $timeout
     * @return $this
     */
    public function connect($callback, $timeout);

    /**
     * @param $callback
     * @return $this
     */
    public function recv($callback);

    /**
     * @param $callback
     * @return $this
     */
    public function error($callback);

    /**
     * @param $data
     */
    public function send($data);

    /**
     * @param array $configure
     * @return $this
     */
    public function configure(array $configure);

    /**
     * @param $callback
     * @return $this
     */
    public function close($callback);

}