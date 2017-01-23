<?php

namespace Swoole\Service\Container;

/**
 * 注册中心Redis容器
 * Class Redis
 * @package Swoole\Service\Container
 */
class Redis implements ContainerInterface {

    protected $redis;

    /**
     * 10s
     * @var int
     */
    protected $interval = 10;

    /**
     * Redis constructor.
     * @param $host
     * @param $port
     * @param bool $auth
     */
    public function __construct($host, $port, $auth = false)
    {
        $this->redis = new \Swoole\Cache\Redis($host, $port, $auth);
    }

    /**
     * 服务注册
     * @param $service_name
     * @param $host
     * @param $port
     */
    public function register($service_name, $host, $port)
    {
        $service_data = [
            'service'   => $service_name ? strtolower($service_name) : 'base',
            'host'      => $host,
            'port'      => $port
        ];

        $this->redis->sAdd('registerlist', json_encode($service_data));

        //todo 记录服务器最后上报时间
        $this->redis->set($service_data['service'] . '_' . $service_data['host'] . '_' . $service_data['port'] . '_runtime', time());

        sleep($this->interval);
    }

    /**
     * 获取可用服务列表
     * @return array
     */
    public function discovery()
    {
        $list = $this->redis->sMembers('servicelist');
        $serviceList = [];

        if (!empty($list)) {
            foreach ($list as $node) {
                $info = json_decode($node, true);
                $serviceList[$info['service']][] = $info;
            }
        }

        return $serviceList;
    }

    /**
     * 服务列表监控
     */
    public function watch()
    {
        $register_list = $this->redis->sMembers('registerlist');

        if ($register_list) {
            foreach ($register_list as $node) {
                $info = json_decode($node, true);

                $time = $this->redis->get($info['service'] . '_' . $info['host'] . '_' . $info['port'] . '_runtime');
                if (time() - $time > $this->interval + 10) {
                    $this->drop($info['service'], $info['host'], $info['port']);
                    continue;
                }

                $this->redis->sAdd('servicelist', $node);
            }
        }
    }

    /**
     * 服务摘除
     * @param $service
     * @param $host
     * @param $port
     */
    public function drop($service, $host, $port)
    {
        $info = [
            'service'   => $service,
            'host'      => $host,
            'port'      => $port
        ];

        $this->redis->sRem('servicelist', json_encode($info));
    }

}