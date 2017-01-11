<?php

namespace Swoole\Client;

use Swoole\Packet\Format;
use Swoole\Protocols\Json;

class SOA {

    /**
     * 请求列表
     * @var array
     */
    protected static $requestList = [];

    /**
     * @var array
     */
    protected $serviceList = [];

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var string
     */
    protected $currentService;

    /**
     * @var int
     */
    protected $guid;

    /**
     * 超时
     * @var int
     */
    protected $timeout = 1;

    /**
     * 服务器
     * @var array
     */
    protected $connections = [];

    /**
     * SOA constructor.
     * @param string $config
     */
    public function __construct($config = '')
    {
        if ($config) {
            $this->setConfig($config);
        }
    }

    /**
     * 发送请求
     * @param $api
     * @param array $params
     * @param string $service
     * @param int $mode
     * @return bool|Result
     */
    public function call($api, $params = [], $service = 'base', $mode = Json::PROTOCOLS_MODE)
    {
        $this->guid = $this->generateGuid();

        $send_data = Format::packEncode(
            [
                'api'       => $api,
                'params'    => $params
            ],
            $mode,
            $this->guid
        );

        $client = $this->request($send_data, $service);

        if ($client) {
            $this->setResultStatus($client, 'WAIT_RECV', Result::WAIT_RECV);
            self::$requestList[$this->guid] = $client;
        }

        return $client;
    }

    /**
     * 投递到task处理
     * @param $api
     * @param array $params
     * @param string $service
     * @param int $mode
     * @return bool|Result
     */
    public function task($api, $params = [], $service = 'base', $mode = Json::PROTOCOLS_MODE)
    {
        $this->guid = $this->generateGuid();

        $send_data = Format::packEncode(
            [
                'cmd'       => 'task',
                'api'       => $api,
                'params'    => $params
            ],
            $mode,
            $this->guid
        );

        $client = $this->request($send_data, $service);

        if ($client) {
            $this->setResultStatus($client, 'SUCCESS_TASK', Result::SUCCESS_TASK);
            self::$requestList[$this->guid] = $client;
        }

        return $client;
    }

    /**
     * 获取结果
     * @param float $timeout
     * @return int
     */
    public function result($timeout = 0.5)
    {
        $start = microtime(true);
        $success_num = 0;

        while (count(self::$requestList) > 0) {
            $write = $error = $read = [];

            foreach (self::$requestList as $_obj) {
                if ($_obj->socket !== null) {
                    $hash = spl_object_hash($_obj->socket);

                    if (!isset($read[$hash])) {
                        $read[$hash] = $_obj->socket;
                    }
                }
            }

            if (empty($read)) {
                break;
            }

            $n = swoole_client_select($read, $write, $error, $timeout);

            if ($n > 0) {
                //可读
                foreach ($read as $sock) {

                    $result = $sock->recv();

                    if (empty($result)) {
                        foreach(self::$requestList as $retObj) {
                            if ($retObj->socket == $sock) {
                                $this->setResultStatus($retObj, 'ERR_CLOSED', Result::ERR_CLOSED);
                                $this->resultError($retObj);
                                $this->closeConnection($retObj->server_host, $retObj->server_port);
                            }
                        }

                        continue;

                    } else if ($result === false) {
                        continue;
                    }

                    $header = Format::packDecodeHeader($result);

                    //错误的包头
                    if ($header === false) {
                        trigger_error(__CLASS__ . ' error header [' . substr($header, 0, Format::HEADER_SIZE) . '] . ', E_USER_WARNING);
                        continue;
                    }

                    //不在请求列表中，错误的请求串号
                    if (!isset(self::$requestList[$header['guid']])) {
                        trigger_error(__CLASS__ . " error guid [{$header['guid']}].", E_USER_WARNING);
                        continue;
                    }

                    $retObj = self::$requestList[$header['guid']];

                    if (Format::checkHeaderLength($header['length'], $result) == false) {
                        $this->setResultStatus($retObj, 'ERR_LENGTH', Result::ERR_LENGTH);
                        $this->resultError($retObj);
                        continue;
                    }

                    $data = Format::packDecode($result, $header['type']);

                    //解包失败
                    if ($data === false) {
                        $this->setResultStatus($retObj, 'ERR_UNPACK', Result::ERR_UNPACK);
                        $this->resultError($retObj);
                        continue;
                    }

                    //投递task成功
                    if ($data['code'] == Result::SUCCESS_TASK) {
                        $this->setResultStatus($retObj, 'WAIT_TASK_RECV', Result::WAIT_TASK_RECV);
                        continue;
                    }

                    if ($data['code'] != 0) {
                        $this->setResultStatus($retObj, $data['message'], $data['code']);
                        $this->resultError($retObj);
                        continue;
                    }

                    $this->setResultStatus($retObj, 'OK', 0, $data['data']);

                    $success_num++;
                    unset(self::$requestList[$header['guid']]);
                }
            }

            //发生超时
            if ((microtime(true) - $start) > $timeout) {
                foreach (self::$requestList as $retObj) {
                    if ($retObj->socket->isConnected()) {
                        if ($retObj->is_task === false) {
                            $this->setResultStatus($retObj, 'ERR_TIMEOUT', Result::ERR_TIMEOUT);
                        }
                    } else {
                        $this->setResultStatus($retObj, 'ERR_CONNECT', Result::ERR_CONNECT);
                    }

                    $this->resultError($retObj);
                }

                //清空当前列表
                self::$requestList = [];
                return $success_num;
            }
        }

        return $success_num;
    }

    /**
     * 设置服务列表
     * @param $list
     * @return $this
     */

    public function setServiceList(array $list)
    {
        if (empty($list)) {
            trigger_error('service list is empty', E_USER_ERROR);
        }

        $this->serviceList = $list;

        return $this;
    }

    /**
     * 设置配置
     * @param $config
     */
    public function setConfig($config)
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

        $this->config = $config;
    }

    /**
     * 获取配置
     * @return array|string
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * 设置超时
     * @param $time
     */
    public function setTimeOut($time)
    {
        $this->timeout = $time;
    }

    /**
     * 发起请求
     * @param $send_data
     * @param string $service
     * @return bool|Result
     */
    protected function request($send_data, $service = 'base')
    {
        $result_obj = new Result($this);

        if (!isset($this->serviceList[$service])) {
            trigger_error('service does not exist', E_USER_ERROR);
            return false;
        }

        $this->currentService = $service;

        if ($this->connectToServer($result_obj) === false) {
            $result_obj->code = Result::ERR_CONNECT;
            $result_obj->message = 'ERR_CONNECT';
            return false;
        }

        $result_obj->requestId = $this->guid;
        $result_obj->is_task = isset($send_data['cmd']) && $send_data['cmd'] == 'task' ? true : false;

        if ($result_obj->socket->send($send_data) === false) {
            $result_obj->code = Result::ERR_SEND;
            $result_obj->message = 'ERR_SEND';
            unset($result_obj->socket);
            return false;
        }
        
        return $result_obj;
    }

    /**
     * 连接server
     * @param Result $result_obj
     * @return bool
     */
    protected function connectToServer(Result $result_obj)
    {
        while (count($this->serviceList[$this->currentService]) > 0) {
            $server = $this->getServer();
            $socket = $this->getConnection($server['host'], $server['port']);
            //连接失败，服务器节点不可用
            if ($socket === false) {
                $this->onConnectServerFailed($server);
            } else {
                $result_obj->socket = $socket;
                $result_obj->server_host = $server['host'];
                $result_obj->server_port = $server['port'];
                return true;
            }
        }

        return false;
    }

    protected function getServer()
    {
        $key = array_rand($this->serviceList[$this->currentService]);
        $connect_info = $this->serviceList[$this->currentService][$key];

        return $connect_info;
    }

    protected function getConnection($host, $port)
    {
        $ret = false;
        $conn_key = $host . ':' . $port;
        if (isset($this->connections[$conn_key])) {
            return $this->connections[$conn_key];
        }

        $socket = new \swoole_client(SWOOLE_SOCK_TCP | SWOOLE_KEEP, SWOOLE_SOCK_SYNC);
        if ($this->config) {
            $socket->set(isset($this->config['swoole']) ? $this->config['swoole'] : $this->config);
        }

        /**
         * 尝试重连一次
         */
        for ($i = 0; $i < 2; $i++) {
            $ret = $socket->connect($host, $port, $this->timeout);
            if ($ret === false && ($socket->errCode == 114 || $socket->errCode == 115)) {
                //强制关闭，重连
                $socket->close(true);
                continue;
            } else {
                break;
            }
        }

        if ($ret) {
            $this->connections[$conn_key] = $socket;
            return $socket;
        } else {
            return false;
        }
    }

    /**
     * 连接服务器失败
     * @param $server
     * @return bool
     */
    function onConnectServerFailed($server)
    {
        foreach($this->serviceList[$this->currentService] as $k => $v) {
            if ($v['host'] == $server['host'] && $v['port'] == $server['port']) {
                //从Server列表中移除
                unset($this->serviceList[$this->currentService][$k]);
                return true;
            }
        }

        return false;
    }

    /**
     * 关闭连接
     * @param $host
     * @param $port
     * @return bool
     */
    protected function closeConnection($host, $port)
    {
        $conn_key = $host . ':' . $port;
        if (!isset($this->connections[$conn_key])) {
            return false;
        }

        $socket = $this->connections[$conn_key];
        $socket->close();
        unset($this->connections[$conn_key]);
        return true;
    }

    /**
     * 生成唯一请求id
     * @return int
     */
    protected function generateGuid()
    {
        $us = strstr(microtime(), ' ', true);
        return intval(strval($us * 1000 * 1000) . rand(100, 999));
    }

    /**
     * 错误响应
     * @param Result $result_obj
     */
    protected function resultError(Result $result_obj)
    {
        unset(self::$requestList[$result_obj->requestId]);
    }

    /**
     * @param Result $result_obj
     * @param $message
     * @param $erron
     * @param null $data
     */
    protected function setResultStatus(Result $result_obj, $message, $erron, $data = null)
    {
        $result_obj->code = $erron;
        $result_obj->message = $message;
        $result_obj->data = $data;
    }

}

/**
 * SOA服务请求结果对象
 * Class SOA_Result
 * @package Swoole\Client
 */
class Result
{
    public $id;
    public $code = self::ERR_NO_READY;
    public $message = 'ERR_NO_READY';
    public $data = null;

    /**
     * 请求串号
     */
    public $requestId;

    /**
     * @var \Swoole\Client
     */
    public $socket = null;

    /**
     * 服务器的IP地址
     * @var string
     */
    public $server_host;

    /**
     * 服务器的端口
     * @var int
     */
    public $server_port;

    /**
     * 是否task
     * @var bool
     */
    public $is_task = false;

    /**
     * @var SOA
     */
    protected $soa_client;

    const WAIT_TASK_RECV  = 7001; //等待投递结果
    const WAIT_RECV       = 7002; //等待接收数据

    const ERR_NO_READY   = 8001; //未就绪
    const ERR_CONNECT    = 8002; //连接服务器失败
    const ERR_TIMEOUT    = 8003; //服务器端超时
    const ERR_SEND       = 8004; //发送失败
    const ERR_SERVER     = 8005; //server返回了错误码
    const ERR_UNPACK     = 8006; //解包失败了

    const ERR_HEADER     = 8007; //错误的协议头
    const ERR_LENGTH     = 8008; //错误的长度
    const ERR_CLOSED     = 8009; //连接被关闭
    const ERR_GUID       = 8010; //错误的GUID

    const SUCCESS_TASK   = 9000; //投递task成功
    
    public function __construct($soa_client)
    {
        $this->soa_client = $soa_client;
    }

    public function result($timeout = 1)
    {
        $this->soa_client->result($timeout);
        return $this->data;
    }

}