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

    private static $yaf_instance;

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
        if (!self::$yaf_instance instanceof \Yaf_Application) {
            try {
                self::$yaf_instance = (new \Yaf_Application(APPLICATION_PATH . '/config/yaf.ini', 'yaf'));
                self::$yaf_instance->bootstrap();
                self::$yaf_instance->getDispatcher()->disableView()->returnResponse(true);
            } catch (Yaf_Exception $e) {
                return Format::packFormat('', $e->getMessage(), $e->getCode());
            }
        }

        try {
            $yaf_request = new \Yaf_Request_Http($data['api']);

            if (!empty($data['params'])) {
                foreach ($data['params'] as $key => $value) {
                    $yaf_request->setParam($key, $value);
                }
            }

            $response = self::$yaf_instance->getDispatcher()->dispatch($yaf_request);
            return Format::packFormat($response->getBody('content'));
        } catch (Yaf_Exception $e) {
            return Format::packFormat('', $e->getMessage(), $e->getCode());
        }

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
        if (!self::$yaf_instance instanceof \Yaf_Application) {
            try {
                self::$yaf_instance = (new \Yaf_Application(APPLICATION_PATH . '/config/yaf.ini', 'yaf'));
                self::$yaf_instance->bootstrap();
                self::$yaf_instance->getDispatcher()->disableView()->returnResponse(true);
            } catch (Yaf_Exception $e) {
                return Format::packFormat('', $e->getMessage(), $e->getCode());
            }
        }

        try {
            $yaf_request = new \Yaf_Request_Http($data['api']);

            if (!empty($data['params'])) {
                foreach ($data['params'] as $key => $value) {
                    $yaf_request->setParam($key, $value);
                }
            }

            $response = self::$yaf_instance->getDispatcher()->dispatch($yaf_request);
            return Format::packFormat($response->getBody('content'));
        } catch (Yaf_Exception $e) {
            return Format::packFormat('', $e->getMessage(), $e->getCode());
        }
    }

}

/*
 * 项目所在目录
 */
define('PROJECT_ROOT', dirname(__DIR__));

/*
 * YAF所在目录
 */
define('APPLICATION_PATH', realpath('../../') . '/application');

$server = new DemoServer('../config/swoole.ini', 'rpc_yaf_');
$server->run();

