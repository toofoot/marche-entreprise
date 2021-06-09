<?php
require_once "AbstractController.php";
/**
 * Class Admin_PagesController
 *
 * @author Aurel
 *
 */
class Admin_MenuController extends Admin_AbstractController 
{
	public function preDispatch(){
		parent::preDispatch();
		$this->view->headScript()
		->appendFile('/javascript/ckeditor/ckeditor.js')
		;
		
		
	}
    
	/**
	 * Page index
	 *
	 * @return void
	 */
    public function indexAction(){
    	
    }
    
    /**
     * Page edit
     *
     * @return void
     */
    public function addEditAction(){
    	$this->_disableLayout();
    	
    	$oMenu = new Aurel_Table_Menu();
    	$menu = $oMenu->getByTitle($this->getParam('basename_principal'));
    	
    	$this->view->menuannonce = $menu->annonces;
    	
    	$id_sous_menu = $this->getParam("id_sous_menu","999999999999");
    	
    	$oSousMenu = new Aurel_Table_SousMenu();
    	$sousmenu = $oSousMenu->getById($id_sous_menu);
    	if(!$sousmenu){
    		$sousmenu = $oSousMenu->createRow();
    		$sousmenu->sous_menu_annuaire = 0;
    		$sousmenu->sous_menu_annonce = 0;
    		if($menu->annonces)
    			$sousmenu->sous_menu_annonce = 1;
    		$sousmenu->id_user_creation = $this->_getUser()->id_user;
			$sousmenu->date_creation = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
    	}
    	$this->view->sousmenu = $sousmenu;
    	
    	$formData = $this->_request->getPost();
    	if($this->_request->isPost()){
    		$this->_disableLayout();
    		$this->_disableView();
    		$sousmenu->name = stripslashes($formData["name"]);
    		$sousmenu->basename = $oMenu->getBasename(stripslashes($formData["name"]));
			$sousmenu->id_menu = $menu->id_menu;
    		$sousmenu->id_user_modification = $this->_getUser()->id_user;
			$sousmenu->date_modification = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
            if(isset($formData["sous_menu_annuaire"]))
    		    $sousmenu->sous_menu_annuaire = $formData["sous_menu_annuaire"];
			if(isset($formData["sous_menu_annonce"]))
    			$sousmenu->sous_menu_annonce = $formData["sous_menu_annonce"];
    		$sousmenu->save();
    		
    		$arrayUrl['basename_principal'] = $menu->basename;
    		$arrayUrl['basename_secondaire'] = $sousmenu->basename;
    		
    		$this->redirect($this->view->url($arrayUrl,'basenames',true));
    	}
    }
    
    /**
     * Page edit
     *
     * @return void
     */
    public function addEditMenuAction(){
    	$this->_disableLayout();
    	 
    	$oMenu = new Aurel_Table_Menu();
    	$oSousMenu = new Aurel_Table_SousMenu();
    	$id_menu = $this->getParam('id_menu');
    	if($id_menu){
    		$menu = $oMenu->getById($id_menu);
    	} else {
    		$menu = $oMenu->createRow();
    		$menu->id_user_creation = $this->_getUser()->id_user;
    		$menu->date_creation = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
    		$menu->agenda = 0;
    		$menu->annonces = 0;
    		$menu->news = 0;
    	}
    	$this->view->menu = $menu;
    	 
    	$formData = $this->_request->getPost();
    	if($this->_request->isPost()){
    		$this->_disableLayout();
    		$this->_disableView();
    		$menu->name = stripslashes($formData["name"]);
    		$menu->basename = $oMenu->getBasename(stripslashes($formData["name"]));
    		$menu->id_user_modification = $this->_getUser()->id_user;
    		$menu->date_modification = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
    		$menu->agenda = $formData["agenda"];
    		$menu->annonces = $formData["annonces"];
    		$menu->news = $formData["news"];
    		$menu->save();
    		
    		$sousmenus = $oSousMenu->getAllByMenu($menu->id_menu);
    		if($sousmenus){
    			foreach($sousmenus as $ssmenu){
    				$ssmenu->sous_menu_annonce = $formData["annonces"];
    				$ssmenu->save();
    			}
    		}
    
    		$arrayUrl['basename_principal'] = $menu->basename;
    		$arrayUrl['basename_secondaire'] = null;
    
    		$this->redirect($this->view->url($arrayUrl,'basenames',true));
    	}
    }
    
    public function editTitleAction(){
    	$this->_disableLayout();
    	$this->_disableView();
    	
    	$oMenu = new Aurel_Table_Menu();
    	$menu = $oMenu->getByTitle($this->getParam('basename_principal'));
    	
    	if($this->hasParam('basename_secondaire')){
	    	$oSousMenu = new Aurel_Table_SousMenu();
	    	$menu = $oSousMenu->getByTitle($this->getParam('basename_secondaire'),$menu->id_menu);
    	}
    	$return = array();
    	$formData = $this->_request->getPost();
    	if($this->_request->isPost()){
    		$this->_disableLayout();
    		$this->_disableView();
    	
    		$menu->title = stripslashes($formData['title']);
    		$menu->save();
    		
    		$return['returncode'] = 'ok';
    		echo json_encode($return);
    	}
    }
    
    public function deleteMenuPrincipalAction(){
    	$this->_disableLayout();
    	$this->_disableView();
    	
    	$oMenu = new Aurel_Table_Menu();
    	$oSousMenu = new Aurel_Table_SousMenu();
    	
    	$id_menu = $this->getParam("id_menu","999999999999");
    	$sousmenu = $oMenu->getById($id_menu);
    	
    	$menu = $oMenu->getById($sousmenu->id_menu);
    	
    	if($menu){
    		$menu->delete();
    	}
    	$this->redirect($this->view->url(array(),'accueil',true));
    }
    
    public function deleteMenuAction(){
    	$this->_disableLayout();
    	$this->_disableView();
    	
    	$oMenu = new Aurel_Table_Menu();
    	
    	$id_sous_menu = $this->getParam("id_sous_menu","999999999999");
    	$oSousMenu = new Aurel_Table_SousMenu();
    	$sousmenu = $oSousMenu->getById($id_sous_menu);
    	
    	$menu = $oMenu->getById($sousmenu->id_menu);
    	
    	if($sousmenu){
    		$sousmenu->delete();
    	}
    	$this->redirect($this->view->url(array('basename_principal'=>$menu->basename),'basenames',true));
    }
    
    public function sortAction(){
    	$this->_disableLayout();
    	$this->_disableView();
    	$oSousMenu = new Aurel_Table_SousMenu();
    	$order = $this->getParam('order');
    	 
    	$tabOrdre = explode(',',$order);
    	 
    	if (is_array($tabOrdre))
    	{
    		foreach($tabOrdre as $key=>$value){
    			$id = str_replace('sousmenu-','',$value);
    			$ligne = $oSousMenu->getById($id);
    			if($ligne){
	    			$ligne->order = $key;
	    			$ligne->save();
    			}
    		}
    	}
    }
    
    public function sortMenuAction(){
    	$this->_disableLayout();
    	$this->_disableView();
    	$oMenu = new Aurel_Table_Menu();
    	$order = $this->getParam('order');
    	 
    	$tabOrdre = explode(',',$order);
    	 
    	if (is_array($tabOrdre))
    	{
    		foreach($tabOrdre as $key=>$value){
    			$id = str_replace('menu_','',$value);
    			$ligne = $oMenu->getById($id);
    			if($ligne){
	    			$ligne->order = $key;
	    			$ligne->save();
    			}
    		}
    	}
    }
    
    public function changeCoverAction(){
    	$this->_disableLayout();
    	$this->_disableView();
    
    	$oMenu = new Aurel_Table_Menu();
    	$menu = $oMenu->getByTitle($this->getParam('basename_principal'));
    	$menuToFill = $menu;
    	
    	if($this->hasParam('basename_secondaire')){
    		$oSousMenu = new Aurel_Table_SousMenu();
    		$sousmenu = $oSousMenu->getByTitle($this->getParam('basename_secondaire'),$menu->id_menu);
    		$menuToFill = $sousmenu;
    	}
    	 
    	$dataReturn = array();
    	if (!empty($_FILES)) {
    		$path = UPLOAD_PATH . DIRECTORY_SEPARATOR;
    
    		$old = $path . $menuToFill->picture;
    		if(is_file($old)){
    			unlink($old);
    		}
    
    		$extension = strtolower(pathinfo($_FILES['cov']['name'], PATHINFO_EXTENSION));
    		$fileName = uniqid(). '.' . $extension;
    		$targetFile =  $path . $fileName;
    		$ret = move_uploaded_file($_FILES['cov']['tmp_name'], $targetFile);
    		Aurel_Upload::resizeImg($targetFile,1000,2500,'',true);
    
    		$menuToFill->picture = $fileName;
    		$menuToFill->picture_position = "0 0";
    		$menuToFill->save();
    
    		$dataReturn['returncode'] = 'ok';
    		echo "<script type='text/javascript'>
        		window.parent.coverUploaded('/images/upload/".$fileName."');
    		</script>";
    	} else {
    		$dataReturn['returncode'] = 'ko';
    	}
    	 
    }
    
    public function setCovPositionAction(){
    	$this->_disableLayout();
    	$this->_disableView();
    	 
    	$oMenu = new Aurel_Table_Menu();
    	$menu = $oMenu->getByTitle($this->getParam('basename_principal'));
    	
    	$menuToFill = $menu;
    	 
    	if($this->hasParam('basename_secondaire')){
    		$oSousMenu = new Aurel_Table_SousMenu();
    		$sousmenu = $oSousMenu->getByTitle($this->getParam('basename_secondaire'),$menu->id_menu);
    		$menuToFill = $sousmenu;
    	}
    	 
    	$backgroundPosition = $this->getParam('backgroundPosition');
    	$menuToFill->picture_position = $backgroundPosition;
    	$menuToFill->save();
    }
    
    public function deleteCovAction(){
    	$this->_disableLayout();
    	$this->_disableView();
    	 
    	$oMenu = new Aurel_Table_Menu();
    	$menu = $oMenu->getByTitle($this->getParam('basename_principal'));
    	
    	$menuToFill = $menu;
    	
    	if($this->hasParam('basename_secondaire')){
    		$oSousMenu = new Aurel_Table_SousMenu();
    		$sousmenu = $oSousMenu->getByTitle($this->getParam('basename_secondaire'),$menu->id_menu);
    		$menuToFill = $sousmenu;
    	}
    	
    	$menuToFill->picture = null;
    	$menuToFill->picture_position = null;
    	$menuToFill->save();
    	 
    	$return['returncode'] = 'ok';
    	$return['background'] = "";
    	$return['background_position'] = "0 0";
    	if($menu->picture){
    		$return['background'] = "url(/images/upload/".$menu->picture.")";
    		$return['background_position'] = $menu->picture_position;
    	}
    	echo json_encode($return);
    }
    
    public function setAccessRapideAction(){

    	$file = "access_rapide.xml";
    	$config = new Zend_Config_Xml(CONFIG_PATH . "/$file",
    				null,
    				array('skipExtends'        => true,
    						'allowModifications' => true));
    	
    	$this->view->configMenu = $config;
    	
    	$formData = $this->_request->getPost();
    	if($this->_request->isPost()){
    		$this->_disableLayout();
    		$this->_disableView();
    		 
    		$config = new Zend_Config(array(
    				'menu'=>array(),
    				'sous_menu'=>array(),
    		),true);
    		
    		// Modify a value
    		foreach($formData["active_menu"] as $key => $menu_id){
    			$lbl = "menu_" . $key;
    			$config->menu->$lbl = $menu_id;
    		}
    		foreach($formData["active_menu_secondaire"] as $key => $menu_id){
    			$lbl = "sous_menu_" . $key;
    			$config->sous_menu->$lbl = $menu_id;
    		}
    	
    		// Write the config file
    		$writer = new Zend_Config_Writer_Xml(array('config'   => $config,
    				'filename' => CONFIG_PATH . "/$file"));
    		$writer->write();
    	
    		$this->redirect($this->view->url());
    	}
    }
}