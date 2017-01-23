<?php

class IndexController extends Yaf_Controller_Abstract {

    public function indexAction()
    {
        $params = $this->getRequest()->getParams();
        unset($params['version']);
        $this->getResponse()->contentBody = 'api :' . $this->getRequest()->getParam('version') . ' params :' . implode(' | ', $params);
    }

}

