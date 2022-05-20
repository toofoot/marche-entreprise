<?php

require_once "AbstractController.php";

/**
 * Class Admin_ArticlesController
 *
 * @author Aurel
 *
 */
class Admin_ArticlesController extends Admin_AbstractController {

    /**
     * Pre-dispatch routines
     *
     * @return void
     */
    public function preDispatch() {
        parent::preDispatch();
    }

    /**
     * Ajoute article
     *
     * @return void
     */
    public function addArticleAction() {
        $array = [];
        $this->_disableLayout();

        $oMenu = new Aurel_Table_Menu();
        $menu = $oMenu->getByTitle($this->getParam('basename_principal'));

        if ($this->hasParam('basename_secondaire')) {
            $oSousMenu = new Aurel_Table_SousMenu();
            $sousmenu = $oSousMenu->getByTitle($this->getParam('basename_secondaire'), $menu->id_menu);
        }

        $oArticle = new Aurel_Table_Article();
        $new = $oArticle->createRow();
        if (isset($sousmenu) && $sousmenu)
            $new->id_sous_menu = $sousmenu->id_sous_menu;
        elseif ($menu)
            $new->id_menu = $menu->id_menu;
        $new->save();

        $array['basename_principal'] = $menu->basename;
        if (isset($sousmenu) && $sousmenu)
            $array['basename_secondaire'] = $sousmenu->basename;

        $this->redirect($this->view->url($array, 'basenames', true));
    }

    /**
     * Corbeille
     *
     * @return void
     */
    public function trashAction() {
        
    }

    /**
     * Liste des annonces
     *
     * @return void
     */
    public function annoncesAction() {
        if (!$this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_ADMIN_ANNONCES)) {
            throw new Zend_Acl_Exception("Ressource non autorisé for $this->_role");
        }
        $oArticle = new Aurel_Table_Article();

        $articles = $oArticle->getAllAnnonces();

        $dateArchive = Aurel_Date::now()->subDay($this->_config->daysArchiveAnnonce - 1)->setTime("00:00");

        $tabAnnonceAttente = array();
        $tabAnnonceEnCours = array();
        $tabAnnonceArchives = array();
        $tabAnnonceRefuses = array();
        foreach ($articles as $article) {
            if ($article->state_annonce == Aurel_Table_Article::STATE_ANNONCE_WAITING) {
                $tabAnnonceAttente[] = $article;
            } elseif ($article->state_annonce == Aurel_Table_Article::STATE_ANNONCE_REFUSED) {
                $tabAnnonceRefuses[] = $article;
            } elseif ($article->state_annonce != Aurel_Table_Article::STATE_ANNONCE_WAITING && $article->date_validation < $dateArchive->get(Aurel_Date::MYSQL_DATETIME)) {
                $tabAnnonceArchives[] = $article;
            } else {
                $tabAnnonceEnCours[] = $article;
            }
        }

        $this->view->annonces = $tabAnnonceEnCours;
        $this->view->annoncesArchives = $tabAnnonceArchives;
        $this->view->annoncesAttente = $tabAnnonceAttente;
        $this->view->annonceRefuses = $tabAnnonceRefuses;
    }

    /**
     * Change le statut des annonces
     *
     * @return void
     */
    public function statusAnnonceAction() {
        if (!$this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_ADMIN_ANNONCES)) {
            throw new Zend_Acl_Exception("Ressource non autorisé for $this->_role");
        }
        $this->_disableLayout();

        $status = $this->getParam('status');
        $id_annonce = $this->getParam('id_annonce');

        $oArticle = new Aurel_Table_Article();
        $oUser = new Aurel_Table_User();

        $annonce = $oArticle->getById($id_annonce);
        $annonceur = $oUser->getById($annonce->id_user_creation);

        $this->view->status = $status;

        $formData = $this->_request->getPost();

        if ($formData) {
            $status = $formData['status'];
            $commentaire = stripslashes(trim($formData['commentaire']));

            $annonce->state_annonce = $status;
            $annonce->date_validation = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
            $annonce->save();

            $subject = "Votre annonce numéro " . $annonce->id_article;

            if ($status == Aurel_Table_Article::STATE_ANNONCE_REFUSED) {
                $body = "Votre annonce numéro " . $annonce->id_article . " a été refusée.\n" .
                        "Veuillez prendre connaissance des règles générales et particulières de diffusion des annonces (consultable à la rubriques ANNONCES)";
                if ($commentaire != "") {
                    $body .= "<div style='margin-top:10px;padding:5px;border:1px solid #323B8C'>";
                    $body .= "<strong>Motif particulier :</strong>\n";
                    $body .= $commentaire;
                    $body .= "</div>";
                }
            } else {
                $body = "Votre annonce numéro " . $annonce->id_article . " a été acceptée et est désormais en ligne.\n" .
                        "Vous pouvez la consulter en cliquant sur ce lien :\n" .
                        "Votre annonce : <a href='http://{$_SERVER['HTTP_HOST']}/annonce/{$annonce->basename}'>{$annonce->title}</a>\n";
            }

            $mail = new Aurel_Mailer("utf-8");
            $mail->setBodyHtmlWithDesign($body, $subject)
                    ->setSubject($subject)
                    ->addTo($annonceur->email, $annonceur->firstname . " " . $annonceur->lastname)
                    ->setFrom("no-reply@lepetitcharsien.com", "Le Petit Charsien");
            try {
                $mail->send();
            } catch (Exception) {
                
            }

            $this->redirect($this->view->url(array('action' => 'annonces', 'controller' => 'articles'), 'admin', true));
        }
    }

    public function sortArticlesAction() {
        $this->_disableLayout();
        $this->_disableView();
        $oArticle = new Aurel_Table_Article();
        $order = $this->getParam('order');

        $tabOrdre = explode(',', $order);

        if (is_array($tabOrdre)) {
            $count = count($tabOrdre);

            $date = new Aurel_Date();
            foreach ($tabOrdre as $key => $value) {
                $id = str_replace('article-', '', $value);
                $ligne = $oArticle->getById($id);
                if ($ligne) {
                    $ligne->order = $key;
                    $ligne->save();
                }
            }
        }
    }
    
    public function sortArticles2Action() {
        $this->_disableLayout();
        $this->_disableView();
        $oArticle = new Aurel_Table_Article();
        $order = $this->getParam('order');

        $tabOrdre = explode(',', $order);

        if (is_array($tabOrdre)) {
            $count = count($tabOrdre);

            $date = new Aurel_Date();
            foreach ($tabOrdre as $key => $value) {
                $id = str_replace('article-', '', $value);
                $ligne = $oArticle->getById($id);
                if ($ligne) {
                    $ligne->order = $key + 2;
                    $ligne->save();
                }
            }
        }
    }

    public function sortArticlesRubriqueAction() {
        $this->_disableLayout();
        $this->_disableView();
        $oArticle = new Aurel_Table_Article();
        $order = $this->getParam('order');

        $tabOrdre = explode(',', $order);

        if (is_array($tabOrdre)) {
            $count = count($tabOrdre);
            $articles = $oArticle->updateSupOrderRubrique($count);

            $date = new Aurel_Date();
            foreach ($tabOrdre as $key => $value) {
                $id = str_replace('article-', '', $value);
                $ligne = $oArticle->getById($id);
                if ($ligne) {
                    $ligne->order_rubrique = $key;
                    $ligne->save();
                }
            }
        }
    }

    public function sortFilesAction() {
        $this->_disableLayout();
        $this->_disableView();
        $oFile = new Aurel_Table_File();
        $order = $this->getParam('order');

        $tabOrdre = explode(',', $order);

        if (is_array($tabOrdre)) {
            foreach ($tabOrdre as $key => $value) {
                $id = str_replace('file-', '', $value);
                $file = $oFile->getById($id);
                if ($file) {
                    $file->order = $key;
                    $file->save();
                }
            }
        }
    }

    public function toggleArticleAction() {
        $this->_disableLayout();
        $this->_disableView();
        $oArticle = new Aurel_Table_Article();
        $id_article = $this->getParam('id_article');
        $state = $this->getParam('state');
        if ($id_article) {
            $article = $oArticle->getById($id_article);
            $article->status = $state == "true" ? Aurel_Table_Article::STATUS_ACTIF : Aurel_Table_Article::STATUS_INACTIF;
            $article->save();
        }
    }

    public function editArticleAction() {
        $sousmenu = null;
        $oSousMenu = new Aurel_Table_SousMenu();
        $oMenu = new Aurel_Table_Menu();
        $oArticle = new Aurel_Table_Article();
        $oInscription = new Aurel_Table_Inscription();
        $oSondage = new Aurel_Table_Sondage();

        if ($this->hasParam('url_retour'))
            $this->view->url_retour = urldecode($this->getParam('url_retour'));
        else
            $this->view->url_retour = "/";

        $id_article = $this->getParam('id_article');
        $tabInscriptions = array();
        if ($id_article) {
            $article = $oArticle->getById($this->getParam('id_article'));
            $article->start_hour = substr($article->start_hour, 0, 5);
            $article->end_hour = substr($article->end_hour, 0, 5);

            if ($article->id_menu)
                $menu = $oMenu->getById($article->id_menu);
            else {
                $sousmenu = $oSousMenu->getById($article->id_sous_menu);
                $menu = $oMenu->getById($sousmenu->id_menu);
            }

            if ($article->inscription_fct) {
                $inscriptions = $oInscription->getByArticle($article->id_article);
                $tabInscriptions = $inscriptions;
            }
        } else {
            $article = $oArticle->createRow();

            $menu = $oMenu->getByTitle($this->getParam('basename_principal'));

            if ($this->hasParam('basename_secondaire')) {
                $sousmenu = $oSousMenu->getByTitle($this->getParam('basename_secondaire'), $menu->id_menu);
            }

            if ($this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_ADMIN_ARTICLES) || $this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_ADMIN_REDACTEUR) && isset($this->view->menus_redacteur[$menu->id_menu][$sousmenu->id_sous_menu]) && $this->view->menus_redacteur[$menu->id_menu][$sousmenu->id_sous_menu] == "1") {
                
            } else {
                throw new Zend_Acl_Exception("Page interdite");
            }


            if (isset($sousmenu) && $sousmenu)
                $article->id_sous_menu = $sousmenu->id_sous_menu;
            elseif ($menu)
                $article->id_menu = $menu->id_menu;

            $article->hide_home = 0;
            $article->annonce = 0;
            $article->portrait = 0;
            $article->id_user_creation = $this->_getUser()->id_user;
            $article->date_creation = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
            $article->start_date = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATE);
            $article->end_date = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATE);
            $article->id_sondage = null;

            $article->start_hour = "10:00";
            $article->end_hour = "10:00";

            $article->inscription_fct = 0;
            $article->inscription_nominative = 0;
        }

        $sondages = $oSondage->getAll();
        $tabSondages = array();
        foreach ($sondages as $sondage) {
            $tabSondages[$sondage->id_sondage] = $sondage->name;
        }
        $this->view->sondages = $tabSondages;

        $MenusForSelect = $oMenu->getAll();
        $selectMenus = array();
        foreach ($MenusForSelect as $MenuForSelect) {
            $selectMenus[$MenuForSelect->id_menu] = $MenuForSelect->name;
        }
        $this->view->tabInscriptions = $tabInscriptions;
        $this->view->menus_select = $selectMenus;
        $this->view->menu_selected = $menu->id_menu;
        $this->view->sous_menu_selected = isset($sousmenu) ? $sousmenu->id_sous_menu : null;

        $SousMenusForSelect = $oSousMenu->getAllByMenu($menu->id_menu);
        $selectSousMenus = array();
        foreach ($SousMenusForSelect as $SousMenuForSelect) {
            $selectSousMenus[$SousMenuForSelect->id_sous_menu] = $SousMenuForSelect->name;
        }
        $this->view->sousmenus = $selectSousMenus;

        $return = array();
        $formData = $this->_request->getPost();
        if ($formData) {
            $this->_disableLayout();
            $this->_disableView();
            if (isset($formData['id_sous_menu']) && $formData['id_sous_menu'] != '') {
                $article->id_sous_menu = $formData['id_sous_menu'];
                $article->id_menu = null;
            } elseif (isset($formData['id_menu']) && $formData['id_menu'] != '') {
                $article->id_menu = $formData['id_menu'];
                $article->id_sous_menu = null;
            }
            $article->hide_home = $formData['hide_home'];
            $article->annonce = 0;
            $article->portrait = $formData['portrait'];
            $article->status = $formData['status'];
            $article->title = stripslashes($formData['title']);
            $article->basename = $oArticle->getBasename(stripslashes($formData['title']));
            $article->content = stripslashes($formData['content']);

            //$article->title_soundex = $this->_soundex(stripslashes($formData['title']));
            //$article->content_soundex = $this->_soundex(stripslashes($formData['content']));

            $article->link_event = $formData['link_event'];
            $date_start = new Aurel_Date($formData['start_date']);
            $article->start_date = $date_start->get(Aurel_Date::MYSQL_DATE);
            $date_end = new Aurel_Date($formData['end_date']);
            $article->end_date = $date_end->get(Aurel_Date::MYSQL_DATE);

            $article->with_hours = $formData['with_hours'];
            $article->start_hour = $formData['start_hour'];
            $article->end_hour = $formData['end_hour'];

            $article->inscription_fct = $formData['inscription_fct'];
            $article->inscription_nominative = $formData['inscription_nominative'];

            $article->id_user_modification = $this->_getUser()->id_user;
            $article->date_modification = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);

            if (isset($formData['link_sondage']) && $formData['link_sondage'] == "1" && isset($formData['id_sondage'])) {
                $article->id_sondage = $formData['id_sondage'];
            } elseif (isset($formData['link_sondage']) && $formData['link_sondage'] == "0") {
                $article->id_sondage = null;
            }
            $article->date_modification = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);

            $article->order = null;
            $article->save();

            if ($article->inscription_fct) {
                $inscription_date_limite = new Aurel_Date($formData['inscription_date_limite']);
                $article->inscription_date_limite = $inscription_date_limite->get(Aurel_Date::MYSQL_DATE);
                if ($formData["inscription_quantite_limite"] != "") {
                    $article->inscription_quantite_limite = $formData['inscription_quantite_limite'];
                } else {
                    $article->inscription_quantite_limite = null;
                }

                if ((is_countable($tabInscriptions) ? count($tabInscriptions) : 0) > (is_countable($formData["inscription"]) ? count($formData["inscription"]) : 0)) {
                    $from = is_countable($formData["inscription"]) ? count($formData["inscription"]) : 0;
                    $to = (is_countable($tabInscriptions) ? count($tabInscriptions) : 0) - 1;

                    for ($i = $from; $i <= $to; $i++) {
                        $tabInscriptions[$i]->delete();
                    }
                }

                foreach ($formData["inscription"] as $key => $value) {
                    $id = $key - 1;
                    if (isset($tabInscriptions[$id])) {
                        $line = $tabInscriptions[$id];
                    } else {
                        $line = $oInscription->createRow();
                        $line->id_article = $article->id_article;
                    }
                    $line->name = $value;
                    $line->save();
                }
            } else {
                $article->inscription_date_limite = null;
                $article->inscription_quantite_limite = null;
            }

            $article->basename = $oArticle->getBasename(stripslashes($formData['title'])) . "-" . $article->id_article;
            $article->save();

            if ($formData['choixType'] == 'youtube' && $formData['linkyoutube'] != "") {
                $article->picture = null;
                $youtube = stripslashes($formData['linkyoutube']);
                $youtube = str_replace("https://www.youtube.com/watch?v=", "", $youtube);
                $youtube = str_replace("http://www.youtube.com/watch?v=", "", $youtube);
                $youtube = str_replace("http://youtu.be/", "", $youtube);
                $youtube = str_replace("https://youtu.be/", "", $youtube);
                $youtube = str_replace("//www.youtube.com/embed/", "", $youtube);
                $youtube = str_replace("?rel=0", "", $youtube);

                $article->youtube = $youtube;
                $article->save();
            } elseif ($formData['choixType'] == 'picture' && isset($_FILES) && !empty($_FILES) && is_uploaded_file($_FILES["visuel"]["tmp_name"])) {
                $oPhoto = new Aurel_Table_Photo();
                $extension = strtolower(pathinfo($_FILES["visuel"]["name"], PATHINFO_EXTENSION));

                $upload_dir = UPLOAD_PATH . "/";
                $this->_check_dir($upload_dir);
                $upload_dir .= $article->id_article . "/";
                $this->_check_dir($upload_dir);

                $new = $oPhoto->createRow();
                $new->extension = $extension;
                $new->id_article = $article->id_article;
                $new->order = 0;
                $new->id_user_creation = $this->_getUser()->id_user;
                $new->date_creation = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
                $new->save();
                $name = $new->id_photo . '.' . $extension;

                $upload_path = $upload_dir . $name;
                $upload_paththumb = $upload_dir . 'thumb' . $name;
                $upload_pathsmallthumb = $upload_dir . 'smallthumb' . $name;
                $upload_pathminithumb = $upload_dir . 'minithumb' . $name;

                $article->picture = $new->id_photo;
                $article->save();

                if (move_uploaded_file($_FILES["visuel"]["tmp_name"], $upload_path)) {
                    include('SimpleImage.php');
                    $img = new abeautifulsite\SimpleImage($upload_path);
                    $img->auto_orient();

                    $img->best_fit(1000, 1000);
                    $img->save($upload_path, 80);

                    $img->adaptive_resize(640, 360);
                    $img->save($upload_paththumb, 80);

                    $img->adaptive_resize(200, 200);
                    $img->save($upload_pathsmallthumb, 80);

                    $img->adaptive_resize(40, 40);
                    $img->save($upload_pathminithumb, 80);
                } else {
                    $new->delete();
                }
                $return['returncode'] = 'ok';
            }

            $return['returncode'] = 'ok';

            if ($this->hasParam('url_retour')) {
                $url_redirect = urldecode($this->getParam('url_retour'));
                $this->redirect($url_redirect);
            } else
                $this->redirect($this->view->url(array("basename_article" => $article->basename), 'basename_article', true));
        }
        $this->view->event_checked = false;
        if ($article->link_event)
            $this->view->event_checked = true;
        $this->view->with_hours = false;
        if ($article->with_hours)
            $this->view->with_hours = true;
        $this->view->article = $article;
    }

    private function _soundex($string) {
        $content = strtolower(html_entity_decode(strip_tags($string)));
        $words = preg_split(
                '/((^\p{P}+)|(\p{P}*\s+\p{P}*)|(\p{P}+$))/', $content, -1, PREG_SPLIT_NO_EMPTY
        );
        $soundexWords = array();
        foreach ($words as $word) {
            $soundexWords[] = Aurel_Phonetique::convert($word);
        }
        return implode(' ', $soundexWords);
    }

    private function _check_dir($dir) {
        if (!is_dir($dir)) {
            mkdir($dir);
        }
    }

    public function postHomeAction() {
        $oArticle = new Aurel_Table_Article();
        $article = $oArticle->getById($this->getParam('id_article'));

        $this->view->article = $article;

        $return = array();
        $formData = $this->_request->getPost();
        if ($formData) {
            $this->_disableLayout();
            $this->_disableView();
            if (isset($formData["post-home"]) && $formData["post-home"] == 1) {
                // REORGANISE LES AUTRES ARTICLES
                $articlesToOrganise = $oArticle->getAllIntegrity(null, null, true);
                $cpt = 0;
                foreach ($articlesToOrganise as $articleToOrganise) {
                    $articleToOrganise->order = $cpt++;
                    $articleToOrganise->save();
                }
                if ($article->annonce && $article->hide_home == 0)
                    $article->hide_home = 1;
                else
                    $article->hide_home = 0;
                $article->id_user_modification = $this->_getUser()->id_user;
                $article->date_modification = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
                $article->order = null;
                $article->save();
            }

            if ($this->hasParam('url_redirect')) {
                $url_redirect = urldecode($this->getParam('url_redirect'));
                $this->redirect($url_redirect);
            } else
                $this->redirect($this->view->url(array("basename_article" => $article->basename), 'basename_article', true));
        }
    }

    public function getSousMenusAction() {
        $this->_disableLayout();
        $this->_disableView();
        $id_menu = $this->getParam('id_menu');
        $oSousMenu = new Aurel_Table_SousMenu();
        if ($id_menu) {
            $SousMenusForSelect = $oSousMenu->getAllByMenu($id_menu);
            $selectSousMenus = array();
            foreach ($SousMenusForSelect as $SousMenuForSelect) {
                $selectSousMenus[$SousMenuForSelect->id_sous_menu] = $SousMenuForSelect->name;
            }

            echo $this->view->formSelect('id_sous_menu', null, array('class' => 'form-control'), $selectSousMenus);
        }
    }

    public function emptyTrashAction() {
        $this->_disableLayout();
        $this->_disableView();

        $oArticle = new Aurel_Table_Article();
        $articles = $oArticle->getAllCorbeilleForDelete();
        foreach ($articles as $article) {
            $article->delete();
        }

        $this->redirect($this->view->url(array('action' => 'trash', 'controller' => 'articles'), 'admin', true));
    }

    public function restoreAction() {
        if (!$this->hasParam('id_article') || !$this->hasParam('comHash'))
            throw new Zend_Exception();

        $id_article = $this->getParam('id_article');
        $comHash = $this->getParam('comHash');

        $oArticle = new Aurel_Table_Article();
        $article = $oArticle->getById($id_article);

        $arrayUrl = array();

        if ($article) {
            if ($article->id_sous_menu) {
                $oSousMenu = new Aurel_Table_SousMenu();
                $sousmenu = $oSousMenu->getById($article->id_sous_menu);

                $oMenu = new Aurel_Table_Menu();
                $menu = $oMenu->getById($sousmenu->id_menu);

                $arrayUrl['basename_principal'] = $menu->basename;
                $arrayUrl['basename_secondaire'] = $sousmenu->basename;
            } else {
                $oMenu = new Aurel_Table_Menu();
                $menu = $oMenu->getById($article->id_menu);

                $arrayUrl['basename_principal'] = $menu->basename;
            }

            if ($comHash == md5($article->id_article)) {
                $article->status = Aurel_Table_Article::STATUS_ACTIF;
                $article->id_user_modification = $this->_getUser()->id_user;
                $article->date_modification = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
                $article->save();
            }
        }
        $this->redirect($this->view->url($arrayUrl, 'basenames', true));
    }

    public function deleteArticleAction() {
        $this->_disableLayout();
        $this->_disableView();

        if (!$this->hasParam('id_article') || !$this->hasParam('comHash'))
            throw new Zend_Exception();

        $id_article = $this->getParam('id_article');
        $comHash = $this->getParam('comHash');

        $oArticle = new Aurel_Table_Article();
        $article = $oArticle->getById($id_article);

        $arrayUrl = array();

        if ($article) {
            if ($article->id_sous_menu) {
                $oSousMenu = new Aurel_Table_SousMenu();
                $sousmenu = $oSousMenu->getById($article->id_sous_menu);

                $oMenu = new Aurel_Table_Menu();
                $menu = $oMenu->getById($sousmenu->id_menu);

                $arrayUrl['basename_principal'] = $menu->basename;
                $arrayUrl['basename_secondaire'] = $sousmenu->basename;
            } else {
                $oMenu = new Aurel_Table_Menu();
                $menu = $oMenu->getById($article->id_menu);

                $arrayUrl['basename_principal'] = $menu->basename;
            }

            /* $path = UPLOAD_PATH . "/" . $article->id_article . "/";
              if(is_dir($path))
              $this->rrmdir($path); */
            if ($comHash == md5($article->id_article)) {
                if ($this->hasParam('definit') && $this->getParam('definit') == '1') {
                    $photos_dir = UPLOAD_PATH . '/' . $article->id_article;
                    $this->deleteDirectory($photos_dir);
                    $article->delete();
                    $this->redirect($this->view->url(array('action' => 'trash', 'controller' => 'articles'), 'admin', true));
                } else {
                    $article->status = Aurel_Table_Article::STATUS_CORBEILLE;
                    $article->id_user_modification = $this->_getUser()->id_user;
                    $article->date_modification = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
                    $article->save();
                }
            }
        }
        $this->redirect($this->view->url($arrayUrl, 'basenames', true));
    }

    private function deleteDirectory($dir) {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }

    private function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . "/" . $object) == "dir")
                        rrmdir($dir . "/" . $object);
                    else
                        unlink($dir . "/" . $object);
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }

    public function copyArticleAction() {
        $arrayUrl = [];
        $this->_disableLayout();

        $oArticle = new Aurel_Table_Article();
        $oPhotos = new Aurel_Table_Photo();
        $oFile = new Aurel_Table_File();

        $article = $oArticle->getById($this->getParam('id_article'));

        $this->view->article = $article;

        $select = array();
        foreach ($this->view->menus as $menu) {
            if ($menu->sous_menus_name) {
                $liste_basename = explode(",", $menu->sous_menus_basename);
                $liste_name = explode(",", $menu->sous_menus_name);
                $liste_id = explode(",", $menu->sous_menus_id);
                foreach ($liste_basename as $key => $basename) {
                    $select[$menu->name]['sous_menu_' . $liste_id[$key]] = $liste_name[$key];
                }
            } else {
                $select['menu_' . $menu->id_menu] = $menu->name;
            }
        }
        $this->view->select = $select;

        $formData = $this->_request->getPost();
        if ($formData) {
            $this->_disableView();
            $type = $formData['choixType'];
            $copyTo = $formData['copyTo'];

            if ($type == "copy") {
                $newArticle = $oArticle->createRow();
                $newArticle->setFromArray($article->toArray());
                $newArticle->id_article = null;
                $newArticle->order = 0;
            } else {
                $newArticle = $article;
                $newArticle->order = 0;
            }

            if (str_contains($copyTo, "sous_menu")) {
                $id_sous_menu = intval(str_replace("sous_menu_", "", $copyTo));
                $newArticle->id_sous_menu = $id_sous_menu;
                $newArticle->id_menu = null;
            } else {
                $id_menu = intval(str_replace("menu_", "", $copyTo));
                $newArticle->id_sous_menu = null;
                $newArticle->id_menu = $id_menu;
            }

            $newArticle->save();

            if ($type == "copy") {
                $files = $oFile->getByArticle($article->id_article);
                foreach ($files as $file) {
                    $newFile = $oFile->createRow();
                    $newFile->setFromArray($file->toArray());
                    $newFile->id_file = null;
                    $newFile->id_article = $newArticle->id_article;
                    $newFile->save();
                }

                $photos = $oPhotos->getByArticle($article->id_article);
                foreach ($photos as $photo) {
                    $path = UPLOAD_PATH . "/" . $article->id_article . "/" . $photo->id_photo . '.' . $photo->extension;
                    $pathThumb = UPLOAD_PATH . "/" . $article->id_article . "/thumb" . $photo->id_photo . '.' . $photo->extension;

                    $newPhoto = $oPhotos->createRow();
                    $newPhoto->setFromArray($photo->toArray());
                    $newPhoto->id_photo = null;
                    $newPhoto->id_article = $newArticle->id_article;
                    $newPhoto->save();

                    if (!is_dir(UPLOAD_PATH . "/" . $newArticle->id_article))
                        mkdir(UPLOAD_PATH . "/" . $newArticle->id_article);
                    $newPath = UPLOAD_PATH . "/" . $newArticle->id_article . "/" . $newPhoto->id_photo . '.' . $newPhoto->extension;
                    $newPathThumb = UPLOAD_PATH . "/" . $newArticle->id_article . "/thumb" . $newPhoto->id_photo . '.' . $newPhoto->extension;
                    copy($path, $newPath);
                    copy($pathThumb, $newPathThumb);
                }

                $photos = $newArticle->getPhotos();
                if ($photos) {
                    $first = $photos->current();
                    $newArticle->picture = $first->id_photo;
                } else {
                    $newArticle->picture = null;
                }
                $newArticle->save();
            }

            if ($newArticle->id_sous_menu) {
                $oSousMenu = new Aurel_Table_SousMenu();
                $sousmenu = $oSousMenu->getById($newArticle->id_sous_menu);

                $oMenu = new Aurel_Table_Menu();
                $menu = $oMenu->getById($sousmenu->id_menu);

                $arrayUrl['basename_principal'] = $menu->basename;
                $arrayUrl['basename_secondaire'] = $sousmenu->basename;
            } else {
                $oMenu = new Aurel_Table_Menu();
                $menu = $oMenu->getById($newArticle->id_menu);

                $arrayUrl['basename_principal'] = $menu->basename;
            }
            $this->view->arrayUrl = $arrayUrl;
            $this->redirect($this->view->url($arrayUrl, 'basenames', true) . "#telechargement-" . $article->id_article);
        }
    }

    public function editPictureAction() {
        $arrayUrl = [];
        $oArticle = new Aurel_Table_Article();
        $article = $oArticle->getById($this->getParam('id_article'));

        $photos = $article->getPhotos();
        $this->view->photos = $photos;

        if ($article->id_sous_menu) {
            $oSousMenu = new Aurel_Table_SousMenu();
            $sousmenu = $oSousMenu->getById($article->id_sous_menu);

            $oMenu = new Aurel_Table_Menu();
            $menu = $oMenu->getById($sousmenu->id_menu);

            $arrayUrl['basename_principal'] = $menu->basename;
            $arrayUrl['basename_secondaire'] = $sousmenu->basename;
        } else {
            $oMenu = new Aurel_Table_Menu();
            $menu = $oMenu->getById($article->id_menu);

            $arrayUrl['basename_principal'] = $menu->basename;
        }
        $this->view->arrayUrl = $arrayUrl;
        $this->view->article = $article;

        $formData = $this->_request->getPost();
        if ($formData) {
            if ($formData['choixType'] == 'youtube') {
                $article->picture = null;
                $youtube = stripslashes($formData['linkyoutube']);
                $youtube = str_replace("https://www.youtube.com/watch?v=", "", $youtube);
                $youtube = str_replace("http://www.youtube.com/watch?v=", "", $youtube);
                $youtube = str_replace("http://youtu.be/", "", $youtube);
                $youtube = str_replace("https://youtu.be/", "", $youtube);
                $youtube = str_replace("//www.youtube.com/embed/", "", $youtube);
                $youtube = str_replace("?rel=0", "", $youtube);

                $article->youtube = $youtube;
                $article->save();
            } else {
                $photos = $article->getPhotos();
                $first = $photos->current();

                $article->youtube = null;
                $article->picture = $first->id_photo;
                $article->save();
            }
            $this->redirect($this->view->url($arrayUrl, 'basenames', true));
        }
    }

    public function listOfFilesAction() {
        $oFile = new Aurel_Table_File();
        $oArticle = new Aurel_Table_Article();
        $article = $oArticle->getById($this->getParam('id_article'));

        $files = $oFile->getByArticle($article->id_article);

        $this->view->files = $files;
        $this->view->article = $article;
    }

    public function addFileAction() {
        $arrayUrl = [];
        $this->_disableLayout();

        $oFile = new Aurel_Table_File();
        $oArticle = new Aurel_Table_Article();
        $article = $oArticle->getById($this->getParam('id_article'));

        $files = $oFile->getByArticle($article->id_article);

        $this->view->files = $files;
        $this->view->article = $article;

        $formData = $this->_request->getPost();
        if ($formData && is_uploaded_file($_FILES['file']['tmp_name'])) {
            $this->_disableLayout();
            $this->_disableView();

            $fileName = strtolower($_FILES['file']['name']);
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $basename = str_replace("." . $extension, "", $fileName);
            $basename = $oFile->getBasename($basename);

            $newFile = $oFile->createRow();

            $fileName = str_replace(",", "&sbquo;", $formData['name']);
            $newFile->name = $fileName;
            $newFile->basename = $basename;
            $newFile->extension = $extension;
            $newFile->type = $_FILES['file']['type'];
            $newFile->id_article = $article->id_article;
            $newFile->save();

            $upload_dir = UPLOAD_PATH . "/files/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir);
            }

            $pathfile = $upload_dir . $basename . "." . $extension;
            move_uploaded_file($_FILES['file']['tmp_name'], $pathfile);

            if ($article->id_sous_menu) {
                $oSousMenu = new Aurel_Table_SousMenu();
                $sousmenu = $oSousMenu->getById($article->id_sous_menu);

                $oMenu = new Aurel_Table_Menu();
                $menu = $oMenu->getById($sousmenu->id_menu);

                $arrayUrl['basename_principal'] = $menu->basename;
                $arrayUrl['basename_secondaire'] = $sousmenu->basename;
            } else {
                $oMenu = new Aurel_Table_Menu();
                $menu = $oMenu->getById($article->id_menu);

                $arrayUrl['basename_principal'] = $menu->basename;
            }
            $this->view->arrayUrl = $arrayUrl;
            echo "<script>window.top.window.uploadEnd(" . json_encode($newFile->toArray()) . ");</script>";
        }
    }

    public function renameFileAction() {
        $arrayUrl = [];
        $this->_disableLayout();

        $oArticle = new Aurel_Table_Article();
        $article = $oArticle->getById($this->getParam('id_article'));

        $oFile = new Aurel_Table_File();
        $file = $oFile->getById($this->getParam('id_file'));

        $this->view->file = $file;

        $formData = $this->_request->getPost();
        if ($formData) {
            $this->_disableLayout();
            $this->_disableView();

            $file->name = $formData['name'];
            $file->save();

            if ($article->id_sous_menu) {
                $oSousMenu = new Aurel_Table_SousMenu();
                $sousmenu = $oSousMenu->getById($article->id_sous_menu);

                $oMenu = new Aurel_Table_Menu();
                $menu = $oMenu->getById($sousmenu->id_menu);

                $arrayUrl['basename_principal'] = $menu->basename;
                $arrayUrl['basename_secondaire'] = $sousmenu->basename;
            } else {
                $oMenu = new Aurel_Table_Menu();
                $menu = $oMenu->getById($article->id_menu);

                $arrayUrl['basename_principal'] = $menu->basename;
            }
            $this->view->arrayUrl = $arrayUrl;
            $this->redirect($this->view->url($arrayUrl, 'basenames', true) . "#telechargement-" . $article->id_article);
        }
    }

    public function deleteAnnonceAction() {
        if (!$this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_ADMIN_ANNONCES)) {
            throw new Zend_Acl_Exception("Ressource non autorisé for $this->_role");
        }
        $this->_disableLayout();

        $oArticle = new Aurel_Table_Article();

        $id_annonce = $this->getParam("id_article", "999999999999");
        $annonce = $oArticle->getById($id_annonce);

        $this->view->annonce = $annonce;
        $formData = $this->_request->getPost();

        if ($annonce && $formData) {
            $this->_disableView();
            $annonce->delete();

            $url = $this->view->url(array('action' => 'annonces', 'id_annonce' => null));
            if ($this->hasParam('return'))
                $url .= "#" . $this->getParam('return');
            $this->redirect($url);
        }
    }

    public function deleteFileAction() {
        $return = [];
        $this->_disableLayout();
        $this->_disableView();

        if (!$this->hasParam('id_article') || !$this->hasParam('id_file') || !$this->hasParam('comHash'))
            throw new Zend_Exception();

        $id_article = $this->getParam('id_article');
        $id_file = $this->getParam('id_file');
        $comHash = $this->getParam('comHash');
        $arrayUrl = array();

        $oArticle = new Aurel_Table_Article();
        $article = $oArticle->getById($id_article);

        $oFile = new Aurel_Table_File();
        $file = $oFile->getById($id_file);

        $this->view->file = $file;

        $formData = $this->_request->getPost();
        $return['returncode'] = 'ko';
        if ($file) {
            $return['id_file'] = $file->id_file;
            if ($file->id_article == $article->id_article && $comHash == md5($file->id_file)) {
                $upload_dir = UPLOAD_PATH . "/files/";
                $pathfile = $upload_dir . $file->basename . "." . $file->extension;
                $file->delete();
                $return['returncode'] = 'ok';
            }
            echo json_encode($return);
        }
    }

    public function deleteNewsletterAction() {
        if (!$this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_ADMIN_NEWSLETTER)) {
            throw new Zend_Acl_Exception("Ressource non autorisé for $this->_role");
        }
        $this->_disableLayout();

        $oNewsletter = new Aurel_Table_Newsletter();

        $id_newsletter = $this->getParam("id_newsletter", "999999999999");
        $newsletter = $oNewsletter->getById($id_newsletter);

        $this->view->newsletter = $newsletter;
        $formData = $this->_request->getPost();

        if ($newsletter && $formData) {
            $this->_disableView();
            $newsletter->delete();

            $url = $this->view->url(array('action' => 'newsletter', 'controller' => 'articles'), 'admin', true);
            if ($this->hasParam('return'))
                $url .= "#" . $this->getParam('return');
            $this->redirect($url);
        }
    }

    public function afficheNewsletterAction() {
        if (!$this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_ADMIN_NEWSLETTER)) {
            throw new Zend_Acl_Exception("Ressource non autorisé for $this->_role");
        }
        $this->_disableView();
        $this->_helper->layout->setLayout("email");

        $oNewsletter = new Aurel_Table_Newsletter();
        $id_newsletter = $this->getParam('id_newsletter', null);

        if ($id_newsletter) {
            $newsletter = $oNewsletter->getById($id_newsletter);
            $texte = $newsletter->texte1;
            $texte2 = $newsletter->texte2;
            $articles = $newsletter->articles;
            $annonces = $newsletter->annonces;
            $subject = $newsletter->subject;
            $from = $newsletter->from;
        } else {
            $texte = $this->getParam("texte");
            $texte2 = $this->getParam("texte2");
            $articles = $this->getParam("articles", true);
            $annonces = $this->getParam("annonces", true);
            $subject = $this->getParam("subject");
            $from = $this->getParam("from");
        }

        $aEnvoyer = $oNewsletter->genereNewsletter(false, $texte, $texte2, $articles, $annonces, $subject, $from);

        echo $aEnvoyer;
    }

    public function newsletterAction() {
        if (!$this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_ADMIN_NEWSLETTER)) {
            throw new Zend_Acl_Exception("Ressource non autorisé for $this->_role");
        }
        $oNewsletter = new Aurel_Table_Newsletter();

        $newsletters = $oNewsletter->getAllArchived(1);
        $this->view->newsletters = $newsletters;

        $waitings = $oNewsletter->getAllArchived(0);
        $this->view->waitings = $waitings;
    }

    public function addNewsletterAction() {
        $return = [];
        if (!$this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_ADMIN_NEWSLETTER)) {
            throw new Zend_Acl_Exception("Ressource non autorisé for $this->_role");
        }
        $oNewsletter = new Aurel_Table_Newsletter();
        $id_newsletter = $this->getParam('id_newsletter', null);
        $host = $_SERVER["HTTP_HOST"];

        if ($id_newsletter) {
            $newsletter = $oNewsletter->getById($id_newsletter);
        } else {
            $newsletter = $oNewsletter->createRow();
            $newsletter->texte1 = "<div style='text-align:center;'><span style='color:#323B8C'>Vous trouverez ci-dessous les liens vers les derniers articles mis en ligne sur <a style='color:#323B8C' href='http://$host'>$host</a>.<br/>Bonne lecture. La rédaction</span></div>";
            $newsletter->texte2 = "<div style='text-align:center;'><span style='color:#323B8C'>&nbsp;</span></div>";
            $newsletter->archived = 0;
            $newsletter->ready_to_send = Aurel_Table_Newsletter::STATUS_TOSENDADMIN;
            $newsletter->articles = 1;
            $newsletter->annonces = 1;
            $newsletter->subject = "Votre Newsletter du " . Aurel_Date::now()->get(Aurel_Date::DATE_LONG);
            $newsletter->from = "redaction@lepetitcharsien.com";
        }

        $this->view->newsletter = $newsletter;

        $formData = $this->_request->getPost();

        $texte = $this->getParam("texte");
        $texte2 = $this->getParam("texte2");
        $articles = $this->getParam("articles", true);
        $annonces = $this->getParam("annonces", true);
        $subject = $this->getParam("subject");
        $from = $this->getParam("from");

        $oUser = new Aurel_Table_User();
        $usersAll = $oUser->getAllForNewsletter();
        $usersAdmin = $oUser->getAllForNewsletter(true);

        $this->view->countAll = $usersAll->count();
        $this->view->countAdmin = $usersAdmin->count();

        if ($formData) {
            if (isset($formData["send"])) {
                $this->_disableLayout();
                $this->_disableView();

                $aEnvoyer = $oNewsletter->genereNewsletter(true, $texte, $texte2, $articles, $annonces, $subject, $from);
                $return['id_newsletter'] = $aEnvoyer->id_newsletter;
                $return['modal'] = '<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
					        <h4 class="modal-title">Newsletter</h4>
					      </div>
					      <div class="modal-body">
					        <p>La newsletter a bien été envoyée</p>
					      </div>
					      <div class="modal-footer">
					        <button type="button" class="btn btn-default" data-dismiss="modal">Fermer</button>
					      </div>';

                $users = $usersAdmin;
                $aEnvoyer->subject = $aEnvoyer->subject;
                $aEnvoyer->archived = 0;

                $dir = LOG_PATH . DIRECTORY_SEPARATOR . 'newsletter';
                if (!is_dir($dir))
                    mkdir($dir);

                $redacteur = new Zend_Log_Writer_Stream($dir . DIRECTORY_SEPARATOR . "newsletter_{$aEnvoyer->id_newsletter}.log");
                $logger = new Zend_Log();
                $logger->addWriter($redacteur);

                $countSend = 0;
                foreach ($users as $user) {
                    $bodyHtml = str_replace("#emailEncoded#", md5($user->email), $aEnvoyer->body);

                    $mail = new Aurel_Mailer();
                    $mail->setFrom($aEnvoyer->from, "Le Petit Charsien");
                    $mail->setSubject(mb_encode_mimeheader("TEST : " . $aEnvoyer->subject));
                    $mail->setBodyHtml($bodyHtml);
                    $mail->addTo($user->email);

                    $return['send'][] = $user->email;
                    try {
                        if ($mail->send()) {
                            $countSend++;
                            $logTexte = $user->email . ' => TEST OK';
                            $logger->log($logTexte, Zend_Log::INFO);
                        } else {
                            $mail->
                                    $logTexte = $user->email . ' => TEST KO';
                            $logger->log($logTexte, Zend_Log::INFO);
                        }
                    } catch (Exception $e) {
                        $return['errors'][] = $e->getMessage();
                        $logTexte = $user->email . ' => TEST ' . $e->getMessage();
                        $logger->log($logTexte, Zend_Log::INFO);
                    }
                }
                $aEnvoyer->date_envoi = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
                $aEnvoyer->nb_envoi = $countSend;
                $aEnvoyer->save();

                echo json_encode($return);
            }
        }
    }

    public function sendNewsletterAction() {
        $return = [];
        if (!$this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_ADMIN_NEWSLETTER)) {
            throw new Zend_Acl_Exception("Ressource non autorisé for $this->_role");
        }
        $this->_disableLayout();
        $this->_disableView();

        $oNewsletter = new Aurel_Table_Newsletter();
        $oUser = new Aurel_Table_User();

        $id_newsletter = $this->getParam('id_newsletter');

        $aEnvoyer = $oNewsletter->getById($id_newsletter);
        $usersAll = $oUser->getAllForNewsletter();

        $return['id_newsletter'] = $aEnvoyer->id_newsletter;
        $return['modal'] = '<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
		<h4 class="modal-title">Newsletter</h4>
		</div>
		<div class="modal-body">
		<p>La newsletter a bien été envoyée</p>
		</div>
		<div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">Fermer</button>
		</div>';

        $users = $usersAll;
        $aEnvoyer->archived = 1;

        $dir = LOG_PATH . DIRECTORY_SEPARATOR . 'newsletter';
        if (!is_dir($dir))
            mkdir($dir);

        $redacteur = new Zend_Log_Writer_Stream($dir . DIRECTORY_SEPARATOR . "newsletter_{$aEnvoyer->id_newsletter}.log");
        $logger = new Zend_Log();
        $logger->addWriter($redacteur);

        $countSend = 0;
        foreach ($users as $user) {
            $bodyHtml = str_replace("#emailEncoded#", md5($user->email), $aEnvoyer->body);

            $mail = new Aurel_Mailer();
            $mail->setFrom($aEnvoyer->from, "Le Petit Charsien");
            $mail->setSubject(mb_encode_mimeheader($aEnvoyer->subject));
            $mail->setBodyHtml($bodyHtml);
            $mail->addTo($user->email);

            $return['send'][] = $user->email;
            try {
                if ($mail->send()) {
                    $countSend++;
                    $logTexte = $user->email . ' => OK';
                    $logger->log($logTexte, Zend_Log::INFO);
                } else {
                    $logTexte = $user->email . ' => KO';
                    $logger->log($logTexte, Zend_Log::INFO);
                }
            } catch (Exception $e) {
                $return['errors'][] = $e->getMessage();
                $logTexte = $user->email . ' => ' . $e->getMessage();
                $logger->log($logTexte, Zend_Log::INFO);
            }
        }
        $aEnvoyer->date_envoi = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
        $aEnvoyer->nb_envoi = $countSend;
        $aEnvoyer->save();

        echo json_encode($return);
    }

    public function getManquantsAction() {
        $return = [];
        if (!$this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_ADMIN_NEWSLETTER)) {
            throw new Zend_Acl_Exception("Ressource non autorisé for $this->_role");
        }

        $oNewsletter = new Aurel_Table_Newsletter();
        $oUser = new Aurel_Table_User();

        $id_newsletter = $this->getParam('id_newsletter');

        $aEnvoyer = $oNewsletter->getById($id_newsletter);
        $usersAll = $oUser->getAllForNewsletter();

        $users = $usersAll;
        $aEnvoyer->archived = 1;

        $dir = LOG_PATH . DIRECTORY_SEPARATOR . 'newsletter';
        if (!is_dir($dir))
            mkdir($dir);

        $logFile = $dir . DIRECTORY_SEPARATOR . "newsletter_{$aEnvoyer->id_newsletter}.log";

        $apache_errorlog = file_get_contents($logFile);
        $tab = explode("\n", $apache_errorlog);
        $logs = array();
        foreach ($tab as $key => $value) {
            if ($value != '') {
                $dateString = substr($value, 0, 25);
                $date = new Zend_Date($dateString);
                $type = trim(substr($value, 25, 11));

                $reste = str_replace($dateString, "", $value);
                $reste = trim(str_replace($type, "", $reste));

                [$email, $error] = explode(" => ", $reste);

                $logs[$email]['date'] = $date->get(Aurel_Date::DATETIME_SHORT);
                $logs[$email]['email'] = $email;
                $logs[$email]['error'] = $error;
            }
        }
        foreach ($logs as $key => $log) {
            if ($log['error'] == "OK" || $log['error'] == "TEST OK") {
                unset($logs[$key]);
            }
        }

        $this->view->logs = $logs;

        $formData = $this->getRequest()->getPost();
        if ($formData) {
            $this->_disableLayout();
            $this->_disableView();
            $redacteur = new Zend_Log_Writer_Stream($dir . DIRECTORY_SEPARATOR . "newsletter_{$aEnvoyer->id_newsletter}.log");
            $logger = new Zend_Log();
            $logger->addWriter($redacteur);

            $countSend = $aEnvoyer->nb_envoi;
            foreach ($logs as $log) {
                $bodyHtml = str_replace("#emailEncoded#", md5($log['email']), $aEnvoyer->body);

                $mail = new Aurel_Mailer();
                $mail->setFrom($aEnvoyer->from, "Le Petit Charsien");
                $mail->setSubject(mb_encode_mimeheader($aEnvoyer->subject));
                $mail->setBodyHtml($bodyHtml);
                $mail->addTo($log['email']);

                $return['send'][] = $log['email'];
                try {
                    if ($mail->send()) {
                        $countSend++;
                        $logTexte = $log['email'] . ' => OK';
                        $logger->log($logTexte, Zend_Log::INFO);
                    } else {
                        $logTexte = $log['email'] . ' => KO';
                        $logger->log($logTexte, Zend_Log::INFO);
                    }
                } catch (Exception $e) {
                    $return['errors'][] = $e->getMessage();
                    $logTexte = $log['email'] . ' => ' . $e->getMessage();
                    $logger->log($logTexte, Zend_Log::INFO);
                }
            }
            $aEnvoyer->date_envoi = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
            $aEnvoyer->nb_envoi = $countSend;
            $aEnvoyer->save();

            $return['id_newsletter'] = $aEnvoyer->id_newsletter;
            $return['modal'] = '<div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                <h4 class="modal-title">Newsletter</h4>
                </div>
                <div class="modal-body">
                <p>La newsletter a bien été envoyée</p>
                </div>
                <div class="modal-footer">
                    <a href="' . $this->view->url(array('action' => 'get-manquants')) . '" class="closeModal btn btn-default">Fermer</button>
                </div>';

            /* $countSend = 0;
              foreach($users as $user){
              $bodyHtml = str_replace("#emailEncoded#", md5($user->email), $aEnvoyer->body);

              $mail = new Aurel_Mailer();
              $mail->setFrom($aEnvoyer->from,"Le Petit Charsien");
              $mail->setSubject(mb_encode_mimeheader($aEnvoyer->subject));
              $mail->setBodyHtml($bodyHtml);
              $mail->addTo($user->email);

              $return['send'][] = $user->email;
              try {
              if($mail->send()){
              $countSend++;
              $logTexte = $user->email .' => OK' ;
              $logger->log($logTexte, Zend_Log::INFO);
              } else {
              $logTexte = $user->email .' => KO' ;
              $logger->log($logTexte, Zend_Log::INFO);
              }
              } catch (Exception $e){
              $return['errors'][] = $e->getMessage();
              $logTexte = $user->email .' => ' . $e->getMessage();
              $logger->log($logTexte, Zend_Log::INFO);
              }
              }
              $aEnvoyer->date_envoi = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
              $aEnvoyer->nb_envoi = $countSend;
              $aEnvoyer->save();
             */
            echo json_encode($return);
        }
    }

    public function downloadSyntheseAction() {
        $this->_disableLayout();
        $this->_disableView();

        if ($this->hasParam('id_article'))
            $id_article = $this->getParam('id_article');
        else
            throw new Zend_Acl_Exception();

        $oArticle = new Aurel_Table_Article();
        $oInscription = new Aurel_Table_Inscription();
        $oInscriptionHasUser = new Aurel_Table_InscriptionHasUser();

        $article = $oArticle->getById($id_article);
        $inscriptions = $oInscription->getByArticle($id_article);
        $inscriptionshasuser = $oInscriptionHasUser->getByArticle($id_article, true);

        $tabCategories = array();
        $tabSumCategories = array();
        foreach ($inscriptions as $inscription) {
            $tabCategories[$inscription->id_inscription] = $inscription->name;
            $tabSumCategories[$inscription->id_inscription] = 0;
        }
        if (!$article->inscription_nominative) {
            $tabInscription = array();
            foreach ($inscriptionshasuser as $inscription) {
                $tabInscription[$inscription->id_user]['user'] = $inscription;
                $tabInscription[$inscription->id_user]['comment'] = $inscription->comment;
                $tabInscription[$inscription->id_user]['quantite'][$inscription->id_inscription] = $inscription->quantite;
                $tabSumCategories[$inscription->id_inscription] += $inscription->quantite;
            }

            $data = "Emetteur Nom;Emetteur Prenom;Emetteur email;";

            foreach ($tabCategories as $categorie) {
                $data .= "$categorie;";
            }
            $data .= "Commentaire;\n";
            foreach ($tabInscription as $inscription) {
                $data .= "{$inscription['user']->firstname};{$inscription['user']->lastname};{$inscription['user']->email};";
                foreach ($tabCategories as $id_inscription => $categorie) {
                    if (isset($inscription['quantite'][$id_inscription]))
                        $data .= "{$inscription['quantite'][$id_inscription]};";
                    else
                        $data .= ";";
                }
                $data .= "{$inscription['comment']};\n";
            }
            $data .= "TOTAL;";
            foreach ($tabCategories as $id_inscription => $categorie) {
                $data .= "{$tabSumCategories[$id_inscription]};";
            }
            $data .= ";\n";
        } else {
            $data = "Emetteur Nom;Emetteur Prenom;Emetteur email;";
            $data .= "Catégorie;Nom Participant;Prénom Participant;Commentaire;\n";
            foreach ($inscriptionshasuser as $inscription) {
                $firstnames = explode('#', $inscription->firstnames);
                $lastnames = explode('#', $inscription->lastnames);
                foreach ($firstnames as $key => $firstname) {
                    $data .= "{$inscription->firstname};{$inscription->lastname};{$inscription->email};";
                    $data .= $tabCategories[$inscription->id_inscription] . ";";
                    $data .= $lastnames[$key] . ";";
                    $data .= $firstname . ";";
                    $data .= $inscription->comment . ";\n";
                }
            }
            $data .= ";\n";
        }

        $this->getResponse()->setHeader('Content-Type', 'text/csv', true);
        $this->getResponse()->setHeader("Content-disposition", "attachment; filename=inscriptions_{$article->basename}.csv", true);
        print_r(utf8_decode($data));
    }

    public function reservationsAction() {
        $oArticle = new Aurel_Table_Article();
        $oInscription = new Aurel_Table_Inscription();
        $oInscriptionHasUser = new Aurel_Table_InscriptionHasUser();

        $inscriptions = $oInscription->getAllReservations();

        $dateToday = Aurel_Date::now();
        $tabSortie = array();
        $tabSortieHistorique = array();
        foreach ($inscriptions as $inscription) {
            $start_date = new Aurel_Date($inscription->start_date, Aurel_Date::MYSQL_DATE);

            if ($dateToday->get() <= $start_date->get()) {
                $tabSortie[$inscription->id_article]['start_date'] = $start_date;
                $tabSortie[$inscription->id_article]['basename'] = $inscription->basename;
                $tabSortie[$inscription->id_article]['title'] = $inscription->title;
                $tabSortie[$inscription->id_article]['id_user_creation'] = $inscription->id_user_creation;
                $tabSortie[$inscription->id_article]['inscription_quantite_limite'] = $inscription->inscription_quantite_limite;

                if (!isset($tabSortie[$inscription->id_article]['total']))
                    $tabSortie[$inscription->id_article]['total'] = $inscription->quantite;
                else
                    $tabSortie[$inscription->id_article]['total'] += $inscription->quantite;
            } else {
                $tabSortieHistorique[$inscription->id_article]['start_date'] = $start_date;
                $tabSortieHistorique[$inscription->id_article]['basename'] = $inscription->basename;
                $tabSortieHistorique[$inscription->id_article]['title'] = $inscription->title;
                $tabSortieHistorique[$inscription->id_article]['id_user_creation'] = $inscription->id_user_creation;
                $tabSortieHistorique[$inscription->id_article]['inscription_quantite_limite'] = $inscription->inscription_quantite_limite;

                if (!isset($tabSortieHistorique[$inscription->id_article]['total']))
                    $tabSortieHistorique[$inscription->id_article]['total'] = $inscription->quantite;
                else
                    $tabSortieHistorique[$inscription->id_article]['total'] += $inscription->quantite;
            }
        }
        $this->view->tabSortie = $tabSortie;
        $this->view->tabSortieHistorique = $tabSortieHistorique;
    }

    public function envoiMailInscritsAction() {
        if ($this->hasParam('id_article'))
            $id_article = $this->getParam('id_article');
        else
            throw new Zend_Acl_Exception();

        $oArticle = new Aurel_Table_Article();
        $oInscription = new Aurel_Table_Inscription();
        $oInscriptionHasUser = new Aurel_Table_InscriptionHasUser();

        $article = $oArticle->getById($id_article);
        $inscriptions = $oInscription->getByArticle($id_article);
        $inscriptionshasuser = $oInscriptionHasUser->getByArticle($id_article, true);

        if ($this->hasParam('url_retour'))
            $this->view->url_retour = urldecode($this->getParam('url_retour'));
        else
            $this->view->url_retour = $this->view->url(array('basename_article' => $article->basename), 'basename_article', true);

        $tabInscription = array();
        $sumTotal = 0;
        foreach ($inscriptionshasuser as $inscription) {
            $tabInscription[$inscription->id_user]['user'] = $inscription->firstname . ' ' . $inscription->lastname;
            $tabInscription[$inscription->id_user]['comment'] = $inscription->comment;
            $tabInscription[$inscription->id_user]['quantite'] = isset($tabInscription[$inscription->id_user]['quantite']) ? $tabInscription[$inscription->id_user]['quantite'] + $inscription->quantite : $inscription->quantite;
            $sumTotal += $inscription->quantite;
        }

        $this->view->sumTotal = $sumTotal;
        $this->view->article = $article;
        $this->view->inscriptionshasuser = $tabInscription;

        $dir = LOG_PATH . DIRECTORY_SEPARATOR . 'mail_inscrits';
        if (!is_dir($dir))
            mkdir($dir);



        $formData = $this->_request->getPost();
        if ($formData) {
            $this->_disableLayout();
            $this->_disableView();

            $body = $formData["message"];
            $subject = "{$article->title}";

            $date = Aurel_Date::now();
            $dateString = $date->get(Aurel_Date::ISO_8601);
            $redacteur = new Zend_Log_Writer_Stream($dir . DIRECTORY_SEPARATOR . "mail_inscrits_{$id_article}_{$dateString}.log");
            $logger = new Zend_Log();
            $logger->addWriter($redacteur);

            $return = array();
            $oUser = new Aurel_Table_User();
            foreach ($formData['envoi'] as $id_user => $bool) {
                if ($bool == "1") {
                    $user = $oUser->getById($id_user);

                    $mail = new Aurel_Mailer("utf-8");
                    $mail->setBodyHtmlWithDesign($body, $subject)
                            ->setSubject($subject)
                            ->setFrom($this->_getUser()->email, "Le Petit Charsien")
                            ->addTo($user->email, $user->firstname . " " . $user->lastname);

                    $return['html'] = $mail->getBodyHtml(true);
                    try {
                        if ($mail->send()) {
                            $return['sent'] = 1;
                            $logTexte = $user->email . ' => OK';
                            $logger->log($logTexte, Zend_Log::INFO);
                        } else {
                            $return['sent'] = 0;
                            $logTexte = $user->email . ' => KO';
                            $logger->log($logTexte, Zend_Log::INFO);
                        }
                    } catch (Exception $e) {
                        $return['sent'] = 0;
                        $logTexte = $user->email . ' => ' . $e->getMessage();
                        $logger->log($logTexte, Zend_Log::INFO);
                    }
                }
            }

            $return['modal'] = '<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
			<h4 class="modal-title">Envoi d\'email</h4>
			</div>
			<div class="modal-body">
			<p>L\'email a bien été envoyée</p>
			</div>
			<div class="modal-footer">
			<a href="' . $this->view->url_retour . '" class="btn btn-default">Fermer</button>
			</div>';


            echo json_encode($return);
        }
    }

    public function envoiMailAnnonceurAction() {
        if ($this->hasParam('id_annonce'))
            $id_annonce = $this->getParam('id_annonce');
        else
            throw new Zend_Acl_Exception();

        $oArticle = new Aurel_Table_Article();
        $annonce = $oArticle->getById($id_annonce);

        $oUser = new Aurel_Table_User();
        $annonceur = $oUser->getById($annonce->id_user_creation);

        if ($this->hasParam('url_retour'))
            $this->view->url_retour = urldecode($this->getParam('url_retour'));
        else
            $this->view->url_retour = $this->view->url(array('basename_article' => $annonce->basename), 'basename_article', true);

        $dir = LOG_PATH . DIRECTORY_SEPARATOR . 'email_annonceur';
        if (!is_dir($dir))
            mkdir($dir);

        $formData = $this->_request->getPost();
        if ($formData) {
            $this->_disableLayout();
            $this->_disableView();

            $body = $formData["message"];
            $subject = "{$annonce->title}";

            $date = Aurel_Date::now();
            $dateString = $date->get(Aurel_Date::ISO_8601);
            $redacteur = new Zend_Log_Writer_Stream($dir . DIRECTORY_SEPARATOR . "mail_{$id_annonce}_{$dateString}.log");
            $logger = new Zend_Log();
            $logger->addWriter($redacteur);

            $return = array();

            $mail = new Aurel_Mailer("utf-8");
            $mail->setBodyHtmlWithDesign($body, $subject)
                    ->setSubject($subject)
                    ->setFrom($this->_getUser()->email, "Le Petit Charsien")
                    ->addTo($annonceur->email, $annonceur->firstname . " " . $annonceur->lastname);

            //$return['html'] = $mail->getBodyHtml(true);
            try {
                if ($mail->send()) {
                    $return['sent'] = 1;
                    $logTexte = $annonceur->email . ' => OK';
                    $logger->log($logTexte, Zend_Log::INFO);
                } else {
                    $return['sent'] = 0;
                    $logTexte = $annonceur->email . ' => KO';
                    $logger->log($logTexte, Zend_Log::INFO);
                }
            } catch (Exception $e) {
                $return['sent'] = 0;
                $logTexte = $annonceur->email . ' => ' . $e->getMessage();
                $logger->log($logTexte, Zend_Log::INFO);
            }

            $return['modal'] = '<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
			<h4 class="modal-title">Envoi d\'email</h4>
			</div>
			<div class="modal-body">
			<p>L\'email a bien été envoyée</p>
			</div>
			<div class="modal-footer">
			<a href="' . $this->view->url_retour . '" class="btn btn-default">Fermer</button>
			</div>';


            echo json_encode($return);
        }
    }

    public function reportingRedacteursAction() {
        $this->view->headScript()
                ->appendFile('http://momentjs.com/downloads/moment.min.js')
                ->appendFile('http://momentjs.com/downloads/moment-with-locales.min.js')
                ->appendFile('/javascript/daterangepicker/daterangepicker.js');
        $this->view->headLink()->appendStylesheet('/javascript/daterangepicker/daterangepicker-bs3.css');

        $start_date = $this->getParam('start_date');
        $end_date = $this->getParam('end_date');

        if ($start_date && $end_date) {
            $start_date = new Aurel_Date($start_date);
            $end_date = new Aurel_Date($end_date);
        } else {
            $start_date = new Aurel_Date();
            $start_date->subDay(29);
            $end_date = new Aurel_Date();
        }

        $oArticle = new Aurel_Table_Article();
        $oMenu = new Aurel_Table_Menu();
        $oSousMenu = new Aurel_Table_SousMenu();
        $oUser = new Aurel_Table_User();

        $articles = $oArticle->getAll();
        $reporting = $oUser->getReportingRedacteurs($start_date, $end_date);
        $menus = $oMenu->getAll();
        $sous_menus = $oSousMenu->getAll();

        $totalRedacteur = array();
        $totalMenu = array();
        $totalSousMenu = array();
        foreach ($reporting as $report) {
            $totalRedacteur[$report->email] = isset($totalRedacteur[$report->email]) ? $totalRedacteur[$report->email] + $report->count : $report->count;
            $totalMenu[$report->email][$report->menu_name] = isset($totalMenu[$report->email]) && isset($totalMenu[$report->email][$report->menu_name]) ? $totalMenu[$report->email][$report->menu_name] + $report->count : $report->count;
            $totalSousMenu[$report->email][$report->menu_name][$report->sous_menu_name] = isset($totalSousMenu[$report->email]) && isset($totalSousMenu[$report->email][$report->menu_name]) && isset($totalSousMenu[$report->email][$report->menu_name][$report->sous_menu_name]) ? $totalSousMenu[$report->email][$report->menu_name][$report->sous_menu_name] + $report->count : $report->count;
        }

        arsort($totalRedacteur, SORT_DESC);
        foreach ($totalRedacteur as $email => $t) {
            arsort($totalMenu[$email], SORT_DESC);
            foreach ($totalMenu[$email] as $menu_name => $t1) {
                arsort($totalSousMenu[$email][$menu_name], SORT_DESC);
            }
        }

        $this->view->totalRedacteur = $totalRedacteur;
        $this->view->totalMenu = $totalMenu;
        $this->view->totalSousMenu = $totalSousMenu;
        $this->view->start_date = $start_date;
        $this->view->end_date = $end_date;

        if ($this->_isAjax()) {
            $this->render('reporting-content');
        }
    }

    public function envoiMailAction() {
        
    }

}
