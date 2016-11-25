## RPC
swoole rpc

----------
##环境依赖
> * Swoole 1.8.x+
> * PHP 5.4+
> * Composer

## Install

### Install composer
```
 curl -sS https://getcomposer.org/installer | php
```

### Install swoole
```
cd swoole-src
phpize
./configure
make && make install
```
----------

##安装
```php
composer require "longxinh/rpc:dev-master"
```
----------

##使用
###RPC/monitor/server/discovery.php 服务发现服务端
> * 服务发现服务端，通过扫描Redis获取到各服务器上报的地址和端口，并生成配置到指定路径

```php
$server = new \Swoole\Monitor\Discovery('monitorpath/config/monitor.ini');
$server->run();
```

###RPC/monitor/config/monitor.ini 服务发现服务端配置参数
1. ```redis```     redis ip，端口
2. ```server```    swoole 服务的ip，端口，运行模式
3. ```swoole```    swoole 配置选项

```ini
[redis]
host = "127.0.0.1"
;端口
port = 6379

[server]
;ip地址
host = "0.0.0.0"
;端口
port = 9569
;运行模式
mode = SWOOLE_PROCESS
;socket类型
sock_type = SWOOLE_SOCK_TCP
;pid存放路径
pid_path = '../run'

[swoole]
dispatch_mode = 3
;worker进程数
worker_num = 2
reactor_num = 1
open_length_check = 1
package_length_type = "N"
package_length_offset = 0
package_body_offset = 12
package_max_length = 2000000
log_file = "/tmp/swoole_monitor.log"
;守护进程改成1
daemonize = 0

```

###RPC/service/server/swoole.php RPC服务端
> * ```$server->setServiceName(string $name)``` 用于多个服务同时运行时，作为服务的区分，同时也可以使客户端，更容易调用不同的服务  
> * ```doWork``` 方法, 服务器在接收信息 ```onReceive``` 回调中会调用 ```doWork``` 方法
> * ```doTask``` 方法, 服务器在接收信息 ```onTask``` 回调中会调用 ```doTask``` 方法，并返回数据给 ```onFinish```

```php
class DemoServer extends Server
{

    public function doWork(\swoole_server $server, $fd, $from_id, $data, $header)
    {
        $this->sendMessage($fd, \Swoole\Packet\Format::packFormat($data['params']), $header['type'], $header['guid']);
    }

    public function doTask(\swoole_server $server, $task_id, $from_id, $data)
    {
        return $data['params'];
    }
}

$server = new DemoServer('servicepath/config/swoole.ini');
$server->setServiceName('userService');
$server->run();

```

###RPC/service/config/swoole.ini 服务端配置参数
1. ```server```   swoole 服务的ip，端口，运行模式
2. ```monitor```  服务上报服务端的ip，端口，运行模式
3. ```swoole```   swoole配置选项

```ini
[server]
;地址
host = "0.0.0.0"
;端口
port = 9501
;运行模式
mode = SWOOLE_PROCESS
;socket类型
sock_type = SWOOLE_SOCK_TCP
;pid存放路径
pid_path = '../run'

[monitor]
;服务上报地址
host = "127.0.0.1"
;端口
port = 9569
;socket类型
sock_type = SWOOLE_SOCK_TCP

[swoole]
dispatch_mode = 3
;worker进程数
worker_num = 4
max_request = 0
open_length_check = 1
package_length_type = "N"
package_length_offset = 0
package_max_length = 2000000
task_worker_num = 20
log_file = "/tmp/swoole-server-0.0.0.0_9501.log"
;守护进程改成1
daemonize = 0

```

###RPC/client/client.php RPC客户端
####SOA client 服务化客户端
> * ```$client->setService(string $name)``` 设置需要调用的服务名称，会根据服务发现生成配置文件，连接到对应的服务端
> * ```$client->setServiceList(serverlist)``` 需要配置可用的服务列表
> * ```$client->call(string $api, array $params, int $mode)``` 下发任务给服务端
> * ```$client->task(string $api, array $params, int $mode)``` 下发任务给服务端，服务端使用 ```onTask``` 方式执行，用于处理一些逻辑时间长的任务，客户端可不关心执行结果

```php
$client = new \Swoole\Client\SOA('config/client.ini');
$config = $client->getConfig();

$client->setServiceList(
    (new \Swoole\Service\ServiceList($config['redis']))->getServiceList()
);
//设置调用的服务列表
$client->setService('userservice');

$call1 = $client->call('11', ['test1']);
$call2 = $client->call('22', ['test2']);
$call3 = $client->call('33', ['test3']);
$client->resultData();
var_dump($call1->data, $call2->data, $call3->data);

$task_call = $client->task('11', ['test1']);
var_dump($task_call->getTaskResult());

```

####client 非服务化客户端
```php
$client = new \Swoole\Client\Client();
$client->connect(host, port);
$result = $client->send(\Swoole\Packet\Format::packEncode([
    'params'   => 'client test'
]));
var_dump($result);

```

----------

#快速开始
```
 composer install
```
##运行服务指令
```
 start | stop | reload | restart | help
```

###运行服务监控
> * 服务注册/发现，通过扫描redis获取到所有可用服务列表，并生成配置到指定路径

```
 cd RPC/monitor/server/
 php discovery.php start
```

###运行服务
```
 cd RPC/service/server
 php swoole.php start
```

###客户端
> * 需要配置服务发现生成的ip地址文件

```
 cd RPC/service/client
 php client.php
```

# License MIT
