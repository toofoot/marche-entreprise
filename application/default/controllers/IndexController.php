<?php

/**
 * IndexController - The default controller class
 * 
 * @author
 * @version 
 */
class IndexController extends Aurel_Controller_Abstract {

    protected $_basenamePrincipal = null;
    protected $_basenameSecondaire = null;
    protected $_articles = array();
    protected $_menu = null;
    protected $_sousmenu = null;

    /**
     * Pre-dispatch routines
     *
     * @return void
     */
    public function preDispatch() {
        parent::preDispatch();
    }

    public function home1Action() {
        $oArticle = new Aurel_Table_Article();

        $articles = $oArticle->getAllNews(5, null, 2);

        $this->view->headScript()
                ->appendFile('/javascript/lightslider/js/lightslider.js');

        // Styles CSS
        $this->view->headLink()
                ->appendStylesheet('/javascript/lightslider/css/lightslider.css');

        $this->view->articles = $articles;
    }

    public function contactAnnonceurAction() {
        $oArticle = new Aurel_Table_Article();
        $oSousMenu = new Aurel_Table_SousMenu();
        $oUser = new Aurel_Table_User();

        $id_annonce = $this->getParam('id_annonce');
        $annonce = $oArticle->getById($id_annonce);
        if ($annonce) {
            $categorie = $oSousMenu->getById($annonce->id_sous_menu);
            $annonceur = $oUser->getById($annonce->id_user_creation);

            $this->view->annonce = $annonce;
            $this->view->annonceur = $annonceur;

            $formData = $this->_request->getPost();
            if ($formData) {
                $this->_disableLayout();
                $this->_disableView();

                $return = array();
                $return["sent"] = false;
                $continue = true;

                $validate = new Zend_Validate_EmailAddress();
                if (empty($formData["email"])) {
                    $continue = false;
                    $return['errors'][] = 'email';
                    $return['message']['email'] = "Veuillez saisir votre adresse e-mail.";
                } elseif (!$validate->isValid($formData["email"])) {
                    $continue = false;
                    $return['errors'][] = 'email';
                    $return['message']['email'] = "Veuillez saisir votre adresse e-mail complète, y compris le signe @.";
                }

                if (empty($formData["text"])) {
                    $continue = false;
                    $return['errors'][] = 'text';
                    $return['message']['text'] = "Veuillez saisir votre texte.";
                }

                if ($continue) {
                    if ($annonce) {

                        $replace = array(
                            "CATEGORIE_ANNONCE" => $categorie->name,
                            "TITRE_ANNONCE" => $annonce->title,
                            "NUM_ANNONCE" => $annonce->id_article,
                            "MESSAGE_DEMANDEUR" => $formData["text"],
                            "EMAIL_DEMANDEUR" => $formData["email"],
                        );

                        $subject = "Carbon12011 Licensing, demande de contact. Votre annonce référence #NUM_ANNONCE#";
                        $html = "Catégorie annonce : #CATEGORIE_ANNONCE#\n" .
                                "Titre annonce : #TITRE_ANNONCE#\n" .
                                "Référence annonce : #NUM_ANNONCE#\n" .
                                "Email demandeur : #EMAIL_DEMANDEUR#\n" .
                                "Message : #MESSAGE_DEMANDEUR#\n";

                        foreach ($replace as $key => $value) {
                            $subject = str_replace("#$key#", $value, $subject);
                            $html = str_replace("#$key#", $value, $html);
                        }

                        $mail = new Aurel_Mailer('utf-8');

                        $mail->setBodyHtmlWithDesign($html, $subject);
                        $mail->setSubject($subject);
                        $mail->setFrom($formData["email"]);
                        $mail->addTo($annonceur->email);

                        try {
                            $mail->send();
                            $return["sent"] = true;
                        } catch (Exception $e) {
                            
                        }
                    }
                }

                echo json_encode($return);
            }
        }
    }

    public function deleteAnnonceAction() {
        $this->_disableLayout();

        $oArticle = new Aurel_Table_Article();

        $id_annonce = $this->getParam("id_annonce", "999999999999");
        $annonce = $oArticle->getById($id_annonce);

        $formData = $this->_request->getPost();

        if ($annonce && $annonce->id_user_creation == $this->_getUser()->id_user && $formData) {
            $this->_disableView();
            $annonce->delete();

            $url = $this->view->url(array('action' => 'annonces'), 'compte', true);
            if ($this->hasParam('return'))
                $url .= "#" . $this->getParam('return');
            $this->redirect($url);
        }
    }

    public function renewAnnonceAction() {
        $this->_disableLayout();

        $oArticle = new Aurel_Table_Article();
        $oSousMenu = new Aurel_Table_SousMenu();

        $id_annonce = $this->getParam("id_annonce", "999999999999");
        $annonce = $oArticle->getById($id_annonce);

        $formData = $this->_request->getPost();

        if ($annonce && $annonce->id_user_creation == $this->_getUser()->id_user && $formData) {
            $this->_disableView();
            $annonce->date_validation = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
            //$annonce->state_annonce = Aurel_Table_Article::STATE_ANNONCE_WAITING;
            $annonce->save();


            $oUser = new Aurel_Table_User();
            $usersAdminAnnonce = $oUser->getUsersWithRight(Aurel_Acl::RESSOURCE_ADMIN_ANNONCES);

            $sousmenu = $oSousMenu->getById($annonce->id_sous_menu);
            $rubrique = $sousmenu->name;

            /* $subject = "Renouvellement annonce en attente de validation (Annonce n° {$annonce->id_article})";
              $body = "Un renouvellement annonce a été demandé par {$this->_getUser()->firstname} {$this->_getUser()->lastname}\n" .
              "Rubrique : {$rubrique}\n" .
              "Titre : {$annonce->title}\n" .
              "Texte de l'annonce : {$annonce->content}\n" .
              "Prix : {$annonce->prix}\n";

              $photos = $annonce->getPhotos();
              if($photos->count() > 0){
              $body .= "<div style='text-align:center'>Photos :\n";
              foreach($photos as $photo){
              $body .= "<img style='width:150px' src='http://{$_SERVER['HTTP_HOST']}/images/upload/{$annonce->id_article}/smallthumb{$photo->id_photo}.{$photo->extension}' alt='{$photo->id_photo}' /> ";
              }
              $body .= "</div>";
              }
              $body .= "<br/>\n";
              $body .= "<div style='text-align:center'><a href='http://{$_SERVER['HTTP_HOST']}/admin/articles/annonces'>Je me connecte pour valider la mise en ligne</a></div>\n\n";

              $mail = new Aurel_Mailer("utf-8");
              $mail->setBodyHtmlWithDesign($body,$subject)
              ->setSubject($subject)
              ->setFrom("no-reply@lepetitcharsien.com","Carbon12011 Licensing");

              foreach($usersAdminAnnonce as $user){
              $mail->addTo($user->email,$user->firstname . " " . $user->lastname);
              }
              try{
              $mail->send();
              } catch(Exception $e){

              } */

            $url = $this->view->url(array('action' => 'annonces'), 'compte', true);
            if ($this->hasParam('return'))
                $url .= "#" . $this->getParam('return');
            $this->redirect($url);
        }
    }

    public function addAnnonceAction() {
        $this->view->validAuto = $this->getParam('valid');
        $sessionAnnonce = new Zend_Session_Namespace('annonce');
        $oMenu = new Aurel_Table_Menu();
        $oSousMenu = new Aurel_Table_SousMenu();
        $oArticle = new Aurel_Table_Article();

        $menu_annonce = $oMenu->getMenuAnnonces();

        $this->view->errors = $sessionAnnonce->errors;

        if ($menu_annonce) {
            $sous_menus = $oSousMenu->getAllByMenu($menu_annonce->id_menu);

            $tab = array('0' => '--- Choisir la catégorie ---');
            foreach ($sous_menus as $sous) {
                if ($sous->sous_menu_annonce) {
                    $tab[$sous->id_sous_menu] = $sous->name;
                }
            }

            $this->view->tabCategories = $tab;
        }

        if ($this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_MEMBRE))
            $this->view->masque_tel = $this->_getUser()->masque_tel;

        $id_annonce = $this->getParam('id_annonce');
        if ($id_annonce) {
            $article = $oArticle->getById($id_annonce);
            $photos = $article->getPhotos();
            $tabPhotos = array();
            $i = 1;
            foreach ($photos as $photo) {
                $tabPhotos[$i] = $photo;
                $i++;
            }
            $this->view->tabPhotos = $tabPhotos;

            $this->view->masque_tel = $article->masque_tel;
        } else {
            $article = $oArticle->createRow();

            $article->hide_home = 1;
            $article->annonce = 1;
            $article->portrait = 0;
            $article->date_creation = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
            $article->start_date = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATE);
            $article->end_date = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATE);

            if ($sessionAnnonce->formData) {
                $article->id_sous_menu = $sessionAnnonce->formData['categorie'];
                $article->title = $sessionAnnonce->formData['title'];
                $article->content = $sessionAnnonce->formData['content'];
                $article->prix = $sessionAnnonce->formData['prix'];

                $this->view->file = $sessionAnnonce->formData['file'];
            }
        }
        $this->view->annonce = $article;

        if ($this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_MEMBRE)) {
            $upload_dir = UPLOAD_PATH . "/";
            $formData = $this->_request->getPost();
            if ($formData) {
                $sessionAnnonce->unsetAll();
                $continu = true;
                $errors = array();
                if ($formData['categorie'] == '0') {
                    $continu = false;
                    $errors['categorie'] = 'Veuillez selectionner une catégorie.';
                }
                if ($formData['title'] == '') {
                    $continu = false;
                    $errors['title'] = 'Veuillez saisir un titre pour votre annonce.';
                }
                if ($formData['content'] == '') {
                    $continu = false;
                    $errors['content'] = 'Veuillez saisir un texte pour votre annonce.';
                }

                if ($continu) {
                    $sous_menu = $oSousMenu->getById($formData['categorie']);
                    $article->id_user_creation = $this->_getUser()->id_user;
                    $article->id_sous_menu = $formData['categorie'];
                    $article->title = stripslashes($formData['title']);
                    $article->basename = $oArticle->getBasename(stripslashes($formData['title']));
                    $article->content = stripslashes($formData['content']);
                    $article->prix = stripslashes($formData['prix']);
                    if (isset($formData['masque_tel']))
                        $article->masque_tel = $formData['masque_tel'];
                    else
                        $article->masque_tel = $this->_getUser()->masque_tel;
                    if (isset($formData['tel'])) {
                        $this->_getUser()->tel = $formData['tel'];
                        $this->_getUser()->save();
                    }
                    $article->state_annonce = Aurel_Table_Article::STATE_ANNONCE_WAITING;
                    $article->save();

                    $id_article = $article->id_article;

                    $article->basename = $oArticle->getBasename(stripslashes($formData['title'])) . "-" . $id_article;

                    $this->_check_dir($upload_dir);
                    $upload_dir .= $id_article . "/";
                    $this->_check_dir($upload_dir);

                    $oPhoto = new Aurel_Table_Photo();
                    foreach ($formData['file'] as $key => $file) {
                        if ($file != '' && isset($tabPhotos) && isset($tabPhotos[$key]) && $file == $tabPhotos[$key]->id_photo) {
                            
                        } elseif ($file != '') {
                            if (isset($tabPhotos) && isset($tabPhotos[$key]) && $file != $tabPhotos[$key]->id_photo)
                                $tabPhotos[$key]->delete();

                            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

                            $new = $oPhoto->createRow();
                            $new->extension = $extension;
                            $new->id_article = $id_article;
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
                    $photos = $article->getPhotos();
                    foreach ($photos as $photo) {
                        $article->picture = $photo->id_photo;
                        break;
                    }
                    $article->save();

                    $oUser = new Aurel_Table_User();
                    $usersAdminAnnonce = $oUser->getUsersWithRight(Aurel_Acl::RESSOURCE_ADMIN_ANNONCES);

                    if (isset($tab) && isset($tab[$article->id_sous_menu]))
                        $rubrique = $tab[$article->id_sous_menu];
                    else
                        $rubrique = "";

                    $subject = "Nouvelle annonce en attente de validation (Annonce n° {$article->id_article})";
                    $body = "Une nouvelle annonce a été mise en ligne par {$this->_getUser()->firstname} {$this->_getUser()->lastname}\n" .
                            "Rubrique : {$rubrique}\n" .
                            "Titre : {$article->title}\n" .
                            "Texte de l'annonce : {$article->content}\n" .
                            "Prix : {$article->prix}\n";
                    if ($photos->count() > 0) {
                        $body .= "<div style='text-align:center'>Photos :\n";
                        foreach ($photos as $photo) {
                            $body .= "<img style='width:150px' src='http://{$_SERVER['HTTP_HOST']}/images/upload/{$article->id_article}/smallthumb{$photo->id_photo}.{$photo->extension}' alt='{$photo->id_photo}' /> ";
                        }
                        $body .= "</div>";
                    }
                    $body .= "<br/>\n";
                    $body .= "<div style='text-align:center'><a href='http://{$_SERVER['HTTP_HOST']}/admin/articles/annonces'>Je me connecte pour valider la mise en ligne</a></div>\n\n";

                    $mail = new Aurel_Mailer("utf-8");
                    $mail->setBodyHtmlWithDesign($body, $subject)
                            ->setSubject($subject)
                            ->setFrom("no-reply@lepetitcharsien.com", "Carbon12011 Licensing");

                    foreach ($usersAdminAnnonce as $user) {
                        $mail->addTo($user->email, $user->firstname . " " . $user->lastname);
                    }
                    try {
                        $mail->send();
                    } catch (Exception $e) {
                        
                    }

                    $this->redirect($this->view->url(array('basename_article' => $article->basename), 'basename_annonce', true));
                } else {
                    $sessionAnnonce->formData = $formData;
                    $sessionAnnonce->errors = $errors;
                    $this->redirect($this->view->url(array('action' => 'add-annonce'), 'action', true));
                }
            }
        }
    }

    public function isUserAction() {
        $this->_disableLayout();
        $this->_disableView();

        $formData = $this->_request->getPost();
        $sessionAnnonce = new Zend_Session_Namespace('annonce');
        $sessionAnnonce->formData = $formData;

        $email = $this->getParam('email_connexion');
        $oUser = new Aurel_Table_User();

        $continu = true;
        $errors = array();

        if (isset($formData['categorie'])) { // Annonce
            if ($formData['categorie'] == '0') {
                $continu = false;
                $return['errors']['categorie'] = 'Veuillez selectionner une catégorie.';
            }
            if ($formData['title'] == '') {
                $continu = false;
                $return['errors']['title'] = 'Veuillez saisir un titre pour votre annonce.';
            }
            if ($formData['content'] == '') {
                $continu = false;
                $return['errors']['content'] = 'Veuillez saisir un texte pour votre annonce.';
            }
        } elseif (isset($formData['quantite'])) { // Participation évenement
            $id_article = $this->getParam('id_article');
            $oArticle = new Aurel_Table_Article();
            $article = $oArticle->getById($id_article);

            $sum = array_sum($formData['quantite']);
            if ($sum == 0) {
                $continu = false;
                $return['errors']['quantite'] = 'Veuillez selectionner au moins 1 personne';
            }
            if ($article->inscription_nominative && isset($formData['firstname']) && isset($formData['lastname'])) {
                foreach ($formData['firstname'] as $id_inscription => $firstnames) {
                    foreach ($firstnames as $firstname) {
                        if ($firstname == '') {
                            $continu = false;
                            $return['errors']['names_' . $id_inscription] = 'Veuillez indiquer les noms et prénoms des inscrits.';
                        }
                    }
                }
                foreach ($formData['lastname'] as $id_inscription => $lastnames) {
                    foreach ($lastnames as $lastname) {
                        if ($lastname == '') {
                            $continu = false;
                            $return['errors']['names_' . $id_inscription] = 'Veuillez indiquer les noms et prénoms des inscrits.';
                        }
                    }
                }
            }
        }

        $validate = new Zend_Validate_EmailAddress();
        if ($formData['email_connexion'] == '') {
            $continu = false;
            $return['errors']['email_connexion'] = 'Veuillez saisir une adresse email.';
        } elseif (!$validate->isValid($formData['email_connexion'])) {
            $continu = false;
            $return['errors']['email_connexion'] = 'Veuillez saisir une adresse email valide.';
        }

        if ($continu) {
            $user = $oUser->getByEmail($email);

            $return = array();
            $return['user'] = false;
            $return['email'] = $email;
            if ($user) {
                $return['user'] = true;
            }
        }
        echo json_encode($return);
    }

    public function rssAction() {
        $this->_disableView();
        $this->_disableLayout();

        $oArticle = new Aurel_Table_Article();
        $articles = $oArticle->getAll(null, null, true);

        $entries = array();
        foreach ($articles as $article) {
            $route = $article->annonce ? "basename_annonce" : "basename_article";
            $date = new Aurel_Date($article->date_creation);

            $entry = array(
                'title' => $article->title,
                'link' => "http://www.lepetitcharsien.com" . $this->view->url(array('basename_article' => $article->basename), $route, true),
                'description' => $this->view->cesure(strip_tags($article->content), 55),
                'lastUpdate' => $date->get(Aurel_Date::TIMESTAMP)
            );

            array_push($entries, $entry);
        }

        $rss = array(
            'title' => "Carbon12011 Licensing",
            'link' => "http://www.lepetitcharsien.com",
            'description' => "",
            //'description'   => $config->site->description,
            'charset' => "UTF-8",
            'language' => 'fr',
            'entries' => $entries
        );

        $feed = Zend_Feed::importArray($rss, 'rss');
        $rssFeed = $feed->saveXML();

        $fh = fopen(BASE_PATH . "/www/rss.xml", "w");
        fwrite($fh, $rssFeed);
        fclose($fh);
    }

    public function indexAction() {
        $title = $this->view->headTitle('Accueil', "PREPEND");
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
            $http = "https://";
        } else {
            $http = "http://";
        }
        $url = $http . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];

        $this->view->headMeta()
                ->appendProperty('og:title', strip_tags($title->toString()))
                ->appendProperty('og:type', 'website')
                ->appendProperty('og:image', '')
                ->appendProperty('og:url', $url)
                ->appendProperty('og:description', '')
                ->appendProperty('og:video', '')
                ->appendProperty('og:locale', 'fr_FR')
                ->appendProperty('og:site_name', 'La joie au 21');

        $oArticle = new Aurel_Table_Article();
        $articles = $oArticle->getAll(2, null, true);
        $paginator = Zend_Paginator::factory($articles);
        $paginator->setCurrentPageNumber(1);
        $paginator->setDefaultItemCountPerPage(2);
        $this->view->articles = $paginator;

        $articles = $oArticle->getAll(8, 2, true);
        $paginator2 = Zend_Paginator::factory($articles);
        $paginator2->setCurrentPageNumber(2);
        $paginator2->setDefaultItemCountPerPage(8);
        $this->view->articles2 = $paginator2;

        $file = "access_rapide.xml";
        $configMenu = new Zend_Config_Xml(CONFIG_PATH . "/$file", null, array('skipExtends' => true,
            'allowModifications' => true));

        $access_rapide = array();
        foreach ($this->view->menus as $menu) {
            if ($menu->sous_menus_name) {
                $liste_basename = explode(",", $menu->sous_menus_basename);
                $liste_name = explode(",", $menu->sous_menus_name);
                $liste_id = explode(",", $menu->sous_menus_id);

                foreach ($liste_basename as $key => $basename) {
                    $lblSousMenu = "sous_menu_" . $liste_id[$key];

                    if ($configMenu->sous_menu->$lblSousMenu) {
                        $access_rapide[$this->view->url(array('basename_principal' => $menu->basename, 'basename_secondaire' => $liste_basename[$key]), 'basenames', true)] = mb_strtoupper($liste_name[$key]);
                    }
                }
            } else {
                $lblMenu = "menu_" . $menu->id_menu;
                if ($configMenu->menu->$lblMenu) {
                    $access_rapide[$this->view->url(array('basename_principal' => $menu->basename), 'basenames', true)] = strtoupper($menu->name);
                }
            }
        }

        $this->view->access_rapide = $access_rapide;
    }

    public function articlesInAccueilAction() {
        $this->_disableLayout();
        $this->_disableView();

        $page = $this->getParam('page');

        $oArticle = new Aurel_Table_Article();
        $articles = $oArticle->getAll();

        $paginator = Zend_Paginator::factory($articles);
        $paginator->setCurrentPageNumber($page);
        $paginator->setDefaultItemCountPerPage(9);

        $this->view->articles = $paginator;

        $return['html'] = $this->view->render('index/articles-in-accueil.phtml');
        $return['pagination'] = $this->view->paginationControl($paginator, 'elastic', 'index/control.phtml');

        echo json_encode($return);
    }

    public function accueilannoncesAction() {
        $this->_basenamePrincipal = $this->getParam('basename_principal', null);
        $this->_basenameSecondaire = $this->getParam('basename_secondaire', null) !== '' ? $this->getParam('basename_secondaire', null) : null;

        $this->view->basename_principal = $this->_basenamePrincipal;
        $this->view->basename_secondaire = $this->_basenameSecondaire;

        $oMenu = new Aurel_Table_Menu();
        $oSousMenu = new Aurel_Table_SousMenu();
        $oArticle = new Aurel_Table_Article();

        $this->_menu = $oMenu->getByTitle($this->_basenamePrincipal);

        $this->_articles = $oArticle->getAllAnnoncesLimit($this->_config->daysArchiveAnnonce);
        if ($this->_articles) {
            $paginator = Zend_Paginator::factory($this->_articles);
            $paginator->setCurrentPageNumber(1);
            $paginator->setDefaultItemCountPerPage(12);
            $this->view->articles = $paginator;
        }

        $title = $this->view->headTitle($this->view->title, "PREPEND");
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
            $http = "https://";
        } else {
            $http = "http://";
        }
        $url = $http . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];

        $this->view->headMeta()
                ->appendProperty('og:title', strip_tags($title->toString()))
                ->appendProperty('og:type', 'article')
                ->appendProperty('og:image', '')
                ->appendProperty('og:url', $url)
                ->appendProperty('og:description', '')
                ->appendProperty('og:video', '')
                ->appendProperty('og:locale', 'fr_FR')
                ->appendProperty('og:site_name', 'La joie au 21')
        ;
    }

    public function annoncesInAccueilAction() {
        $this->_disableLayout();
        $this->_disableView();

        $page = $this->getParam('page');

        $oArticle = new Aurel_Table_Article();
        $articles = $oArticle->getAllAnnoncesLimit($this->_config->daysArchiveAnnonce);

        $paginator = Zend_Paginator::factory($articles);
        $paginator->setCurrentPageNumber($page);
        $paginator->setDefaultItemCountPerPage(12);

        $this->view->articles = $paginator;

        $return['html'] = $this->view->render('index/annonces-in-accueil.phtml');
        $return['pagination'] = $this->view->paginationControl($paginator, 'elastic', 'index/control-annonces.phtml');

        echo json_encode($return);
    }

    public function annoncesAction() {
        $this->_basenamePrincipal = $this->getParam('basename_principal', null);
        $this->_basenameSecondaire = $this->getParam('basename_secondaire', null) !== '' ? $this->getParam('basename_secondaire', null) : null;

        $this->view->basename_principal = $this->_basenamePrincipal;
        $this->view->basename_secondaire = $this->_basenameSecondaire;

        $oMenu = new Aurel_Table_Menu();
        $oSousMenu = new Aurel_Table_SousMenu();
        $oArticle = new Aurel_Table_Article();

        $this->_menu = $oMenu->getByTitle($this->_basenamePrincipal);

        if ($this->_basenameSecondaire !== null)
            $this->_sousmenu = $oSousMenu->getByTitle($this->_basenameSecondaire, $this->_menu->id_menu);

        if ($this->_basenameSecondaire !== null && $this->_sousmenu) {
            $this->view->sousmenu = $this->_sousmenu;
            $this->view->title = $this->_sousmenu->name;
            $this->view->title_articles = $this->_sousmenu->title;
            $this->_articles = $oArticle->getAllAnnoncesBySousMenu($this->_sousmenu->id_sous_menu, $this->_config->daysArchiveAnnonce);
            if ($this->_articles) {
                $this->view->articles = $this->_articles;
            }
        }
        $this->view->menu = $this->_menu;

        $title = $this->view->headTitle($this->view->title, "PREPEND");
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
            $http = "https://";
        } else {
            $http = "http://";
        }
        $url = $http . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];

        $this->view->headMeta()
                ->appendProperty('og:title', strip_tags($title->toString()))
                ->appendProperty('og:type', 'article')
                ->appendProperty('og:image', '')
                ->appendProperty('og:url', $url)
                ->appendProperty('og:description', '')
                ->appendProperty('og:video', '')
                ->appendProperty('og:locale', 'fr_FR')
                ->appendProperty('og:site_name', 'La joie au 21')
        ;

        $newTabArticles = array();
        $compteur = 0;
        $hasOlder = false;
        foreach ($this->_articles as $article) {
            $newTabArticles[$article->id_article] = $article;
        }

        $this->view->articles = $newTabArticles;

        end($newTabArticles);
        $idLastArticle = key($newTabArticles);
        reset($newTabArticles);
        $this->view->urlLink = $this->view->url(array('id_last_article' => $idLastArticle));
        $this->view->textLink = "Lire les articles plus anciens, publiés précédemment";
        $this->view->hasOlder = $hasOlder;

        if ($this->_isAjax()) {
            $this->_disableView();
            $return['html'] = $this->view->render('index/annonces-in-page.phtml');
            $return['hasOlder'] = $hasOlder;
            $return['urlLink'] = $this->view->urlLink;
            $return['ids'] = array_keys($newTabArticles);
            $return['textLink'] = $this->view->textLink;

            echo json_encode($return);
        }
    }

    /**
     * Page index
     *
     * @return void
     */
    public function pageAction() {
        $this->_basenamePrincipal = $this->getParam('basename_principal', null);
        $this->_basenameSecondaire = $this->getParam('basename_secondaire', null) !== '' ? $this->getParam('basename_secondaire', null) : null;

        $this->view->basename_principal = $this->_basenamePrincipal;
        $this->view->basename_secondaire = $this->_basenameSecondaire;

        $oMenu = new Aurel_Table_Menu();
        $oSousMenu = new Aurel_Table_SousMenu();
        $oArticle = new Aurel_Table_Article();

        $this->_menu = $oMenu->getByTitle($this->_basenamePrincipal);
        $this->view->popupArticles = true;

        if ($this->_basenameSecondaire !== null)
            $this->_sousmenu = $oSousMenu->getByTitle($this->_basenameSecondaire, $this->_menu->id_menu);

        if ($this->_menu && $this->_menu->agenda) {
            $this->_forward("agenda");
        } elseif ($this->_menu && $this->_menu->annonces && $this->_sousmenu && $this->_sousmenu->sous_menu_annonce) {
            $this->_forward("annonces");
        } elseif ($this->_menu && $this->_menu->annonces && !$this->_sousmenu) {
            $this->_forward("accueilannonces");
        } elseif ($this->_menu && $this->_sousmenu && $this->_sousmenu->sous_menu_annuaire) {
            $this->_forward("index", "annuaire", "default");
        } elseif ($this->_menu) {
            if ($this->_basenameSecondaire === null) {
                $sousmenus = $oSousMenu->getAllByMenu($this->_menu->id_menu);
                if ($sousmenus->count() > 0) {
                    $this->view->popupArticles = false;
                }
            }
            if ($this->_basenameSecondaire !== null && $this->_sousmenu) {
                $this->view->sousmenu = $this->_sousmenu;
                $this->view->title = $this->_sousmenu->name;
                $this->view->title_articles = $this->_sousmenu->title;
                $this->_articles = $oArticle->getAllBySousMenu($this->_sousmenu->id_sous_menu, $this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_ADMIN_ARTICLES), $this->_getUser());
                if ($this->_articles) {
                    $this->view->articles = $this->_articles;
                }
            } else {
                $this->view->menu = $this->_menu;
                $this->_articles = $oArticle->getAllByMenu($this->_menu->id_menu, $this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_ADMIN_ARTICLES), $this->_getUser());
                if ($this->_articles) {
                    $this->view->articles = $this->_articles;
                }
                $this->view->title = $this->_menu->name;
                $this->view->title_articles = $this->_menu->title;
            }
            $this->view->menu = $this->_menu;
            if (!$this->_menu->news && $this->_articles->count() == 1) {
                $article = $this->_articles->current();
                $this->redirect($this->view->url(array('basename_article' => $article->basename), 'basename_article'));
            }

            $title = $this->view->headTitle($this->view->title, "PREPEND");
            if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
                $http = "https://";
            } else {
                $http = "http://";
            }
            $url = $http . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];

            $this->view->headMeta()
                    ->appendProperty('og:title', strip_tags($title->toString()))
                    ->appendProperty('og:type', 'article')
                    ->appendProperty('og:image', '')
                    ->appendProperty('og:url', $url)
                    ->appendProperty('og:description', '')
                    ->appendProperty('og:video', '')
                    ->appendProperty('og:locale', 'fr_FR')
                    ->appendProperty('og:site_name', 'La joie au 21')
            ;

            $newTabArticles = array();
            $compteur = 0;
            $hasOlder = false;
            if (!$this->hasParam('id_last_article')) {
                $dateLimiteDebut = new Zend_Date();
                $dateLimiteDebut->setTime("00:00:00")->subDay($this->_config->delaiArticlesPage);

                foreach ($this->_articles as $article) {
                    if (($compteur < $this->_config->minArticlesInPage || $article->date_creation >= $dateLimiteDebut->get(Aurel_Date::MYSQL_DATETIME))) {
                        $newTabArticles[$article->id_article] = $article;
                        $compteur++;
                    } else {
                        $hasOlder = true;
                        break;
                    }
                }
            } else {
                $id_last_article = $this->getParam('id_last_article');
                $affiche = false;
                foreach ($this->_articles as $article) {
                    if ($affiche) {
                        if ($compteur < $this->_config->minArticlesInPage) {
                            $newTabArticles[$article->id_article] = $article;
                            $compteur++;
                        } else {
                            $hasOlder = true;
                            break;
                        }
                    }
                    if ($article->id_article == $id_last_article) {
                        $affiche = true;
                    }
                }
            }

            $this->view->articles = $newTabArticles;

            end($newTabArticles);
            $idLastArticle = key($newTabArticles);
            reset($newTabArticles);
            $this->view->urlLink = $this->view->url(array('id_last_article' => $idLastArticle));
            $this->view->textLink = "Lire les articles plus anciens, publiés précédemment";
            $this->view->hasOlder = $hasOlder;

            if ($this->_isAjax()) {
                $this->_disableView();
                $return['html'] = $this->view->render('index/articles-in-page.phtml');
                $return['hasOlder'] = $hasOlder;
                $return['urlLink'] = $this->view->urlLink;
                $return['ids'] = array_keys($newTabArticles);
                $return['textLink'] = $this->view->textLink;

                echo json_encode($return);
            }
        } else {
            $this->redirect($this->view->url(array(), 'basenames', true));
        }
    }

    public function agendaAction() {
        $this->view->headTitle("Agenda", "PREPEND");
        $oArticle = new Aurel_Table_Article();
        $avenir = $oArticle->getAvenir();
        $this->view->avenir = $avenir;

        $passe = $oArticle->getPasse();

        $newTabArticles = array();
        $compteur = 0;
        $hasOlder = false;
        if (!$this->hasParam('id_last_article')) {
            $dateLimiteDebut = new Zend_Date();
            $dateLimiteDebut->setTime("00:00:00")->subDay($this->_config->delaiArticlesPage);

            foreach ($passe as $article) {
                if (($compteur < $this->_config->minArticlesInPage || $article->start_date >= $dateLimiteDebut->get(Aurel_Date::MYSQL_DATE))) {
                    $newTabArticles[$article->id_article] = $article;
                    $compteur++;
                } else {
                    $hasOlder = true;
                    break;
                }
            }
        } else {
            $id_last_article = $this->getParam('id_last_article');
            $affiche = false;
            foreach ($passe as $article) {
                if ($affiche) {
                    if ($compteur < $this->_config->minArticlesInPage) {
                        $newTabArticles[$article->id_article] = $article;
                        $compteur++;
                    } else {
                        $hasOlder = true;
                        break;
                    }
                }
                if ($article->id_article == $id_last_article) {
                    $affiche = true;
                }
            }
        }

        $this->view->passe = $newTabArticles;

        end($newTabArticles);
        $idLastArticle = key($newTabArticles);
        reset($newTabArticles);
        $this->view->urlLink = $this->view->url(array('id_last_article' => $idLastArticle));
        $this->view->textLink = "Voir les événements plus anciens";
        $this->view->hasOlder = $hasOlder;

        if ($this->_isAjax()) {
            $this->_disableView();
            $return['html'] = $this->view->render('index/ont-eu-lieu.phtml');
            $return['hasOlder'] = $hasOlder;
            $return['urlLink'] = $this->view->urlLink;
            $return['ids'] = array_keys($newTabArticles);
            $return['textLink'] = $this->view->textLink;

            echo json_encode($return);
        }
    }

    /**
     * 
     */
    public function boutiqueAction() {
        if (!$this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_MEMBRE) && $this->_config->connexion_access_code && !isset($_COOKIE["access_code_ok"])) {
            $this->_helper->layout->setLayout('access_code');
        } else {
            $this->_helper->layout->setLayout('main_iframe');
        }
    }

    /**
     * 
     */
    public function pronostiquesAction() {
        if (!$this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_MEMBRE) && $this->_config->connexion_access_code && !isset($_COOKIE["access_code_ok"])) {
            $this->_helper->layout->setLayout('access_code');
        } else {
            $this->_helper->layout->setLayout('main_iframe');
        }
    }

    public function pageIndivAction() {
        $sessionAnnonce = new Zend_Session_Namespace('annonce');
        $this->view->valideParticipation = $sessionAnnonce->valideParticipation;

        $basename_article = $this->getParam('basename_article', null) !== '' ? $this->getParam('basename_article', null) : null;

        $oArticle = new Aurel_Table_Article();
        $article = $oArticle->getByTitle($basename_article);
        if ($article) {
            $this->view->article = $article;
            $title = $this->view->headTitle($article->title, "PREPEND");

            if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
                $http = "https://";
            } else {
                $http = "http://";
            }
            $url = $http . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];

            $this->view->headMeta()
                    ->appendProperty('og:title', strip_tags($title->toString()))
                    ->appendProperty('og:type', 'article')
                    ->appendProperty('og:url', $url)
                    ->appendProperty('og:description', substr(strip_tags($article->content), 0, 300))
                    ->appendProperty('og:locale', 'fr_FR')
                    ->appendProperty('og:site_name', 'La joie au 21');

            $urlImage = "";
            $urlVideo = "";
            if ($article->picture) {
                $photo = $article->getIdPhotos(1);
                $photo = $photo[0];
                $urlImage = $http . $_SERVER["HTTP_HOST"] . "/images/upload/{$article->id_article}/$photo";
            } elseif ($article->youtube) {
                $urlImage = "http://img.youtube.com/vi/{$article->youtube}/maxresdefault.jpg";
                $urlVideo = "https://www.youtube.com/watch?v=" . $article->youtube;
            } else {
                if ($article->annonce) {
                    $urlImage = $http . $_SERVER["HTTP_HOST"] . "/images/no-photo-annonce.jpg";
                } else {
                    $urlImage = $http . $_SERVER["HTTP_HOST"] . "/images/no-photo.jpg";
                }
            }

            $this->view->headMeta()->appendProperty('og:image', $urlImage);
            $properties = getimagesize($urlImage);
            $width = $properties[0];
            $height = $properties[1];
            $this->view->headMeta()->appendProperty('og:image:width', $width);
            $this->view->headMeta()->appendProperty('og:image:height', $height);
            //$this->view->headMeta()->appendProperty('og:video', $urlVideo);

            $oUser = new Aurel_Table_User();
            $annonceur = $oUser->getById($article->id_user_creation);
            $this->view->annonceur = $annonceur;

            if ($article->id_menu) {
                $oMenu = new Aurel_Table_Menu();
                $menu = $oMenu->getById($article->id_menu);
                $this->view->menu = $menu;

                $count = $oArticle->getAllByMenu($article->id_menu)->count();
                $this->view->basename_principal = $menu->basename;
            }
            if ($article->id_sous_menu) {
                $oMenu = new Aurel_Table_Menu();
                $oSousMenu = new Aurel_Table_SousMenu();
                $sousmenu = $oSousMenu->getById($article->id_sous_menu);
                $menu = $oMenu->getById($sousmenu->id_menu);
                $this->view->menu = $menu;
                $this->view->sousmenu = $sousmenu;
                $count = $oArticle->getAllBySousMenu($sousmenu->id_sous_menu)->count();
                $this->view->basename_principal = $menu->basename;
                $this->view->basename_secondaire = $sousmenu->basename;
            }

            $this->view->count = $count;

            if ($article->inscription_fct && $this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_MEMBRE)) {
                $oInscription = new Aurel_Table_Inscription();
                $oInscriptionHasUser = new Aurel_Table_InscriptionHasUser();

                $inscriptions = $oInscription->getByArticle($article->id_article);
                $tabLibelle = array();
                foreach ($inscriptions as $inscription) {
                    $tabLibelle[$inscription->id_inscription] = $inscription->name;
                }
                $inscriptionshasuser = $oInscriptionHasUser->getByUserAndArticle($this->_getUser()->id_user, $article->id_article);
                $tabQuantites = array();
                foreach ($inscriptionshasuser as $inscriptionhasuser) {
                    $tabQuantites[$inscriptionhasuser->id_inscription] = $inscriptionhasuser->quantite;
                }

                $hasParticipate = !empty($tabQuantites);

                $this->view->hasParticipate = $hasParticipate;
                $this->view->tabQuantites = $tabQuantites;
                $this->view->tabLibelle = $tabLibelle;
            }
            if ($article->id_sondage) {
                $oSondage = new Aurel_Table_Sondage();
                $sondage = $oSondage->getById($article->id_sondage);
                $this->view->sondage = $sondage;
            }
        }
        $this->view->ajax = $this->_isAjax();
    }

    public function diaporamaAction() {
        //$this->_disableLayout();
        $this->view->headMeta()->appendName('robots', 'noindex,nofollow');

        if ($this->hasParam('id_article')) {
            $oArticle = new Aurel_Table_Article();
            $article = $oArticle->getById($this->getParam('id_article'));

            $photos = $article->getPhotos();

            $this->view->article = $article;
            $this->view->photos = $photos;
        } elseif ($this->hasParam('id_annuaire_fiche')) {
            $oAnnuaireFiche = new Aurel_Table_AnnuaireFiche();
            $fiche = $oAnnuaireFiche->getById($this->getParam('id_annuaire_fiche'));

            $photos = $fiche->getPhotos();

            $this->view->fiche = $fiche;
            $this->view->photos = $photos;
        }
    }

    public function videoAction() {
        //$this->_disableLayout();

        $this->view->headMeta()->appendName('robots', 'noindex,nofollow');
        $oArticle = new Aurel_Table_Article();
        $article = $oArticle->getById($this->getParam('id_article'));

        $this->view->video = $article->youtube;
        $this->view->article = $article;
    }

    public function newsletterAction() {
        $this->_disableView();
        if (!$this->hasParam('id_newsletter'))
            throw new Zend_Exception();
        $id_newsletter = $this->getParam('id_newsletter');

        $oNewsletter = new Aurel_Table_Newsletter();
        $newsletter = $oNewsletter->getById($id_newsletter);

        $user = $this->getParam('user');
        if ($user) {
            $oUser = new Aurel_Table_User();
            $userObject = $oUser->getByEncodedEmail($user);
            if ($userObject) {
                $newsletter = str_replace("#emailEncoded#", $user, $newsletter->body);
            }
        }

        $this->_helper->layout->setLayout("email");

        echo $newsletter;
    }

    /**
     * Page terms
     *
     * @return void
     */
    public function termsAction() {
        $this->view->content = file_get_contents(CONFIG_PATH . '/terms.txt');
    }

    public function nopopupAction() {
        $this->_disableLayout();
        $this->_disableView();

        $sessionPopup = new Zend_Session_Namespace('popup');
        //$sessionPopup->setExpirationSeconds(10);
        $sessionPopup->noPopup = true;
    }

    public function contactAction() {
        $message = new Zend_Session_Namespace('message');
        $this->view->message = $message;
        $this->view->title = 'Contact';

        $oMenu = new Aurel_Table_Menu();

        $this->_menu = $oMenu->getByTitle('contact');
        $picture = $this->_menu->picture;
        $picturePosition = $this->_menu->picture_position;
        $this->view->picture = $picture;
        $this->view->picture_position = $picturePosition;
        $this->view->modifyPicture = isset($picture);

        $formData = $this->_request->getPost();
        if ($formData) {
            $this->_disableLayout();
            $this->_disableView();

            $validate = new Zend_Validate_EmailAddress();
            $validate->setMessage("%value% n'est pas une adresse email valide", Zend_Validate_EmailAddress::INVALID_FORMAT);

            $filters = array(
                '*' => array('StringTrim')
            );
            $validators = array(
                'prenom' => array(),
                'nom' => array(),
                'adresse' => array(),
                'objet' => array(),
                'message' => array(),
                'email' => array($validate),
                'email2' => array($validate)
            );
            $options = array(
                'missingMessage' => "'%field%' est obligatoire",
                'notEmptyMessage' => "'%field%' est obligatoire"
            );
            $return = array();
            $input = new Zend_Filter_Input($filters, $validators, $formData, $options);
            if ($input->hasInvalid() || $input->hasMissing()) {
                $messageSortie = '';
                $elementsError = array();
                foreach ($input->getMessages() as $elt => $type) {
                    $elementsError[$elt] = true;
                    foreach ($type as $sortie) {
                        $messageSortie .= $sortie . '<br/>';
                    }
                }


                if ($formData['email'] != $formData['email2']) {
                    $messageSortie .= 'Les 2 adresses email ne sont pas identiques' . '<br/>';
                    if (!isset($elementsError['email']))
                        $elementsError['email'] = true;
                    if (!isset($elementsError['email2']))
                        $elementsError['email2'] = true;
                }

                $return['returncode'] = 'ko';
                $return['error'] = $messageSortie;
                $return['elementsError'] = $elementsError;
            }
            elseif ($formData['email'] != $formData['email2']) {
                $return['returncode'] = 'ko';
                $return['error'] = 'Les 2 adresses email ne sont pas identiques';
                $return['elementsError'] = array('email', 'email2');
            } else {
                $oMail = new Aurel_Table_Mail();

                $new = $oMail->createRow();
                $new->prenom = stripslashes($formData['prenom']);
                $new->nom = stripslashes($formData['nom']);
                $new->adresse = stripslashes($formData['adresse']);
                $new->email = stripslashes($formData['email']);
                $new->objet = stripslashes($formData['objet']);
                $new->message = stripslashes($formData['message']);
                $new->status = Aurel_Table_Mail::STATUS_INIT;
                $new->date_creation = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
                $new->comHash = md5(uniqid());

                $new->save();

                $link = "http://" . $_SERVER['HTTP_HOST'] . $this->view->url(array('action' => 'confirm-mail', 'controller' => 'index'), 'action', true) . "?c=" . $new->comHash;

                $body = "Vous avez utilisé le formulaire de contact sur le site Chars Horizon 2020 :\n\n";
                $body .= "<strong>Prenom :</strong>\n" . stripslashes($formData['prenom']) . "\n";
                $body .= "<strong>Nom :</strong>\n" . stripslashes($formData['nom']) . "\n";
                $body .= "<strong>Adresse :</strong>\n" . stripslashes($formData['adresse']) . "\n";
                $body .= "<strong>Email :</strong>\n" . stripslashes($formData['email']) . "\n";
                $body .= "<strong>Objet :</strong>\n" . stripslashes($formData['objet']) . "\n";
                $body .= "<strong>Message :</strong>\n" . stripslashes($formData['message']) . "\n\n";
                $body .= "Veuillez confirmer l'envoi de ce message en cliquant sur ce lien :\n";
                $body .= "<a href='$link'>$link</a>:\n";

                $mail = new Zend_Mail('utf-8');
                $mail->addTo($new->email)
                        ->setFrom($this->_config->emailReception)
                        ->setSubject('[CHARS HORIZON 2020] CONFIRMATION DE VOTRE MESSAGE')
                        ->setBodyHtml(nl2br($body));

                try {
                    if ($mail->send()) {
                        $new->status = Aurel_Table_Mail::STATUS_WAIT;
                        $new->date_creation = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
                        $new->save();
                        $return['returncode'] = 'ok';
                    } else {
                        $return['returncode'] = 'ko';
                    }
                } catch (Exception $e) {
                    $return['returncode'] = 'ko';
                    $return['error'] = $e->getMessage();
                }


                $return['link'] = $link;
            }
            echo json_encode($return);
            //$this->redirect($this->view->url(array(),'contact',true));
        }
    }

    public function confirmMailAction() {
        $oMenu = new Aurel_Table_Menu();
        $this->view->title = 'Contact';
        $this->_menu = $oMenu->getByTitle('contact');
        $picture = $this->_menu->picture;
        $picturePosition = $this->_menu->picture_position;
        $this->view->picture = $picture;
        $this->view->picture_position = $picturePosition;
        $this->view->modifyPicture = isset($picture);

        $comHash = $this->getParam('c');
        $oMail = new Aurel_Table_Mail();
        $message = $oMail->getByComHash($comHash);

        if ($message && $message->status == Aurel_Table_Mail::STATUS_WAIT) {
            $body = "<strong>Prenom :</strong>\n" . $message->prenom . "\n";
            $body .= "<strong>Nom :</strong>\n" . $message->nom . "\n";
            $body .= "<strong>Adresse :</strong>\n" . $message->adresse . "\n";
            $body .= "<strong>Email :</strong>\n" . $message->email . "\n";
            $body .= "<strong>Objet :</strong>\n" . $message->objet . "\n";
            $body .= "<strong>Message :</strong>\n" . $message->message . "\n\n";

            $mail = new Zend_Mail('utf-8');
            $mail->addTo($this->_config->emailReception)
                    ->setFrom($this->_config->emailReception)
                    ->setSubject('[CHARS HORIZON 2020] Nouveau message de ' . $message->email)
                    ->setBodyHtml(nl2br($body));

            try {
                if ($mail->send()) {
                    $message->status = Aurel_Table_Mail::STATUS_SENT;
                    $message->date_validation = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
                    $message->save();
                    $this->view->sent = true;
                }
            } catch (Exception $e) {
                
            }
        } else {
            $this->view->already = true;
        }
    }

    public function downloadAction() {
        $this->_disableLayout();
        $this->_disableView();

        $oFile = new Aurel_Table_File();
        $file = $oFile->getById($this->getParam('id_file'));

        $upload_dir = UPLOAD_PATH . "/files/";
        $pathfile = $upload_dir . $file->basename . "." . $file->extension;

        header('Content-Type: ' . $file->type);
        header("Content-Transfer-Encoding: Binary");
        header("Content-disposition: attachment; filename=\"" . $file->name . "." . $file->extension . "\"");
        readfile($pathfile);
    }

    /**
     * Check if dir exist, if not creates it
     *
     * @param string $dir
     * @return void
     */
    private function _check_dir($dir) {
        if (!is_dir($dir)) {
            mkdir($dir);
        }
    }

    public function uploadTmpAction() {
        $this->_disableLayout();
        $this->_disableView();

        $upload_dir = UPLOAD_PATH . "/";
        $this->_check_dir($upload_dir);
        $upload_dir .= "tmp/";
        $this->_check_dir($upload_dir);

        $return = array();
        $return['returncode'] = 'ko';

        if ($_FILES['images']['error'] == 0) {
            $pic = $_FILES['images'];
            $extension = strtolower(pathinfo($pic['name'], PATHINFO_EXTENSION));

            $name = uniqid() . '.' . $extension;
            $upload_path = $upload_dir . $name;
            $upload_paththumb = $upload_dir . 'thumb' . $name;
            $upload_pathsmallthumb = $upload_dir . 'smallthumb' . $name;
            $upload_pathminithumb = $upload_dir . 'minithumb' . $name;

            if (move_uploaded_file($pic['tmp_name'], $upload_path)) {
                include('SimpleImage.php');
                $img = new abeautifulsite\SimpleImage($upload_path);
                $img->auto_orient();

                $img->best_fit(1000, 1000);
                $img->save($upload_path, 80);

                $img->adaptive_resize(480, 360);
                $img->save($upload_paththumb, 80);

                $img->adaptive_resize(200, 200);
                $img->save($upload_pathsmallthumb, 80);

                $img->adaptive_resize(40, 40);
                $img->save($upload_pathminithumb, 80);

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

    public function cancelInscriptionAction() {
        if ($this->hasParam('id_article'))
            $id_article = $this->getParam('id_article');
        else
            throw new Zend_Acl_Exception();

        $oArticle = new Aurel_Table_Article();
        $oInscription = new Aurel_Table_Inscription();
        $oInscriptionHasUser = new Aurel_Table_InscriptionHasUser();

        $article = $oArticle->getById($id_article);

        if ($this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_MEMBRE)) {
            $inscriptionshasuser = $oInscriptionHasUser->getByUserAndArticle($this->_getUser()->id_user, $id_article);
            foreach ($inscriptionshasuser as $inscriptionhasuser) {
                $inscriptionhasuser->delete();
            }
        }
        if ($this->hasParam('url_retour'))
            $url_retour = urldecode($this->getParam('url_retour'));
        else
            $url_retour = $this->view->url(array('basename_article' => $article->basename), 'basename_article', true);

        $this->redirect($url_retour);
    }

    public function participerAction() {
        $formData = $this->_request->getPost();
        $sessionAnnonce = new Zend_Session_Namespace('annonce');
        if (!$formData && $this->hasParam('valid') && $this->getParam('valid') == '1') {
            $formData = $sessionAnnonce->formData;
        }

        if ($this->hasParam('id_article'))
            $id_article = $this->getParam('id_article');
        elseif (isset($formData['id_article']))
            $id_article = $formData['id_article'];
        else
            throw new Zend_Acl_Exception();

        $oArticle = new Aurel_Table_Article();
        $oInscription = new Aurel_Table_Inscription();
        $oInscriptionHasUser = new Aurel_Table_InscriptionHasUser();
        $oUser = new Aurel_Table_User();

        $article = $oArticle->getById($id_article);
        $inscriptions = $oInscription->getByArticle($id_article);

        $tabQuantites = array();
        $tabLibelles = array();
        if ($article->inscription_nominative) {
            $tabFirstname = array();
            $tabLastname = array();
        }
        foreach ($inscriptions as $inscription) {
            $tabQuantites[$inscription->id_inscription] = 0;
            $tabLibelles[$inscription->id_inscription] = $inscription->name;
        }
        $comment = "";
        if ($this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_MEMBRE)) {
            $inscriptionshasuser = $oInscriptionHasUser->getByUserAndArticle($this->_getUser()->id_user, $id_article);
            foreach ($inscriptionshasuser as $inscriptionhasuser) {
                $tabQuantites[$inscriptionhasuser->id_inscription] = $inscriptionhasuser->quantite;
                $comment = $inscriptionhasuser->comment;
                if ($article->inscription_nominative) {
                    $tabFirstname[$inscriptionhasuser->id_inscription] = explode("#", $inscriptionhasuser->firstnames);
                    $tabLastname[$inscriptionhasuser->id_inscription] = explode("#", $inscriptionhasuser->lastnames);
                }
            }
        }
        $solde = $article->inscription_quantite_limite;
        if ($article->inscription_quantite_limite != null) {
            $inscriptionshasuser = $oInscriptionHasUser->getByArticle($id_article);
            $sum = 0;
            foreach ($inscriptionshasuser as $ins) {
                $sum += $ins->quantite;
            }

            $solde = $solde - $sum;
            $solde += array_sum($tabQuantites);
        }

        $max = $solde !== null ? $solde : 10;
        $tabChiffre = array();
        for ($i = 0; $i <= $max; $i++) {
            $tabChiffre[$i] = $i;
        }

        $hasParticipate = array_sum($tabQuantites) > 0;
        $this->view->hasParticipate = $hasParticipate;
        $this->view->solde = $solde;
        $this->view->tabChiffre = $tabChiffre;
        $this->view->comment = $comment;
        $this->view->tabQuantites = $tabQuantites;
        $this->view->article = $article;
        $this->view->inscriptions = $inscriptions;
        if ($article->inscription_nominative) {
            $this->view->tabFirstname = $tabFirstname;
            $this->view->tabLastname = $tabLastname;
        }

        if ($formData) {
            $this->_disableLayout();
            $this->_disableView();

            $return = array();
            $continu = true;
            $sum = array_sum($formData['quantite']);
            if ($sum == 0) {
                $continu = false;
                $return['errors']['quantite'] = 'Veuillez selectionner au moins 1 personne';
            }
            if ($article->inscription_nominative && isset($formData['firstname']) && isset($formData['lastname'])) {
                foreach ($formData['firstname'] as $id_inscription => $firstnames) {
                    foreach ($firstnames as $firstname) {
                        if ($firstname == '') {
                            $continu = false;
                            $return['errors']['names_' . $id_inscription] = 'Veuillez indiquer les noms et prénoms des inscrits.';
                        }
                    }
                }
                foreach ($formData['lastname'] as $id_inscription => $lastnames) {
                    foreach ($lastnames as $lastname) {
                        if ($lastname == '') {
                            $continu = false;
                            $return['errors']['names_' . $id_inscription] = 'Veuillez indiquer les noms et prénoms des inscrits.';
                        }
                    }
                }
            }

            if ($continu) {
                $stringQte = "";
                foreach ($formData['quantite'] as $id_inscription => $quantite) {
                    $row = $oInscriptionHasUser->find($id_inscription, $this->_getUser()->id_user)->current();
                    if (!$row) {
                        $row = $oInscriptionHasUser->createRow();
                        $row->id_inscription = $id_inscription;
                        $row->id_user = $this->_getUser()->id_user;
                    }
                    $row->date_inscription = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
                    $row->quantite = $quantite;
                    $row->comment = $formData["comment"];
                    if ($article->inscription_nominative && isset($formData['firstname'][$id_inscription]) && isset($formData['lastname'][$id_inscription])) {
                        $row->firstnames = implode("#", $formData['firstname'][$id_inscription]);
                        $row->lastnames = implode("#", $formData['lastname'][$id_inscription]);
                    }
                    if ($quantite > 0) {
                        $stringQte .= "{$tabLibelles[$id_inscription]} : $quantite\n";
                        $row->save();
                    } else
                        $row->delete();
                }


                $subject = "Votre inscription pour {$article->title}";
                $body = "Votre inscription a bien été prise en compte\n" .
                        $stringQte .
                        "Votre commentaire : {$formData["comment"]}\n\n" .
                        "<a href='http://{$_SERVER['HTTP_HOST']}/compte/reservations/open_modal/1/id_article/{$article->id_article}'>Je modifie mon inscription</a>\n\n";

                $user = $this->_getUser();
                $mail = new Aurel_Mailer("utf-8");
                $mail->setBodyHtmlWithDesign($body, $subject)
                        ->setSubject($subject)
                        ->addTo($user->email, $user->firstname . " " . $user->lastname)
                        ->setFrom("no-reply@lepetitcharsien.com", "Carbon12011 Licensing");
                try {
                    $mail->send();
                } catch (Exception $e) {
                    //echo $mail->getBodyHtml();
                }

                $url = "/action/synthese-inscription/id_article/{$article->id_article}/adminConnect/1/?url_retour=/admin/articles/reservations";
                $subject = "Nouvelle inscription pour {$article->title}";
                $body = "Nom : {$user->lastname}\n" .
                        "Prénom : {$user->firstname}\n" .
                        "Email : {$user->email}\n" .
                        $stringQte .
                        "Commentaire : {$formData["comment"]}\n\n" .
                        "<a href='http://{$_SERVER['HTTP_HOST']}{$url}'>Accéder à la synthèse des inscrits</a>\n\n";
                $return['emailAnnonceur'] = $body;
                $annonceur = $oUser->getById($article->id_user_creation);
                $mail = new Aurel_Mailer("utf-8");
                $mail->setBodyHtmlWithDesign($body, $subject)
                        ->setSubject($subject)
                        ->addTo($annonceur->email, $annonceur->firstname . " " . $annonceur->lastname)
                        ->setFrom("no-reply@lepetitcharsien.com", "Carbon12011 Licensing");
                try {
                    $mail->send();
                } catch (Exception $e) {
                    //echo $mail->getBodyHtml();
                }

                $sessionAnnonce->unsetAll();
                $sessionAnnonce->valideParticipation = 1;
                $sessionAnnonce->setExpirationHops(1);

                if ($this->hasParam('url_retour'))
                    $url_retour = urldecode($this->getParam('url_retour'));
                else
                    $url_retour = $this->view->url(array('basename_article' => $article->basename), 'basename_article', true);

                $return['url_redirect'] = $url_retour;
                if (!$this->_isAjax())
                    $this->redirect($url_retour);
            }

            echo json_encode($return);
        }
    }

    public function syntheseInscriptionAction() {
        if ($this->hasParam('admin')) {
            $url_encode = urlencode($_SERVER["REQUEST_URI"]);
            $url_redirect = "/admin/index/login/url_redirect/$url_encode?pageForbidden=1";

            $this->redirect($url_redirect);
        }
        if ($this->hasParam('id_article'))
            $id_article = $this->getParam('id_article');
        else
            throw new Zend_Acl_Exception();

        $oArticle = new Aurel_Table_Article();
        $oInscription = new Aurel_Table_Inscription();
        $oInscriptionHasUser = new Aurel_Table_InscriptionHasUser();

        $article = $oArticle->getById($id_article);
        $inscriptions = $oInscription->getByArticle($id_article);
        $inscriptionshasuser = $oInscriptionHasUser->getByArticle($id_article);

        if ($this->hasParam('url_retour')) {
            $this->view->url_retour = urldecode($this->getParam('url_retour'));
        } else
            $this->view->url_retour = $this->view->url(array('basename_article' => $article->basename), 'basename_article', true);

        $tabCategories = array();
        $tabSumCategories = array();
        foreach ($inscriptions as $inscription) {
            $tabCategories[$inscription->id_inscription] = $inscription->name;
            $tabSumCategories[$inscription->id_inscription] = 0;
        }

        $tabInscription = array();
        $sumTotal = 0;
        foreach ($inscriptionshasuser as $inscription) {
            $sumTotal += $inscription->quantite;
            $tabSumCategories[$inscription->id_inscription] += $inscription->quantite;
        }

        $this->view->sumTotal = $sumTotal;
        $this->view->sumCategories = $tabSumCategories;
        $this->view->categories = $tabCategories;
        $this->view->article = $article;
    }

    public function namesAction() {
        $id_article = $this->getParam('id_article');
        $id_inscription = $this->getParam('id_inscription');
        $return = $this->getParam('return');
        $this->view->return = $return && $return == '1' ? $this->view->url(array('action' => 'synthese-inscription', 'id_inscription' => null, 'return' => null)) : null;

        $oArticle = new Aurel_Table_Article();
        $oInscription = new Aurel_Table_Inscription();
        $oInscriptionHasUser = new Aurel_Table_InscriptionHasUser();
        $oUser = new Aurel_Table_User();

        $article = $oArticle->getById($id_article);
        $inscription = $oInscription->getById($id_inscription);
        $inscriptionshasuser = $oInscriptionHasUser->getByArticleAndInscription($id_article, $id_inscription, true);

        $sum = 0;
        $lastnames = array();
        $firstnames = array();
        $emetteurs = array();
        $tabFinal = array();
        foreach ($inscriptionshasuser as $inscriptionhasuser) {
            $sum += $inscriptionhasuser->quantite;

            if ($article->inscription_nominative) {
                $tabFirstname = explode("#", $inscriptionhasuser->firstnames);
                $tabLastname = explode("#", $inscriptionhasuser->lastnames);

                foreach ($tabFirstname as $firstname) {
                    $firstnames[] = $firstname;
                }
                foreach ($tabLastname as $lastname) {
                    $lastnames[] = $lastname;
                    $emetteurs[] = $inscriptionhasuser->firstname . ' ' . $inscriptionhasuser->lastname;
                }
            } else {
                $tabFinal[$inscriptionhasuser->id_user] = $inscriptionhasuser->quantite;
                $emetteurs[$inscriptionhasuser->id_user] = $inscriptionhasuser->firstname . ' ' . $inscriptionhasuser->lastname;
            }
        }
        if ($article->inscription_nominative)
            array_multisort($lastnames, SORT_ASC, $firstnames, SORT_ASC, $emetteurs, SORT_ASC);
        else
            array_multisort($emetteurs, SORT_ASC, $tabFinal);

        $this->view->lastnames = $lastnames;
        $this->view->firstnames = $firstnames;
        $this->view->emetteurs = $emetteurs;
        $this->view->sum = $sum;
        $this->view->article = $article;
        $this->view->tabFinal = $tabFinal;
        $this->view->inscription = $inscription;
    }

    public function testAction() {
        $this->_disableView();
        Zend_Debug::dump($_SERVER["HTTP_USER_AGENT"]);
    }

    public function annuaireAction() {
        
    }

    /**
     *
     */
    public function showPopupOtherAction() {
        $bootstrap = $this->getInvokeArg('bootstrap');
        $appinidata = $bootstrap->getOptions();
        $cookie_domain = null;
        if (isset($appinidata['resources']['session']) && isset($appinidata['resources']['session']['cookie_domain']))
            $cookie_domain = $appinidata['resources']['session']['cookie_domain'];

        setcookie(
                'popup_other', 1, time() + 3600 * 24, '/', $cookie_domain
        );
    }

    function beforeSave(Aurel_Table_Row_Article $article) {
        $content = strtolower(html_entity_decode(strip_tags($article->content)));
        $words = preg_split(
                '/((^\p{P}+)|(\p{P}*\s+\p{P}*)|(\p{P}+$))/', $content, -1, PREG_SPLIT_NO_EMPTY
        );
        $soundexWords = array();
        foreach ($words as $word) {
            $soundexWords[] = Aurel_Phonetique::convert($word);
        }
        $article->content_soundex = implode(' ', $soundexWords);

        $title = strtolower(html_entity_decode(strip_tags($article->title)));
        $words = preg_split(
                '/((^\p{P}+)|(\p{P}*\s+\p{P}*)|(\p{P}+$))/', $title, -1, PREG_SPLIT_NO_EMPTY
        );
        $soundexWords = array();
        foreach ($words as $word) {
            $soundexWords[] = Aurel_Phonetique::convert($word);
        }
        $article->title_soundex = implode(' ', $soundexWords);


        $article->save();
    }

    /**
     * Recherche
     */
    public function searchAction() {
        $oArticle = new Aurel_Table_Article();

        /*
          $articles = $oArticle->fetchAll('title_soundex is null');

          foreach($articles as $article){
          $this->beforeSave($article);
          } */

        $q = $this->getParam('q');

        if ($q) {

            $oArticle = new Aurel_Table_Article();
            $articles = $oArticle->search($q, null, null);

            $this->view->articles = $articles;
            $this->view->q = $q;
        }
    }

    public function checkAccessCodeAction() {
        $this->_disableLayout();
        $this->_disableView();

        $return = [];
        $return['redirect'] = false;

        $oAccessCode = new Aurel_Table_AccessCode();

        $url_redirect = urldecode($this->getParam('url_redirect'));

        $formData = $this->getRequest()->getPost();
        if ($formData) {
            $access_code = $oAccessCode->getByCode($formData['access_code']);

            if ($access_code) {
                $dateToday = new Aurel_Date();
                
                $date_start = new Aurel_Date($access_code->date_start);
                $date_start->setTime('00:00:00');
                
                $date_end = new Aurel_Date($access_code->date_end);
                $date_end->setTime('00:00:00');
                
                if($dateToday->isLater($date_start) && $dateToday->isEarlier($date_end)){
                    setcookie(
                        'access_code_ok', 1, time() + 3600 * $access_code->delai, '/'
                    );

                    $access_code->count++;
                    $access_code->save();

                    $return['redirect'] = true;
                    $return['redirect_url'] = $url_redirect;
                    $return['message'] = "Code opération valide, redirection en cours";
                    $return['message_class'] = "alert-success";
                } else {
                    if($dateToday->isEarlier($date_start)){
                        $return['message'] = "Code opération valide à partir du " . $date_start->get(Aurel_Date::DATE_SHORT);
                        $return['message_class'] = "alert-danger";
                    } elseif($dateToday->isLater($date_end)) {
                        $return['message'] = "Code opération expiré depuis le " . $date_end->get(Aurel_Date::DATE_SHORT);
                        $return['message_class'] = "alert-danger";
                    }
                }
            } else {
                $return['message'] = "Code opération invalide";
                $return['message_class'] = "alert-danger";
            }
        }

        echo json_encode($return);
    }


    public function vouchersAction() {
        $oUser = new Aurel_Table_User();
        $userToConnect = $oUser->getById($this->_getUser()->id_user);
        $this->view->userToConnect = $userToConnect;

        if (!$this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_MEMBRE) && $this->_config->connexion_access_code && !isset($_COOKIE["access_code_ok"])) {
            $this->_helper->layout->setLayout('access_code');
        } else {
            $this->_helper->layout->setLayout('main_iframe');
        }
    }
}
