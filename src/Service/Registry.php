<?php

namespace Swoole\Service;

use Swoole\Console\Process;
use Swoole\Service\Container\ContainerInterface;
use Swoole\Server\ServerInterface;

/**
 * 服务注册中心
 * Class Registry
 * @package Swoole\Service
 */
class Registry {

    /**
     * 服务注册
     * @param \Swoole\Service\Container\Redis|\Swoole\Service\Container\Zookeeper|ContainerInterface $container
     * @param \Swoole\Server\Tcp|\Swoole\Server\Http|ServerInterface $server
     * @param string $service_name
     * @return \Closure
     */
    public static function register(ContainerInterface $container, ServerInterface $server, $service_name = 'base')
    {
        return function (\swoole_process $process) use ($container, $server, $service_name) {
            Process::setProcessName('swoole_' . $server->getProcessName() . ': register (' . $server->getHost() . ':' . $server->getPort() . ')');
            while (true) {
                $container->register($service_name, $server->getServerHost(), $server->getPort());
            }
        };
    }

    /**
     * 注册中心监控
     * @param \Swoole\Service\Container\Redis|\Swoole\Service\Container\Zookeeper|ContainerInterface $container
     * @param ServerInterface $server
     * @return \Closure
     */
    public static function watch(ContainerInterface $container, ServerInterface $server)
    {
        return function (\swoole_process $process) use ($container, $server) {
            Process::setProcessName('swoole_' . $server->getProcessName() . ': watch');
            while (true) {
                $container->watch();
            }
        };
    }

    /**
     * 获取可用服务列表
     * @param \Swoole\Service\Container\Redis|\Swoole\Service\Container\Zookeeper|ContainerInterface $container
     * @return mixed
     */
    public static function discovery(ContainerInterface $container)
    {
        return $container->discovery();
    }

}