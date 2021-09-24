<?php
/**
 * @see Zend_Acl_Assert_Interface
 */
require_once 'Zend/Acl.php';

class Aurel_Acl extends Zend_Acl {
	/**
	 * ROLES
	 */
	const ROLE_GUEST = 'guest';
	const ROLE_MEMBRE = 'membre';
	const ROLE_ADMIN = 'admin';
	
	/** 
	 * PAGES
	 */
	const PAGE_DEFAULT_ERROR = 'default_error';
	const PAGE_DEFAULT_INDEX = 'default_index';
	const PAGE_DEFAULT_COMPTE = 'default_compte';
	const PAGE_DEFAULT_ANNUAIRE = 'default_annuaire';
	const PAGE_DEFAULT_SONDAGE = 'default_sondage';
	const PAGE_ADMIN_ARTICLES = 'admin_articles';
	const PAGE_ADMIN_INDEX = 'admin_index';
	const PAGE_ADMIN_MENU = 'admin_menu';
	const PAGE_ADMIN_PHOTOS = 'admin_photos';
	const PAGE_ADMIN_TERMS = 'admin_terms';
	const PAGE_ADMIN_USERS = 'admin_users';
	const PAGE_ADMIN_ANNUAIRE = 'admin_annuaire';
	const PAGE_ADMIN_SONDAGE = 'admin_sondage';

	/**
	 * RESSOURCES
	 */
	const RESSOURCE_GUEST = 'guest';
	const RESSOURCE_MEMBRE = 'membre';
	const RESSOURCE_ADMIN = 'admin';
	
	const RESSOURCE_ADMIN_REDACTEUR = 1;
	const RESSOURCE_ADMIN_ARTICLES = 2;
//	const RESSOURCE_ADMIN_NEWSLETTER = 4;
//	const RESSOURCE_ADMIN_ANNONCES = 8;
	const RESSOURCE_ADMIN_MEMBRES = 16;
	const RESSOURCE_ADMIN_HEADER = 32;
	const RESSOURCE_ADMIN_FOOTER = 64;
	const RESSOURCE_ADMIN_ACCESSRAPIDE = 128;
	const RESSOURCE_ADMIN_MENUS = 256;
//	const RESSOURCE_ADMIN_ANNUAIRE = 512;
	const RESSOURCE_ADMIN_SONDAGE = 1024;

	var $_array_page = array(
		self::RESSOURCE_ADMIN_REDACTEUR => array(self::PAGE_ADMIN_ARTICLES,self::PAGE_ADMIN_PHOTOS),
		self::RESSOURCE_ADMIN_ARTICLES => array(self::PAGE_ADMIN_ARTICLES,self::PAGE_ADMIN_PHOTOS),
//		self::RESSOURCE_ADMIN_NEWSLETTER => array(self::PAGE_ADMIN_ARTICLES),
//		self::RESSOURCE_ADMIN_ANNONCES => array(self::PAGE_ADMIN_ARTICLES,self::PAGE_ADMIN_PHOTOS),
		self::RESSOURCE_ADMIN_MEMBRES => array(self::PAGE_ADMIN_USERS),
		self::RESSOURCE_ADMIN_HEADER => array(self::PAGE_ADMIN_INDEX),
		self::RESSOURCE_ADMIN_FOOTER => array(self::PAGE_ADMIN_INDEX),
		self::RESSOURCE_ADMIN_ACCESSRAPIDE => array(self::PAGE_ADMIN_INDEX),
		self::RESSOURCE_ADMIN_MENUS => array(self::PAGE_ADMIN_MENU),
//		self::RESSOURCE_ADMIN_ANNUAIRE => array(self::PAGE_ADMIN_ANNUAIRE),
		self::RESSOURCE_ADMIN_SONDAGE => array(self::PAGE_ADMIN_SONDAGE)
	);
	
	var $_array_libelle = array(
		self::RESSOURCE_ADMIN_ARTICLES => "REDACTEUR EN CHEF",
		self::RESSOURCE_ADMIN_REDACTEUR => "REDACTEUR",
		self::RESSOURCE_ADMIN_MENUS => "ADMIN RUBRIQUAGE",
		self::RESSOURCE_ADMIN_HEADER => "ADMIN HEADER",
		self::RESSOURCE_ADMIN_FOOTER => "ADMIN FOOTER",
		self::RESSOURCE_ADMIN_ACCESSRAPIDE => "ADMIN ACCES RAPIDE",
//		self::RESSOURCE_ADMIN_NEWSLETTER => "ADMIN NEWSLETTER",
//		self::RESSOURCE_ADMIN_ANNONCES => "ADMIN ANNONCES",
		self::RESSOURCE_ADMIN_MEMBRES => "ADMIN UTILISATEURS",
//		self::RESSOURCE_ADMIN_ANNUAIRE => "ADMIN ANNUAIRE",
		self::RESSOURCE_ADMIN_SONDAGE => "ADMIN SONDAGE",
	);
	
	var $_array_description = array(
		self::RESSOURCE_ADMIN_ARTICLES => "Création d’articles sur l’ensembles des rubriques du site. Modification, suppression des articles de l’ensemble des rédacteurs.",
		self::RESSOURCE_ADMIN_REDACTEUR => "Création d’articles sur un périmètre restreint de rubriques et/ou de sous rubriques. Modification, suppression des articles du rédacteur seulement",
		self::RESSOURCE_ADMIN_MENUS => "Création/ Modification/Suppression/Ordonnancement des rubriques et sous rubriques",
		self::RESSOURCE_ADMIN_HEADER => "Création, Modification, Suppression du header",
		self::RESSOURCE_ADMIN_FOOTER => "Création, Modification, Suppression du Footer",
		self::RESSOURCE_ADMIN_ACCESSRAPIDE => "Sélection, Modification, Suppression des accès rapides sur la Home page.",
//		self::RESSOURCE_ADMIN_NEWSLETTER => "Créer, envoyer la newsletter",
//		self::RESSOURCE_ADMIN_ANNONCES => "Administrer les annonces",
		self::RESSOURCE_ADMIN_MEMBRES => "Créer, modifier, supprimer les utilisateurs. Définir les droits associés par utilisateur",
//		self::RESSOURCE_ADMIN_ANNUAIRE => "Créer les rubriques et sous rubrique de ANNUAIRE, créer / modifier / supprimer / suspendre 100% des fiches",
		self::RESSOURCE_ADMIN_SONDAGE => "Créer des sondages et les associé à un article",
	);
	
	public static function getArrayLibelleResources(){
		$objet = new self;
		return $objet->_array_libelle;
	}
	public static function getArrayDescriptionResources(){
		$objet = new self;
		return $objet->_array_description;
	}
	
	private function correspPage($id){
		return $this->_array_page[$id];
	}
	
	private function get_class_constants()
	{
		$reflect = new ReflectionClass(get_class($this));
		return $reflect->getConstants();
	}
	
	private function getConstRessources(){
		$constants = $this->get_class_constants();
		$tab = array();
		foreach($constants as $constant=>$value){
			if(strpos($constant,"RESSOURCE_ADMIN_") !== false)
				$tab[$constant] = $value;
		}
		
		return $tab;
	}
	
	public static function getSommeAllRights(){
		$objet = new self;
		$constsResources = $objet->getConstRessources();
		return array_sum($constsResources);
	}
	
	public function __construct(){
		// ADD ROLES
		$this->addRole(new Zend_Acl_Role(self::ROLE_GUEST));
		$this->addRole(new Zend_Acl_Role(self::RESSOURCE_MEMBRE),self::ROLE_GUEST);
		$this->addRole(new Zend_Acl_Role(self::ROLE_ADMIN),self::RESSOURCE_MEMBRE);
		
		// ADD RESOURCES
		$this->addResource(new Zend_Acl_Resource(self::RESSOURCE_GUEST));
		$this->addResource(new Zend_Acl_Resource(self::RESSOURCE_MEMBRE));
		$this->addResource(new Zend_Acl_Resource(self::RESSOURCE_ADMIN));
		
		// ADD RESOURCES PAGES
		$this->addResource(new Zend_Acl_Resource(self::PAGE_DEFAULT_ERROR));
		$this->addResource(new Zend_Acl_Resource(self::PAGE_DEFAULT_INDEX));
		$this->addResource(new Zend_Acl_Resource(self::PAGE_DEFAULT_COMPTE));
		$this->addResource(new Zend_Acl_Resource(self::PAGE_DEFAULT_ANNUAIRE));
		$this->addResource(new Zend_Acl_Resource(self::PAGE_DEFAULT_SONDAGE));
		$this->addResource(new Zend_Acl_Resource(self::PAGE_ADMIN_ARTICLES));
		$this->addResource(new Zend_Acl_Resource(self::PAGE_ADMIN_INDEX));
		$this->addResource(new Zend_Acl_Resource(self::PAGE_ADMIN_MENU));
		$this->addResource(new Zend_Acl_Resource(self::PAGE_ADMIN_PHOTOS));
		$this->addResource(new Zend_Acl_Resource(self::PAGE_ADMIN_TERMS));
		$this->addResource(new Zend_Acl_Resource(self::PAGE_ADMIN_USERS));
		$this->addResource(new Zend_Acl_Resource(self::PAGE_ADMIN_ANNUAIRE));
		$this->addResource(new Zend_Acl_Resource(self::PAGE_ADMIN_SONDAGE));

		// RIGHTS FOR GUEST
		$this->allow(self::ROLE_GUEST,self::RESSOURCE_GUEST);
		$this->allow(self::ROLE_GUEST,self::PAGE_DEFAULT_ERROR);
		$this->allow(self::ROLE_GUEST,self::PAGE_DEFAULT_INDEX);
		$this->allow(self::ROLE_GUEST,self::PAGE_DEFAULT_ANNUAIRE);
		$this->allow(self::ROLE_GUEST,self::PAGE_DEFAULT_SONDAGE);
		$this->allow(self::ROLE_GUEST,self::PAGE_ADMIN_INDEX,array('login','index','logout'));
		$this->allow(self::ROLE_GUEST,self::PAGE_DEFAULT_COMPTE,array('login','l','logout', 'index','register','verif-element-register','rappel','rappel-other','desinscription','passoublie','register-advice'));
		
		// RIGHTS FOR CONNECTED
		$this->allow(self::ROLE_MEMBRE,self::RESSOURCE_MEMBRE);
		$this->allow(self::ROLE_MEMBRE,self::PAGE_DEFAULT_COMPTE);
		
		// RIGHTS FOR ADMIN
		$this->allow(self::ROLE_ADMIN,self::RESSOURCE_ADMIN);
		
		// ADD CUSTOM RIGHTS
		$constsResources = $this->getConstRessources();
		foreach($constsResources as $resource){
			$this->addResource(new Zend_Acl_Resource($resource));
		}

        if(Zend_Registry::isRegistered('user')){
            $user = Zend_Registry::get('user');
            if($user != "guest" && $user->type != 0){
                $array = Aurel_Table_User::decompose($user->type);
                $this->addRole(new Zend_Acl_Role($user->type),self::ROLE_ADMIN);

                foreach($array as $right){
                    if($this->has($right)) {
                        $this->allow($user->type, $right);
                        $pages = $this->correspPage($right);
                        foreach ($pages as $page) {
                            if ($this->has($page))
                                $this->allow($user->type, $page);
                        }
                    }
                }
            }
        }
	}
}