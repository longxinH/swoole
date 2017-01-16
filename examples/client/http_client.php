<?php

include '../../vendor/autoload.php';

/**
 * post
 */
$client = new \Swoole\Client\Http();
$res = $client->post('http://127.0.0.1:9501', [
    'params'   => 'http_client test'
]);
var_dump($res);
