<?php

namespace Swoole\Service;

use Swoole\Cache\Redis;

class ServiceList {

    /**
     * @var \Redis
     */
    protected static $handle;

    public function __construct(array $config)
    {
        self::$handle = Redis::getInstance($config);
    }

    /**
     * 服务注册
     * @param $service
     * @param $host
     * @param $port
     * @param $time
     */
    public function register($service, $host, $port, $time)
    {
        $_service_data = [
            'service'   => $service ? strtolower($service) : 'base',
            'host'      => $host,
            'port'      => $port
        ];

        self::$handle->sAdd('registerlist', json_encode($_service_data));
        //todo 记录服务器最后上报时间
        self::$handle->set($_service_data['service'] . '_' . $_service_data['host'] . '_' . $_service_data['port'] . '_runtime', $time);

        $register_list = self::$handle->sMembers('registerlist');

        if ($register_list) {
            foreach ($register_list as $node) {
                $info = json_decode($node, true);

                $time = self::$handle->get($info['service'] . '_' . $info['host'] . '_' . $info['port'] . '_runtime');
                if (time() - $time > 20) {
                    $this->drop($info['service'], $info['host'], $info['port']);
                    continue;
                }

                self::$handle->sAdd('servicelist', $node);
            }
        }
    }

    /**
     * 上报超时，服务移除
     * @param $service
     * @param $host
     * @param $port
     * @return mixed
     */
    public function drop($service, $host, $port)
    {
        $service = [
            'service'   => $service,
            'host'      => $host,
            'port'      => $port
        ];

        self::$handle->sRem('servicelist', json_encode($service));
    }

    /**
     * 移除某服务所有
     * @param $service
     * @desc
     */
    public function dropAll($service = '')
    {
        if ($service) {
            $_list = self::$handle->sMembers('servicelist');

            foreach ($_list as $node) {
                $info = json_decode($node, true);

                if ($info['service'] == $service) {
                    $this->drop($info['service'], $info['host'], $info['port']);
                    continue;
                }
            }

        } else {
            self::$handle->flushAll();
        }
    }

    /**
     * 获取服务列表
     * @param $service
     * @return mixed
     */
    public function getServiceList($service = '')
    {
        $_list = self::$handle->sMembers('servicelist');
        $serviceList = [];

        foreach ($_list as $node) {
            $info = json_decode($node, true);

            if ($service) {
                if ($info['service'] == $service) {
                    $serviceList[$service][] = $info;
                }
            } else {
                $serviceList[$info['service']][] = $info;
            }

        }

        return $serviceList;
    }

}