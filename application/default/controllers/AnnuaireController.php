<?php

/**
 * AnnuaireController - The default controller class
 * 
 * @author
 * @version 
 */
class AnnuaireController extends Aurel_Controller_Abstract
{
	protected $_allowmodif;

    /**
     * Pre-dispatch routines
     *
     * @return void
     */
	public function preDispatch(){
		parent::preDispatch();

        $this->_allowmodif = false;
        $sessionAccess = new Zend_Session_Namespace('access');
		/**
		 * Login Annuaire si mot de passe configuré
		 */
		if(!$this->_getAcl()->isAllowed($this->_role,Aurel_Acl::RESSOURCE_ADMIN_ANNUAIRE)){
			
			if($this->view->actionName != 'login' && $this->_config->active_access && $this->_config->password){
				
				$start_date = $this->_config->start_date_access;
				$end_date = $this->_config->end_date_access;
				$today = new Aurel_Date();

				if($start_date <= $today->get(Aurel_Date::MYSQL_DATE) && $today->get(Aurel_Date::MYSQL_DATE) <= $end_date){
					if(!isset($sessionAccess->rights))
						$this->forward('login');
				} 
			}

            if(isset($sessionAccess->rights) && $sessionAccess->rights)
                $this->_allowmodif = true;
		} else {
            if($this->view->actionName != 'download-annuaire' && $this->view->actionName != 'download-csv') {
                $this->redirect($this->view->url(['action' => 'index', 'controller' => 'annuaire'], 'admin', true));
            }
		}
	}

    /**
     * Login Action
     *
     * @return void
     */
	public function loginAction(){
		$formData = $this->_request->getPost();
		$sessionAccess = new Zend_Session_Namespace('access');
		if(isset($sessionAccess->error)){
			$this->view->message = $sessionAccess->error;
			unset($sessionAccess->error);
		}
		
		if($formData){
            $url_retour = $this->view->url();
            if($this->hasParam('url_retour'))
                $url_retour = urldecode((string) $this->getParam('url_retour'));
			if($formData['pass'] == $this->_config->password){
				$sessionAccess->rights = true;
			} else {
				$sessionAccess->error = "Mot de passe incorrect";
                $url_retour = $_SERVER["REQUEST_URI"];
			}
            $this->redirect($url_retour);
		}
	}

    /**
     * Logout Action
     *
     * @return void
     */
    public function logoutAction(){
        $url_retour = $this->view->url([],'annuaire',true);
        if($this->hasParam('url_retour'))
            $url_retour = urldecode((string) $this->getParam('url_retour'));
        $sessionAccess = new Zend_Session_Namespace('access');
        $sessionAccess->unsetAll();
        $this->redirect($url_retour);
    }
	
	private function _check_dir($dir){
		if(!is_dir($dir)){
			mkdir($dir);
		}
	}

    /**
     * Index Action
     *
     * @return void
     */
	public function indexAction(){
		$oAnnuaireCategorie = new Aurel_Table_AnnuaireCategorie();
		$oAnnuaireSousCategorie = new Aurel_Table_AnnuaireSousCategorie();
		$oAnnuaireFiche = new Aurel_Table_AnnuaireFiche();
		
		$categories = $oAnnuaireCategorie->getAll();
		
		$basename_annuaire_sous_categorie = $this->getParam('basenamesouscategorie');
		$basename_annuaire_categorie = $this->getParam('basenamecategorie');
		
		$annuaire_sous_categorie = null;
		$annuaire_categorie = null;
		if($basename_annuaire_sous_categorie){
			$annuaire_sous_categorie = $oAnnuaireSousCategorie->getByTitle($basename_annuaire_sous_categorie);
		}
		if($basename_annuaire_categorie){
			$annuaire_categorie = $oAnnuaireCategorie->getByTitle($basename_annuaire_categorie);
		}
		if($annuaire_sous_categorie){
			$this->view->annuaire_categorie_active = $annuaire_sous_categorie->id_annuaire_categorie;
			$this->view->id_annuaire_sous_categorie_active = $annuaire_sous_categorie->id_annuaire_sous_categorie;
			$this->view->annuaire_sous_categorie_active = $annuaire_sous_categorie->basename;
			$this->view->perimetre = $annuaire_sous_categorie->name;
		} elseif($annuaire_categorie){
			$this->view->annuaire_categorie_active = $annuaire_categorie->id_annuaire_categorie;
			$this->view->id_annuaire_categorie_active = $annuaire_categorie->id_annuaire_categorie;
			$this->view->annuaire_categorie_active = $annuaire_categorie->basename;
			$this->view->perimetre = $annuaire_categorie->name;
		} else {
			$this->view->tous = true;
			$this->view->perimetre = "Toutes catégories";
		}
		
		$arrayCategorie = [];
        $tabPastillesCategorie = [];
        $tabPastillesSousCategorie = [];
		foreach($categories as $categorie){
            $tabPastillesCategorie[$categorie->basename] = 0;
			$arrayCategorie[$categorie->id_annuaire_categorie]['categorie'] = $categorie;
			$liste_basename_sous_categories = explode(",",(string) $categorie->sous_categorie_basename);
			$liste_sous_categories = explode(",",(string) $categorie->sous_categorie_name);
			$liste_id_sous_categories = explode(",",(string) $categorie->sous_categorie_id);
			foreach($liste_sous_categories as $key => $sous_categorie){
                $tabPastillesSousCategorie[$liste_basename_sous_categories[$key]] = 0;
				$arrayCategorie[$categorie->id_annuaire_categorie]['sous_categories'][$liste_basename_sous_categories[$key]] = $sous_categorie;
			}
		}
		$this->view->annuaire_categories = $arrayCategorie;
		
		$q = addslashes((string) $this->getParam('q'));
		$this->view->q = $q;
		$admin = $this->_getAcl()->isAllowed($this->_role,Aurel_Acl::RESSOURCE_ADMIN_ANNUAIRE);
		if($annuaire_sous_categorie){
			$fiches = $oAnnuaireFiche->getAllByAnnuaireSousCategorie($annuaire_sous_categorie->id_annuaire_sous_categorie,$admin,$this->_getUser(),$q,$this->_allowmodif);
		} elseif($annuaire_categorie){
			$fiches = $oAnnuaireFiche->getAllByAnnuaireCategorie($annuaire_categorie->id_annuaire_categorie,$admin,$this->_getUser(),$q,$this->_allowmodif);
		} else {
			$fiches = $oAnnuaireFiche->getAll($admin,$this->_getUser(),$q,$this->_allowmodif);
		}
		$this->view->fiches = $fiches;
		
		// SEARCH AUTOCOMPLETE
		$fichesAutocomplete = $oAnnuaireFiche->getAll($admin,$this->_getUser(),null,$this->_allowmodif);
		$tab = [];
		foreach($fichesAutocomplete as $fiche){
            $tabPastillesCategorie[$fiche->basename_categorie] += 1;
            $tabPastillesSousCategorie[$fiche->basename_sous_categorie] += 1;

			$array['label'] = mb_strtoupper((string) $fiche->nom_etablissement);
			if($fiche->picture)
				$array['pic'] = "<img class='img-responsive' src='/images/upload/fiche_{$fiche->id_annuaire_fiche}/minithumb{$fiche->picture}.{$fiche->extension}' alt='image{$fiche->id_annuaire_fiche}'/>";
			else
				$array['pic'] = "<img class='img-responsive' src='/images/no-photo-fiche.jpg' alt='image{$fiche->id_annuaire_fiche}' width='40' />";
				
			$array['desc'] = $fiche->adresse_1;
			$tab[] = $array;
		}
		$this->view->tabAutocomplete = json_encode($tab, JSON_THROW_ON_ERROR);
		$this->view->tabPastillesCategorie = $tabPastillesCategorie;
		$this->view->tabPastillesSousCategorie = $tabPastillesSousCategorie;

		// RIGHTS
		$this->view->allowmodif = $this->_allowmodif;
		
		$tabRights = [];
		foreach($fiches as $fiche){
			$tabRights[$fiche->id_annuaire_fiche] = true;
			if($this->_getAcl()->isAllowed($this->_role,Aurel_Acl::RESSOURCE_MEMBRE)){
				if($this->_getUser()->id_user == $fiche->id_user_proprietaire){
					$tabRights[$fiche->id_annuaire_fiche] = true;
				}
			}
		}
		if($this->_getAcl()->isAllowed($this->_role,Aurel_Acl::RESSOURCE_ADMIN_ANNUAIRE)){
			foreach($tabRights as &$right){
				$right = true;
			}
		} elseif($this->_getAcl()->isAllowed($this->_role,Aurel_Acl::RESSOURCE_MEMBRE)){
			$fichedetenue = $oAnnuaireFiche->getByProprietaire($this->_getUser());
			if($fichedetenue){
				foreach($tabRights as &$right){
					$right = false;
				}
				$tabRights[$fichedetenue->id_annuaire_fiche] = true;
			} elseif(!$this->_allowmodif){
				foreach($tabRights as &$right){
					$right = false;
				}
			}
		} elseif(!$this->_allowmodif){
            foreach($tabRights as &$right){
                $right = false;
            }
        }
		$this->view->tabRights = $tabRights;

		if($this->_isAjax()){
			$this->render('fiches-annuaire');
		}
	}
	
	public function categorieAction() {
		
	}

    /**
     * Contact
     *
     * 
     * @throws Zend_Mail_Exception
     * @return void
     */
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
				
				$return = [];
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
						$subject = "LE PETIT CHARSIEN, nouvel email de " . stripslashes((string) $formData["email"]);
						$html = stripslashes((string) $formData["text"]);
						
						$mail = new Aurel_Mailer('utf-8');
						
						$mail->setBodyHtmlWithDesign($html,$subject);
						$mail->setSubject($subject);
						$mail->setFrom($formData["email"]);
						$mail->addTo($annuaire_fiche->mail);
						
						try{
							$mail->send();
							$return["sent"] = true;
						} catch(Exception){
							
						}
					}
				}
				
				echo json_encode($return, JSON_THROW_ON_ERROR);
			}
		}
	}
	
	public function editFicheAction(){

		$sendMail = null;
		$this->view->validAuto = $this->getParam('valid');
        $oAnnuaireSousCategorie = new Aurel_Table_AnnuaireSousCategorie();
		$oAnnuaireCategorie = new Aurel_Table_AnnuaireCategorie();
		$oAnnuaireFiche = new Aurel_Table_AnnuaireFiche();

		$sessionAnnonce = new Zend_Session_Namespace('annonce');
		$this->view->errors = $sessionAnnonce->errors;
		
		$this->view->url_retour = $this->view->url([],'annuaire',true);
		if($this->hasParam('url_retour')){
			$this->view->url_retour = urldecode((string) $this->getParam('url_retour'));
		}
		
		$arrayStatus = [Aurel_Table_AnnuaireFiche::STATUS_INACTIF => "Hors ligne", Aurel_Table_AnnuaireFiche::STATUS_ACTIF => "En ligne"];
		$this->view->selectStatus = $arrayStatus;
		
		$categories = $oAnnuaireCategorie->getAll();
	
		$arrayCategorie = ["-1"=>"Choisir la catégorie"];
		foreach($categories as $categorie){
			$liste_id_sous_categories = explode(",",(string) $categorie->sous_categorie_id);
			$liste_sous_categories = explode(",",(string) $categorie->sous_categorie_name);
			foreach($liste_sous_categories as $key => $sous_categorie){
				$arrayCategorie[$categorie->name][$liste_id_sous_categories[$key]] = $sous_categorie;
			}
		}
		$this->view->selectCategorie = $arrayCategorie;
	
		$id_annuaire_fiche = $this->getParam("id_annuaire_fiche","999999999999");
		$annuaire_fiche = $oAnnuaireFiche->getById($id_annuaire_fiche);
		
		if($this->_getAcl()->isAllowed($this->_role,Aurel_Acl::RESSOURCE_MEMBRE)){
			$fichedetenue = $oAnnuaireFiche->getByProprietaire($this->_getUser());
		
			if($fichedetenue && ($annuaire_fiche && $annuaire_fiche->id_annuaire_fiche != $fichedetenue->id_annuaire_fiche || !$annuaire_fiche)){
				$this->view->interdictionProprietaire = true;
				$sous_categorie = $fichedetenue->getAnnuaireSousCategorie();
				$this->view->url_mafiche = $this->view->url(['action'=>'edit-fiche', 'id_annuaire_fiche'=>$fichedetenue->id_annuaire_fiche, 'basename'=>$sous_categorie->basename, 'valid'=>null],'annuaire');
			}

            if($annuaire_fiche && $annuaire_fiche->id_user_proprietaire != null && $this->_getUser()->id_user != $annuaire_fiche->id_user_proprietaire) {
                $this->view->interdictionProprietaireExistant = true;
            }

		}
	
		if($annuaire_fiche){
			if($this->_getAcl()->isAllowed($this->_role,Aurel_Acl::RESSOURCE_ADMIN_ANNUAIRE)){
				$this->view->proprietaire = $annuaire_fiche->getProprietaire();
			}
			$photos = $annuaire_fiche->getPhotos();
			$tabPhotos = [];
			$i = 1;
			foreach($photos as $photo){
				$tabPhotos[$i] = $photo;
				$i++;
			}
			$this->view->tabPhotos = $tabPhotos;
		} else {
			$tabPhotos = [];
				
			$annuaire_fiche = $oAnnuaireFiche->createRow();
			if($this->_getAcl()->isAllowed($this->_role,Aurel_Acl::RESSOURCE_MEMBRE) || $this->_getAcl()->isAllowed($this->_role,Aurel_Acl::RESSOURCE_ADMIN_ANNUAIRE))
				$annuaire_fiche->id_user_creation = $this->_getUser()->id_user;
			$annuaire_fiche->date_creation = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
			if($this->_getAcl()->isAllowed($this->_role,Aurel_Acl::RESSOURCE_ADMIN_ANNUAIRE))
				$annuaire_fiche->status = Aurel_Table_AnnuaireFiche::STATUS_ACTIF;
			else
				$annuaire_fiche->status = Aurel_Table_AnnuaireFiche::STATUS_WAITING;
			
			if($this->hasParam('basename')){
				$basename = $this->getParam('basename');
				$sous_categorie = $oAnnuaireSousCategorie->getByTitle($basename);
				$annuaire_fiche->id_annuaire_sous_categorie = $sous_categorie->id_annuaire_sous_categorie;
			}
		}
		 
		$this->view->annuaire_fiche = $annuaire_fiche;

		if($this->_getAcl()->isAllowed($this->_role,Aurel_Acl::RESSOURCE_MEMBRE) || $this->_getAcl()->isAllowed($this->_role,Aurel_Acl::RESSOURCE_ADMIN_ANNUAIRE)){
			$formData = $this->_request->getPost();
			if($this->_request->isPost()){
				$sessionAnnonce->unsetAll();
				$this->_disableLayout();
				$this->_disableView();
				$return = [];
				$continue = true;
				
				$validate = new Zend_Validate_EmailAddress();
				if(empty($formData['nom_etablissement'])){
					$continue = false;
					$return['errors']['nom_etablissement'] = 'Veuillez indiquer un nom d\'établissement';
				}
				if(!empty($formData['mail'])){
					if(!$validate->isValid($formData["mail"])){
						$continue = false;
						$return['errors']['mail'] = 'Veuillez saisir une adresse email valide.';
					}
				}
				if($formData["id_annuaire_sous_categorie"] == "-1"){
					$continue = false;
					$return['errors']['id_annuaire_sous_categorie'] = 'Veuillez sélectionner une catégorie';
				}
				if(isset($interdictionProprietaire) && $interdictionProprietaire){
					$continue = false;
					$return['interdictionProprietaire'] = true;
					if(isset($fichedetenue)){
						$sous_categorie = $fichedetenue->getAnnuaireSousCategorie();
						$return['url_mafiche'] = $this->view->url(['action'=>'edit-fiche', 'id_annuaire_fiche'=>$fichedetenue->id_annuaire_fiche, 'basename'=>$sous_categorie->basename, 'valid'=>null],'annuaire');
					}
				}
		
				$return['code'] = 'ko';
				if($continue) {
                    $annuaire_fiche->nom_etablissement = stripslashes((string) $formData["nom_etablissement"]);
                    if (!empty($formData["contact_nom"]))
                        $annuaire_fiche->contact_nom = stripslashes((string) $formData["contact_nom"]);
                    else
                        $annuaire_fiche->contact_nom = null;
                    if (!empty($formData["contact_prenom"]))
                        $annuaire_fiche->contact_prenom = stripslashes((string) $formData["contact_prenom"]);
                    else
                        $annuaire_fiche->contact_prenom = null;
                    if (!empty($formData["adresse_1"]))
                        $annuaire_fiche->adresse_1 = stripslashes((string) $formData["adresse_1"]);
                    else
                        $annuaire_fiche->adresse_1 = null;
                    if (!empty($formData["adresse_2"]))
                        $annuaire_fiche->adresse_2 = stripslashes((string) $formData["adresse_2"]);
                    else
                        $annuaire_fiche->adresse_2 = null;
                    if (!empty($formData["code_postal"]))
                        $annuaire_fiche->code_postal = stripslashes((string) $formData["code_postal"]);
                    else
                        $annuaire_fiche->code_postal = null;
                    if (!empty($formData["ville"]))
                        $annuaire_fiche->ville = stripslashes((string) $formData["ville"]);
                    else
                        $annuaire_fiche->ville = null;
                    if (!empty($formData["tel_1"]))
                        $annuaire_fiche->tel_1 = stripslashes((string) $formData["tel_1"]);
                    else
                        $annuaire_fiche->tel_1 = null;
                    if (!empty($formData["tel_2"]))
                        $annuaire_fiche->tel_2 = stripslashes((string) $formData["tel_2"]);
                    else
                        $annuaire_fiche->tel_2 = null;
                    if (!empty($formData["mail"]))
                        $annuaire_fiche->mail = stripslashes((string) $formData["mail"]);
                    else
                        $annuaire_fiche->mail = null;
                    if (!empty($formData["website"])) {
                        $website = stripslashes((string) $formData["website"]);
                        if (!str_contains($website, "http"))
                            $website = "http://" . $website;
                        $annuaire_fiche->website = $website;
                    } else
                        $annuaire_fiche->website = null;
                    if (!empty($formData["horaires"]))
                        $annuaire_fiche->horaires = stripslashes((string) $formData["horaires"]);
                    else
                        $annuaire_fiche->horaires = null;
                    if (!empty($formData["descriptif"]))
                        $annuaire_fiche->descriptif = stripslashes((string) $formData["descriptif"]);
                    else
                        $annuaire_fiche->descriptif = null;

                    $annuaire_fiche->id_annuaire_sous_categorie = stripslashes((string) $formData["id_annuaire_sous_categorie"]);

                    if (!$this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_ADMIN_ANNUAIRE)) {
                        if (!$annuaire_fiche->id_user_proprietaire) {
                            $sendMail = true;
                            $annuaire_fiche->id_user_proprietaire = $this->_getUser()->id_user;
                            $annuaire_fiche->date_proprietaire = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
                            $annuaire_fiche->status = Aurel_Table_AnnuaireFiche::STATUS_WAITING;
                        } else {
                            $sendMail = false;
                        }
                    } elseif (!empty($formData["status"])) {
                        $annuaire_fiche->status = $formData["status"];
                        $sendMail = false;
                    }

                    $annuaire_fiche->id_user_modification = $this->_getUser()->id_user;
                    $annuaire_fiche->date_modification = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);

                    $annuaire_fiche->save();

                    $upload_dir = UPLOAD_PATH . "/";
                    $this->_check_dir($upload_dir);
                    $upload_dir .= 'fiche_' . $annuaire_fiche->id_annuaire_fiche . "/";
                    $this->_check_dir($upload_dir);

                    $oPhoto = new Aurel_Table_Photo();
                    foreach ($formData['file'] as $key => $file) {
                        if ($file != '' && isset($tabPhotos) && isset($tabPhotos[$key]) && $file == $tabPhotos[$key]->id_photo) {

                        } elseif ($file != '') {
                            if (isset($tabPhotos) && isset($tabPhotos[$key]) && $file != $tabPhotos[$key]->id_photo)
                                $tabPhotos[$key]->delete();

                            $extension = strtolower(pathinfo((string) $file, PATHINFO_EXTENSION));

                            $new = $oPhoto->createRow();
                            $new->extension = $extension;
                            $new->id_annuaire_fiche = $annuaire_fiche->id_annuaire_fiche;
                            $new->order = 0;
                            $new->id_user_creation = $this->_getUser()->id_user;
                            $new->date_creation = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
                            $new->save();

                            $oldFile = UPLOAD_PATH . "/tmp/" . $file;
                            $newFile = $upload_dir . $new->id_photo . '.' . $extension;
                            copy($oldFile, $newFile);
                            unlink($oldFile);

                            $oldFile = UPLOAD_PATH . "/tmp/thumb" . $file;
                            $newFile = $upload_dir . "thumb" . $new->id_photo . '.' . $extension;
                            copy($oldFile, $newFile);
                            unlink($oldFile);

                            $oldFile = UPLOAD_PATH . "/tmp/smallthumb" . $file;
                            $newFile = $upload_dir . "smallthumb" . $new->id_photo . '.' . $extension;
                            copy($oldFile, $newFile);
                            unlink($oldFile);

                            $oldFile = UPLOAD_PATH . "/tmp/minithumb" . $file;
                            $newFile = $upload_dir . "minithumb" . $new->id_photo . '.' . $extension;
                            copy($oldFile, $newFile);
                            unlink($oldFile);
                        } elseif ($file == '' && isset($tabPhotos) && isset($tabPhotos[$key])) {
                            $tabPhotos[$key]->delete();
                        }
                    }
                    $photos = $annuaire_fiche->getPhotos();
                    foreach ($photos as $photo) {
                        $annuaire_fiche->picture = $photo->id_photo;
                        break;
                    }
                    $annuaire_fiche->save();

                    $return['code'] = 'ok';

                    $oUser = new Aurel_Table_User();
                    $usersAdminAnnuaire = $oUser->getUsersWithRight(Aurel_Acl::RESSOURCE_ADMIN_ANNUAIRE);

                    $daoAnnuaireSousCategorie = new Aurel_Table_AnnuaireSousCategorie();
                    $daoAnnuaireCategorie = new Aurel_Table_AnnuaireCategorie();

                    $annuaire_sous_categorie = $daoAnnuaireSousCategorie->getById($annuaire_fiche->id_annuaire_sous_categorie);
                    $annuaire_categorie = $daoAnnuaireCategorie->getById($annuaire_sous_categorie->id_annuaire_categorie);

                    $subject = "Nouvelle fiche en attente de validation (Fiche n° {$annuaire_fiche->id_annuaire_fiche})";
                    $body = "Une nouvelle fiche a été mise en ligne par {$this->_getUser()->firstname} {$this->_getUser()->lastname}\n" .
                        "Nom etablissement : {$annuaire_fiche->nom_etablissement}\n" .
                        "Sous catégorie : {$annuaire_sous_categorie->name}\n";
                    /*if($photos->count() > 0){
                        $body .= "<div style='text-align:center'>Photos :\n";
                        foreach($photos as $photo){
                            $body .= "<img style='width:150px' src='http://{$_SERVER['HTTP_HOST']}/images/upload/{$article->id_article}/smallthumb{$photo->id_photo}.{$photo->extension}' alt='{$photo->id_photo}' /> ";
                        }
                        $body .= "</div>";
                    }*/
                    $body .= "<br/>\n";
                    $body .= "<div style='text-align:center'><a href='http://{$_SERVER['HTTP_HOST']}/admin/annuaire/index/state/waiting'>Je me connecte pour valider la mise en ligne</a></div>\n\n";

                    $return['sent'] = 0;
                    if ($sendMail) {
                        $mail = new Aurel_Mailer("utf-8");

                        $mail->setBodyHtmlWithDesign($body, $subject)
                            ->setSubject($subject)
                            ->setFrom("no-reply@lepetitcharsien.com", "Le Petit Charsien");

                        foreach ($usersAdminAnnuaire as $user) {
                            $mail->addTo($user->email, $user->firstname . " " . $user->lastname);
                        }

                        try {
                            $mail->send();
                            $return['sent'] = 1;
                        } catch (Exception) {
                            //Zend_Debug::dump($usersAdminAnnuaire->toArray());
                            //echo $body;die();
                        }
                    }

					if($this->view->url_retour)
						$return['url_redirect'] = $this->view->url_retour;
					else
						$return['url_redirect'] = $this->view->url(['action'=>'index', 'basenamesouscategorie'=>$annuaire_sous_categorie->basename, 'basenamecategorie'=>$annuaire_categorie->basename],'annuaire',true);
				}
				 
				echo json_encode($return, JSON_THROW_ON_ERROR);
			}
		}
	}
	
	public function isUserAction(){
		$return = [];
		$user = null;
		$this->_disableLayout();
		$this->_disableView();
	
		$formData = $this->_request->getPost();
		$sessionAnnonce = new Zend_Session_Namespace('annonce');
		$sessionAnnonce->formData = $formData;
	
		$email = $this->getParam('email_connexion');
		$oUser = new Aurel_Table_User();
	
		$continu = true;


	
		$validate = new Zend_Validate_EmailAddress();
		if($formData['email_connexion'] == ''){
			$continu = false;
			$return['errors']['email_connexion'] = 'Veuillez saisir une adresse email.';
		} elseif(!$validate->isValid($formData['email_connexion'])){
			$continu = false;
			$return['errors']['email_connexion'] = 'Veuillez saisir une adresse email valide.';
		} else {
            $continu = true;
            $oAnnuaireFiche = new Aurel_Table_AnnuaireFiche();
            $id_annuaire_fiche = $this->getParam("id_annuaire_fiche","999999999999");
            $annuaire_fiche = $oAnnuaireFiche->getById($id_annuaire_fiche);
            $user = $oUser->getByEmail($email);

            if($annuaire_fiche->id_user_proprietaire !== null && (!$user || $user->id_user != $annuaire_fiche->id_user_proprietaire)){
                $continu = false;
                $return['errors']['email_connexion'] = 'Cette fiche a déjà été modifiée par un autre utilisateur. Vous n\'avez donc pas les droits pour la modifier';
            }
        }
	
		if($continu){
			$return = [];
			$return['user'] = false;
			$return['email'] = $email;
			if($user){
				$return['user'] = true;
			}
		}
		echo json_encode($return, JSON_THROW_ON_ERROR);
	}
	
	public function uploadTmpAction(){
		$this->_disableLayout();
		$this->_disableView();
	
		$upload_dir = UPLOAD_PATH . "/";
		$this->_check_dir($upload_dir);
		$upload_dir .= "tmp/";
		$this->_check_dir($upload_dir);
			
		$return = [];
		$return['returncode'] = 'ko';
			
		if($_FILES['images']['error'] == 0 ){
			$pic = $_FILES['images'];
			$extension = strtolower(pathinfo((string) $pic['name'],PATHINFO_EXTENSION));
	
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
		echo json_encode($return, JSON_THROW_ON_ERROR);
	}
	
	public function autocompleteSearchAction(){
		$this->_disableLayout();
		$this->_disableView();
		
		$term = addslashes((string) $this->getParam('term'));
		
		$oAnnuaireFiche = new Aurel_Table_AnnuaireFiche();
		
		$annuairesFiche = $oAnnuaireFiche->getAll(false,null,$term);
		
		$tab = [];
		foreach($annuairesFiche as $fiche){
			$array['label'] = mb_strtoupper((string) $fiche->nom_etablissement);
			if($fiche->picture)
				$array['pic'] = "<img class='img-responsive' src='/images/upload/fiche_{$fiche->id_annuaire_fiche}/minithumb{$fiche->picture}.{$fiche->extension}' alt='image{$fiche->id_annuaire_fiche}'/>";
			else
				$array['pic'] = "<img class='img-responsive' src='/images/no-photo-fiche.jpg' alt='image{$fiche->id_annuaire_fiche}' width='40' />";
			$array['desc'] = $fiche->adresse_1;
			$tab[] = $array;
		}
		
		echo json_encode($tab, JSON_THROW_ON_ERROR);
	}
	
	public function downloadAnnuaireAction()
    {
        $this->_disableLayout();
        $this->_disableView();

        $oAnnuaireCategorie = new Aurel_Table_AnnuaireCategorie();
        $oAnnuaireSousCategorie = new Aurel_Table_AnnuaireSousCategorie();
        $oAnnuaireFiche = new Aurel_Table_AnnuaireFiche();

        $categories = $oAnnuaireCategorie->getAll();
        $sous_categories = $oAnnuaireSousCategorie->getAll();

        $tabFiches = [];
        $libelleCategorie = [];
        $libelleSousCategorie = [];
        foreach($categories as $categorie){
            $libelleCategorie[$categorie->id_annuaire_categorie]['name'] = $categorie->name;
            $libelleCategorie[$categorie->id_annuaire_categorie]['color'] = $categorie->color_code;
            $tabFiches[$categorie->id_annuaire_categorie] = [];
            $sous_categories_in_cat = explode(",",(string) $categorie->sous_categorie_id);
            foreach($sous_categories_in_cat as $sous_categorie){
                $tabFiches[$categorie->id_annuaire_categorie][$sous_categorie] = [];
            }
        }
        foreach($sous_categories as $sous_categorie) {
            $libelleSousCategorie[$sous_categorie->id_annuaire_sous_categorie]['name'] = $sous_categorie->name;
        }

        $fiches = $oAnnuaireFiche->getAll();
        foreach($fiches as $fiche){
            $annuaire_sous_categorie = $fiche->getAnnuaireSousCategorie();
            $tabFiches[$annuaire_sous_categorie->id_annuaire_categorie][$annuaire_sous_categorie->id_annuaire_sous_categorie][] = $fiche;
        }
        $this->view->libelleCategorie = $libelleCategorie;
        $this->view->libelleSousCategorie = $libelleSousCategorie;
        $this->view->fiches = $tabFiches;

        $layout = new Zend_Layout();
        $layout->setLayoutPath(BASE_PATH . "/layouts");
        $layout->setLayout('pdf');
        $layout->content = $this->view->render("annuaire/download-annuaire.phtml");

        if (!$this->hasParam('inline')) {
            header("Content-Disposition: attachment; filename=annuaire.pdf;");
            header("Pragma: public");
            header("Expires: 0");
            header("Content-Type: application/pdf");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

            define('DOMPDF_TEMP_DIR', CACHE_PATH);
            require_once 'dompdf-master/dompdf_config.inc.php';
            $dompdf = new DOMPDF();
            $dompdf->load_html($layout->render());
            $dompdf->render();
            echo $dompdf->output();
        } else {
            echo $layout->render();
        }
	}

    public function downloadCsvAction(){
        $this->_disableLayout();
        $this->_disableView();

        $oAnnuaireCategorie = new Aurel_Table_AnnuaireCategorie();
        $oAnnuaireSousCategorie = new Aurel_Table_AnnuaireSousCategorie();
        $oAnnuaireFiche = new Aurel_Table_AnnuaireFiche();

        $categories = $oAnnuaireCategorie->getAll();
        $sous_categories = $oAnnuaireSousCategorie->getAll();

        $tabFiches = [];
        $libelleCategorie = [];
        $libelleSousCategorie = [];
        foreach($categories as $categorie){
            $libelleCategorie[$categorie->id_annuaire_categorie]['name'] = $categorie->name;
            $libelleCategorie[$categorie->id_annuaire_categorie]['color'] = $categorie->color_code;
            $tabFiches[$categorie->id_annuaire_categorie] = [];
            $sous_categories_in_cat = explode(",",(string) $categorie->sous_categorie_id);
            foreach($sous_categories_in_cat as $sous_categorie){
                $tabFiches[$categorie->id_annuaire_categorie][$sous_categorie] = [];
            }
        }
        foreach($sous_categories as $sous_categorie) {
            $libelleSousCategorie[$sous_categorie->id_annuaire_sous_categorie]['name'] = $sous_categorie->name;
        }

        $fiches = $oAnnuaireFiche->getAll();
        foreach($fiches as $fiche){
            $annuaire_sous_categorie = $fiche->getAnnuaireSousCategorie();
            $tabFiches[$annuaire_sous_categorie->id_annuaire_categorie][$annuaire_sous_categorie->id_annuaire_sous_categorie][] = $fiche;
        }
        $this->view->libelleCategorie = $libelleCategorie;
        $this->view->libelleSousCategorie = $libelleSousCategorie;
        $this->view->fiches = $tabFiches;

        $return = "Catégorie;Sous Catégorie;Nom établissement;Contact Nom;Contact Prénom;Adresse 1;Adresse 2;Code postal;Villes;Tel 1;Tel 2;Email;Site web;Descriptif;Horaires;\r\n";
        foreach($tabFiches as $id_annuaire_categorie => $sous_categories){
            foreach($sous_categories as $id_annuaire_sous_categorie => $fiches){
                foreach($fiches as $fiche){
                    $return .= $libelleCategorie[$id_annuaire_categorie]['name'] . ";";
                    $return .= $libelleSousCategorie[$id_annuaire_sous_categorie]['name'] . ";";
                    $return .= $fiche->nom_etablissement . ";";
                    $return .= $fiche->contact_nom . ";";
                    $return .= $fiche->contact_prenom . ";";
                    $return .= $fiche->adresse_1 . ";";
                    $return .= $fiche->adresse_2 . ";";
                    $return .= $fiche->code_postal . ";";
                    $return .= $fiche->ville . ";";
                    $return .= $fiche->tel_1 . ";";
                    $return .= $fiche->tel_2 . ";";
                    $return .= $fiche->mail . ";";
                    $return .= $fiche->website . ";";
                    $return .= str_replace("\r\n"," ",(string) $fiche->descriptif) . ";";
                    $return .= str_replace("\r\n"," ",(string) $fiche->horaires) . ";";
                    $return .= "\r\n";
                }
            }
        }
        header("Content-type: application/vnd.ms-excel");
        header("Content-disposition: attachment; filename=\"annuaire.csv\"");

        print(utf8_decode($return));
    }
}