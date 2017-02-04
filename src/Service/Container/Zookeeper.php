<?php

namespace Swoole\Service\Container;

/**
 * 注册中心Zookeeper容器
 * Class Zookeeper
 * @package Swoole\Service\Container
 */
class Zookeeper implements ContainerInterface {

    const CONTAINER = '/rpc';

    protected $zookeeper;

    protected $host;

    protected $port;

    public function __construct($host, $port)
    {
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * 服务注册
     * @param $service_name
     * @param $host
     * @param $port
     */
    public function register($service_name, $host, $port)
    {
        if (!$this->zookeeper instanceof \Zookeeper) {
            $this->initContainer();
        }

        $service_name = $service_name ? strtolower($service_name) : 'base';

        $service_data = [
            'service'   => $service_name,
            'host'      => $host,
            'port'      => $port
        ];

        $node = self::CONTAINER . '/' . $service_name . '/' . $host . ':' . $port;

        if (!$this->zookeeper->exists($node)) {
            $this->createPath($node);
            $this->createNode($node, json_encode($service_data), [], \Zookeeper::EPHEMERAL);
        } else {
            $this->zookeeper->set($node, json_encode($service_data));
        }

    }

    /**
     * 获取可用服务列表
     * @return array
     */
    public function discovery()
    {
        $serviceList = [];

        if (!$this->zookeeper instanceof \Zookeeper) {
            $this->initContainer();
        }

        if ($this->zookeeper->exists(self::CONTAINER)) {
            $path = $this->zookeeper->getChildren(self::CONTAINER);
            foreach ($path as $nodes) {
                $service = $this->zookeeper->getChildren(self::CONTAINER . '/' . $nodes);
                foreach ($service as $node) {
                    $info = json_decode($this->zookeeper->get(self::CONTAINER . '/' . $nodes . '/' . $node), true);
                    $serviceList[$info['service']][] = $info;
                }
            }
        }

        return $serviceList;
    }

    /**
     * 服务列表监控
     */
    public function watch()
    {
        //
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

    protected function initContainer()
    {
        $address = $this->host . ':' . $this->port;
        $this->zookeeper = new \Zookeeper($address);
    }

    /**
     * 创建路径
     * @param $path
     * @param string $value
     * @param array $acls
     */
    protected function createPath($path, $value = '', array $acls = [])
    {
        $parts = explode('/', $path);
        $parts = array_filter($parts);
        $subpath = '';
        while (count($parts) > 1) {
            $subpath .= '/' . array_shift($parts);
            if (!$this->zookeeper->exists($subpath)) {
                $this->createNode($subpath, $value, $acls);
            }
        }
    }

    /**
     * 创建节点
     * @param $path
     * @param $value
     * @param array $acls
     * @param int $flags
     * @return mixed
     */
    protected function createNode($path, $value, array $acls = [], $flags = null)
    {
        if (empty($acls)) {
            $acls = [
                [
                    'perms'  => \Zookeeper::PERM_ALL,
                    'scheme' => 'world',
                    'id'     => 'anyone',
                ]
            ];
        }

        return $this->zookeeper->create($path, $value, $acls, $flags);
    }

}