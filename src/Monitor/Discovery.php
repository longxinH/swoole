<?php

namespace Swoole\Monitor;

use Swoole\Cache\Redis;
use Swoole\Packet\Format;
use Swoole\Server\Server;

class Discovery extends Server {

    /**
     * @var Redis
     */
    protected static $handle;

    protected function initHandleInstance()
    {
        if (!self::$handle instanceof Redis) {
            self::$handle = Redis::getInstance($this->config['redis']);
        }
    }

    /**
     * 服务注册
     * @param $data
     */
    protected function register($data)
    {
        $this->initHandleInstance();

        $_service_data = [
            'service'   => $data['service'] ? strtolower($data['service']) : 'base',
            'host'      => $data['host'],
            'port'      => $data['port']
        ];

        self::$handle->sAdd('registerlist', json_encode($_service_data));
        //todo 记录服务器最后上报时间
        self::$handle->set($_service_data['service'] . '_' . $_service_data['host'] . '_' . $_service_data['port'] . '_runtime', $data['time']);

        $register_list = self::$handle->sMembers('registerlist');

        if ($register_list) {
            foreach ($register_list as $node) {
                $info = json_decode($node, true);

                $time = self::$handle->get($info['service'] . '_' . $info['host'] . '_' . $info['port'] . '_runtime');
                if (time() - $time > 20) {
                    $this->drop($node);
                    continue;
                }

                self::$handle->sAdd('serverlist', $node);
            }
        }

    }

    /**
     * 上报超时 服务摘除
     * @param $key
     */
    protected function drop($key)
    {
        self::$handle->sRem('serverlist', $key);
    }

    /**
     * @param \swoole_server $server
     * @param int $fd
     * @param int $from_id
     * @param array $data
     * @param array $header
     * @return mixed|void
     */
    public function doWork(\swoole_server $server, $fd, $from_id, $data, $header)
    {

        if (empty($data['host']) || empty($data['port']) || empty($data['time'])) {
            return $this->sendMessage($fd, Format::packFormat('', '', self::ERR_PARAMS), $header['type']);
        }

        $this->register($data);

        return $this->sendMessage($fd, Format::packFormat('', 'general config success'), $header['type'], $header['guid']);
    }


}