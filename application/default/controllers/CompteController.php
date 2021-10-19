<?php

/**
 * CompteController - The default controller class
 * 
 * @author
 * @version 
 */
class CompteController extends Aurel_Controller_Abstract
{
    /**
     * Undocumented function
     *
     * @return boolean
     */
    public function isSecure()
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || $_SERVER['SERVER_PORT'] == 443;
    }

    /**
     * Pre-dispatch routines
     *
     * @return void
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $menu_ini = new Zend_Config_ini(CONFIG_PATH . "/menu_compte.ini");
        $navigation = new Zend_Navigation($menu_ini);

        if ($this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_MEMBRE)) {
            $oAnnuaireFiche = new Aurel_Table_AnnuaireFiche();
            $fichedetenue = $oAnnuaireFiche->getByProprietaire($this->_getUser());

            $this->view->fiche = $fichedetenue;

            foreach ($navigation as $nav) {
                if ($nav->action == 'annuaire' && !$fichedetenue) {
                    $nav->setVisible(false);
                }
            }
        }


        $this->view->navigation = $navigation;
    }

    /**
     * 
     */
    public function previewAction()
    {
        $this->_disableLayout();
        $this->_disableView();
        $subject = $this->_config->subject_invitation;
        $body = $this->_config->body_invitation;

        $inviteur = $this->_getUser();

        $oInvitation = new Aurel_Table_Invitation();
        $oUser = new Aurel_Table_User();

        $id_invitation = $this->getParam('invitation');
        $invitation = $oInvitation->getById($id_invitation);

        if ($invitation) {
            $subject = $this->_config->subject_reinvitation;
            $body = $this->_config->body_reinvitation;
        }

        $message = $this->getParam('message', $invitation ? $invitation->message : null);

        $link = "#";
        $replacement = [
            '#INVITEUR_PRENOM#' => $inviteur->firstname,
            '#INVITEUR_NOM#' => $inviteur->lastname,
            '#INVITEUR_SOCIETE#' => $inviteur->societe,
            '#INVITEUR_EMAIL#' => $inviteur->email,
            '#INVITEUR_FONCTION#' => $inviteur->fonction,
            '#INVITE_EMAIL#' => "<i>emails</i>",
            '#INVITE_MESSAGE#' => $message,
            '#LIEN#' => $link
        ];

        foreach ($replacement as $key => $value) {
            $subject = str_replace($key, $value, $subject);
            $body = str_replace($key, $value, $body);
        }


        $mailSend = new Aurel_Mailer("utf-8");
        $mailSend->setBodyHtmlWithDesign($body, $subject)
            ->setFrom('contact@btob-adidas.com', $this->_config->from_mail)
            ->setSubject($subject);

        echo $mailSend->getHtml();
    }

    public function indexAction()
    {
        if (!$this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_MEMBRE)) {
            $this->redirect("/compte/login");
        }

        $formData = $this->_request->getPost();
        if ($this->_request->isPost()) {
            $this->_disableLayout();
            $this->_disableView();

            $continue = true;
            $validate = new Zend_Validate_EmailAddress();
            if (empty($formData["email"])) {
                $continue = false;
                $return['errors'][] = 'email';
                $return['message']['email'] = "Ce champ est obligatoire.";
            } elseif (!$validate->isValid($formData["email"])) {
                $continue = false;
                $return['errors'][] = 'email';
                $return['message']['email'] = "Veuillez saisir votre adresse e-mail complète, y compris le signe @.";
            } else {
                $oUser = new Aurel_Table_User();
                $user = $oUser->getByEmail($formData["email"]);
                if (($this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_MEMBRE) && $user && $this->_getUser()->id_user != $user->id_user) || (!$this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_MEMBRE) && $user)) {
                    $continue = false;
                    $return['errors'][] = 'email';
                    $return['message']['email'] = "Il existe déjà un utilisateur avec l'adresse e-mail indiquée.";
                }
            }
            if (trim($formData['firstname']) == "") {
                $continue = false;
                $return['errors'][] = 'firstname';
                $return['message']['firstname'] = "Ce champ est obligatoire.";
            }
            if (trim($formData['lastname']) == "") {
                $continue = false;
                $return['errors'][] = 'lastname';
                $return['message']['lastname'] = "Ce champ est obligatoire.";
            }
            if (trim($formData['password']) != "" || trim($formData['password2']) != "") {
                if (trim($formData['password']) != trim($formData['password2'])) {
                    $continue = false;
                    $return['errors'][] = 'password';
                    $return['errors'][] = 'password2';
                    $return['message']['password2'] = "Les 2 mots de passes ne sont pas identiques";
                }
            }

            $user = $this->_getUser();
            $return['code'] = 'ko';
            if ($continue) {
                $user->lastname = stripslashes($formData["lastname"]);
                $user->firstname = stripslashes($formData["firstname"]);
                $user->email = stripslashes($formData["email"]);
                if (isset($formData["societe"]))
                    $user->societe = stripslashes($formData["societe"]);
                if (isset($formData["fonction"]))
                    $user->fonction = stripslashes($formData["fonction"]);

                $user->id_user_modification = $this->_getUser()->id_user;
                $user->date_modification = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);

                if ($formData["password"] == $formData["password2"] && trim($formData["password"]) != "") {
                    $user->password = stripslashes($formData["password"]);
                }
                $user->save();

                $return['code'] = 'ok';
            }

            echo json_encode($return);
        }
    }

    public function loginAction()
    {
        if ($this->hasParam('pageForbidden'))
            $this->view->message = "Vous devez vous connecter pour accéder à cette page";

        $auth = Zend_Auth::getInstance();

        if ($this->hasParam('url_redirect'))
            $url_redirect = urldecode($this->getParam('url_redirect'));
        else
            $url_redirect = $this->view->url(array(), 'default', true);

        $this->view->emailLogin = $this->getParam('emailLogin');
        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();
            $db = Zend_Registry::get('db');

            $validate = new Zend_Validate_EmailAddress();
            if ($validate->isValid($formData['username'])) {
                $authAdapter = new Zend_Auth_Adapter_DbTable($db, 'user', 'email', 'password');
            } else {
                $authAdapter = new Zend_Auth_Adapter_DbTable($db, 'user', 'name', 'password');
            }

            if (!empty($formData['username']) && !empty($formData['pass'])) {
                $authAdapter->setIdentity($formData['username'])
                    ->setCredential($formData['pass']);
                $result = $auth->authenticate($authAdapter);
                if ($result->isValid()) {
                    $id = $authAdapter->getResultRowObject('id_user');
                    $oUser = new Aurel_Table_User();
                    $user = $oUser->getById($id->id_user);

                    $bootstrap = $this->getInvokeArg('bootstrap');
                    $appinidata = $bootstrap->getOptions();
                    $cookie_domain = '';

                    if ($user->status == Aurel_Table_User::STATUS_ACTIF) {
                        $auth->getStorage()->write($id->id_user);
                        $type_connexion = new Zend_Session_Namespace('type_connexion');
                        $type_connexion->type = 'membre';

                        if (isset($formData["remember"]) && $formData["remember"] == "1") {
                            setcookie(
                                'Auth',
                                $user->id_user,
                                time() + 3600 * 24 * 365,
                                '/',
                                $cookie_domain,
                                $this->isSecure(),
                                true
                            );
                        }

                        $user->date_last_connexion = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
                        $user->ip_last_connexion = $_SERVER["REMOTE_ADDR"];
                        $user->save();

                        $this->view->message = "Vous êtes maintenant connecté";
                        $this->view->displayMessage = true;
                        $this->view->error = false;

                        $sessionAnnonce = new Zend_Session_Namespace('annonce');
                        if ($this->hasParam('after') && $this->getParam('after') == 'valid-annonce' && isset($sessionAnnonce->formData))
                            $this->redirect($this->view->url(array('action' => 'add-annonce', 'valid' => true), 'action', true));
                        elseif ($this->hasParam('after') && $this->getParam('after') == 'valid-participation' && isset($sessionAnnonce->formData))
                            $this->redirect($this->view->url(array('action' => 'participer', 'valid' => true), 'action', true));
                        elseif ($this->hasParam('after') && $this->getParam('after') == 'valid-annuaire' && isset($sessionAnnonce->formData)) {
                            $url_validation = urldecode($this->getParam('url_validation'));
                            $this->redirect($url_validation);
                        } else
                            $this->redirect($url_redirect);
                    } else {
                        $this->view->message = "Utilisateur inactif";
                        $this->view->displayMessage = true;
                        $this->view->error = true;
                    }
                } else {
                    $this->view->message = "Utilisateur ou mot de passe incorrect";
                    $this->view->displayMessage = true;
                    $this->view->error = true;
                }
            } else {
                $this->view->message = "Utilisateur ou mot de passe incorrect";
                $this->view->displayMessage = true;
                $this->view->error = true;
            }
        } elseif ($this->_isAjax()) {
            $this->render('login-modal');
        }
    }

    public function lAction()
    {
        $this->_disableView();

        $auth = Zend_Auth::getInstance();

        if ($this->hasParam('url_redirect'))
            $url_redirect = urldecode($this->getParam('url_redirect'));
        else
            $url_redirect = $this->view->url(array(), 'default', true);

        if (!$this->hasParam('h')) {
            $this->redirect('/');
        }

        $hash = $this->getParam('h');

        $connect = Aurel_Encryptor::getInstance();
        $connect->setEncryptedValue($hash);
        $connect->decrypt();

        if ($connect->getDecryptedValue()) {
            if ($connect->isExpired()) {

                $session = new Zend_Session_Namespace('inscription');
                $session->message = "Lien d'auto-connexion expiré";
                $session->setExpirationHops(1);

                $this->redirect($url_redirect);
            } else {
                $email = $connect->getDecryptedValue();
                $oUser = new Aurel_Table_User();
                $user = $oUser->getByEmail($email);
                if ($user->status == Aurel_Table_User::STATUS_ACTIF) {
                    $auth->getStorage()->write($user->id_user);
                    $type_connexion = new Zend_Session_Namespace('type_connexion');
                    $type_connexion->type = 'membre';

                    $user->date_last_connexion = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
                    $user->ip_last_connexion = $_SERVER["REMOTE_ADDR"];
                    $user->save();

                    $this->view->message = "Vous êtes maintenant connecté";
                    $this->view->displayMessage = true;
                    $this->view->error = false;

                    $session = new Zend_Session_Namespace('inscription');
                    $session->message = "Vous êtes maintenant connecté";
                    $session->setExpirationHops(1);

                    $this->redirect($url_redirect);
                }
            }
        } else {

            $session = new Zend_Session_Namespace('inscription');
            $session->message = "Lien d'auto-connexion invalide";
            $session->setExpirationHops(1);

            $this->redirect($url_redirect);
        }
    }

    public function logoutAction()
    {
        $this->_disableLayout();
        $this->_disableView();

        $auth = Zend_Auth::getInstance();
        $auth->clearIdentity();

        $type_connexion = new Zend_Session_Namespace('type_connexion');
        $type_connexion->unsetAll();

        $bootstrap = $this->getInvokeArg('bootstrap');
        $appinidata = $bootstrap->getOptions();
        $cookie_domain = '';

        setcookie(
            'Auth',
            '',
            time() - 3600,
            '/',
            $cookie_domain,
            $this->isSecure(),
            true
        );

        setcookie(
            'popup',
            '',
            time() - 3600,
            '/',
            $cookie_domain,
            $this->isSecure(),
            true
        );
        setcookie(
            'access_code_ok',
            '',
            time() - 3600,
            '/',
            $cookie_domain,
            $this->isSecure(),
            true
        );

        session_destroy();

        sleep(2);

        if ($this->hasParam('url_redirect'))
            $url_redirect = urldecode($this->getParam('url_redirect'));
        else
            $url_redirect = $this->view->url(array(), 'default', true);

        $this->redirect($url_redirect);
    }

    public function newsletterAction()
    {
        $formData = $this->_request->getPost();
        if ($this->_request->isPost()) {
            $this->_disableLayout();
            $this->_disableView();
            if (isset($formData['newsletter'])) {
                $user = $this->_getUser();

                $user->newsletter = $formData['newsletter'];

                $db = Zend_Registry::get('db');

                $select = "UPDATE `pronoteam_adidas`.`utilisateurs`
                SET recoimail = {$formData['newsletter']}, recoimailforum = {$formData['newsletter']}
                where `email` = '{$user->email}'";

                try {
                    $result = $db->query($select);
                } catch (Exception $e) {
                    
                }

                $user->save();
            }
        }
    }

    public function removeAuthAction()
    {
        $this->_disableLayout();
        $this->_disableView();

        $bootstrap = $this->getInvokeArg('bootstrap');
        $appinidata = $bootstrap->getOptions();
        $cookie_domain = '';

        setcookie(
            'Auth',
            '',
            time() - 2600,
            '/',
            $cookie_domain,
            $this->isSecure(),
            true
        );
        setcookie(
            'access_code_ok',
            1,
            time() - 3600,
            '/',
            $cookie_domain,
            $this->isSecure(),
            true
        );
    }

    public function annonceAction()
    {
        $formData = $this->_request->getPost();
        if ($this->_request->isPost()) {
            $this->_disableLayout();
            $this->_disableView();

            $user = $this->_getUser();
            $return['code'] = 'ko';

            if (isset($formData["tel"]) && $formData["tel"] != "")
                $user->tel = stripslashes($formData["tel"]);
            else
                $user->tel = null;

            $user->masque_tel = $formData["masque_tel"];
            $user->regles = $formData["regles"];

            $user->id_user_modification = $this->_getUser()->id_user;
            $user->date_modification = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);

            $user->save();

            $return['code'] = 'ok';

            echo json_encode($return);
        }
    }

    public function annoncesAction()
    {
        $oAnnonces = new Aurel_Table_Article();

        $annonces = $oAnnonces->getAllAnnoncesByUser($this->_getUser()->id_user);

        $tabAnnonceAttente = array();
        $tabAnnonceEnCours = array();
        $tabAnnonceArchives = array();
        $dateArchive = Aurel_Date::now()->subDay($this->_config->daysArchiveAnnonce - 1)->setTime("00:00");
        $dateRefused = Aurel_Date::now()->subDay(6)->setTime("00:00");

        foreach ($annonces as $annonce) {
            if ($annonce->state_annonce == Aurel_Table_Article::STATE_ANNONCE_WAITING) {
                $tabAnnonceAttente[] = $annonce;
            } elseif ($annonce->state_annonce == Aurel_Table_Article::STATE_ANNONCE_REFUSED && $annonce->date_validation > $dateRefused->get(Aurel_Date::MYSQL_DATETIME)) {
                $tabAnnonceAttente[] = $annonce;
            } elseif ($annonce->state_annonce != Aurel_Table_Article::STATE_ANNONCE_REFUSED && $annonce->date_validation > $dateArchive->get(Aurel_Date::MYSQL_DATETIME)) {
                $tabAnnonceEnCours[] = $annonce;
            } else {
                $tabAnnonceArchives[] = $annonce;
            }
        }
        $this->view->annonces = $tabAnnonceEnCours;
        $this->view->annoncesArchives = $tabAnnonceArchives;
        $this->view->annoncesAttente = $tabAnnonceAttente;

        $oMenu = new Aurel_Table_Menu();
        $oSousMenu = new Aurel_Table_SousMenu();
        $oArticle = new Aurel_Table_Article();

        $menu_annonce = $oMenu->getMenuAnnonces();

        if ($menu_annonce) {
            $sous_menus = $oSousMenu->getAllByMenu($menu_annonce->id_menu);

            $tab = array();
            foreach ($sous_menus as $sous) {
                $tab[$sous->id_sous_menu] = $sous->name;
            }

            $this->view->tabCategories = $tab;
        }
    }

    public function verifElementRegisterAction()
    {
        $this->_disableLayout();
        $this->_disableView();

        $input = $this->getParam('input');
        $value = $this->getParam('value');

        $return = array();
        $return['error'] = false;

        switch ($input) {
            case 'email':
                $return['obligatoire'] = true;
                $validate = new Zend_Validate_EmailAddress();
                if (empty($value)) {
                    $return['error'] = true;
                    $return['message'] = "Ce champ est obligatoire.";
                } elseif (!$validate->isValid($value)) {
                    $return['error'] = true;
                    $return['message'] = "Veuillez saisir votre adresse e-mail complète, y compris le signe @.";
                } else {
                    $oUser = new Aurel_Table_User();
                    $user = $oUser->getByEmail($value);
                    if (($this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_MEMBRE) && $user && $this->_getUser()->id_user != $user->id_user) || (!$this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_MEMBRE) && $user)) {
                        $hash = $this->getParam('hash');
                        $userCompare = null;
                        if ($hash) {
                            $userCompare = $oUser->findcomHash($hash);
                        }
                        if ($userCompare && $user != $userCompare || !$userCompare) {
                            $return['error'] = true;
                            $return['message'] = "Un compte est déjà créé avec cette adresse email. <a class='connect btn btn-danger btn-xs' href='" . $this->view->url(array('action' => 'login'), 'compte', true) . "'>Connectez-vous</a>";
                        }
                    }
                }
                break;
            case 'email2':
                $return['obligatoire'] = true;
                $validate = new Zend_Validate_EmailAddress();
                if (empty($value)) {
                    $return['error'] = true;
                    $return['message'] = "Ce champ est obligatoire.";
                } elseif (!$validate->isValid($value)) {
                    $return['error'] = true;
                    $return['message'] = "Veuillez saisir votre adresse e-mail complète, y compris le signe @.";
                } elseif ($value != $this->getParam('email')) {
                    $return['error'] = true;
                    $return['message'] = "Les 2 adresses email ne sont pas identiques";
                } else {
                    $oUser = new Aurel_Table_User();
                    $user = $oUser->getByEmail($value);
                    if (($this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_MEMBRE) && $user && $this->_getUser()->id_user != $user->id_user) || (!$this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_MEMBRE) && $user)) {
                        $return['error'] = true;
                    }
                }
                break;
            case 'password2':
                $return['obligatoire'] = true;
                if (empty($value)) {
                    $return['error'] = true;
                    $return['message'] = "Ce champ est obligatoire.";
                } elseif ($value != $this->getParam('password')) {
                    $return['error'] = true;
                    $return['message'] = "Les 2 mots de passent ne sont pas identique.";
                }

                break;
            case 'tel':
                $return['obligatoire'] = false;

                break;
            default:
                $return['obligatoire'] = true;
                if (empty($value)) {
                    $return['error'] = true;
                    $return['message'] = "Ce champ est obligatoire.";
                }
                break;
        }

        echo json_encode($return);
    }

    public function registerAction()
    {
        Zend_Auth::getInstance()->clearIdentity();
        $this->view->show_popup = false;
        $this->view->headMeta()->appendName('robots', 'noindex,nofollow');
        $this->view->popup = $this->getParam("popup");
        $this->view->emailLogin = $this->getParam('emailLogin');

        $oUser = new Aurel_Table_User();
        $oInvitation = new Aurel_Table_Invitation();


        if ($this->getParam('after') == 'valid-annuaire') {
            $this->view->adminAnnuaire = true;
        }
        $new_user = null;
        $id = null;
        if ($this->hasParam('invitation')) {

            $invitation = $oInvitation->findcomHash($this->getParam('invitation'));

            if ($invitation) {
                if ($invitation->state == Aurel_Table_Invitation::TYPE_VALIDATED && $invitation->id_user_invited) {
                    $new_user = $oUser->getById($invitation->id_user_invited);
                    $this->view->user = $new_user;
                    $auth = Zend_Auth::getInstance();
                    $auth->getStorage()->write($new_user->id_user);
                    if ($this->hasParam('url_redirect'))
                        $url_redirect = urldecode($this->getParam('url_redirect'));
                    else
                        $url_redirect = '/';
                    $this->redirect($url_redirect);
                } else {
                    $new_user = $oUser->createRow();
                    $new_user->email = $invitation->email;

                    $this->view->user = $new_user;
                }
            }
        }
        if ($this->hasParam('hash')) {
            $new_user = $oUser->findcomHash($this->getParam('hash'));

            if ($new_user) {
                $this->view->user = $new_user;

                if ($new_user->password && trim($new_user->password) != '') {
                    $auth = Zend_Auth::getInstance();
                    $auth->getStorage()->write($new_user->id_user);
                    if ($this->hasParam('url_redirect'))
                        $url_redirect = urldecode($this->getParam('url_redirect'));
                    else
                        $url_redirect = '/';
                    $this->redirect($url_redirect);
                }
            }
        }
        if ($this->hasParam('email')) {
            $new_user = $oUser->getByEmail($this->getParam('email'));

            if ($new_user) {
                Zend_Auth::getInstance()->clearIdentity();
                $this->view->user = $new_user;
                if ($new_user->password && trim($new_user->password) != '') {
                    $auth = Zend_Auth::getInstance();
                    $auth->getStorage()->write($new_user->id_user);

                    if ($this->hasParam('url_redirect'))
                        $url_redirect = urldecode($this->getParam('url_redirect'));
                    else
                        $url_redirect = '/';
                    $this->redirect($url_redirect);
                }
            } else {
                $new_user = $oUser->createRow();
                $new_user->email = $this->getParam('email');
                $new_user->firstname = $this->getParam('firstname');
                $new_user->lastname = $this->getParam('lastname');
                $new_user->societe = $this->getParam('societe');
                $new_user->fonction = $this->getParam('fonction');
            }
        }
        $this->view->user = $new_user;

        $formData = $this->_request->getPost();
        if ($this->_request->isPost()) {
            $this->_disableLayout();
            $this->_disableView();

            $continue = true;
            $validate = new Zend_Validate_EmailAddress();
            if (!$validate->isValid($formData["email"])) {
                $continue = false;
                $return['errors'][] = 'email';
                $return['message']['email'] = "Veuillez saisir votre adresse e-mail complète, y compris le signe @.";
            }
            $user = $oUser->getByEmail($formData["email"]);
            if ($user && !$new_user || $user && $new_user && $user->id_user != $new_user->id_user) {
                $continue = false;
                $return['errors'][] = 'email';
                $return['message']['email'] = "Un compte est déjà créé avec cette adresse email. <a class='connect btn btn-danger btn-xs' href='" . $this->view->url(array('action' => 'login'), 'compte', true) . "'>Connectez-vous</a>";
            }

            if (trim($formData['password']) == "" || trim($formData['password2']) == "") {
                $continue = false;
                $return['errors'][] = 'password';
                $return['errors'][] = 'password2';
                if (trim($formData['password']) == "")
                    $return['message']['password'] = "Ce champ est obligatoire";
                if (trim($formData['password2']) == "")
                    $return['message']['password2'] = "Ce champ est obligatoire";
            }
            if (trim($formData['societe']) == "") {
                $continue = false;
                $return['errors'][] = 'societe';
                $return['message']['societe'] = "Ce champ est obligatoire";
            }
            if (trim($formData['fonction']) == "") {
                $continue = false;
                $return['errors'][] = 'fonction';
                $return['message']['fonction'] = "Ce champ est obligatoire";
            }
            if (trim($formData['lastname']) == "") {
                $continue = false;
                $return['errors'][] = 'lastname';
                $return['message']['lastname'] = "Ce champ est obligatoire";
            }
            if (trim($formData['firstname']) == "") {
                $continue = false;
                $return['errors'][] = 'firstname';
                $return['message']['firstname'] = "Ce champ est obligatoire";
            }
            if (trim($formData['password']) != "" || trim($formData['password2']) != "") {
                if (trim($formData['password']) != trim($formData['password2'])) {
                    $continue = false;
                    $return['errors'][] = 'password';
                    $return['errors'][] = 'password2';
                    $return['message']['password2'] = "Les 2 mots de passent ne sont pas identique.";
                }
            }
            $return['code'] = 'ko';
            if ($continue) {
                $auth = Zend_Auth::getInstance();
                if ($new_user) {
                    $user = $new_user;
                } else {
                    $user = $oUser->createRow();
                }

                $user->name = '';
                $user->lastname = stripslashes($formData["lastname"]);
                $user->firstname = stripslashes($formData["firstname"]);
                $user->email = stripslashes($formData["email"]);
                $user->fonction = stripslashes($formData["fonction"]);
                $user->societe = stripslashes($formData["societe"]);

                $user->masque_tel = $formData["masque_tel"];
                $user->newsletter = $formData["newsletter"];
                $user->regles = $formData["regles"];
                $user->type = 0;
                $user->directeur = 0;
                $user->menus_redacteur = null;

                $user->date_creation = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
                $user->date_modification = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);

                if ($formData["password"] == $formData["password2"] && trim($formData["password"]) != "") {
                    $user->password = stripslashes($formData["password"]);
                }
                $user->hash = md5($formData["email"]);
                $user->save();

                $user->id_user_modification = $user->id_user;
                $user->id_user_creation = $user->id_user;

                $user->date_last_connexion = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
                $user->ip_last_connexion = $_SERVER["REMOTE_ADDR"];
                $user->save();

                $auth->getStorage()->write($user->id_user);

                if (isset($invitation) && $invitation) {
                    $invitation->id_user_invited = $user->id_user;
                    $invitation->state = Aurel_Table_Invitation::TYPE_VALIDATED;
                    $invitation->date_inscription = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
                    $invitation->save();
                }

                $invitations = $oInvitation->getByMail($user->email);
                foreach ($invitations as $invitation) {
                    $invitation->id_user_invited = $user->id_user;
                    $invitation->state = Aurel_Table_Invitation::TYPE_VALIDATED;
                    $invitation->date_inscription = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
                    $invitation->save();
                }

                $session = new Zend_Session_Namespace('inscription');
                $session->inscription = 1;
                $session->setExpirationHops(1);

                $return['code'] = 'ok';
                $sessionAnnonce = new Zend_Session_Namespace('annonce');
                if ($this->hasParam('after') && $this->getParam('after') == 'valid-annonce' && isset($sessionAnnonce->formData))
                    $url_redirect = $this->view->url(array('action' => 'add-annonce', 'valid' => true), 'action', true);
                elseif ($this->hasParam('after') && $this->getParam('after') == 'valid-participation' && isset($sessionAnnonce->formData))
                    $this->redirect($this->view->url(array('action' => 'participer', 'valid' => true), 'action', true));
                elseif ($this->hasParam('after') && $this->getParam('after') == 'valid-annuaire' && isset($sessionAnnonce->formData)) {
                    $url_redirect = urldecode($this->getParam('url_redirect'));
                } elseif ($this->hasParam('url_redirect'))
                    $url_redirect = urldecode($this->getParam('url_redirect'));
                else
                    $url_redirect = $this->view->url(array(), 'default', true);
                $return['url_redirect'] = $url_redirect;
            }

            echo json_encode($return);
        } elseif ($this->_isAjax()) {
            $this->render('register-modal');
        }
    }

    /**
     * @return void
     */
    public function registerAdviceAction()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');
        $appinidata = $bootstrap->getOptions();
        $cookie_domain = '';

        setcookie(
            'popup',
            1,
            time() + 3600 * 24 * 30,
            '/',
            $cookie_domain,
            $this->isSecure(),
            true
        );
    }

    public function registerOkAction()
    {
    }

    public function rappelAction()
    {
        $this->_disableLayout();
        $this->_disableView();

        $bootstrap = $this->getInvokeArg('bootstrap');
        $appinidata = $bootstrap->getOptions();
        $cookie_domain = '';

        setcookie(
            'popup',
            1,
            time() + 3600 * 24 * 30,
            '/',
            $cookie_domain,
            $this->isSecure(),
            true
        );
    }

    public function rappelOtherAction()
    {
        $this->_disableLayout();
        $this->_disableView();

        $bootstrap = $this->getInvokeArg('bootstrap');
        $appinidata = $bootstrap->getOptions();
        $cookie_domain = '';

        /* setcookie(
          'popup_other',
          1,
          time() + 3600*24*3,
          '/',
          $cookie_domain
          ); */
    }

    /**
     * Desinscription
     *
     * @return void
     */
    public function desinscriptionAction()
    {
        $unsubscribe = $this->getParam('unsubscribe');

        $oUser = new Aurel_Table_User();
        $user = $oUser->getByEncodedEmail($unsubscribe);

        $this->view->userToUnsubscribe = $user;

        $formData = $this->_request->getPost();
        if ($formData) {
            if (isset($formData['email']) && $formData['email'] == $user->email) {
                $user->newsletter = 0;
                $user->save();

                $this->render('desinscription-ok');
            }
        }
    }

    /**
     * Reservations
     *
     * @return void
     */
    public function reservationsAction()
    {
        if ($this->hasParam('open_modal') && $this->getParam('open_modal') == '1' && $this->hasParam('id_article')) {
            $id_article = $this->getParam('id_article');
            $this->view->modal_url = $this->view->url(array('action' => 'participer', 'id_article' => $id_article, 'url_retour' => urlencode($this->view->url(array('open_modal' => null, 'id_article' => null)))), 'action', true);
        }
        $sessionAnnonce = new Zend_Session_Namespace('annonce');
        $this->view->valideParticipation = $sessionAnnonce->valideParticipation;

        $oArticle = new Aurel_Table_Article();
        $oInscription = new Aurel_Table_Inscription();
        $oInscriptionHasUser = new Aurel_Table_InscriptionHasUser();

        $inscriptions = $oInscription->getByUser($this->_getUser()->id_user);

        $dateToday = Aurel_Date::now();
        $tabSortie = array();
        $tabSortieHistorique = array();
        foreach ($inscriptions as $inscription) {
            $start_date = new Aurel_Date($inscription->start_date, Aurel_Date::MYSQL_DATE);

            if ($dateToday->get() <= $start_date->get()) {
                $tabSortie[$inscription->id_article]['start_date'] = $start_date;
                $tabSortie[$inscription->id_article]['basename'] = $inscription->basename;
                $tabSortie[$inscription->id_article]['title'] = $inscription->title;
                $tabSortie[$inscription->id_article]['comment'] = $inscription->comment;
                $tabSortie[$inscription->id_article]['date_inscription'] = new Aurel_Date($inscription->date_inscription, Aurel_Date::MYSQL_DATETIME);

                if (!isset($tabSortie[$inscription->id_article]['total']))
                    $tabSortie[$inscription->id_article]['total'] = $inscription->quantite;
                else
                    $tabSortie[$inscription->id_article]['total'] += $inscription->quantite;
            } else {
                $tabSortieHistorique[$inscription->id_article]['start_date'] = $start_date;
                $tabSortieHistorique[$inscription->id_article]['basename'] = $inscription->basename;
                $tabSortieHistorique[$inscription->id_article]['title'] = $inscription->title;
                $tabSortieHistorique[$inscription->id_article]['comment'] = $inscription->comment;
                $tabSortieHistorique[$inscription->id_article]['date_inscription'] = new Aurel_Date($inscription->date_inscription, Aurel_Date::MYSQL_DATETIME);

                if (!isset($tabSortieHistorique[$inscription->id_article]['total']))
                    $tabSortieHistorique[$inscription->id_article]['total'] = $inscription->quantite;
                else
                    $tabSortieHistorique[$inscription->id_article]['total'] += $inscription->quantite;
            }
        }
        $this->view->tabSortie = $tabSortie;
        $this->view->tabSortieHistorique = $tabSortieHistorique;
    }

    /**
     * Mot de passe oublié
     *
     * @return void
     */
    public function passoublieAction()
    {
        $oUser = new Aurel_Table_User();


        $mail = new Aurel_Mailer("utf-8");
        $ll = $mail->getDefaultTransport();

        $this->view->FormPassOublie = true;
        $this->view->FormReinitPass = false;

        if ($this->hasParam('comHash')) {
            $encodedEmail = $this->getParam('comHash');
            $user = $oUser->getByEncodedEmail($encodedEmail);

            if ($user) {
                $this->view->FormPassOublie = false;
                $this->view->FormReinitPass = true;
            } else {
                $this->view->message = 'Utilisateur introuvable dans la base de données';
            }
        }

        if ($this->_request->isPost()) {
            $formData = $this->_request->getPost();

            if ($this->hasParam('email') && !empty($formData['email'])) {
                $user = $oUser->getByEmail($formData['email']);

                if ($user) {
                    $url = 'http://' . $_SERVER['HTTP_HOST'] . "/compte/passoublie?comHash=" . md5($user->email);
                    $body = "<h4>Oubli de votre mot de passe</h4>" . "\n\t" .
                        "Veuillez suivre ce lien pour modifier votre mot de passe : <a href='$url'>$url</a><br/><br/>" . "\n\t" .
                        "En cas de difficultés, allez à l'adresse suivante : $url<br/><br/><br/>" . "\n\t";

                    $mail = new Aurel_Mailer("utf-8");
                    $mail->setBodyHtmlWithDesign($body, "Mot de passe oublié")
                        ->setSubject("Mot de passe oublié")
                        ->addTo($user->email);

                    try {
                        $mail->send();
                    } catch (Exception $e) {
                        echo $body;
                        var_dump($e);
                    }
                    $this->view->message = 'Vous avez reçu un mail avec le lien qui vous permettra de modifier votre mot de passe.';

                    $this->view->FormPassOublie = false;
                } else {
                    $this->view->message = 'Email introuvable dans la base de données';
                }
            } elseif ($this->hasParam('comHash')) {
                $encodedEmail = $this->getParam('comHash');

                $oUser = new Aurel_Table_User();
                $user = $oUser->getByEncodedEmail($encodedEmail);

                if (empty($formData['password']) || empty($formData['password2'])) {
                    $this->view->message = 'Merci de saisir un nouveau mot de passe (2 fois par sécurité)';
                } elseif ($formData['password'] != $formData['password2']) {
                    $this->view->message = 'Les 2 mots de passes ne correspondent pas';
                } elseif ($formData['password'] == $formData['password2']) {
                    $user->password = stripslashes($formData["password"]);
                    $user->save();

                    $this->view->connectButton = true;
                    $this->view->FormReinitPass = false;
                    $this->view->message = 'Votre mot de passe a été modifié';
                }
            }
        }
    }

    /**
     *
     */
    public function annuaireAction()
    {
    }

    public function invitationsAction()
    {
        $oInvitation = new Aurel_Table_Invitation();

        $invitations = $oInvitation->getByUser($this->_getUser()->id_user);
        $ready = $oInvitation->getReadyToSendByUser($this->_getUser()->id_user);

        $this->view->invitations = $invitations;
        $this->view->ready = $ready->count() > 0;
    }

    public function checkReadyAction()
    {
        $this->_disableLayout();
        $this->_disableView();

        $oInvitation = new Aurel_Table_Invitation();

        $ready = $oInvitation->getReadyToSendByUser($this->_getUser()->id_user);

        $return = [
            'refresh' => $ready->count() == 0
        ];

        echo json_encode($return);
    }

    public function inviteAction()
    {

        $oInvitation = new Aurel_Table_Invitation();
        $oUser = new Aurel_Table_User();

        $formData = $this->_request->getPost();
        if ($this->_request->isPost()) {
            $this->_disableLayout();
            $this->_disableView();

            $continue = true;
            $validate = new Zend_Validate_EmailAddress();
            foreach ($formData['email'] as $key => $email) {
                if (!$validate->isValid($email)) {
                    $continue = false;
                    $return['errors'][] = 'email-' . $key;
                    $return['message']['email-' . $key] = "Veuillez saisir une adresse e-mail complète, y compris le signe @.";
                }

                $user = $oUser->getByEmail($email);
                if ($user) {
                    $continue = false;
                    $return['errors'][] = 'email-' . $key;
                    $return['message']['email-' . $key] = "Un compte est déjà créé avec cette adresse email.";
                }
            }

            $return['code'] = 'ko';
            if ($continue) {
                foreach ($formData['email'] as $key => $email) {
                    $new = $oInvitation->createRow();

                    $new->email = $email;
                    $new->message = $formData['message'];
                    $new->state = Aurel_Table_Invitation::TYPE_READYTOSEND;
                    $new->id_user_creation = $this->_getUser()->id_user;
                    $new->date_creation = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);

                    $new->save();
                }

                $return['code'] = 'ok';
                $url_redirect = $this->view->url(array('action' => 'invitations'), 'compte', true);
                $return['url_redirect'] = $url_redirect;
            }

            echo json_encode($return);
        }
    }

    public function resendAction()
    {
        $oInvitation = new Aurel_Table_Invitation();
        $oUser = new Aurel_Table_User();

        $id_invitation = $this->getParam('id_invitation');
        $invitation = $oInvitation->getById($id_invitation);

        $formData = $this->_request->getPost();
        if ($this->_request->isPost()) {
            $this->_disableLayout();
            $this->_disableView();
            $return['code'] = 'ko';
            if ($invitation) {
                $inviteur = $oUser->getById($invitation->id_user_creation);
                $subject = $this->_config->subject_reinvitation;
                $body = $this->_config->body_reinvitation;

                $link = "http://marche-entreprises.btob-adidas.com/compte/register?invitation=" . md5($invitation->id_invitation);
                $replacement = [
                    '#INVITEUR_PRENOM#' => $inviteur->firstname,
                    '#INVITEUR_NOM#' => $inviteur->lastname,
                    '#INVITEUR_SOCIETE#' => $inviteur->societe,
                    '#INVITEUR_EMAIL#' => $inviteur->email,
                    '#INVITEUR_FONCTION#' => $inviteur->fonction,
                    '#INVITE_EMAIL#' => $invitation->email,
                    '#INVITE_MESSAGE#' => $formData['message'],
                    '#LIEN#' => $link
                ];

                foreach ($replacement as $key => $value) {
                    $subject = str_replace($key, $value, $subject);
                    $body = str_replace($key, $value, $body);
                }


                $mailSend = new Aurel_Mailer("utf-8");
                $mailSend->setBodyHtmlWithDesign($body, $subject)
                    ->setFrom('contact@btob-adidas.com', $this->_config->from_mail)
                    ->setSubject($subject)
                    ->addTo($invitation->email);
                try {
                    $mailSend->send();

                    $invitation->state = Aurel_Table_Invitation::TYPE_RESENT;
                    $invitation->date_resent = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
                } catch (Exception $e) {
                }
                $invitation->save();

                $return['code'] = 'ok';
                $url_redirect = $this->view->url(array('action' => 'invitations'), 'compte', true);
                $return['url_redirect'] = $url_redirect;
            }

            echo json_encode($return);
        }

        $this->view->invitation = $invitation;
    }
}
