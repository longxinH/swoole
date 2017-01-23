<?php

include __DIR__ . '/../../vendor/autoload.php';

$client = new \Swoole\Client\RPC;

//启动服务发现
$client->startDiscovery(
    new \Swoole\Service\Container\Redis('127.0.0.1', '6379')
);

/**
 * RPC客户端
 */
for ($x=0; $x<1; $x++) {
    $call1 = $client->call('/api/v1/', ['test1']);
    $task_call = $client->task('/api/v1/task', ['task-test1']);
    $call2 = $client->call('/api/v1.1/', ['test2']);
    $call3 = $client->call('/api/v1.2/', ['test3']);
    $client->result();
    echo $x . '--------------------' . "\r\n";
    //var_dump($call1->data, $call1->code, $call1->message, $call2->data, $call3->data);
    var_dump($call1->data, $call2->data, $call3->data, $task_call->data);
    echo $x . '--------------------' . "\r\n";
//    $task_call = $client->task('11', ['task-test1']);
//    $client->resultTaskData();
//    var_dump($task_call->message, $task_call->code);
}