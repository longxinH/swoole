<?php

include '../../vendor/autoload.php';

$client = new \Swoole\Client\SOA('config/client.ini');
$config = $client->getConfig();

$client->setServiceList(
    (new \Swoole\Service\ServiceList($config['redis']))->getServiceList()
);

/**
 * SOA客户端
 */

for ($x=0; $x<1; $x++) {
    $call1 = $client->call('/api/v1/', ['test1']);
    $task_call = $client->task('/api/v1.1/', ['task-test1']);
    $call2 = $client->call('/index/test', ['test2']);
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


die;

/**
 * 客户端
 */
$client = new \Swoole\Client\Client();
$client->connect('0.0.0.0', '9501');
$result = $client->send([
    'params'   => 'client test'
]);
var_dump($result);