<?php

class IndexController extends Yaf_Controller_Abstract {

    public function indexAction()
    {
        $this->getResponse()->contentBody = 'module :' . $this->getRequest()->getModuleName() . ' action :' . $this->getRequest()->getActionName() . ' controller :' . $this->getRequest()->getControllerName();
    }

    public function testAction()
    {
        $this->getResponse()->contentBody = 'module :' . $this->getRequest()->getModuleName() . ' action :' . $this->getRequest()->getActionName() . ' controller :' . $this->getRequest()->getControllerName();
    }

}

