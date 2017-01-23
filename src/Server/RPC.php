<?php

namespace Swoole\Server;

use Swoole\Packet\Format;

abstract class RPC extends Base implements ServerInterface {

    const HttpServer = false;

    const SUCCESS_TASK  = 9000; //投递task成功

    const ERR_HEADER    = 9001; //错误的包头
    const ERR_LENGTH    = 9002; //错误的长度

    const ERR_UNPACK    = 9204; //解包失败
    const ERR_PARAMS    = 9205; //参数错误
    const ERR_CALL      = 9206; //执行错误

    /**
     * RPC constructor.
     * @param $config
     * @param string $process_name
     */
    public function __construct($config, $process_name = 'swoole')
    {
        parent::__construct($config, $process_name);
    }

    /**
     * 初始化服务
     */
    protected function initServer()
    {
        $this->server = new \swoole_server($this->host, $this->port, SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
        parent::initServer();
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
            return $this->sendMessage($fd, Format::packFormat('', 'ERR_LENGTH', self::ERR_LENGTH), $header['type'], $header['guid']);
        }

        $data = Format::packDecode($data, $header['type']);

        if ($data == false) {
            return $this->sendMessage($fd, Format::packFormat('', 'ERR_UNPACK', self::ERR_UNPACK), $header['type'], $header['guid']);
        }

        //投递task
        if (isset($data['cmd']) && $data['cmd'] == 'task') {
            $task_data = [
                'header'    => $header,
                'fd'        => $fd,
                'content'   => $data
            ];

            $server->task($task_data);
            $this->sendMessage($fd, Format::packFormat('', 'SUCCESS_TASK', self::SUCCESS_TASK), $header['type'], $header['guid']);
        } else {
            try {
                $data = $this->doWork($data);
                $this->sendMessage($fd, $data, $header['type'], $header['guid']);
            } catch (\Exception $e) {
                $this->sendMessage($fd, Format::packFormat('', 'ERR_CALL', self::ERR_CALL), $header['type'], $header['guid']);
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
        $data = null;
        if (method_exists($this, 'doTask')) {
            $data = $this->doTask($task_data['content']);
        }

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

    final public function sendMessage($fd, $send_data, $protocol_mode, $guid = 0)
    {
        $this->server->send($fd, Format::packEncode($send_data, $protocol_mode, $guid));
    }

    abstract public function doWork($data);

}