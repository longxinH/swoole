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
$call1 = $client->call('11', ['test1']);
$call2 = $client->call('22', ['test2']);
$call3 = $client->call('33', ['test3']);
$client->resultData();
var_dump($call1->data, $call1->code, $call1->message, $call2->data, $call3->data);

$task_call = $client->task('11', ['test1']);
var_dump($task_call->getTaskResult());

die;

/**
 * 客户端
 */
$client = new \Swoole\Client\Client();
$client->connect('0.0.0.0', '9502');
$result = $client->send([
    'params'   => 'client test'
]);
var_dump($result);