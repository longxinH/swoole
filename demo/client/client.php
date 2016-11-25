<?php

include '../../vendor/autoload.php';

$client = new \Swoole\Client\SOA('config/client.ini');
$config = $client->getConfig();

$client->setServiceList(
    (new \Swoole\Service\ServiceList($config['redis']))->getServiceList()
);

//$client->setService('userservice');

//$client->setConfig([
//    'open_length_check' => true,
//    'package_max_length' => 2000000,
//    'package_length_type' => 'N',
//    'package_body_offset' => 12,
//    'package_length_offset' => 0,
//]);


$call1 = $client->call('11', ['test1']);
$call2 = $client->call('22', ['test2']);
$call3 = $client->call('33', ['test3']);
$client->resultData();
var_dump($call1->data, $call2->data, $call3->data);

$task_call = $client->task('11', ['test1']);
var_dump($task_call->getTaskResult());