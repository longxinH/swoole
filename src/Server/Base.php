<?php

namespace Swoole\Server;

use Swoole\CallBack\SwooleCallback;
use Swoole\Console\Server;
use Swoole\Tool;

abstract class Base implements ServerInterface {

    /**
     * @var array
     */
    protected $config = [];

    /**
     * 用户自定义进程
     * @var array
     */
    protected $processes = [];

    /**
     * @var string
     */
    protected $host = '127.0.0.1';

    /**
     * @var string
     */
    protected $port = '9501';

    /**
     * @var string
     */
    protected $pidPath = '/tmp';

    /**
     * 进程名称
     */
    protected $processName;

    /**
     * 主进程pid文件
     */
    protected $masterPidFile;

    /**
     * 管理进程pid文件
     */
    protected $managerPidFile;

    /**
     * @var \swoole_server
     */
    protected $server;

    /**
     * Base constructor.
     * @param $address
     * @param string $process_name
     */
    public function __construct($address, $process_name = 'swoole')
    {
        $info = Tool::parse_address($address);

        $this->host = $info['host'];
        $this->port = $info['port'];

        $this->processName = $process_name;
    }

    /**
     * 运行服务
     * @param $config
     */
    public function run($config)
    {
        $this->masterPidFile = $this->pidPath . '/' . $this->processName . '.master.pid';
        $this->managerPidFile = $this->pidPath . '/' . $this->processName . '.manager.pid';
        $this->config = array_merge($this->config, (array) $config);

        $cmd = isset($_SERVER['argv'][1]) ? strtolower($_SERVER['argv'][1]) : 'help';
        switch ($cmd) {
            case 'stop':
                $this->shutdown();
                break;
            case 'start':
                $this->initServer();
                $this->start();
                break;
            case 'reload':
                $this->reload();
                break;
            case 'restart':
                $this->shutdown();
                sleep(2);
                $this->initServer();
                $this->start();
                break;
            default:
                echo 'Usage:php ' . $_SERVER["PHP_SELF"] . ' start | stop | reload | restart | help' . PHP_EOL;
                break;
        }
    }

    /**
     * 获取ip
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * 获取端口
     * @return string
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * 获取进程名称
     * @return string
     */
    public function getProcessName()
    {
        if (empty($this->processName)) {
            return "php {$_SERVER['argv'][0]}";
        } else {
            return $this->processName;
        }
    }

    /**
     *
     * @return \swoole_server
     */
    public function getSwoole()
    {
        return $this->server;
    }

    /**
     * 获取Master文件地址
     * @return string
     */
    public function getMasterPidFile()
    {
        return $this->masterPidFile;
    }

    /**
     * 获取Manager文件地址
     * @return string
     */
    public function getManagerPidFile()
    {
        return $this->managerPidFile;
    }

    /**
     * 添加用户自定义进程
     * @param \swoole_process $process
     */
    public function addProcess(\swoole_process $process)
    {
        $this->processes[] = $process;
    }

    /**
     * 获取服务器真实ip
     * @return string
     */
    public function getServerHost()
    {
        if ($this->host == '0.0.0.0' || $this->host == '127.0.0.1') {
            $serverIps = swoole_get_local_ip();
            $patternArray = [
                '192\.168\.'
            ];

            foreach ($serverIps as $serverIp) {
                // 匹配内网IP
                if (preg_match('#^' . implode('|', $patternArray) . '#', $serverIp)) {
                    return $serverIp;
                }
            }
        }

        return $this->host;
    }

    /**
     * 设置Pid存放路径
     * @param $path
     */
    public function setPidPath($path)
    {
        $this->pidPath = $path;
    }

    /**
     * 初始化服务
     */
    protected function initServer()
    {
        $this->server->set($this->config);
        (new SwooleCallback($this))->initCallback();
    }

    /**
     * 启动服务
     */
    protected function start()
    {
        foreach ($this->processes as $process) {
            $this->server->addProcess($process);
        }

        if (method_exists($this, 'afterStart')) {
            $this->afterStart();
        }

        $this->server->start();
    }

    /**
     * 关闭服务
     * @return bool
     */
    protected function shutdown()
    {
        if (Server::shutdown($this->masterPidFile, $this->processName)) {
            unlink($this->masterPidFile);
            unlink($this->managerPidFile);

            return true;
        }

        return false;
    }

    /**
     * 重启worker进程
     * @return bool
     */
    protected function reload()
    {
        return Server::reload($this->managerPidFile);
    }

}