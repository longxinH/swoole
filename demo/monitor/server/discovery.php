<?php
/*
  +----------------------------------------------------------------------+
  | 服务发现 server-demo                                                  |
  +----------------------------------------------------------------------+
  | Author:  longxinH       <longxinhui.e@gmail.com>                     |
  +----------------------------------------------------------------------+
*/

use \Swoole\Server\Server;
use \Swoole\Packet\Format;
use \Swoole\Service\ServiceList;

include '../../../vendor/autoload.php';

class Discovery extends Server {

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

        //todo 注册服务
        (new ServiceList($this->config['redis']))->register($data['service'], $data['host'], $data['port'], $data['time']);

        return Format::packFormat('', 'register success');
    }

}

$server = new Discovery('../config/monitor.ini');
$server->run();
