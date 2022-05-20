<?php

/**
 * ErrorController - The default error controller class
 * 
 * @author
 * @version 
 */

class ErrorController extends Aurel_Controller_Abstract
{
    private static $errorMessage;
    private static $httpCode;
    
	public function errorAction()
    {
        $errors = $this->getParam('error_handler');
        
        if($errors){
        	switch ($errors->type) {
	        	case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
	        	case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
	        		self::$httpCode = 404;
	        		$this->getResponse()->setRawHeader('HTTP/1.1 404 non trouvé');
	        		$this->view->title = 'Page Web introuvable ';
	        		self::$errorMessage = "La page que vous tentez d'accéder n'existe pas ou peut-être
	                 a t-elle été; enlevé";
	        
	        		break;
	        	case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_OTHER:
	        		switch ($errors->exception::class)
	        		{
	        			case \Zend_View_Exception::class :
	        				$this->getResponse()->setRawHeader('HTTP/1.1 404 non trouvé');
	        				self::$httpCode = 404;
	        				self::$errorMessage = 'Erreur de traitement d\'une vue';
	        				break;
	        			case \Zend_Db_Exception::class :
	        				$this->getResponse()->setRawHeader('HTTP/1.1 500');
	        				self::$httpCode = 500;
	        				self::$errorMessage = 'Erreur de traitement dans la base de données';
	        				break;
	        			case \Zend_Acl_Exception::class :
	        				$this->getResponse()->setRawHeader('HTTP/1.1 403');
	        				self::$httpCode = 403;
	        				self::$errorMessage = 'Page interdite';
	        				break;
	        			default:
	        				$this->getResponse()->setRawHeader('HTTP/1.1 500');
	        				self::$httpCode = 500;
	        				self::$errorMessage = $errors->exception->getMessage();
	        				break;
	        		}
	        		 
	        	default:
	        		$this->view->title = 'Application Error';
	        		break;
        	}

        	$this->view->message = self::$errorMessage;
        	$this->view->code = self::$httpCode;
        	
        	$flux = @fopen(BASE_PATH . '/logs/access.log', 'a', false);
        	if (! $flux) {
        		throw new Exception('Impossible d\'ouvrir le flux');
        	}
        		
        	$redacteur = new Zend_Log_Writer_Stream($flux);
        	$logger = new Zend_Log($redacteur);
        	$logger->err($errors->exception->getMessage());
        	
        	$this->view->debug = $errors->exception;
        	$this->view->headTitle('Erreur',"PREPEND");
        	
        	$this->view->showDebug = $this->hasParam('debug');
        	
        	$this->getResponse()->clearAllHeaders()->clearBody();
        } else {
        	
        }
    }
}
 
?>