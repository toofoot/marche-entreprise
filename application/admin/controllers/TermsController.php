<?php
require_once "AbstractController.php";
/**
 * Class Admin_PagesController
 *
 * @author Aurel
 *
 */
class Admin_TermsController extends Admin_AbstractController 
{
	public function preDispatch(){
		parent::preDispatch();
		$this->view->headScript()
		->appendFile('/javascript/ckeditor/ckeditor.js')
		;

		/*$this->view->headLink()
		->appendStylesheet('/javascript/ckeditor/content.css')
		;*/
	}
    
    /**
     * Page edit
     *
     * @return void
     */
    public function editAction(){
    	$langue = $this->getParam("langue");
    	$this->view->text = file_get_contents(CONFIG_PATH . '/terms.txt');
    	
    	$formData = $this->_request->getPost();
    	if($this->_request->isPost()){
    		$this->_disableLayout();
    		$this->_disableView();
    		
    		file_put_contents(CONFIG_PATH . '/terms.txt', stripslashes($formData['content']));
    		
    		echo json_encode(array("returncode"=>"ok","message"=>"Enregistrement effectué"));
    	}
    }
}
