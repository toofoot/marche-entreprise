<?php

require_once 'Zend/View/Helper/Abstract.php';
/**
 */
class Aurel_View_Helper_Acl extends Zend_View_Helper_Abstract
{
    /**
     * @var Fis_Acl
     */
    private $_acl;


    public function __construct()
    {
        $acl = Zend_Registry::get('acl');

        if (!isset($acl)) {
            require_once 'Zend/View/Exception.php';
            throw new Zend_View_Exception('acl view helper requires a registered acl object in the container');
        }

        $this->_acl   = $acl;
    }

    /**
     * 
     * @return Zend_Acl
     */
    public function acl() {
        return $this->_acl;
    }
}