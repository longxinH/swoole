<?php
/*
  +----------------------------------------------------------------------+
  | 注册中心监控 watch-server                                              |
  +----------------------------------------------------------------------+
  | Author:  longxinH       <longxinhui.e@gmail.com>                     |
  +----------------------------------------------------------------------+
*/

use \Swoole\Server\Http;

include __DIR__ . '/../../../vendor/autoload.php';

class Watch extends Http {

    public function doRequest(\Swoole\Server\Request $request)
    {
        $container = $this->config['server']['container'];
        $list = \Swoole\Service\Registry::discovery($this->config[$container], $container);

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

$server = new Watch('../config/monitor.ini', 'watch');

/*
 * 注册中心监控
 */
$server->addProcess(
    \Swoole\Console\Process::createProcess(
        \Swoole\Service\Registry::watch($server, $server->getConfig('server')['container'])
    )
);

$server->run();
