<?php

namespace Swoole\Server;

abstract class Http extends Base implements ServerInterface {

    /**
     * @var array
     */
    protected $headers = [];

    const HttpServer = true;

    public function __construct($config, $process_name = 'swoole')
    {
        parent::__construct($config, $process_name);
    }

    /**
     * 初始化服务
     */
    protected function initServer()
    {
        $this->server = new \swoole_http_server($this->host, $this->port);
        unset($this->config['monitor']);
        parent::initServer();
    }

    /**
     * 设置Http头信息
     * @param $key
     * @param $value
     */
    final public function setHeader($key, $value)
    {
        $this->headers[$key] = $value;
    }

    /**
     * @param \swoole_http_request $request
     * @param \swoole_http_response $response
     */
    final public function onRequest(\swoole_http_request $request, \swoole_http_response $response)
    {
        $request = new Request($request);
        $response = new Response($response);

        try {
            $response->setHttpStatus(200);
            $body = $this->doRequest($request);
        } catch (\Exception $e) {
            $response->setHttpStatus(500);
            $body = $e->getMessage();
        }

        $response->addHeaders($this->headers);
        $response->send($body);

        return;
    }

    /**
     * @param Request $request
     * @return string
     */
    abstract public function doRequest(Request $request);

}

/**
 * Class Request
 * @package Swoole\Server
 */
class Request {

    /**
     * @var \swoole_http_request
     */
    protected $request;

    /**
     * @param $request
     */
    public function __construct($request)
    {
        $this->request = $request;
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->request, $name], $arguments);
    }

    public function getHeader($key = null, $def = null)
    {
        if (!$key) {
            return $this->request->header;
        }

        return isset($this->request->header[$key]) ? $this->request->header[$key] : $def;
    }

    public function getUri()
    {
        return $this->request->server['request_uri'];
    }

    public function getPath()
    {
        return $this->request->server['path_info'];
    }

    public function getIp()
    {
        return $this->request->server['remote_addr'];
    }

    public function getMethod()
    {
        return $this->request->server['request_method'];
    }

    public function isPost()
    {
        return $this->request->server['request_method'] == 'POST';
    }

    public function getPost()
    {
        return isset($this->request->post) ? $this->request->post : null;
    }

    public function getGet()
    {
        return isset($this->request->get) ? $this->request->get : null;
    }

    public function post($key)
    {
        return isset($this->request->post[$key]) ? $this->request->post[$key] : null;
    }

    public function get($key)
    {
        return isset($this->request->get[$key]) ? $this->request->get[$key] : null;
    }

}

/**
 * Class Response
 * @package Swoole\Server
 */
class Response  {

    /**
     * @var \swoole_http_response
     */
    protected $response;

    public function __construct($response)
    {
        $this->response = $response;
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->response, $name], $arguments);
    }

    /**
     * 设置Http状态
     * @param $code
     */
    public function setHttpStatus($code)
    {
        $this->response->status($code);
    }

    /**
     * 设置Http头信息
     * @param $key
     * @param $value
     */
    public function setHeader($key, $value)
    {
        $this->response->header($key, $value);
    }

    public function gzip($level = 1)
    {
        $this->response->gzip($level);
    }

    /**
     * 添加http header
     * @param $header
     */
    public function addHeaders(array $header)
    {
        foreach($header as $key => $value) {
            $this->response->header($key, $value);
        }
    }

    public function send($body)
    {
        $this->response->end($body);
    }

}