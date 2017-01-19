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
> * ```doWork(string $data)``` 方法, 服务在接收信息 ```onReceive``` 回调中会调用 ```doWork``` 方法，返回给客户端的格式： ```字符串```

```php
class TcpServer extends \Swoole\Server\Tcp {

    /**
     * @param array $data
     * @return string
     */
    public function doWork($data)
    {
        return 'tcp :' . $data;
    }
}

$server = new TcpServer('0.0.0.0:9503', 'tcp');
$server->run(array Swoole 配置);
```

###HTTP Server
> * 服务继承 ```\Swoole\Server\HTTP```
> * ```doRequest(\Swoole\Server\Request $request)``` 方法, 服务在接收信息 ```onRequest``` 回调中会调用 ```doRequest``` 方法，返回给客户端的格式： ```字符串```

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
> * ```doRequest(array $data)``` 方法, 服务在接收信息 ```onRequest``` 回调中会调用 ```doRequest``` 方法，返回给客户端的格式： ```经过打包协议的字符串```
> * ```doTask(array $data)``` 方法, 服务在接收信息 ```onTask``` 回调中会调用 ```onTask``` 方法，返回给客户端的格式： ```经过打包协议的字符串```，并返回数据给 ```onFinish```
> * 服务注册目前提供 ```redis``` 和 ```zookeeper```两种形式，需调用 ```addProcess()``` 新建一个进程注册服务

```php
class RpcServer extends \Swoole\Server\RPC {

    /**
     * @param array $data
     * @return array
     */
    public function doWork($data)
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
            $server
        )
    )
);
$server->run(array Swoole 配置);
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
