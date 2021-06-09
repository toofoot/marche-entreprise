<?php

require_once "AbstractController.php";

/**
 * Class Admin_IndexController
 *
 * @author Aurel
 *
 */
class Admin_IndexController extends Admin_AbstractController {

    /**
     * Page index
     *
     * @return void
     */
    public function indexAction() {
        $this->redirect("/");
    }
    
    public function invitationsAction() {
        $state = $this->getParam('state', Aurel_Table_Invitation::TYPE_SENT);
        
        $oInvitation = new Aurel_Table_Invitation();

        $invitations = $oInvitation->getAll($state);
        $ready = $oInvitation->getReadyToSend();

        $this->view->invitations = $invitations;
        $this->view->state = $state;
        $this->view->ready = $ready->count() > 0;
    }

    /**
     * Page de Login
     * 
     * @return void
     */
    public function loginAction() {
        if ($this->hasParam('pageForbidden'))
            $this->view->message = "Vous devez vous connecter pour accéder à cette page";

        if ($this->hasParam('url_redirect'))
            $url_redirect = urldecode($this->getParam('url_redirect'));
        else
            $url_redirect = $this->view->url(array(), 'default', true);

        $auth = Zend_Auth::getInstance();
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

                    if ($user->status == Aurel_Table_User::STATUS_ACTIF) {
                        $auth->getStorage()->write($id->id_user);

                        $type_connexion = new Zend_Session_Namespace('type_connexion');
                        $type_connexion->type = 'admin';

                        $user->date_last_connexion = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
                        $user->ip_last_connexion = $_SERVER["REMOTE_ADDR"];
                        $user->save();

                        $this->view->message = "Vous êtes maintenant connecté";
                        $this->view->displayMessage = true;
                        $this->view->error = false;
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
        }
    }

    /**
     * Page de Logout
     *
     * @return void
     */
    public function logoutAction() {
        $this->_disableLayout();
        $this->_disableView();

        $auth = Zend_Auth::getInstance();
        $auth->clearIdentity();

        $type_connexion = new Zend_Session_Namespace('type_connexion');
        $type_connexion->unsetAll();

        $bootstrap = $this->getInvokeArg('bootstrap');
        $appinidata = $bootstrap->getOptions();
        $cookie_domain = null;
        if (isset($appinidata['resources']['session']) && isset($appinidata['resources']['session']['cookie_domain']))
            $cookie_domain = $appinidata['resources']['session']['cookie_domain'];

        setcookie(
                'Auth', '', time() - 2600, '/', $cookie_domain
        );

        setcookie(
                'popup', 1, time() + 30 * 3600 * 24, '/', $cookie_domain
        );

        if ($this->hasParam('url_redirect'))
            $url_redirect = urldecode($this->getParam('url_redirect'));
        else
            $url_redirect = $this->view->url(array(), 'default', true);

        $this->redirect($url_redirect);
    }

    /**
     * Formate Text
     *
     * @param string $texte
     * @return string
     */
    private function getFormatedText($texte) {
        return $texte;
    }

    /**
     * Page edit Footer
     *
     * @return void
     */
    public function editFooterAction() {
        $formData = $this->_request->getPost();
        if ($formData) {
            $this->_disableLayout();
            $this->_disableView();

            $arrayToUpdate = [
                'col1' => $this->getFormatedText($formData["col1"]),
                'col2' => $this->getFormatedText($formData["col2"]),
                'col3' => $this->getFormatedText($formData["col3"]),
            ];

            $oConfig = new Aurel_Table_Config();
            foreach ($arrayToUpdate as $key => $value) {
                $oConfig->insertOrUpdate($key, $value);
            }

            $return['returncode'] = "ok";

            echo json_encode($return);
        }
    }

    public function configAction() {
        $session = new Zend_Session_Namespace('config');
        $this->view->session = $session;

        $formData = $this->_request->getPost();
        if ($formData) {
            $arrayToUpdate['minArticlesInPage'] = $formData["minArticlesInPage"];
            $arrayToUpdate['delaiArticlesPage'] = $formData["delaiArticlesPage"];
            $arrayToUpdate['daysArchiveAnnonce'] = $formData["daysArchiveAnnonce"];
            $arrayToUpdate['title_popup_inscription'] = $formData["title_popup_inscription"];
            $arrayToUpdate['text_popup_inscription'] = $formData["text_popup_inscription"];

            include('SimpleImage.php');
            if (is_uploaded_file($_FILES["imgDefaultArticle"]["tmp_name"])) {
                $extension = pathinfo($_FILES["imgDefaultArticle"]["name"], PATHINFO_EXTENSION);
                $pathFileTmp = BASE_PATH . "/www/images/TMPno-photo." . $extension;
                $pathFileArticle = BASE_PATH . "/www/images/no-photo.jpg";
                if (move_uploaded_file($_FILES["imgDefaultArticle"]["tmp_name"], $pathFileTmp)) {
                    $img = new abeautifulsite\SimpleImage($pathFileTmp);
                    $img->auto_orient();

                    $img->adaptive_resize(600, 337.5);
                    $img->save($pathFileArticle, 80);
                    unlink($pathFileTmp);
                }
            }

            $oConfig = new Aurel_Table_Config();
            foreach ($arrayToUpdate as $key => $value) {
                $oConfig->insertOrUpdate($key, $value);
            }

            $session->message = "Données enregistrées";
            $session->setExpirationHops(1);

            $this->redirect($this->view->url());
        }
    }

    public function configAccessAction() {
        $session = new Zend_Session_Namespace('config');
        $this->view->session = $session;

        $formData = $this->_request->getPost();
        if ($formData) {
            $arrayToUpdate['connexion_access_code'] = $formData["connexion_access_code"];


            $oConfig = new Aurel_Table_Config();
            foreach ($arrayToUpdate as $key => $value) {
                $oConfig->insertOrUpdate($key, $value);
            }

            $session->message = "Données enregistrées";
            $session->setExpirationHops(1);

            $this->redirect($this->view->url());
        }

        $oAccessCode = new Aurel_Table_AccessCode();
        $accesss = $oAccessCode->getAll();

        $this->view->accesss = $accesss;
    }
    
    public function configInvitationsAction() {
        $session = new Zend_Session_Namespace('config');
        $this->view->session = $session;

        $formData = $this->_request->getPost();
        if ($formData) {
            $arrayToUpdate['subject_invitation'] = $formData["subject_invitation"];
            $arrayToUpdate['body_invitation'] = $formData["body_invitation"];
            $arrayToUpdate['subject_reinvitation'] = $formData["subject_reinvitation"];
            $arrayToUpdate['body_reinvitation'] = $formData["body_reinvitation"];

            $oConfig = new Aurel_Table_Config();
            foreach ($arrayToUpdate as $key => $value) {
                $oConfig->insertOrUpdate($key, $value);
            }

            $session->message = "Données enregistrées";
            $session->setExpirationHops(1);

            $this->redirect($this->view->url());
        }
    }

    public function addEditAccessCodeAction() {
        $oAccessCode = new Aurel_Table_AccessCode();
        $id_access_code = $this->getParam('id_access_code');

        $access = null;
        if ($id_access_code) {
            $access = $oAccessCode->getById($id_access_code);
        }
        if (!$access) {
            $access = $oAccessCode->createRow();
        }

        $formData = $this->getRequest()->getPost();
        if ($formData) {
            $access->code = $formData['code'];
            $access->delai = $formData['delai'];
            $access->date_start = $formData['date_start'];
            $access->date_end = $formData['date_end'];
            $access->save();

            $this->redirect($this->view->url(['action' => 'config-access', 'id_access_code' => null]));
        }

        $this->view->access_code = $access;
    }

    public function deleteAccessCodeAction() {
        $oAccessCode = new Aurel_Table_AccessCode();
        $id_access_code = $this->getParam('id_access_code');

        $access = null;
        if ($id_access_code) {
            $access = $oAccessCode->getById($id_access_code);
        }
        if (!$access) {
            $access = $oAccessCode->createRow();
        }

        $formData = $this->getRequest()->getPost();
        if ($formData) {
            $access->delete();

            $this->redirect($this->view->url(['action' => 'config-access', 'id_access_code' => null]));
        }

        $this->view->access_code = $access;
    }

}
