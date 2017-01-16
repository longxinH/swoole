<?php
/*
  +----------------------------------------------------------------------+
  | rpc服务 rpc-server                                                    |
  +----------------------------------------------------------------------+
  | Author:  longxinH       <longxinhui.e@gmail.com>                     |
  +----------------------------------------------------------------------+
*/

use \Swoole\Server\Tcp;
use \Swoole\Packet\Format;

include __DIR__ . '/../../../vendor/autoload.php';

class RpcDemo extends Tcp {

    /**
     * @param array $data
     * @return array
     */
    public function doWork($data)
    {
        //return error
        //return Format::packFormat('', 'error', '-1');
        
        return Format::packFormat($data['params']);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function doTask($data)
    {
        //return error
        //return Format::packFormat('', 'error', '-1');

        return Format::packFormat($data['params']);
    }

}

/*
 * 项目所在目录
 */
define('PROJECT_ROOT', dirname(__DIR__));

$server = new RpcDemo('../config/swoole.ini', 'rpc');

/*
 * 服务注册
 */
$server->addProcess(
    \Swoole\Console\Process::createProcess(
        \Swoole\Service\Registry::register($server)
    )
);

$server->run();

