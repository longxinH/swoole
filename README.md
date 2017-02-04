## SWOOLE
swoole library

----------
##环境依赖
> * Swoole 1.8.x+
> * PHP 5.4+
> * Composer

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
composer require "longxinh/swoole:dev-master"
```
----------

##使用
###TCP Server
> * 服务继承 ```\Swoole\Server\Tcp```
> * ```doWork(swoole_server $server, int $fd, int $from_id, string $data)``` 方法, 服务在接收 ```onReceive``` 事件回调时会调用 ```doWork``` 方法执行自定义逻辑，返回给客户端的格式： ```字符串```

```php
class TcpServer extends \Swoole\Server\Tcp {

    /**
     * @param array $data
     * @return string
     */
    public function doWork(\swoole_server $server, $fd, $from_id, $data)
    {
        return 'tcp :' . $data;
    }
}

$server = new TcpServer('0.0.0.0:9503', 'tcp');
$server->run(array Swoole 配置);
```

###HTTP Server
> * 服务继承 ```\Swoole\Server\HTTP```
> * ```doRequest(\Swoole\Server\Request $request)``` 方法, 服务在接收 ```onRequest``` 事件回调时会调用 ```doRequest``` 方法执行自定义逻辑，返回给客户端的格式： ```字符串```

```php
class HttpServer extends \Swoole\Server\HTTP {

    public function doRequest(\Swoole\Server\Request $request)
    {
        return $request->isPost() ? 'post : ' . json_encode($request->getPost()) : 'get : ' . json_encode($request->getGet());
    }

}

$server = new HttpServer('0.0.0.0:9502', 'http');
$server->run(array Swoole 配置);
```

###RPC Server
> * 服务继承 ```\Swoole\Server\RPC```
> * ```doWork(swoole_server $server, int $fd, int $from_id, array $data)``` 方法, 服务在接收 ```onReceive``` 事件回调时会调用 ```doWork``` 方法执行自定义逻辑，返回给客户端的格式： ```经过打包协议的字符串```
> * ```doTask(array $data)``` 方法, 服务在接收 ```onTask``` 事件回调时会调用 ```onTask``` 方法，返回给客户端的格式： ```经过打包协议的字符串```，并返回数据给 ```onFinish```
> * 服务注册目前提供 ```redis``` 和 ```zookeeper```两种形式，需调用 ```addProcess``` 新建一个进程注册服务

```php
class RpcServer extends \Swoole\Server\RPC {

    /**
     * @param array $data
     * @return array
     */
    public function doWork(\swoole_server $server, $fd, $from_id, $data)
    {
        return Format::packFormat($data['params']);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function doTask($data)
    {
        return Format::packFormat($data['params']);
    }

}

$server = new RpcServer('0.0.0.0:9501', 'rpc');
/*
 * 服务注册
 */
$server->addProcess(
    \Swoole\Console\Process::createProcess(
        \Swoole\Service\Registry::register(
            new \Swoole\Service\Container\Redis('127.0.0.1', '6379'),
            //使用zookeeper作为注册中心
            //new \Swoole\Service\Container\Zookeeper('127.0.0.1', '2181'),
            $server,
            //可自定义服务名称，默认名称为base
            'base'
        )
    )
);
$server->run(array Swoole 配置);
```

###服务监控中心
> * ```\Swoole\Service\Registry```
> * ```register``` 方法, 实现向服务存储容器上报注册信息，提供当前服务 ```host``` 和 ```ip```
> * ```watch``` 方法, 实现服务监控，检测超时服务，并剔除不可用服务
> * ```discovery``` 方法, 实现服务发现，在服务存储容器里获取到目前可用服务

###服务存储容器
> * ```\Swoole\Service\Container\Redis```
> * ```\Swoole\Service\Container\Zookeeper```

```php
/*
 * 服务注册
 */
$server->addProcess(
    \Swoole\Console\Process::createProcess(
        \Swoole\Service\Registry::register(
            new \Swoole\Service\Container\Redis('127.0.0.1', '6379'),
            //使用zookeeper作为注册中心
            //new \Swoole\Service\Container\Zookeeper('127.0.0.1', '2181'),
            $server,
            //可自定义服务名称，默认名称为base
            'base'
        )
    )
);
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

###运行服务注册中心
> * 对注册服务添加到可用服务列表中，并剔除超时服务
> * 由于watch继承于 ```\Swoole\Server\HTTP```，可通过浏览器查看可用服务，url输入 ```http://127.0.0.1:9569/```

```shell
 cd __Your swoole path__/examples/monitor/
 php watch.php start
```

###运行RPC服务
```shell
 cd __Your swoole path__/examples/service/
 php rpc.php start
```

###RPC客户端

```shell
 cd __Your swoole path__/examples/client/
 php rpc.php
```

# License MIT
