<?php
/**
 * Class Admin_AbstractController
 * 
 * @author Aurel
 *
 */
class Admin_AbstractController extends Aurel_Controller_Abstract 
{
	protected $_role;
	
	protected $_articles = [];
	protected $_menu = null;
	protected $_sousmenu = null;
	
	/**
	 * Pre-dispatch routines
	 *
	 * @return void
	 */
    public function preDispatch() 
    {
    	parent::preDispatch();
    	
    	$this->_role = Zend_Registry::get('role');
    	$auth = Zend_Auth::getInstance();
    	
    	if((!$auth->hasIdentity() || !$this->_getAcl()->isAllowed($this->_role,Aurel_Acl::RESSOURCE_ADMIN)) && $this->view->actionName != 'login'){
    		$this->redirect($this->view->url(["action"=>"login", "controller"=>"index"],'admin',true));
    	}
    }
}