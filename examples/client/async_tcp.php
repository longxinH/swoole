<?php
/*
  +----------------------------------------------------------------------+
  | 异步tcp客户端 async-client                                            |
  +----------------------------------------------------------------------+
  | Author:  longxinH       <longxinhui.e@gmail.com>                     |
  +----------------------------------------------------------------------+
*/

include __DIR__ . '/../../vendor/autoload.php';

$client = new \Swoole\Client\Async\Tcp('0.0.0.0:9503', SWOOLE_SOCK_TCP);
$client->connect(function (\swoole_client $client) {
    //
})->recv(function (\swoole_client $client, $data) {
    echo  $data . PHP_EOL;
    $client->close();
})->error(function ($client) {
    var_dump($client);
})->close(function (){
    echo 'close' . PHP_EOL;
})->send('async-tcp');
