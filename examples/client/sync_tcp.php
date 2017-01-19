<?php
/*
  +----------------------------------------------------------------------+
  | 同步tcp客户端 sync-client                                             |
  +----------------------------------------------------------------------+
  | Author:  longxinH       <longxinhui.e@gmail.com>                     |
  +----------------------------------------------------------------------+
*/

include __DIR__ . '/../../vendor/autoload.php';

$client = new \Swoole\Client\Sync\Tcp('0.0.0.0:9503');
$client->connect(function (\swoole_client $client) {
    echo 'tcp connect' . PHP_EOL;
})->recv(function (\swoole_client $client, $data) {
    echo  $data . PHP_EOL;
    $client->close();
})->send('sync-tcp');
