<?php
/*
  +----------------------------------------------------------------------+
  | rpc服务 server-demo                                                  |
  +----------------------------------------------------------------------+
  | Author:  longxinH       <longxinhui.e@gmail.com>                     |
  +----------------------------------------------------------------------+
*/

use \Swoole\Server\Http;

include __DIR__ . '/../../../vendor/autoload.php';

class HttpDemo extends Http {

    public function doRequest(\Swoole\Server\Request $request)
    {
        // TODO: Implement doRequest() method.
    }

}

/*
 * 项目所在目录
 */
define('PROJECT_ROOT', dirname(__DIR__));

$server = new HttpDemo('../config/swoole.ini', 'http');
$server->run();

