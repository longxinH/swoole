<?php
/*
  +----------------------------------------------------------------------+
  | 同步http客户端 sync-client                                            |
  +----------------------------------------------------------------------+
  | Author:  longxinH       <longxinhui.e@gmail.com>                     |
  +----------------------------------------------------------------------+
*/

include __DIR__ . '/../../vendor/autoload.php';

$client = new \Swoole\Client\Sync\Http();
$client->post('http://127.0.0.1:9502', ['a' => 'post', 'b' => 'demo'], function ($result, $error_code, $error_message, $http_status_code) {
    echo $result . ' ' . $error_code . ' ' . $error_message . ' ' . $http_status_code . PHP_EOL;
});

$client->get('http://127.0.0.1:9502', ['a' => 'get', 'b' => 'demo'], function ($result, $error_code, $error_message, $http_status_code) {
    echo $result . ' ' . $error_code . ' ' . $error_message . ' ' . $http_status_code . PHP_EOL;
});
