<?php
/*
  +----------------------------------------------------------------------+
  | rpc服务 server-demo                                                  |
  +----------------------------------------------------------------------+
  | Author:  longxinH       <longxinhui.e@gmail.com>                     |
  +----------------------------------------------------------------------+
*/

use \Swoole\Server\RPC;
use \Swoole\Packet\Format;

include '../../vendor/autoload.php';

class YafServer extends RPC {

    /**
     * @var Yaf_Application
     */
    private $yaf;

    public function onWorkerStart(\swoole_server $server, $workerId)
    {
        try {
            $this->yaf = (new \Yaf_Application(APPLICATION_PATH . '/config/yaf.ini', 'yaf'));
            $this->yaf->bootstrap();
            $this->yaf->getDispatcher()->disableView()->returnResponse(true);
        } catch (Yaf_Exception $e) {
            echo sprintf("[%s]\t" . 'YAF INIT ERROR: ' . $e->getMessage() . PHP_EOL, date('Y-m-d H:i:s'));
        }
    }

    /**
     * @param swoole_server $server
     * @param $fd
     * @param $from_id
     * @param $data
     * @return array
     */
    public function doWork(\swoole_server $server, $fd, $from_id, $data)
    {
        if (!$this->yaf instanceof \Yaf_Application) {
            return Format::packFormat('', 'YAF ERROR', -1);
        }

        try {
            return $this->yafDispatch($data['api'], $data['params']);
        } catch (Yaf_Exception $e) {
            return Format::packFormat('', $e->getMessage(), $e->getCode());
        }

    }

    /**
     * @param $data
     * @return array
     */
    public function doTask($data)
    {
        if (!$this->yaf instanceof \Yaf_Application) {
            return Format::packFormat('', 'YAF ERROR', -1);
        }

        try {
            return $this->yafDispatch($data['api'], $data['params']);
        } catch (Yaf_Exception $e) {
            return Format::packFormat('', $e->getMessage(), $e->getCode());
        }
    }

    /**
     * 请求分发
     * @param $api
     * @param string $params
     * @return array
     */
    private function yafDispatch($api, $params = '')
    {
        $yaf_request = new \Yaf_Request_Http($api);

        if (!empty($params) && is_array($params)) {
            foreach ($params as $key => $value) {
                $yaf_request->setParam($key, $value);
            }
        }

        $response = $this->yaf->getDispatcher()->dispatch($yaf_request);
        return Format::packFormat($response->contentBody);
    }

}

/*
 * 项目所在目录
 */
define('PROJECT_ROOT', dirname(__DIR__));

/*
 * YAF所在目录
 */
define('APPLICATION_PATH', realpath('../') . '/application');

$server = new YafServer('0.0.0.0:9501', 'rpc_yaf_');

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
    'log_file'              => "/tmp/swoole-yaf-rpc-0.0.0.0:9501.log",
    //todo 守护进程改成1
    'daemonize'             => 0
]);

