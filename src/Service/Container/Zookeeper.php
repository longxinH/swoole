<?php

namespace Swoole\Service\Container;

/**
 * 注册中心Zookeeper容器
 * Class Zookeeper
 * @package Swoole\Service\Container
 */
class Zookeeper implements ContainerInterface {

    protected $zookeeper;

    public function __construct($host, $port, $auth = false)
    {

    }

    /**
     * 服务注册
     * @param $service_name
     * @param $host
     * @param $port
     */
    public function register($service_name, $host, $port)
    {

    }

    /**
     * 获取可用服务列表
     * @return array
     */
    public function discovery()
    {

    }

    /**
     * 服务列表监控
     */
    public function watch()
    {

    }

    /**
     * 服务摘除
     * @param $service
     * @param $host
     * @param $port
     */
    public function drop($service, $host, $port)
    {

    }

}