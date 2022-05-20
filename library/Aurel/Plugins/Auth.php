<?php

/**
 * Auth Plugin
 *
 * The auth plugin authenticate users thanks to Zend_Auth
 *
 * @package Plugins
 */
class Aurel_Plugins_Auth extends Zend_Controller_Plugin_Abstract {

    /**
     * Authenticate the user
     *
     */
    public function routeShutdown(Zend_Controller_Request_Abstract $request) {
        // get the Zend_Auth instance
        $auth = Zend_Auth::getInstance();
        $type_connexion = new Zend_Session_Namespace('type_connexion');

        // Login cookie
        $cookie = $this->_request->getcookie('Auth');

        if ($auth->hasIdentity()) {
            $identity = $auth->getIdentity();

            $oUser = new Aurel_Table_User();
            $user = $oUser->getById($identity);
            if ($user) {
                Zend_Registry::set('user', $user);
                if ($type_connexion->type == 'admin')
                    Zend_Registry::set('role', $user->__toString());
                else
                    Zend_Registry::set('role', Aurel_Acl::ROLE_MEMBRE);
            } else {
                $auth->clearIdentity();
            }
        } elseif (!empty($cookie)) {
            $oUser = new Aurel_Table_User();
            $user = $oUser->getById($cookie);

            if ($user) {
                $auth->getStorage()->write($user->id_user);
                Zend_Registry::set('user', $user);

                if ($type_connexion->type == 'admin')
                    Zend_Registry::set('role', $user->__toString());
                else
                    Zend_Registry::set('role', Aurel_Acl::ROLE_MEMBRE);
            } else {
                $auth->clearIdentity();
            }
        } else {
            Zend_Registry::set('user', Aurel_Acl::ROLE_GUEST);
            Zend_Registry::set('role', Aurel_Acl::ROLE_GUEST);
        }
    }

}
