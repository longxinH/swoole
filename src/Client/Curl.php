<?php

namespace Swoole\Client;

use Swoole\Packet\Format;

/**
 * Class Curl
 * @package Swoole\Client
 */
class Curl {

    private $curl;
    private $headers = [];
    private $cookies = [];

    public $info;

    public $error_code = 0;
    public $error_message = null;
    public $http_status_code = 0;

    public function __construct()
    {
        if (!extension_loaded('curl')) {
            throw new \ErrorException('cURL library is not loaded');
        }

        $this->curl = curl_init();
    }

    public function post($url, $data = [], $timeout = 10)
    {
        $this->setOpt(CURLOPT_URL, $url);
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'POST');
        $this->setOpt(CURLOPT_POST, true);
        $this->setOpt(CURLOPT_POSTFIELDS, $this->postfields($data));
        $this->setOpt(CURLOPT_TIMEOUT, $timeout);
        $this->setOpt(CURLOPT_RETURNTRANSFER, true);
        return $this->execute();
    }

    public function get($url, $data = [], $timeout = 10)
    {
        $this->setopt(CURLOPT_URL, $this->buildURL($url, $data));
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'GET');
        $this->setOpt(CURLOPT_HTTPGET, true);
        $this->setOpt(CURLOPT_TIMEOUT, $timeout);
        $this->setOpt(CURLOPT_RETURNTRANSFER, true);
        return $this->execute();
    }

    public function setUserAgent($user_agent)
    {
        $this->setOpt(CURLOPT_USERAGENT, $user_agent);
    }

    public function setHeader($key, $value)
    {
        $this->headers[$key] = $key . ': ' . $value;
        $this->setOpt(CURLOPT_HTTPHEADER, array_values($this->headers));
    }

    public function setCookie($key, $value)
    {
        $this->cookies[$key] = $value;
        $this->setOpt(CURLOPT_COOKIE, http_build_query($this->cookies, '', '; '));
    }

    public function setOpt($option, $value)
    {
        return curl_setopt($this->curl, $option, $value);
    }

    private function postfields($data)
    {
        if (is_array($data)) {
            $binary_data = false;
            $data['params'] = Format::packEncode($data);

            if (!$binary_data) {
                $data = http_build_query($data);
            }
        }

        return $data;
    }

    private function buildURL($url, $data = [])
    {
        return $url . (empty($data) ? '' : '?' . http_build_query($data));
    }

    private function execute()
    {
        $result = curl_exec($this->curl);
        $this->info = curl_getinfo($this->curl);
        if ($this->info)
        {
            $this->http_status_code = $this->info['http_code'];
        }
        if (curl_errno($this->curl))
        {
            $this->error_code = curl_errno($this->curl);
            $this->error_message = curl_error($this->curl) . '[' . $this->error_code . ']';
            return false;
        }
        else
        {
            return json_decode($result, true);
        }
    }

}
