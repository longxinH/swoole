<?php

namespace Swoole\Service\Container;

interface ContainerInterface {

    /**
     * @param $service_name
     * @param $host
     * @param $port
     */
    public function register($service_name, $host, $port);

    /**
     * @return array
     */
    public function discovery();

    public function watch();

    public function drop($service, $host, $port);

}