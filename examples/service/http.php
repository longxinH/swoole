<?php
/*
  +----------------------------------------------------------------------+
  | rpc服务 server-demo                                                  |
  +----------------------------------------------------------------------+
  | Author:  longxinH       <longxinhui.e@gmail.com>                     |
  +----------------------------------------------------------------------+
*/

use \Swoole\Server\Http;

include __DIR__ . '/../../vendor/autoload.php';

class HttpServer extends Http {

    public function doRequest(\Swoole\Server\Request $request)
    {
        return $request->isPost() ? 'post : ' . json_encode($request->getPost()) : 'get : ' . json_encode($request->getGet());
    }

}

/*
 * 项目所在目录
 */
define('PROJECT_ROOT', dirname(__DIR__));

$server = new HttpServer('0.0.0.0:9502', 'http');

/*
 * 设置Pid存放路径
 */
$server->setPidPath(__DIR__ . '/run');

$server->run([
    'worker_num' => 0,
    'max_request' => 5000,
    'log_file' => '/tmp/swoole-http-0.0.0.0:9502.log'
]);

