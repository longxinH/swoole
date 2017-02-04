<?php
/*
  +----------------------------------------------------------------------+
  | 注册中心监控 watch-server                                              |
  +----------------------------------------------------------------------+
  | Author:  longxinH       <longxinhui.e@gmail.com>                     |
  +----------------------------------------------------------------------+
*/

use \Swoole\Server\Http;

include __DIR__ . '/../../vendor/autoload.php';

class Watch extends Http {

    /**
     * @param \Swoole\Server\Request $request
     * @return string
     */
    public function doRequest(\Swoole\Server\Request $request)
    {
        $list = \Swoole\Service\Registry::discovery(
            new \Swoole\Service\Container\Redis('127.0.0.1', '6379')
            //new \Swoole\Service\Container\Zookeeper('127.0.0.1', '2181')
        );

        if (empty($list)) {
            $html = '无可用服务';
        } else {
            $html = '可用服务' . '<br />';
            foreach ($list as $name => $server) {
                $html .= $name . '<br />';
                foreach ($server as $node) {
                    $html .= $node['host'] .  ':' . $node['port'] . '|';
                }
                $html .= '<br />';
            }
        }

        return $html;
    }

}

/*
 * 项目所在目录
 */
define('PROJECT_ROOT', dirname(__DIR__));

$server = new Watch('0.0.0.0:9569', 'watch');

/*
 * 设置Pid存放路径
 */
$server->setPidPath(__DIR__ . '/run');

/*
 * 注册中心监控
 */
$server->addProcess(
    \Swoole\Console\Process::createProcess(
        \Swoole\Service\Registry::watch(
            new \Swoole\Service\Container\Redis('127.0.0.1', '6379'),
            //new \Swoole\Service\Container\Zookeeper('127.0.0.1', '2181'),
            $server
        )
    )
);

$server->run([
    'worker_num' => 0,
    'max_request' => 5000,
    'log_file' => '/tmp/swoole-watch-0.0.0.0:9569.log'
]);
