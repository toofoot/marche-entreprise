<?php

require_once 'Zend/View/Helper/Abstract.php';
/**
 */
class Aurel_View_Helper_IsAllowed extends Zend_View_Helper_Abstract
{
    /**
     * @var Fis_Acl
     */
    private $_acl;
    private $_role;


    public function __construct()
    {
        $acl = Zend_Registry::get('Zend_Acl');

        if (!isset($acl)) {
            require_once 'Zend/View/Exception.php';
            throw new Zend_View_Exception('acl view helper requires a registered acl object in the container');
        }

        $this->_acl   = $acl;
        $this->_role = Zend_Registry::get('role');
    }

    /**
     * 
     * @return Zend_Acl
     */
    public function isAllowed($resource = null,$action = null) {
        return $this->_acl->isAllowed($this->_role,$resource,$action);
    }
}