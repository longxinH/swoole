<?php

class Bootstrap extends Yaf_Bootstrap_Abstract {

    public function _initRoute()
    {
        //todo 路由规则
        Yaf_Dispatcher::getInstance()->getRouter()->addConfig(
            (new Yaf_Config_Ini(APPLICATION_PATH . '/config/routes.ini', 'yaf'))->get('routes')
        );
    }

    public function _initPlugins()
    {
        Yaf_Dispatcher::getInstance()->registerPlugin(new SystemPlugin());
    }

}
