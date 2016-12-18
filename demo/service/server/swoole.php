<?php
/*
  +----------------------------------------------------------------------+
  | rpc服务 server-demo                                                  |
  +----------------------------------------------------------------------+
  | Author:  longxinH       <longxinhui.e@gmail.com>                     |
  +----------------------------------------------------------------------+
*/

use \Swoole\Server\Server;
use \Swoole\Packet\Format;

include '../../../vendor/autoload.php';

class DemoServer extends Server {

    /**
     * @param swoole_server $server
     * @param int $fd
     * @param int $from_id
     * @param array $data
     * @param array $header
     * @return array
     */
    public function doWork(\swoole_server $server, $fd, $from_id, $data, $header)
    {
        return Format::packFormat($data['params']);
    }

    /**
     * @param swoole_server $server
     * @param $task_id
     * @param $from_id
     * @param $data
     * @return mixed
     */
    public function doTask(\swoole_server $server, $task_id, $from_id, $data)
    {
        return $data['params'];
    }

}

/*
 * 项目所在目录
 */
define('PROJECT_ROOT', dirname(__DIR__));

$server = new DemoServer('../config/swoole.ini', 'rpc');
$server->run();

