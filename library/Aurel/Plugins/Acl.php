<?php

class Aurel_Plugins_Acl extends Zend_Controller_Plugin_Abstract {

    public function preDispatch(Zend_Controller_Request_Abstract $request) {
        $front = Zend_Controller_Front::getInstance();
        $front->throwExceptions(false);

        $module = $this->getRequest()->getModuleName();
        $controller = $this->getRequest()->getControllerName();
        $action = $this->getRequest()->getActionName();

        $acl = new Aurel_Acl();

        Zend_Registry::set(\Zend_Acl::class, $acl);

        $role = 'guest';
        if(Zend_Registry::isRegistered('role'))
            $role = Zend_Registry::get('role');
        else{
            Zend_Registry::set('user', Aurel_Acl::ROLE_GUEST);
            Zend_Registry::set('role', Aurel_Acl::ROLE_GUEST);
        }

        if (!$acl->isAllowed($role, $module . "_" . $controller, $action)) {

            if ($module == 'admin' && ( $role == Aurel_Acl::ROLE_GUEST || $role == Aurel_Acl::ROLE_MEMBRE )) {
                $url_encode = urlencode($_SERVER["REQUEST_URI"]);
                $url_redirect = "/admin/index/login?url_redirect=$url_encode&pageForbidden=1";

                $this->getResponse()->setRedirect($url_redirect);
                $this->getResponse()->sendHeaders();
                die();
            } elseif ($controller == 'compte' && $role == 'guest') {
                $url_encode = urlencode($_SERVER["REQUEST_URI"]);
                $url_redirect = "/compte/login?url_redirect=$url_encode&pageForbidden=1";

                $this->getResponse()->setRedirect($url_redirect);
                $this->getResponse()->sendHeaders();
                die();
            } else
                throw new Zend_Acl_Exception("Ressource {$module}_{$controller}/{$action} non autorisé for {$role}");
            exit;
        } elseif (($role == Aurel_Acl::ROLE_GUEST || $role == Aurel_Acl::ROLE_MEMBRE) && $this->_request->getParam('adminConnect')) {
            $url_encode = urlencode($_SERVER["REQUEST_URI"]);
            $url_redirect = "/admin/index/login?url_redirect=$url_encode&pageForbidden=1";

            $this->getResponse()->setRedirect($url_redirect);
            $this->getResponse()->sendHeaders();
            die();
        }

        $front->throwExceptions(true);
    }

}
