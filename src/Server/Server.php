<?php

namespace Swoole\Server;

use Swoole\Client\Client;
use Swoole\Packet\Format;

abstract class Server extends ServerCallback implements ServerInterface {
    /**
     * @var Server
     */
    protected static $instance;

    /**
     * @var string
     */
    protected static $serviceName;

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var array
     */
    protected $monitors = [];

    /**
     * @var \swoole_server
     */
    protected $server;

    /**
     * 主进程pid文件
     */
    protected $masterPidFile;

    /**
     * 管理进程pid文件
     */
    protected $managerPidFile;

    /**
     * 进程名称
     */
    protected $processName;

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
    protected $pidPath;

    /**
     * @var int
     */
    protected $sockType = SWOOLE_SOCK_TCP;

    /**
     * @var int
     */
    protected $mode = SWOOLE_PROCESS;
    
    const SUCCESS_TASK  = 9000; //投递task成功
        
    const ERR_HEADER    = 9001; //错误的包头
    const ERR_LENGTH    = 9002; //错误的长度

    const ERR_UNPACK    = 9204; //解包失败
    const ERR_PARAMS    = 9205; //参数错误
    const ERR_CALL      = 9206; //执行错误
    

    public function __construct($config, $process_name = 'swoole')
    {
        $this->configure($config);

        $file_name = $process_name . '-server-' . $this->host . '-' . $this->port;
        $this->masterPidFile = $this->pidPath . '/' . $file_name . '.master.pid';
        $this->managerPidFile = $this->pidPath . '/' . $file_name . '.manager.pid';

        $this->processName = $process_name;
    }

    public function configure($config)
    {
        if (is_string($config)) {
            if (!is_file($config)) {
                trigger_error($config . ' configuration does not exist', E_USER_ERROR);
            }

            $config = parse_ini_file($config, true);

        } else if (is_array($config)) {
            if (empty($config)) {
                trigger_error('configure is empty', E_USER_ERROR);
            }
        } else {
            trigger_error('parameter array or file path', E_USER_ERROR);
        }
        
        if (isset($config['server']['host'])) {
            $this->host = $config['server']['host'];
            unset($config['server']['host']);
        }

        if (isset($config['server']['port'])) {
            $this->port = $config['server']['port'];
            unset($config['server']['port']);
        }

        if (isset($config['server']['mode'])) {
            $this->mode = $config['server']['mode'];
            unset($config['server']['mode']);
        }

        if (isset($config['server']['sock_type'])) {
            $this->sockType = $config['server']['sock_type'];
            unset($config['server']['sock_type']);
        }

        if (isset($config['server']['pid_path'])) {
            $this->pidPath = $config['server']['pid_path'];
            unset($config['server']['pid_path']);
        } else {
            $this->pidPath = realpath('..') . '/run';
        }

        if (!file_exists($this->pidPath)) {
            mkdir($this->pidPath, 0700);
        }

        $config['swoole']['package_body_offset'] = Format::HEADER_SIZE;
        $this->config = $config;
    }
    
    public function setServiceName($name)
    {
        self::$serviceName = $name;
    }

    public function run()
    {
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
     * 初始化服务
     */
    protected function initServer()
    {
        $this->server = new \swoole_server($this->host, $this->port, $this->mode, $this->sockType);
        $this->server->set($this->config['swoole']);
        $this->initCallback($this->server);
    }

    /**
     * 启动服务
     */
    protected function start()
    {
        if (isset($this->config['monitor']) && !empty($this->config['monitor'])) {
            $this->serviceDiscovery($this->config['monitor']);
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
    private function shutdown()
    {
        if (\Swoole\Console\Server::shutdown($this->masterPidFile, $this->processName)) {
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
        return \Swoole\Console\Server::reload($this->managerPidFile);
    }

    /**
     * 服务状态上报
     * @param array $monitor
     */
    public function serviceDiscovery(array $monitor)
    {
        $server = $this;
        $process = new \swoole_process(function (\swoole_process $process) use ($monitor, $server) {
            while (true) {
                $client = new Client($monitor['sock_type']);
                $client->connect($monitor['host'], $monitor['port']);

                $ip = $server->getServerHost();
                swoole_set_process_name('swoole_' . $this->processName . ': monitor (' . $ip . ')');
                $client->send([
                    'service'   => self::$serviceName,
                    'host'      => $ip,
                    'port'      => $server->getPort(),
                    'time'      => time()
                ]);

                $client->close();

                sleep(10);
            }
        });

        $this->server->addProcess($process);
    }

    /**
     * 获取服务器真实ip
     * @return string
     */
    protected function getServerHost()
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
     * @param \swoole_server $server
     * @param $fd
     * @param $from_id
     * @param $data
     * @return mixed
     */
    final public function onReceive(\swoole_server $server, $fd, $from_id, $data)
    {
        $header = Format::packDecodeHeader($data);

        //错误的包头
        if ($header == false) {
            $server->close($fd);
            return true;
        }

        if (Format::checkHeaderLength($header['length'], $data) == false) {
            return $this->sendMessage($fd, Format::packFormat('', '', self::ERR_LENGTH), $header['type'], $header['guid']);
        }

        $data = Format::packDecode($data, $header['type']);

        if ($data == false) {
            return $this->sendMessage($fd, Format::packFormat('', '', self::ERR_UNPACK), $header['type'], $header['guid']);
        }
        
        //投递task
        if (isset($data['cmd']) && $data['cmd'] == 'task') {
            $task_data = [
                'header'    => $header,
                'fd'        => $fd,
                'content'   => $data
            ];
            
            $server->task($task_data);
            $this->sendMessage($fd, Format::packFormat('', '', self::SUCCESS_TASK), $header['type'], $header['guid']);
        } else {
            try {
                $data = $this->doWork($server, $fd, $from_id, $data, $header);
                $this->sendMessage($fd, $data, $header['type'], $header['guid']);
            } catch (\Exception $e) {
                $this->sendMessage($fd, Format::packFormat('', '', self::ERR_CALL), $header['type'], $header['guid']);
            }
        }
        
        return true;
    }

    /**
     * 向task_worker进程投递新的任务
     * @param \swoole_server $server
     * @param $task_id
     * @param $from_id
     * @param $task_data
     * @return array
     */
    final public function onTask(\swoole_server $server, $task_id, $from_id, $task_data)
    {
        $data = $this->doTask($server, $task_id, $from_id, $task_data['content']);

        return [
            'header'    => $task_data['header'],
            'fd'        => $task_data['fd'],
            'content'   => $data
        ];
    }

    /**
     * task_worker完成
     * @param \swoole_server $server
     * @param $task_id
     * @param $data
     */
    final public function onFinish(\swoole_server $server, $task_id, $data)
    {
        $send_data = isset($data['content']['data']) ? $data['content']['data'] : $data['content'];
        $send_code = isset($data['content']['code']) ? $data['content']['code'] : 0;
        $send_message = isset($data['content']['message']) ? $data['content']['message'] : '';
        $fd = $data['fd'];
        $header = $data['header'];
        
        $this->sendMessage($fd, Format::packFormat($send_data, $send_message, $send_code), $header['type'], $header['guid']);
    }
    
    public function getHost()
    {
        return $this->host;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function sendMessage($fd, $send_data, $protocol_mode, $guid = 0)
    {
        $this->server->send($fd, Format::packEncode($send_data, $protocol_mode, $guid));
    }

    public function doTask(\swoole_server $server, $task_id, $from_id, $data) {}

    public function doWork(\swoole_server $server, $fd, $from_id, $data, $header) {}

}