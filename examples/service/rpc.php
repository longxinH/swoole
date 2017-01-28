<?php
/*
  +----------------------------------------------------------------------+
  | rpc服务 rpc-server                                                    |
  +----------------------------------------------------------------------+
  | Author:  longxinH       <longxinhui.e@gmail.com>                     |
  +----------------------------------------------------------------------+
*/

use \Swoole\Server\RPC;
use \Swoole\Packet\Format;

include __DIR__ . '/../../vendor/autoload.php';

class RpcServer extends RPC {

    /**
     * @param swoole_server $server
     * @param $fd
     * @param $from_id
     * @param $data
     * @return array
     */
    public function doWork(\swoole_server $server, $fd, $from_id, $data)
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

$server = new RpcServer('0.0.0.0:9501', 'rpc');

/*
 * 设置Pid存放路径
 */
$server->setPidPath(__DIR__ . '/run');

/*
 * 服务注册
 */
$server->addProcess(
    \Swoole\Console\Process::createProcess(
        \Swoole\Service\Registry::register(
            new \Swoole\Service\Container\Redis('127.0.0.1', '6379'),
            $server
        )
    )
);

$server->run([
    'worker_num'            => 4,
    'task_worker_num'       => 4,
    'max_request'           => 5000,
    'dispatch_mode'         => 3,
    'open_length_check'     => 1,
    'package_max_length'    => 2000000,
    'package_length_type'   => 'N',
    'package_body_offset'   => Format::HEADER_SIZE,
    'package_length_offset' => 0,
    'log_file'              => "/tmp/swoole-rpc-0.0.0.0:9501.log",
    //todo 守护进程改成1
    'daemonize'             => 0
]);


