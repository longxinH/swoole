## RPC
swoole rpc

----------
##环境依赖
> * Swoole 1.8.x+
> * PHP 5.4+
> * Composer
> * Redis

## Install

### Install composer
```shell
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
```
composer require "longxinh/rpc:dev-master"
```
----------

##使用
###rpc_path/monitor/server/discovery.php 服务发现服务端
> * 服务发现服务端，通过扫描Redis获取到各服务器上报的地址和端口，并生成配置到指定路径

```php
class Discovery extends Server {

    /**
     * @param \swoole_server $server
     * @param int $fd
     * @param int $from_id
     * @param array $data
     * @param array $header
     * @return array
     */
    public function doWork(\swoole_server $server, $fd, $from_id, $data, $header)
    {

        if (empty($data['host']) || empty($data['port']) || empty($data['time'])) {
            return Format::packFormat('', '', self::ERR_PARAMS);
        }

        //todo 注册服务
        (new ServiceList($this->config['redis']))->register($data['service'], $data['host'], $data['port'], $data['time']);

        return Format::packFormat('', 'register success');
    }

}

/*
 * 项目所在目录
 */
define('PROJECT_ROOT', dirname(__DIR__));

$server = new Discovery('../config/monitor.ini', 'discovery');
$server->run();
```

###rpc_path/monitor/config/monitor.ini 服务发现服务端配置参数
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
pid_path = PROJECT_ROOT'/run'

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

###\Swoole\Service\ServiceList 服务注册
####依赖redis
> * ```register``` 方法, 服务注册 收集不同服务上报的host、port保存在redis中，并且注册可用服务和移除不可用服务
> * ```drop``` 方法, 移除不可用服务
> * ```getServiceList``` 方法, 获取可用的服务列表

###rpc_path/service/server/swoole.php  RPC服务端
> * ```afterStart``` 方法 在服务启动之前的hook，可用于自行创建 ```swoole_process``` 等 
> * ```$server->setServiceName(string $name)``` 用于多个服务同时运行时，作为服务的区分，同时也可以使客户端，更容易调用不同的服务  
> * ```doWork``` 方法, 服务器在接收信息 ```onReceive``` 回调中会调用 ```doWork``` 方法
> * ```doTask``` 方法, 服务器在接收信息 ```onTask``` 回调中会调用 ```doTask``` 方法，并返回数据给 ```onFinish```

```php
class DemoServer extends Server
{

    public function doWork(\swoole_server $server, $fd, $from_id, $data, $header)
    {
        //return error
        //return Format::packFormat('', 'error', '-1');
        
        return Format::packFormat($data['params']);
    }

    public function doTask(\swoole_server $server, $task_id, $from_id, $data)
    {
        //return error
        //return Format::packFormat('', 'error', '-1');
        
        return Format::packFormat($data['params']);
    }
}

/*
 * 项目所在目录
 */
define('PROJECT_ROOT', dirname(__DIR__));

$server = new DemoServer('servicepath/config/swoole.ini', 'rpc');
$server->setServiceName('userService');
$server->run();

```

###rpc_path/service/config/swoole.ini 服务端配置参数
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
pid_path = PROJECT_ROOT'/run'

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

###rpc_path/client/client.php RPC客户端
####SOA client 服务化客户端
> * ```$client->setServiceList(serverlist)``` 需要配置可用的服务列表
> * ```$client->call(string $api, array $params, string $service, int $mode)``` 下发任务给服务端， ```$service``` 用于多服务情况下，指定调用某服务
> * ```$client->task(string $api, array $params, string $service, int $mode)``` 下发任务给服务端，服务端使用 ```onTask``` 方式执行，用于处理一些逻辑时间长的任务，客户端可不关心执行结果
> * ```$client->result(int|float $timeout)``` 获取请求结果 

```php
$client = new \Swoole\Client\SOA('config/client.ini');
$config = $client->getConfig();

$client->setServiceList(
    (new \Swoole\Service\ServiceList($config['redis']))->getServiceList()
);
//设置调用的服务列表
$client->setService('userservice');

$call1 = $client->call('/api/v1/', ['test1']);
$task_call = $client->task('/api/v1/task', ['task-test1']);
$call2 = $client->call('/api/v1.1/', ['test2']);
$call3 = $client->call('/api/v1.2/', ['test3']);
$client->result();
var_dump($call1->message, $call2->message, $call3->message, $task_call->message);

$task_call2 = $client->task('/api/v1/task', ['task-test2']);
var_dump($task_call2->result());

```

####client 非服务化客户端
```php
$client = new \Swoole\Client\Client();
$client->connect(host, port);
$result = $client->send([
    'params'   => 'client test'
]);
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
> * 服务注册/发现，通过扫描redis获取到所有可用服务列表，并保存

```
 cd rpc_path/demo/monitor/server/
 php discovery.php start
```

###运行服务
```
 cd rpc_path/demo/service/server/
 php swoole.php start
```

###客户端
> * 需要配置服务发现，注册在redis中的可用服务列表

```
 cd rpc_path/demo/client/
 php client.php
```

# License MIT
