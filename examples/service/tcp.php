<?php
/*
  +----------------------------------------------------------------------+
  | tcp服务 tcp-server                                                    |
  +----------------------------------------------------------------------+
  | Author:  longxinH       <longxinhui.e@gmail.com>                     |
  +----------------------------------------------------------------------+
*/

use \Swoole\Server\Tcp;

include __DIR__ . '/../../vendor/autoload.php';

class TcpServer extends Tcp {

    /**
     * @param array $data
     * @return string
     */
    public function doWork($data)
    {
        return 'tcp :' . $data;
    }

}

/*
 * 项目所在目录
 */
define('PROJECT_ROOT', dirname(__DIR__));

$server = new TcpServer('0.0.0.0:9503', 'tcp');

/*
 * 设置Pid存放路径
 */
$server->setPidPath(__DIR__ . '/run');

$server->run([
    'worker_num'            => 4,
    'max_request'           => 5000,
    'dispatch_mode'         => 3,
    'log_file'              => "/tmp/swoole-tcp-0.0.0.0:9503.log",
    //todo 守护进程改成1
    'daemonize'             => 0
]);


