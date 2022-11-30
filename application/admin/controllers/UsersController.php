<?php

require_once "AbstractController.php";

/**
 * Class Admin_IndexController
 *
 * @author Aurel
 *
 */
class Admin_UsersController extends Admin_AbstractController
{

    /**
     * Page index
     *
     * @return void
     */
    public function indexAction()
    {
        $oUsers = new Aurel_Table_User();
        $search = $this->getParam('search');
        $orderby = $this->getParam('orderby', 'status');
        $order = $this->getParam('order', 'asc');
        $this->view->search = $search;

        $users = $oUsers->getAll(Aurel_Table_User::STATUS_ACTIF, $search, $orderby, $order, true);

        $paginator = Zend_Paginator::factory($users);
        $paginator->setCurrentPageNumber($this->getParam('p', 1));
        $paginator->setDefaultItemCountPerPage(20);

        $this->view->usersActif = $paginator;

        $users = $oUsers->getAll(Aurel_Table_User::STATUS_INACTIF);
        $this->view->usersInactif = $users;

        $links = ['name' => ['orderby' => 'name', 'order' => 'asc'], 'firstname' => ['orderby' => 'firstname', 'order' => 'asc'], 'lastname' => ['orderby' => 'lastname', 'order' => 'asc'], 'email' => ['orderby' => 'email', 'order' => 'asc'], 'directeur' => ['orderby' => 'directeur', 'order' => 'asc'], 'type' => ['orderby' => 'type', 'order' => 'asc'], 'date_last_connexion' => ['orderby' => 'date_last_connexion', 'order' => 'asc']];
        $icones = ['name' => null, 'firstname' => null, 'lastname' => null, 'email' => null, 'directeur' => null, 'type' => null, 'date_last_connexion' => null];
        $drop = ['name' => null, 'firstname' => null, 'lastname' => null, 'email' => null, 'directeur' => null, 'type' => null, 'date_last_connexion' => null];
        if (isset($links[$orderby])) {
            if ($order == 'asc') {
                $links[$orderby]['order'] = 'desc';
                $icones[$orderby] = 'caret';
                $drop[$orderby] = 'dropup';
            } else {
                $links[$orderby]['order'] = 'asc';
                $icones[$orderby] = 'caret';
                $drop[$orderby] = 'dropdown';
            }
        }

        $this->view->links = $links;
        $this->view->icones = $icones;
        $this->view->drop = $drop;
        $this->view->addLink = $_SERVER["QUERY_STRING"] != "" ? "?" . $_SERVER["QUERY_STRING"] : "";
    }

    /**
     * 
     */
    public function downloadAction(): never
    {
        $array = [];
        $this->_disableLayout();
        $this->_disableView();

        $oUsers = new Aurel_Table_User();
        $users = $oUsers->getAllForExtract();

        //var_dump($users->toArray());
        $this->getResponse()->setHeader('Content-Type', 'text/csv', true);
        $this->getResponse()->setHeader('Content-disposition', 'attachment; filename=users.csv', true);

        $array[] = ['email', 'prenom', 'nom', 'societe', 'fonction', 'date derniere connexion'];
        $array += $users->toArray();

        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private", false);
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=users.csv;");
        header("Content-Transfer-Encoding: binary");
        exit(utf8_decode((string) $this->getCSV($array)));
    }

    /**
     * 
     * @param type $array
     */
    public function outputCSV($array)
    {
        $fp = fopen('php://output', 'w'); // this file actual writes to php output
        foreach ($array as $arr) {
            fputcsv($fp, $arr, ';');
        }
        fclose($fp);
    }

    /**
     *  getCSV creates a line of CSV and returns it. 
     */
    public function getCSV($array)
    {
        ob_start(); // buffer the output ...
        $this->outputCSV($array);
        return ob_get_clean(); // ... then return it as a string!
    }

    /**
     * Page index
     *
     * @return void
     */
    public function addEditAction()
    {
        //$this->_disableLayout();

        $arrayStatus = [Aurel_Table_User::STATUS_INACTIF => "Inactif", Aurel_Table_User::STATUS_ACTIF => "Actif"];
        $this->view->selectStatus = $arrayStatus;
        $this->view->selectType = Aurel_Acl::getArrayLibelleResources();
        $this->view->selectDescription = Aurel_Acl::getArrayDescriptionResources();

        $oUsers = new Aurel_Table_User();
        $id_user = $this->getParam("id_user", "999999999999");
        $user = $oUsers->getById($id_user);

        $menu_redacteurs_all = [];
        foreach ($this->view->menus as $menu) {
            if (!$menu->annonces && !$menu->agenda) {
                $lblMenu = "menu_" . $menu->id_menu;

                if ($menu->sous_menus_name) {
                    $menu_redacteurs_all[$menu->id_menu] = [];
                    $liste_id = explode(",", (string) $menu->sous_menus_id);
                    foreach ($liste_id as $key => $id_sous_menu) {
                        $menu_redacteurs_all[$menu->id_menu][$id_sous_menu] = "1";
                    }
                } else {
                    $menu_redacteurs_all[$menu->id_menu] = "1";
                }
            }
        }

        $tabCoche = [];
        if ($user) {
            foreach ($user->decompose() as $id_right) {
                $tabCoche[$id_right] = true;
            }
            if (!$user->hasRight(Aurel_Acl::RESSOURCE_ADMIN_ARTICLES) && $user->hasRight(Aurel_Acl::RESSOURCE_ADMIN_REDACTEUR))
                $user->menus_redacteur = json_decode((string) $user->menus_redacteur, true, 512, JSON_THROW_ON_ERROR);
            else
                $user->menus_redacteur = $menu_redacteurs_all;
        }
        $this->view->coche = $tabCoche;

        if (!$user) {
            $user = $oUsers->createRow();
            $user->id_user_creation = $this->_getUser()->id_user;
            $user->date_creation = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
            $user->status = Aurel_Table_User::STATUS_ACTIF;
            $user->menus_redacteur = $menu_redacteurs_all;
            $user->directeur = 0;
        }

        $this->view->userToModify = $user;

        $formData = $this->_request->getPost();
        if ($this->_request->isPost()) {
            $this->_disableLayout();
            $this->_disableView();
            $return = [];
            $continue = true;
            $validate = new Zend_Validate_EmailAddress();
            if (!$validate->isValid($formData["email"])) {
                $continue = false;
                $return['errors'][] = 'email';
            }
            if (!$user->id_user && (trim((string) $formData['password']) == "" || trim((string) $formData['password2']) == "")) {
                $continue = false;
                $return['errors'][] = 'password';
                $return['errors'][] = 'password2';
            }
            if (trim((string) $formData['password']) != "" || trim((string) $formData['password2']) != "") {
                if (trim((string) $formData['password']) != trim((string) $formData['password2'])) {
                    $continue = false;
                    $return['errors'][] = 'password';
                    $return['errors'][] = 'password2';
                }
            }
            $return['code'] = 'ko';
            if ($continue) {
                $user->name = stripslashes((string) $formData["name"]);
                $user->lastname = stripslashes((string) $formData["lastname"]);
                $user->firstname = stripslashes((string) $formData["firstname"]);
                $user->email = stripslashes((string) $formData["email"]);
                if (isset($formData["societe"]))
                    $user->societe = stripslashes((string) $formData["societe"]);
                if (isset($formData["fonction"]))
                    $user->fonction = stripslashes((string) $formData["fonction"]);
                $user->status = $formData["status"];

                if (isset($formData["directeur"]) && $user->id_user != $this->_getUser()->id_user) {
                    $user->directeur = $formData["directeur"];
                }

                if ($user->directeur == 1) {
                    $sum = Aurel_Acl::getSommeAllRights();
                } else {
                    $sum = 0;
                    foreach ($formData['rights'] as $id_right => $bool) {
                        if ($bool == "1") {
                            $sum += $id_right;
                        }
                    }
                }
                $user->type = $sum;

                if (isset($formData['rights']) && $formData['rights'][Aurel_Acl::RESSOURCE_ADMIN_ARTICLES] == "0" && $formData['rights'][Aurel_Acl::RESSOURCE_ADMIN_REDACTEUR] == "1")
                    $user->menus_redacteur = json_encode($formData['menus_redacteur'], JSON_THROW_ON_ERROR);
                else
                    $user->menus_redacteur = null;

                $user->id_user_modification = $this->_getUser()->id_user;
                $user->date_modification = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);

                if ($formData["password"] == $formData["password2"] && trim((string) $formData["password"]) != "") {
                    $user->password = stripslashes((string) $formData["password"]);
                }
                $user->save();

                $return['code'] = 'ok';
            }

            echo json_encode($return, JSON_THROW_ON_ERROR);
        }
    }

    public function deleteAction()
    {
        $this->_disableLayout();

        $oUser = new Aurel_Table_User();

        $id_user = $this->getParam("id_user", "999999999999");
        $user = $oUser->getById($id_user);

        $this->view->userToModify = $user;
        $formData = $this->_request->getPost();

        if ($user && $formData) {
            $this->_disableView();
            if ($user->status == Aurel_Table_User::STATUS_ACTIF) {
                $user->status = Aurel_Table_User::STATUS_INACTIF;
                $user->save();
            } else {
                $user->delete();
            }

            $this->redirect($this->view->url(['action' => 'index', 'id_user' => null]));
        }
    }

    /**
     * 
     */
    public function previewAction()
    {
        $this->_disableLayout();
        $this->_disableView();
        $subject = $this->_config->subject_notification;
        $body = $this->_config->body_notification;

        $inviteur = $this->_getUser();

        $oInvitation = new Aurel_Table_Invitation();
        $oUser = new Aurel_Table_User();

        $message = $this->getParam('message');
        $object = $this->getParam('objet');

        $subject = $object;

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
            $subject = str_replace($key, $value, (string) $subject);
            $body = str_replace($key, $value, (string) $body);
        }


        $mailSend = new Aurel_Mailer("utf-8");
        $mailSend->setBodyHtmlWithDesign($body, $subject)
            ->setFrom('contact@btob-adidas.com', $this->_config->from_mail)
            ->setSubject($subject);

        echo $mailSend->getHtml();
    }

    public function sendAction()
    {
        $return = [];
        $oInvitation = new Aurel_Table_Invitation();
        $oUser = new Aurel_Table_User();
        $oQueue = new Aurel_Table_Queue();

        $id_user = $this->getParam('id_user');
        $userInvited = $oUser->getById($id_user);

        $formData = $this->_request->getPost();
        if ($this->_request->isPost()) {
            $this->_disableLayout();
            $this->_disableView();
            $return['code'] = 'ko';

            $identification = uniqid();
            if ($userInvited) {
                $subject = $formData['objet'];
                $body = $this->_config->body_notification;

                $hash = Aurel_Encryptor::getInstance();
                $hash->setDecryptedValue($userInvited->email);
                $hash->setExpirySeconds(20_000_000);
                $hash->encrypt();

                $link = "https://marche-entreprises.btob-adidas.com/compte/l?h=" . $hash->getEncryptedValue();
                $replacement = [
                    '#INVITE_MESSAGE#' => $formData['message'],
                    '#LIEN#' => $link,
                ];

                foreach ($replacement as $key => $value) {
                    $subject = str_replace($key, $value, (string) $subject);
                    $body = str_replace($key, $value, (string) $body);
                }

                $queue = $oQueue->createRow();
                $queue->to = $userInvited->email;
                $queue->subject = $subject;
                $queue->body = $body;
                $queue->status = Aurel_Table_Queue::STATUS_READYTOSEND;
                $queue->date_creation = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
                $queue->identification = $identification;
                $queue->id_user_creation = $this->_getUser()->id_user;
                $queue->id_user = $userInvited->id_user;
                $queue->save();

                $return['code'] = 'ok';
                $url_redirect = $this->view->url(['action' => 'index', 'id_user' => null]);
                $return['url_redirect'] = $url_redirect;
            } else {
                $users = $oUser->getAllForNewsletter();

                foreach ($users as $userInvited) {

                    $subject = $formData['objet'];
                    $body = $this->_config->body_notification;

                    $hash = Aurel_Encryptor::getInstance();
                    $hash->setDecryptedValue($userInvited->email);
                    $hash->setExpirySeconds(20_000_000);
                    $hash->encrypt();

                    $link = "http://marche-entreprises.btob-adidas.com/compte/l?h=" . $hash->getEncryptedValue();
                    $replacement = [
                        '#INVITE_MESSAGE#' => $formData['message'],
                        '#LIEN#' => $link,
                    ];

                    foreach ($replacement as $key => $value) {
                        $subject = str_replace($key, $value, (string) $subject);
                        $body = str_replace($key, $value, (string) $body);
                    }

                    $queue = $oQueue->createRow();
                    $queue->to = $userInvited->email;
                    $queue->subject = $subject;
                    $queue->body = $body;
                    $queue->status = Aurel_Table_Queue::STATUS_READYTOSEND;
                    $queue->date_creation = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
                    $queue->identification = $identification;
                    $queue->id_user_creation = $this->_getUser()->id_user;
                    $queue->id_user = $userInvited->id_user;
                    $queue->save();
                }

                $return['code'] = 'ok';
                $url_redirect = $this->view->url(['action' => 'index', 'id_user' => null]);
                $return['url_redirect'] = $url_redirect;
            }

            echo json_encode($return, JSON_THROW_ON_ERROR);
        }

        $this->view->userInvited = $userInvited;
    }
}
