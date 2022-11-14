<?php

/**
 * Abstract - The default controller class
 * 
 * @author Aurélien Cornu
 */
abstract class Aurel_Controller_Abstract extends Zend_Controller_Action {

    protected $_role;
    protected $_config;
    protected $_user;

    /**
     * Post-dispatch routines
     *
     * @return void
     */
    public function postDispatch() {
        $db = Zend_Registry::get('db');
        $profiler = $db->getProfiler();
        $this->view->totalTime = number_format($profiler->getTotalElapsedSecs(), 3, ',', '\'');
        $this->view->queryCount = $profiler->getTotalNumQueries();
    }

    /**
     * Pre-dispatch routines
     *
     * @return void
     */
    public function preDispatch() {
        $this->_role = Zend_Registry::get('role');
        // Desactivation du layout si AJAX (sauf page menu)
        if ($this->_isAjax()) {
            $this->view->ajax = true;
            $this->_disableLayout();
        }
        $this->getFrontController()->throwExceptions(false);

        // Variables de vue
        $this->view->layoutName = $this->_helper->layout->getLayoutInstance()->getLayout();
        $this->view->moduleName = $moduleName = $this->_request->getModuleName();
        $this->view->controllerName = $controllerName = $this->_request->getControllerName();
        $this->view->actionName = $actionName = $this->_request->getActionName();

        if ($this->_getAcl()->isAllowed(Aurel_Acl::ROLE_GUEST, $moduleName . "_" . $controllerName, $actionName)) {
            $this->view->logout_url_redirect = urlencode($_SERVER["REQUEST_URI"]);
        } else {
            $this->view->logout_url_redirect = urlencode($this->view->url(array(), 'default', true));
        }


        $config = Aurel_Table_Config::getConfig();
        Zend_Registry::set("config", $config);
        $this->view->config = $this->_config = $config;

        $this->view->url_redirect = $this->hasParam('url_redirect') ? urldecode($this->getParam('url_redirect')) : $this->view->url();
        // Scripts JS
        $this->view->headScript()
                ->appendFile("https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js", "text/javascript", array('conditional' => 'lt IE 9'))
                ->appendFile("https://oss.maxcdn.com/respond/1.4.2/respond.min.js", "text/javascript", array('conditional' => 'lt IE 9'))
                ->appendFile('/javascript/jquery-1.11.0.js');

        //if($this->_getAcl()->isAllowed(Zend_Registry::get('role'),Aurel_Acl::RESSOURCE_ADMIN)){
        $this->view->headScript()->appendFile('/javascript/jquery-ui-1.11.2.custom/jquery-ui.min.js')
                ->appendFile('/javascript/datepicker-fr.js');
        $this->view->headLink()->appendStylesheet('/javascript/jquery-ui-1.11.2.custom/jquery-ui.min.css');
        //}

        $this->view->headScript()->appendFile('/javascript/jquery.cycle2.min.js')
                //->appendFile('/javascript/galleria/galleria-1.3.6.min.js')
                //->appendFile('/javascript/galleria/themes/classic/galleria.classic.min.js')
                ->appendFile('/javascript/swiper/idangerous.swiper.js')
                ->appendFile('/javascript/fullscreen/jquery.fullscreen-min.js')
                ->appendFile('/javascript/colorbox-master/jquery.colorbox-min.js')
                ->appendFile('/javascript/bootstrap/js/bootstrap.min.js');

        // Styles CSS
        $this->view->headLink()
                ->appendStylesheet('/javascript/bootstrap/css/bootstrap.min.css')
                //->appendStylesheet('/javascript/bootstrap/css/bootstrap-theme.min.css')
                //->appendStylesheet('/javascript/galleria/themes/classic/galleria.classic.css')
                ->appendStylesheet('/javascript/swiper/idangerous.swiper.css')
                ->appendStylesheet('/css/style.css?t=9')
                ->appendStylesheet('/javascript/colorbox-master/example1/colorbox.css')
                ->appendStylesheet('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css');
        //$this->view->headLink()->appendAlternate('http://' . $_SERVER["HTTP_HOST"] . '/flux.rss','application/rss+xml','Flux RSS');

        /* ->headLink(array('rel' => 'shortcut icon',
          'type' => 'image/x-icon',
          'href' => '/images/favicon.ico'),
          'PREPEND'); */

        $this->view->doctype(Zend_View_Helper_Doctype::HTML5);
        if (isset($config->appname))
            $this->view->headTitle($config->appname);
        $this->view->headTitle()->setSeparator(' | ');

        // Meta NAME
        $this->view->headMeta()
                ->appendName('viewport', 'width=device-width, initial-scale=1')
                ->appendHttpEquiv('X-UA-Compatible', 'IE=edge')
                //->appendHttpEquiv('CACHE-CONTROL', 'Private')
                //->appendHttpEquiv('EXPIRES', gmdate('D, d M Y H:i:s \G\M\T', time() + 48 * 60 * 60))
                ->appendHttpEquiv('Content-Type', 'text/html; charset=utf-8')
                ->appendHttpEquiv('Content-Language', 'fr');

        if ($this->_getAcl()->isAllowed(Zend_Registry::get('role'), Aurel_Acl::RESSOURCE_ADMIN)) {
            $this->view->headScript()
                    ->appendFile('/javascript/bootstrap/js/bootstrap-switch.min.js')
                    ->appendFile('/javascript/ckeditor/ckeditor.js')
                    ->appendFile('/javascript/uploadphoto/js/jquery.filedrop.js')
                    ->appendFile('/javascript/uploadphoto/js/script.js');

            $this->view->headLink()
                    ->appendStylesheet('/javascript/bootstrap/css/bootstrap-switch.min.css')
                    ->appendStylesheet('/javascript/uploadphoto/css/styles.css');
        }

        $oArticle = new Aurel_Table_Article();
        if ($this->_getAcl()->isAllowed(Zend_Registry::get('role'), Aurel_Acl::RESSOURCE_ADMIN)) {
            $this->view->articles_corbeilles = $oArticle->getAllCorbeille();
            $this->view->annonces_waiting = $oArticle->getAllAnnoncesWaiting();

            $oUser = new Aurel_Table_User();
            $this->view->users_pastille = $oUser->getAll()->count();
        }

        $oMenu = new Aurel_Table_Menu();
        $menus = $oMenu->getAll($this->_getAcl()->isAllowed($this->_role, Aurel_Acl::ROLE_ADMIN));
        $this->view->menus = $menus;

        $oSousMenu = new Aurel_Table_SousMenu();
        $sousmenu = $oSousMenu->getAnnuaire();
        if ($sousmenu)
            $this->view->sousmenuAnnuaire = $sousmenu->id_sous_menu;

        $user = $this->_getUser();
        if ($user instanceof Aurel_Table_Row_User)
            $this->view->menus_redacteur = json_decode($user->menus_redacteur, true);
        $this->view->user = $this->view->me = $user;


       /*  var_dump($this->_role); 
        var_dump($this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_MEMBRE)); 
        die(); */


        if (!$this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_MEMBRE)) {
            if (!isset($_COOKIE["popup"])) {
                $this->view->show_popup = false;
            }

            if ($this->_config->connexion_access_code && !isset($_COOKIE["access_code_ok"]) && !str_contains($_SERVER["REQUEST_URI"], 'admin') && !str_contains($_SERVER["REQUEST_URI"], 'login') && !str_contains($_SERVER["REQUEST_URI"], 'passoublie') && !str_contains($_SERVER["REQUEST_URI"], 'desinscription') && !$this->hasParam('invitation')) {
                $emailGet = $this->getParam('email',null);
                //var_dump($emailGet);die();
                if (isset($emailGet)) {
                    $email = $emailGet;
                    $oUser = new Aurel_Table_User();
                    $user = $oUser->getByEmail($email);

                    if ($user) {
                        $this->_helper->layout->setLayout('access_code');
                        $this->view->popup_login = true;
                        $this->view->email_login = $email;
                    } elseif (!str_contains($_SERVER["REQUEST_URI"], 'compte/register')) {
                        $query_string = $_SERVER['QUERY_STRING'];
                        $this->redirect('/compte/register?' . $query_string);
                    }
                } else {
                    $this->_helper->layout->setLayout('access_code');
                }
            } else {
                if (isset($emailGet)) {
                    $email = $this->getParam('email');
                    $oUser = new Aurel_Table_User();
                    $user = $oUser->getByEmail($email);

                    if ($user) {
                        $this->view->popup_login = true;
                        $this->view->email_login = $email;
                    } elseif (!str_contains($_SERVER["REQUEST_URI"], 'compte/register')) {
                        $query_string = $_SERVER['QUERY_STRING'];
                        $this->redirect('/compte/register?' . $query_string);
                    }
                }
            }
        }
        if (!isset($_COOKIE["popup_other"])) {
            $this->view->show_popup_other = false;
        }

        $session = new Zend_Session_Namespace('inscription');
        $this->view->session = $session;
    }

    /**
     * Verifie si la requete est AJAX
     * 
     * @return bool
     */
    protected function _isAjax() {
        return $this->getRequest()->isXmlHttpRequest();
    }

    /**
     * Disable Layout
     * 
     * @return void
     */
    protected function _disableLayout() {
        $this->_helper->layout->disableLayout();
    }

    /**
     * Disable View
     * 
     * @return void
     */
    protected function _disableView() {
        $this->_helper->viewRenderer->setNoRender();
    }

    /**
     * @return Aurel_Acl
     */
    protected function _getAcl() {
        return Zend_Registry::get(\Zend_Acl::class);
    }

    /**
     * @return Aurel_Table_Row_User
     */
    protected function _getUser() {
        return Zend_Registry::get('user');
    }

}
