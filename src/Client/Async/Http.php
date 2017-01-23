<?php

namespace Swoole\Client\Async;

use Swoole\Client\HttpInterface;
use Swoole\Tool;

/**
 * Class Http
 * @package Swoole\Client\Async
 */
class Http implements HttpInterface {

    protected $client;

    protected $host;

    protected $port;

    protected $ssl;

    /**
     * Http constructor.
     * @param $address
     */
    public function __construct($address)
    {
//        $info = Tool::parse_address($address);
//
//        $this->host = $info['host'];
//        $this->port = $info['port'];
//        $this->ssl = $info['scheme'] == 'https' ? true : false;
//
//        $this->client = new \swoole_http_client($this->host, $this->port, $this->ssl);
    }

    /**
     * @param $method
     * @return $this
     */
    public function setMethod($method)
    {
        $this->client->setMethod($method);

        return $this;
    }

    /**
     * @param $headers
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        $this->client->setHeaders($headers);

        return $this;
    }

    /**
     * @param array $cookies
     * @return $this
     */
    public function setCookies(array $cookies)
    {
        $this->client->setCookies($cookies);

        return $this;
    }

    /**
     * @param $url
     * @param array $data
     * @param $callback
     */
    public function post($url, array $data, $callback)
    {
//        $url = $this->ssl ? 'https://' : 'http://' . $this->host . ':' . $this->port;
        \swoole_async_dns_lookup($url, function ($host, $ip) use ($path, $data, $callback) {
            $this->client->post($path, $data, function ($client) use ($callback) {
                if (is_callable($callback)) {
                    $callback($client);
                }
            });
        });
    }

    /**
     * @param $path
     * @param array $data
     * @param $callback
     */
    public function get($path, array $data, $callback)
    {
        $url = $this->ssl ? 'https://' : 'http://' . $this->host . ':' . $this->port;
        \swoole_async_dns_lookup($url, function ($host, $ip) use ($path, $data, $callback) {
            $this->client->get($path . '?' . http_build_query($data), function ($client) use ($callback) {
                if (is_callable($callback)) {
                    $callback($client);
                }
            });
        });
    }

}