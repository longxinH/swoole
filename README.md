## RPC
swoole + yaf rpc

----------
##环境依赖
> * Swoole 1.8.x+
> * PHP 5.4+
> * YAF 2.3.x+
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
extension=swoole.so
```

### Install Yaf
```
cd yaf-src
phpize
./configure
make && make install
extension=yaf.so
```
----------

##安装
```
composer require "longxinh/swoole:dev-yaf"
```
----------
##使用
###YAF MVC
> * ```action``` 响应客户端的格式： ```字符串```

```php
class IndexController extends Yaf_Controller_Abstract {

    public function indexAction()
    {
        $this->getResponse()->contentBody = 'module :' . $this->getRequest()->getModuleName() . ' action :' . $this->getRequest()->getActionName() . ' controller :' . $this->getRequest()->getControllerName();
    }
}
```


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
 cd __Your swoole-yaf path__/yaf/monitor/
 php watch.php start
```

###运行Yaf-RPC服务
```shell
 cd __Your swoole-yaf path__/yaf/service/
 php swoole.php start
```

###Yaf-RPC客户端
> * 需要配置服务发现，注册在redis中的可用服务列表

```
 cd __Your swoole-yaf path__/yaf/client/
 php yaf_client.php
```

# License MIT
