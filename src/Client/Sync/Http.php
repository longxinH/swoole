<?php

namespace Swoole\Client\Sync;

use Swoole\Client\HttpInterface;
use Swoole\Tool;

/**
 * Class Http
 * @package Swoole\Client\Sync
 */
class Http implements HttpInterface {

    protected $client;

    /**
     * Http constructor.
     */
    public function __construct()
    {
        $this->client = curl_init();
    }

    /**
     * @param $method
     * @return $this
     */
    public function setMethod($method)
    {
        $this->setOpt(CURLOPT_CUSTOMREQUEST, $method);

        return $this;
    }

    /**
     * @param $headers
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        $_headers = [];
        foreach ($headers as $key => $value) {
            $_headers[$key] = $key . ': ' . $value;
        }

        $this->setOpt(CURLOPT_HTTPHEADER, array_values($_headers));

        return $this;
    }

    /**
     * @param array $cookies
     * @return $this
     */
    public function setCookies(array $cookies)
    {
        $this->setOpt(CURLOPT_COOKIE, http_build_query($cookies, '', '; '));

        return $this;
    }

    /**
     * @param $url
     * @param array $data
     * @param $callback
     * @param int $timeout
     * @return mixed
     */
    public function post($url, array $data, $callback, $timeout = 5)
    {
        $this->setOpt(CURLOPT_URL, $url);
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'POST');
        $this->setOpt(CURLOPT_POST, true);
        $this->setOpt(CURLOPT_POSTFIELDS, http_build_query($data));
        $this->setOpt(CURLOPT_TIMEOUT, $timeout);
        $this->setOpt(CURLOPT_RETURNTRANSFER, true);

        list($result, $error_code, $error_message, $http_status_code) = $this->execute();

        if (is_callable($callback)) {
            $callback($result, $error_code, $error_message, $http_status_code);
        }
    }

    /**
     * @param $url
     * @param array $data
     * @param $callback
     * @param int $timeout
     */
    public function get($url, array $data, $callback, $timeout = 5)
    {
        $this->setopt(CURLOPT_URL, $this->buildURL($url, $data));
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'GET');
        $this->setOpt(CURLOPT_HTTPGET, true);
        $this->setOpt(CURLOPT_TIMEOUT, $timeout);
        $this->setOpt(CURLOPT_RETURNTRANSFER, true);

        list($result, $error_code, $error_message, $http_status_code) = $this->execute();

        if (is_callable($callback)) {
            $callback($result, $error_code, $error_message, $http_status_code);
        }
    }

    protected function buildURL($url, $data = [])
    {
        return $url . (empty($data) ? '' : '?' . http_build_query($data));
    }

    protected function setOpt($option, $value)
    {
        return curl_setopt($this->client, $option, $value);
    }

    protected function execute()
    {
        $error_message = null;
        $error_code = 0;
        $http_status_code = 200;

        $result = curl_exec($this->client);
        $info = curl_getinfo($this->client);
        if ($info) {
            $http_status_code = $info['http_code'];
        }

        if (curl_errno($this->client)) {
            $error_code = curl_errno($this->client);
            $error_message = curl_error($this->client) . '[' . $error_code . ']';
            return [
                false,
                $error_code,
                $error_message,
                $http_status_code
            ];
        }
        else
        {
            return [
                $result,
                $error_code,
                $error_message,
                $http_status_code
            ];
        }
    }

}