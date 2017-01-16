<?php

namespace Swoole\Service;

use Swoole\Console\Process;
use Swoole\Server\ServerInterface;

class Registry {

    /**
     * 服务注册
     * @param \Swoole\Server\Tcp|\Swoole\Server\Http|ServerInterface $server
     * @param string $service_name
     * @param string $container
     * @return \Closure
     */
    public static function register(ServerInterface $server, $service_name = 'base', $container = 'redis')
    {
        $container_obj = self::getHandle($server->getConfig($container), $container);

        return function (\swoole_process $process) use ($container_obj, $server, $service_name) {
            Process::setProcessName('swoole_' . $server->getProcessName() . ': register (' . $server->getHost() . ':' . $server->getPort() . ')');
            while (true) {
                $container_obj->register($service_name, $server->getServerHost(), $server->getPort());
            }
        };
    }

    /**
     * 注册中心监控
     * @param \Swoole\Server\Tcp|\Swoole\Server\Http|ServerInterface $server
     * @param string $container
     * @return \Closure
     */
    public static function watch(ServerInterface $server, $container = 'redis')
    {
        $container_obj = self::getHandle($server->getConfig($container), $container);

        return function (\swoole_process $process) use ($container_obj, $server) {
            Process::setProcessName('swoole_' . $server->getProcessName() . ': watch');
            while (true) {
                $container_obj->watch();
            }
        };
    }

    /**
     * 获取可用服务列表
     * @param array $config
     * @param string $container
     * @return array
     */
    public static function discovery(array $config, $container = 'redis')
    {
        $container_obj = self::getHandle($config, $container);

        return $container_obj->discovery();
    }

    /**
     * @param array $config
     * @param string $container
     * @return Redis|Zookeeper
     */
    protected static function getHandle(array $config, $container = 'redis')
    {
        $container = strtolower($container ?: 'redis');

        /**
         * @var Redis|Zookeeper
         */
        $class = __NAMESPACE__ . '\\' . ucfirst($container);

        if (!class_exists($class)) {
            trigger_error('registry ' . strtolower($container) . ' does not exist', E_USER_ERROR);
        }

        if (!isset($config['host']) || !isset($config['port'])) {
            trigger_error($container . 'missing connection parameters', E_USER_ERROR);
        }

        return new $class($config);
    }

}

/**
 * Class Redis
 * @package Swoole\Service
 */
class Redis {

    protected $redis;

    /**
     * 10s
     * @var int
     */
    protected $interval = 10;

    public function __construct(array $config)
    {
        $this->redis = new \Swoole\Cache\Redis($config['host'], $config['port'], isset($config['auth']) ? $config['auth'] : false);
    }

    /**
     * 保存redis信息
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

class Zookeeper {

    public function __construct(array $config)
    {
    }
}