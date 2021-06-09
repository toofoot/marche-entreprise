<?php
require_once "AbstractController.php";
/**
 * Class Admin_AnnuaireController
 *
 * @author Aurel
 *
 */
class Admin_AnnuaireController extends Admin_AbstractController 
{
	
	public function preDispatch(){
		parent::preDispatch();
		
		$this->view->headScript()
		->appendFile('/javascript/pick-a-color-master/build/dependencies/tinycolor-0.9.15.min.js')
		->appendFile('/javascript/pick-a-color-master/build/1.2.3/js/pick-a-color-1.2.3.min.js')
		;
		
		$this->view->headLink()
		->appendStylesheet('/javascript/pick-a-color-master/build/1.2.3/css/pick-a-color-1.2.3.min.css')
		;
	}

	private function _check_dir($dir){
		if(!is_dir($dir)){
			mkdir($dir);
		}
	}
	
	public function contactAction(){
		$oAnnuaireFiche = new Aurel_Table_AnnuaireFiche();
	
		$id_annuaire_fiche = $this->getParam('id_annuaire_fiche');
		$annuaire_fiche = $oAnnuaireFiche->getById($id_annuaire_fiche);
		if($annuaire_fiche){
			$this->view->annuaire_fiche = $annuaire_fiche;
				
			$formData = $this->_request->getPost();
			if($formData){
				$this->_disableLayout();
				$this->_disableView();
	
				$return = array();
				$return["sent"] = false;
				$continue = true;
	
				$validate = new Zend_Validate_EmailAddress();
				if(empty($formData["email"])){
					$continue = false;
					$return['errors'][] = 'email';
					$return['message']['email'] = "Veuillez saisir votre adresse e-mail.";
				} elseif(!$validate->isValid($formData["email"])){
					$continue = false;
					$return['errors'][] = 'email';
					$return['message']['email'] = "Veuillez saisir votre adresse e-mail complète, y compris le signe @.";
				}
	
				if(empty($formData["text"])){
					$continue = false;
					$return['errors'][] = 'text';
					$return['message']['text'] = "Veuillez saisir votre texte.";
				}
	
				if($continue){
					if($annuaire_fiche){
						$subject = "LE PETIT CHARSIEN, nouvel email de " . stripslashes($formData["email"]);
						$html = stripslashes($formData["text"]);
	
						$mail = new Aurel_Mailer('utf-8');
	
						$mail->setBodyHtmlWithDesign($html,$subject);
						$mail->setSubject($subject);
						$mail->setFrom($formData["email"]);
						$mail->addTo($annuaire_fiche->mail);
	
						try{
							$mail->send();
							$return["sent"] = true;
						} catch(Exception $e){
								
						}
					}
				}
	
				echo json_encode($return);
			}
		}
	}
	
	public function deleteCategorieAction(){
		$oAnnuaireCategorie = new Aurel_Table_AnnuaireCategorie();
		$id_annuaire_categorie = $this->getParam('id_annuaire_categorie');
		
		$annuaire_categorie = $oAnnuaireCategorie->getById($id_annuaire_categorie);
		if($annuaire_categorie){
			$annuaire_categorie->delete();
		}
		
		$this->redirect($this->view->url(array('action'=>'categories','controller'=>'annuaire'),'admin',true));
	}
	
	public function deleteFicheAction(){
		$this->_disableLayout();
		$this->_disableView();
		
		$oAnnuaireFiche = new Aurel_Table_AnnuaireFiche();
		$id_annuaire_fiche = $this->getParam('id_annuaire_fiche');
		
		$permanent = $this->getParam('permanent');
		
		$annuaire_fiche = $oAnnuaireFiche->getById($id_annuaire_fiche);
		if($annuaire_fiche){
			if(!$permanent){
				$annuaire_fiche->status = Aurel_Table_AnnuaireFiche::STATUS_CORBEILLE;
				$annuaire_fiche->save();
			} else {
				$annuaire_fiche->delete();
			}
		}
		if($this->hasParam('url_retour')){
			$url_retour = urldecode($this->getParam('url_retour'));
			$this->redirect($url_retour);
		} else 
			$this->redirect($this->view->url(array('action'=>'index','controller'=>'annuaire'),'admin',true));
	}
	
	public function restoreFicheAction(){
		$this->_disableLayout();
		$this->_disableView();
		
		$oAnnuaireFiche = new Aurel_Table_AnnuaireFiche();
		$id_annuaire_fiche = $this->getParam('id_annuaire_fiche');
		
		$annuaire_fiche = $oAnnuaireFiche->getById($id_annuaire_fiche);
		if($annuaire_fiche){
			$annuaire_fiche->status = Aurel_Table_AnnuaireFiche::STATUS_ACTIF;
			$annuaire_fiche->save();
		}
		$this->redirect($this->view->url(array('action'=>'index','controller'=>'annuaire'),'admin',true));
	}
	
	public function deleteSousCategorieAction(){
		$oAnnuaireSousCategorie = new Aurel_Table_AnnuaireSousCategorie();
		$id_annuaire_sous_categorie = $this->getParam('id_annuaire_sous_categorie');
		
		$annuaire_sous_categorie = $oAnnuaireSousCategorie->getById($id_annuaire_sous_categorie);
		$id_annuaire_categorie = null;
		if($annuaire_sous_categorie){
			$id_annuaire_categorie = $annuaire_sous_categorie->id_annuaire_categorie;
			$annuaire_sous_categorie->delete();
		}
		
		$this->redirect($this->view->url(array('action'=>'categories','controller'=>'annuaire','id_annuaire_categorie'=>$id_annuaire_categorie),'admin',true));
	}
	
	public function indexAction(){
		$oAnnuaireCategorie = new Aurel_Table_AnnuaireCategorie();
		$oAnnuaireSousCategorie = new Aurel_Table_AnnuaireSousCategorie();
		
		$categories = $oAnnuaireCategorie->getAll();
		
		$basename_annuaire_sous_categorie = $this->getParam('basename');
		$annuaire_sous_categorie = null;
		if($basename_annuaire_sous_categorie){
			$annuaire_sous_categorie = $oAnnuaireSousCategorie->getByTitle($basename_annuaire_sous_categorie);
		}

        $state = $this->getParam('state');
		if($annuaire_sous_categorie){
			$this->view->annuaire_categorie_active = $annuaire_sous_categorie->id_annuaire_categorie;
			$this->view->id_annuaire_sous_categorie_active = $annuaire_sous_categorie->id_annuaire_sous_categorie;
			$this->view->annuaire_sous_categorie_active = $annuaire_sous_categorie->basename;
		} elseif($state){
            $this->view->state_active = $state;
        } else {
			$this->view->tous = true;
		}
		
		$arrayCategorie = array();
		foreach($categories as $categorie){
			$arrayCategorie[$categorie->id_annuaire_categorie]['categorie'] = $categorie;
			$liste_basename_sous_categories = explode(",",$categorie->sous_categorie_basename);
			$liste_sous_categories = explode(",",$categorie->sous_categorie_name);
			foreach($liste_sous_categories as $key => $sous_categorie){
				$arrayCategorie[$categorie->id_annuaire_categorie]['sous_categories'][$liste_basename_sous_categories[$key]] = $sous_categorie;
			}
		}
		$this->view->annuaire_categories = $arrayCategorie;
		
		$oAnnuaireFiche = new Aurel_Table_AnnuaireFiche();
		if($state){
			$fiches = $oAnnuaireFiche->getAllByState($state);
		} elseif($annuaire_sous_categorie){
			$fiches = $oAnnuaireFiche->getAllByAnnuaireSousCategorie($annuaire_sous_categorie->id_annuaire_sous_categorie,true);
		} else {
			$fiches = $oAnnuaireFiche->getAll(true);
		}
		$this->view->fiches = $fiches;
		
		$sommes = $oAnnuaireFiche->getSommeStatus();
		$this->view->sommes = $sommes;
	}

    public function deleteProprietaireAction(){
        $oAnnuaireFiche = new Aurel_Table_AnnuaireFiche();

        $id_annuaire_fiche = $this->getParam("id_annuaire_fiche","999999999999");
        $annuaire_fiche = $oAnnuaireFiche->getById($id_annuaire_fiche);

        if($annuaire_fiche){
            $oUser = new Aurel_Table_User();
            $proprio = $oUser->getById($annuaire_fiche->id_user_proprietaire);
            $this->view->proprio = $proprio;

            $formData = $this->_request->getPost();
            if($formData){
                $annuaire_fiche->id_user_proprietaire = null;
                $annuaire_fiche->date_proprietaire = null;
                $annuaire_fiche->status = Aurel_Table_AnnuaireFiche::STATUS_ACTIF;
                $annuaire_fiche->id_user_validation = null;
                $annuaire_fiche->date_validation = null;
                $annuaire_fiche->save();

                $url_retour = urldecode($this->getParam('url_retour'));
                $this->redirect($url_retour);
            }
        }
    }
	
	public function addFicheAction(){
		$oAnnuaireSousCategorie = new Aurel_Table_AnnuaireSousCategorie();
		$oAnnuaireCategorie = new Aurel_Table_AnnuaireCategorie();
		$oAnnuaireFiche = new Aurel_Table_AnnuaireFiche();
		
		$categories = $oAnnuaireCategorie->getAll();
		
		$arrayCategorie = array("-1"=>"Choisir la catégorie");
		foreach($categories as $categorie){
			$liste_id_sous_categories = explode(",",$categorie->sous_categorie_id);
			$liste_sous_categories = explode(",",$categorie->sous_categorie_name);
			foreach($liste_sous_categories as $key => $sous_categorie){
				$arrayCategorie[$categorie->name][$liste_id_sous_categories[$key]] = $sous_categorie;
			}
		}
		$this->view->selectCategorie = $arrayCategorie;
		
		$this->view->url_retour = $this->view->url(array('controller'=>'annuaire'),'admin',true);
		if($this->hasParam('url_retour')){
			$this->view->url_retour = urldecode($this->getParam('url_retour'));
		}
		
		$arrayStatus = array(
			Aurel_Table_AnnuaireFiche::STATUS_INACTIF => "Hors ligne",
			Aurel_Table_AnnuaireFiche::STATUS_ACTIF => "En ligne",
			Aurel_Table_AnnuaireFiche::STATUS_WAITING => "En attente"
		);
		$this->view->selectStatus = $arrayStatus;
		
		$id_annuaire_fiche = $this->getParam("id_annuaire_fiche","999999999999");
		$annuaire_fiche = $oAnnuaireFiche->getById($id_annuaire_fiche);
		
		if($annuaire_fiche){
			$photos = $annuaire_fiche->getPhotos();
			$tabPhotos = array();
			$i = 1;
			foreach($photos as $photo){
				$tabPhotos[$i] = $photo;
				$i++;
			}
			$this->view->tabPhotos = $tabPhotos;
		} else {
			$tabPhotos = array();
			
    		$annuaire_fiche = $oAnnuaireFiche->createRow();
    		$annuaire_fiche->id_user_creation = $this->_getUser()->id_user;
    		$annuaire_fiche->date_creation = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
    		$annuaire_fiche->status = Aurel_Table_AnnuaireFiche::STATUS_ACTIF;
    		
    		if($this->hasParam('basename')){
    			$basename = $this->getParam('basename');
    			$sous_categorie = $oAnnuaireSousCategorie->getByTitle($basename);
    			$annuaire_fiche->id_annuaire_sous_categorie = $sous_categorie->id_annuaire_sous_categorie;
    		}
    	}
    	
    	$this->view->annuaire_fiche = $annuaire_fiche;
    	
    	$formData = $this->_request->getPost();
    	if($this->_request->isPost()){
    		$this->_disableLayout();
    		$this->_disableView();
    		$return = array();
    		$continue = true;
    		if($formData["id_annuaire_sous_categorie"] == "-1"){
    			$continue = false;
    			$return['errors'][] = 'id_annuaire_sous_categorie';
    		}
    		
    		if(empty($formData['nom_etablissement'])){
	    		$continue = false;
	    		$return['errors'][] = 'nom_etablissement';
    		}

    		if(!empty($formData['mail'])){
	    		$validate = new Zend_Validate_EmailAddress();
	    		if(!$validate->isValid($formData["mail"])){
	    			$continue = false;
	    			$return['errors'][] = 'mail';
	    		}
    		}
    		
    		$return['code'] = 'ko';
    		if($continue){
    			$annuaire_fiche->nom_etablissement = stripslashes($formData["nom_etablissement"]);
    			if(!empty($formData["contact_nom"]))
    			    $annuaire_fiche->contact_nom = stripslashes($formData["contact_nom"]);
                else
                    $annuaire_fiche->contact_nom = null;
    			if(!empty($formData["contact_prenom"]))
    			    $annuaire_fiche->contact_prenom = stripslashes($formData["contact_prenom"]);
                else
                    $annuaire_fiche->contact_prenom = null;
    			if(!empty($formData["adresse_1"]))
    			    $annuaire_fiche->adresse_1 = stripslashes($formData["adresse_1"]);
                else
                    $annuaire_fiche->adresse_1 = null;
    			if(!empty($formData["adresse_2"]))
    			    $annuaire_fiche->adresse_2 = stripslashes($formData["adresse_2"]);
                else
                    $annuaire_fiche->adresse_2 = null;
    			if(!empty($formData["code_postal"]))
    			    $annuaire_fiche->code_postal = stripslashes($formData["code_postal"]);
                else
                    $annuaire_fiche->code_postal = null;
    			if(!empty($formData["ville"]))
    			    $annuaire_fiche->ville = stripslashes($formData["ville"]);
                else
                    $annuaire_fiche->ville = null;
    			if(!empty($formData["tel_1"]))
    			    $annuaire_fiche->tel_1 = stripslashes($formData["tel_1"]);
                else
                    $annuaire_fiche->tel_1 = null;
    			if(!empty($formData["tel_2"]))
    			    $annuaire_fiche->tel_2 = stripslashes($formData["tel_2"]);
                else
                    $annuaire_fiche->tel_2 = null;
    			if(!empty($formData["mail"]))
    			    $annuaire_fiche->mail = stripslashes($formData["mail"]);
                else
                    $annuaire_fiche->mail = null;
    			if(!empty($formData["website"])){
    				$website = stripslashes($formData["website"]);
    				if(strpos($website, "http") === false)
    					$website = "http://" . $website;
    				$annuaire_fiche->website = $website;
    			}
                else
                    $annuaire_fiche->website = null;
    			if(!empty($formData["horaires"]))
    			    $annuaire_fiche->horaires = stripslashes($formData["horaires"]);
                else
                    $annuaire_fiche->horaires = null;
    			if(!empty($formData["descriptif"]))
    			    $annuaire_fiche->descriptif = stripslashes($formData["descriptif"]);
                else
                    $annuaire_fiche->descriptif = null;
    			if(!empty($formData["status"]))
    			    $annuaire_fiche->status = stripslashes($formData["status"]);
                else
                    $annuaire_fiche->status = null;
    			$annuaire_fiche->id_annuaire_sous_categorie = stripslashes($formData["id_annuaire_sous_categorie"]);
    			 
    			$annuaire_fiche->id_user_modification = $this->_getUser()->id_user;
    			$annuaire_fiche->date_modification = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
    	
    			$annuaire_fiche->save();
    			
    			$upload_dir = UPLOAD_PATH . "/";
    			$this->_check_dir($upload_dir);
    			$upload_dir .= 'fiche_' . $annuaire_fiche->id_annuaire_fiche."/";
    			$this->_check_dir($upload_dir);
    			
    			$oPhoto = new Aurel_Table_Photo();
    			foreach($formData['file'] as $key => $file){
    				if($file != '' && isset($tabPhotos) && isset($tabPhotos[$key]) && $file == $tabPhotos[$key]->id_photo){
    						
    				} elseif($file != ''){
    					if(isset($tabPhotos) && isset($tabPhotos[$key]) && $file != $tabPhotos[$key]->id_photo)
    						$tabPhotos[$key]->delete();
    			
    					$extension = strtolower(pathinfo($file,PATHINFO_EXTENSION));
    						
    					$new = $oPhoto->createRow();
    					$new->extension = $extension;
    					$new->id_annuaire_fiche = $annuaire_fiche->id_annuaire_fiche;
    					$new->order = 0;
    					$new->id_user_creation = $this->_getUser()->id_user;
    					$new->date_creation = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
    					$new->save();
    						
    					$oldFile = UPLOAD_PATH . "/tmp/" . $file;
    					$newFile = $upload_dir . $new->id_photo.'.'.$extension;
    					copy($oldFile,$newFile); unlink($oldFile);
                        $return['oldfile'] = $oldFile;
                        $return['newfile'] = $newFile;
    						
    					$oldFile = UPLOAD_PATH . "/tmp/thumb" . $file;
    					$newFile = $upload_dir . "thumb" . $new->id_photo.'.'.$extension;
    					copy($oldFile,$newFile); unlink($oldFile);
    						
    					$oldFile = UPLOAD_PATH . "/tmp/smallthumb" . $file;
    					$newFile = $upload_dir . "smallthumb" . $new->id_photo.'.'.$extension;
    					copy($oldFile,$newFile); unlink($oldFile);
    						
    					$oldFile = UPLOAD_PATH . "/tmp/minithumb" . $file;
    					$newFile = $upload_dir . "minithumb" . $new->id_photo.'.'.$extension;
    					copy($oldFile,$newFile); unlink($oldFile);
    				} elseif($file == '' && isset($tabPhotos) && isset($tabPhotos[$key])) {
    					$tabPhotos[$key]->delete();
    				}
    			}
    			$photos = $annuaire_fiche->getPhotos();
    			foreach($photos as $photo){
    				$annuaire_fiche->picture = $photo->id_photo;
    				break;
    			}
    			$annuaire_fiche->save();
    			 
    			$return['code'] = 'ok';
    			$oAnnuaireSousCategorie = new Aurel_Table_AnnuaireSousCategorie();
    			$annuaire_sous_categorie = $oAnnuaireSousCategorie->getById($annuaire_fiche->id_annuaire_sous_categorie);
    			
    			$return['url_redirect'] = $this->view->url(array('action'=>'index','controller'=>'annuaire','basename'=>$annuaire_sous_categorie->basename),'admin',true);
    		}
    		 
    		echo json_encode($return);
    	}
	}
	
	public function categoriesAction(){
		$oAnnuaireCategorie = new Aurel_Table_AnnuaireCategorie();
		$categories = $oAnnuaireCategorie->getAll();
		$this->view->annuaire_categories = $categories;
		
		$id_annuaire_categorie = $this->getParam("id_annuaire_categorie");
		if($id_annuaire_categorie){
			$annuaire_categorie = $oAnnuaireCategorie->getById($id_annuaire_categorie);
			if($annuaire_categorie){
				$this->view->list = true;
				$this->view->annuaire_categorie = $annuaire_categorie;
			}
		}
	}
	
	public function configAction(){
    	$session = new Zend_Session_Namespace('config');
    	$this->view->session = $session;
    	
    	$file = new Zend_Config_Xml(CONFIG_PATH . "/strings.xml",
    			null,
    			array('skipExtends'        => true,
    					'allowModifications' => true));
    	
    	if($file->start_date_access){
    		$start_date_access = new Aurel_Date($file->start_date_access);
    		$this->view->start_date_access = $start_date_access->get('dd/MM/YYYY');
    	}
    	if($file->end_date_access){
    		$end_date_access = new Aurel_Date($file->end_date_access);
    		$this->view->end_date_access = $end_date_access->get('dd/MM/YYYY');
    	}
    	
    	$formData = $this->_request->getPost();
    	if($formData){
    		$file->max_char_descriptif = $formData["max_char_descriptif"];
    		$file->max_char_horaires = $formData["max_char_horaires"];
    		$file->active_access = $formData["active_access"];
    		$file->password = null;
    		$file->start_date_access = null;
    		$file->end_date_access = null;
            $file->password = stripslashes($formData["password"]);
    		if($file->active_access){
	    		if($formData["start_date_access"]){
	    			$start_date_access = new Aurel_Date($formData["start_date_access"]);
	    			$file->start_date_access = $start_date_access->get(Aurel_Date::MYSQL_DATE);
	    		}
	    		if($formData["end_date_access"]){
	    			$end_date_access = new Aurel_Date($formData["end_date_access"]);
	    			$file->end_date_access = $end_date_access->get(Aurel_Date::MYSQL_DATE);
	    		}
    		}
    		
    		include('SimpleImage.php');
    		if(is_uploaded_file($_FILES["imgDefaultFiche"]["tmp_name"])){
    			$extension = pathinfo($_FILES["imgDefaultFiche"]["name"],PATHINFO_EXTENSION);
    			$pathFileTmp = BASE_PATH . "/www/images/TMPno-photo-fiche." . $extension;
    			$pathFileArticle = BASE_PATH . "/www/images/no-photo-fiche.jpg";
    			if(move_uploaded_file($_FILES["imgDefaultFiche"]["tmp_name"], $pathFileTmp)){
    				$img = new abeautifulsite\SimpleImage($pathFileTmp);
	    			$img->auto_orient();
	    			
	    			$img->adaptive_resize(480, 360);
	    			$img->save($pathFileArticle,80);
	    			unlink($pathFileTmp);
    			}
    		}
    		 
    		// Write the config file
    		$writer = new Zend_Config_Writer_Xml(array('config'   => $file,
    				'filename' => CONFIG_PATH . "/strings.xml"));
    		$writer->write();
    		
    		$session->message = "Données enregistrées";
    		$session->setExpirationHops(1);
    		 
    		$this->redirect($this->view->url());
    	}
    }
	
	public function addCategorieAction(){
		$oAnnuaireCategorie = new Aurel_Table_AnnuaireCategorie();
		$id_annuaire_categorie = $this->getParam("id_annuaire_categorie","999999999999");
		$annuaire_categorie = $oAnnuaireCategorie->getById($id_annuaire_categorie);
		
		if(!$annuaire_categorie){
			$annuaire_categorie = $oAnnuaireCategorie->createRow();
			$annuaire_categorie->id_user_creation = $this->_getUser()->id_user;
			$annuaire_categorie->date_creation = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
			$annuaire_categorie->color_code = null;
		}
		
		$this->view->annuaire_categorie = $annuaire_categorie;
		
		$formData = $this->_request->getPost();
		if($this->_request->isPost()){
			$this->_disableLayout();
			$this->_disableView();
			$return = array();
			
			$annuaire_categorie->name = stripslashes($formData["name"]);
			$annuaire_categorie->basename = $oAnnuaireCategorie->getBasename(stripslashes($formData["name"]));
			if(isset($formData["color_code"]))
				$annuaire_categorie->color_code = stripslashes($formData["color_code"]);
			$annuaire_categorie->id_user_modification = $this->_getUser()->id_user;
			$annuaire_categorie->date_modification = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
			$annuaire_categorie->save();
			
			$this->redirect($this->view->url(array('action'=>'categories')));
		}
	}
	
	public function addSousCategorieAction(){
		$oAnnuaireCategorie = new Aurel_Table_AnnuaireCategorie();
		$oAnnuaireSousCategorie = new Aurel_Table_AnnuaireSousCategorie();
		
		$id_annuaire_categorie = $this->getParam("id_annuaire_categorie","999999999999");
		$id_annuaire_sous_categorie = $this->getParam("id_annuaire_sous_categorie","999999999999");
		
		$annuaire_categorie = $oAnnuaireCategorie->getById($id_annuaire_categorie);
		$annuaire_sous_categorie = $oAnnuaireSousCategorie->getById($id_annuaire_sous_categorie);
	
		if(!$annuaire_sous_categorie){
			$annuaire_sous_categorie = $oAnnuaireSousCategorie->createRow();
			$annuaire_sous_categorie->id_user_creation = $this->_getUser()->id_user;
			$annuaire_sous_categorie->date_creation = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
		}
	
		$this->view->annuaire_sous_categorie = $annuaire_sous_categorie;
	
		$formData = $this->_request->getPost();
		if($this->_request->isPost()){
			$this->_disableLayout();
			$this->_disableView();
			$return = array();
				
			$annuaire_sous_categorie->id_annuaire_categorie = $id_annuaire_categorie;
			$annuaire_sous_categorie->name = stripslashes($formData["name"]);
			$annuaire_sous_categorie->basename = $oAnnuaireCategorie->getBasename(stripslashes($formData["name"]));
			$annuaire_sous_categorie->id_user_modification = $this->_getUser()->id_user;
			$annuaire_sous_categorie->date_modification = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
			$annuaire_sous_categorie->save();
				
			$this->redirect($this->view->url(array('action'=>'categories','id_annuaire_categorie'=>$id_annuaire_categorie)));
		}
	}
	
	public function listSousCategorieAction(){
		$oAnnuaireCategorie = new Aurel_Table_AnnuaireCategorie();
		
		$id_annuaire_categorie = $this->getParam("id_annuaire_categorie");
		$annuaire_categorie = $oAnnuaireCategorie->getById($id_annuaire_categorie);
	
		$this->view->annuaire_categorie = $annuaire_categorie;
		$this->view->annuaire_sous_categories = $annuaire_categorie->getSousCategories();
		
	}
	
	public function sortAnnuaireCategoriesAction(){
		$this->_disableLayout();
		$this->_disableView();
		$oAnnuaireCategorie = new Aurel_Table_AnnuaireCategorie();
		 
		$order = $this->getParam('order');
		 
		$tabOrdre = explode(',',$order);
		 
		if (is_array($tabOrdre))
		{
			foreach($tabOrdre as $key=>$value){
				$id_annuaire_categorie = str_replace("annuaire_categorie_","",$value);
				$ligne = $oAnnuaireCategorie->getById($id_annuaire_categorie);
				if($ligne){
					$ligne->order = $key;
					$ligne->save();
				}
			}
		}
	}
	
	public function sortAnnuaireSousCategoriesAction(){
		$this->_disableLayout();
		$this->_disableView();
		$oAnnuaireSousCategorie = new Aurel_Table_AnnuaireSousCategorie();
		 
		$order = $this->getParam('order');
		 
		$tabOrdre = explode(',',$order);
		 
		if (is_array($tabOrdre))
		{
			foreach($tabOrdre as $key=>$value){
				$id_annuaire_sous_categorie = str_replace("annuaire_sous_categorie_","",$value);
				$ligne = $oAnnuaireSousCategorie->getById($id_annuaire_sous_categorie);
				if($ligne){
					$ligne->order = $key;
					$ligne->save();
				}
			}
		}
	}
	
	public function uploadTmpAction(){
		$this->_disableLayout();
		$this->_disableView();
	
		$upload_dir = UPLOAD_PATH . "/";
		$this->_check_dir($upload_dir);
		$upload_dir .= "tmp/";
		$this->_check_dir($upload_dir);
		 
		$return = array();
		$return['returncode'] = 'ko';
		 
		if($_FILES['images']['error'] == 0 ){
			$pic = $_FILES['images'];
			$extension = strtolower(pathinfo($pic['name'],PATHINFO_EXTENSION));
	
			$name = uniqid().'.'.$extension;
			$upload_path = $upload_dir . $name;
			$upload_paththumb = $upload_dir . 'thumb' .$name;
			$upload_pathsmallthumb = $upload_dir . 'smallthumb' .$name;
			$upload_pathminithumb = $upload_dir . 'minithumb' .$name;
	
			if(move_uploaded_file($pic['tmp_name'], $upload_path)){
				include('SimpleImage.php');
				$img = new abeautifulsite\SimpleImage($upload_path);
				$img->auto_orient();
				 
				$img->best_fit(1000, 1000);
				$img->save($upload_path,80);
				 
				$img->adaptive_resize(480, 360);
				$img->save($upload_paththumb,80);
				 
				$img->adaptive_resize(200, 200);
				$img->save($upload_pathsmallthumb,80);
				 
				$img->adaptive_resize(40, 40);
				$img->save($upload_pathminithumb,80);
				 
				//Aurel_Upload::resizeImg($upload_path,1000,1000,'');
				//Aurel_Upload::cropImg($upload_path,480,360);
				//Aurel_Upload::cropImg($upload_path,200,200,'smallthumb');
				 
				$return['returncode'] = 'ok';
				$return['name'] = $name;
				$return['src'] = "/images/upload/tmp/smallthumb" . $name;
			}
		}
		echo json_encode($return);
	}
	
	public function toggleFicheAction(){
		$this->_disableLayout();
		$this->_disableView();
		$oAnnuaireFiche = new Aurel_Table_AnnuaireFiche();
		$id_annuaire_fiche = $this->getParam('id_annuaire_fiche');
		$state = $this->getParam('state');
		$return['returncode'] = 'ko';
		if($id_annuaire_fiche){
			$annuaire_fiche = $oAnnuaireFiche->getById($id_annuaire_fiche);
			$annuaire_fiche->id_user_validation = $this->_getUser()->id_user;
			$annuaire_fiche->date_validation = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
			$annuaire_fiche->status = $state == "true" ? Aurel_Table_AnnuaireFiche::STATUS_ACTIF : Aurel_Table_AnnuaireFiche::STATUS_INACTIF;
			$annuaire_fiche->save();
			$return['returncode'] = 'ok';
			$return['classCss'] = '';
			$return['id_annuaire_fiche'] = $id_annuaire_fiche;
			if($annuaire_fiche->status == Aurel_Table_AnnuaireFiche::STATUS_INACTIF)
				$return['classCss'] = 'inactive';
			if($annuaire_fiche->status == Aurel_Table_AnnuaireFiche::STATUS_ACTIF)
				$return['classCss'] = 'valide';
            $sommes = $oAnnuaireFiche->getSommeStatus();
            $return['sommes'] = $sommes;
		}
		
		echo json_encode($return);
	}

    public function downloadsAction(){

    }
}