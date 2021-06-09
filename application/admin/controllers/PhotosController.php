<?php

require_once "AbstractController.php";

/**
 * Class Admin_PhotosController
 *
 * @author Aurel
 *
 */
class Admin_PhotosController extends Admin_AbstractController {

    /**
     * Pre-dispatch routines
     *
     * @return void
     */
    public function preDispatch() {
        parent::preDispatch();
        if ($this->_isAjax())
            $this->_disableLayout();
    }

    /**
     * Page add-photos
     *
     * @return void
     */
    public function addPhotosAction() {
        $oArticle = new Aurel_Table_Article();
        $article = $oArticle->getById($this->getParam('id_article'));

        $this->view->article = $article;
    }

    public function uploadMiscAction() {
        $this->_disableLayout();
        $this->_disableView();

        $funcNum = $_GET['CKEditorFuncNum'];
        $CKEditor = $_GET['CKEditor'];
        $langCode = $_GET['langCode'];

        $upload_dir = UPLOAD_PATH . "/";
        $allowed_ext = array('jpg', 'jpeg', 'png', 'gif');

        if (array_key_exists('upload', $_FILES) && $_FILES['upload']['error'] == 0) {

            $pic = $_FILES['upload'];

            $extension = $this->get_extension($pic['name']);

            if (!in_array($extension, $allowed_ext)) {
                $this->exit_status('Fichiers : "' . implode(',', $allowed_ext) . '" autorisés uniquement');
            }

            // Move the uploaded file from the temporary
            // directory to the uploads folder:
            $this->_check_dir($upload_dir);
            $upload_dir .= 'misc' . "/";
            $this->_check_dir($upload_dir);
            $name = uniqid() . '.' . $extension;

            $upload_path = $upload_dir . $name;
            $upload_paththumb = $upload_dir . 'thumb' . $name;
            $upload_pathsmallthumb = $upload_dir . 'smallthumb' . $name;
            $upload_pathminithumb = $upload_dir . 'minithumb' . $name;

            if (move_uploaded_file($pic['tmp_name'], $upload_path)) {
                include('SimpleImage.php');
                $img = new abeautifulsite\SimpleImage($upload_path);
                $img->auto_orient();

                $img->best_fit(600, 600);
                $img->save($upload_path, 80);

                $img->adaptive_resize(640, 360);
                $img->save($upload_paththumb, 80);

                $img->adaptive_resize(40, 40);
                $img->save($upload_pathminithumb, 80);

                $server = '';
                if ($this->hasParam('url') && $this->getParam('url') == 'absolute')
                    $server = "http://" . $_SERVER["HTTP_HOST"];
                echo "<script type='text/javascript'>window.parent.CKEDITOR.tools.callFunction($funcNum, '{$server}/images/upload/misc/$name');</script>";
                exit();
            } else {
                $this->exit_status('Une erreur s\'est produite !');
            }
        }

        $this->exit_status('Une erreur s\'est produite !');
    }

    public function browserAction() {
        $this->_helper->layout->setLayout('admin');
        $oPhoto = new Aurel_Table_Photo();

        $photos = $oPhoto->getAllOrder();
        $this->view->photos = $photos;

        $this->view->funcNum = $_GET['CKEditorFuncNum'];
        $this->view->CKEditor = $_GET['CKEditor'];
        $this->view->langCode = $_GET['langCode'];

        $server = '';
        if ($this->hasParam('url') && $this->getParam('url') == 'absolute')
            $server = "http://" . $_SERVER["HTTP_HOST"];

        $this->view->server = $server;
    }

    /**
     * Page upload
     *
     * @return void
     */
    public function uploadAction() {
        $this->_disableLayout();
        $this->_disableView();

        $oPhoto = new Aurel_Table_Photo();
        $oArticle = new Aurel_Table_Article();

        $upload_dir = UPLOAD_PATH . "/";
        $allowed_ext = array('jpg', 'jpeg', 'png', 'gif');

        if (array_key_exists('pic', $_FILES) && $_FILES['pic']['error'] == 0) {
            $formData = $this->_request->getPost();
            $pic = $_FILES['pic'];
            $extension = $this->get_extension($pic['name']);
            $id_article = $formData['id_article'];

            $article = $oArticle->getById($id_article);
            $photos = $article->getPhotos();

            if (!in_array($extension, $allowed_ext)) {
                $this->exit_status('Fichiers : "' . implode(',', $allowed_ext) . '" autorisés uniquement');
            }

            // Move the uploaded file from the temporary
            // directory to the uploads folder:
            $this->_check_dir($upload_dir);
            $upload_dir .= $id_article . "/";
            $this->_check_dir($upload_dir);

            $new = $oPhoto->createRow();
            $new->extension = $extension;
            $new->id_article = $id_article;
            $new->order = 0;
            $new->id_user_creation = $this->_getUser()->id_user;
            $new->date_creation = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
            $new->save();
            $name = $new->id_photo . '.' . $extension;

            $upload_path = $upload_dir . $name;
            $upload_paththumb = $upload_dir . 'thumb' . $name;
            $upload_pathsmallthumb = $upload_dir . 'smallthumb' . $name;
            $upload_pathminithumb = $upload_dir . 'minithumb' . $name;

            if ($photos->count() == 0) {
                $article->picture = $new->id_photo;
                $article->save();
            }

            if (move_uploaded_file($pic['tmp_name'], $upload_path)) {
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

                $this->exit_status('Tous les fichiers ont étés uploadés !');
            } else {
                $new->delete();
                $this->exit_status('Une erreur s\'est produite !');
            }
        }

        $this->exit_status('Une erreur s\'est produite !');
    }

    public function resizeAllAction() {
        $this->_disableLayout();
        $this->_disableView();
        $oPhoto = new Aurel_Table_Photo();
        $upload_dir = UPLOAD_PATH . "/";
        include('SimpleImage.php');
        $photos = $oPhoto->getAll();
        foreach ($photos as $photo) {
            $path = $upload_dir . $photo->id_article . "/" . $photo->id_photo . '.' . $photo->extension;
            $pathmini = $upload_dir . $photo->id_article . "/minithumb" . $photo->id_photo . '.' . $photo->extension;
            if (is_file($path) && !is_file($pathmini)) {

                $img = new abeautifulsite\SimpleImage($path);

                $img->adaptive_resize(40, 40);
                $img->save($pathmini, 80);

                Zend_Debug::dump($photo->id_article . ' / ' . $photo->id_photo);
            }
        }
    }

    /**
     * Page voir-photos
     *
     * @return void
     */
    public function voirPhotosAction() {
        
    }

    /**
     * Page sort
     *
     * @return void
     */
    public function sortAction() {
        $this->_disableLayout();
        $this->_disableView();
        $oPhoto = new Aurel_Table_Photo();

        $order = $this->getParam('order');

        $tabOrdre = explode(',', $order);

        if (is_array($tabOrdre)) {
            foreach ($tabOrdre as $key => $value) {
                $ligne = $oPhoto->getById($value);
                if ($ligne) {
                    $ligne->order = $key;
                    $ligne->save();
                }
            }
        }

        $oArticle = new Aurel_Table_Article();
        $article = $oArticle->getById($this->getParam('id_article'));

        $photos = $article->getPhotos();
        $first = $photos->current();

        $article->picture = $first->id_photo;
        $article->save();
    }

    /**
     * Page set-cover
     *
     * @return void
     */
    public function setCoverAction() {
        $this->_disableLayout();
        $this->_disableView();

        if (!$this->hasParam(('id_serie_photos')))
            throw new Zend_Exception();

        $id_serie_photos = $this->getParam('id_serie_photos');
        $oSeries = new Aurel_Table_SeriePhotos();
        $serie = $oSeries->getById($id_serie_photos);
        if (!$serie) {
            throw new Zend_Exception();
        }
        $formData = $this->_request->getPost();
        $return = array();
        if ($formData) {
            $sup = current($formData['sup']);
            $serie->cover = $sup;
            $serie->save();
            $return['cover'] = $sup;
        }
        echo json_encode($return);
    }

    /**
     * Page delete-photo
     *
     * @return void
     */
    public function deletePhotoAction() {
        $this->_disableLayout();
        $this->_disableView();

        $id_article = $this->getParam('id_article');
        $oArticle = new Aurel_Table_Article();

        $oPhoto = new Aurel_Table_Photo();
        $formData = $this->_request->getPost();
        $return = array();
        $return['returncode'] = 'ko';
        $return['formdata'] = $formData;
        if ($formData) {
            foreach ($formData['sup'] as $id_photo) {
                $photo = $oPhoto->getById($id_photo);
                $photo->deletePhoto();
            }

            $oArticle = new Aurel_Table_Article();
            $article = $oArticle->getById($this->getParam('id_article'));

            $photos = $article->getPhotos();
            if ($photos->count() > 0) {
                $first = $photos->rewind()->current();
                $article->picture = $first->id_photo;
            } else {
                $article->picture = null;
            }
            $article->save();

            $return['returncode'] = 'ok';
        }
        echo json_encode($return);
    }

    /**
     * Exit with message
     *
     * @param string $str
     * @return void
     */
    private function exit_status($str) {
        echo json_encode(array('status' => $str));
        exit;
    }

    /**
     * Get extension from file
     *
     * @param string $file_name
     * @return void
     */
    private function get_extension($file_name) {
        return strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
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

    public function uploadPhotoSiteAction() {
        $this->_disableLayout();
        $this->_disableView();

        $return = array();
        if (!empty($_FILES) && !empty($_FILES['photo'])) {
            $extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $page = $this->getParam('page');
            if ($page == "home") {
                $upload_dir = UPLOAD_PATH . "/";
                $this->_check_dir($upload_dir); // Crée le repertoire s'il n'existe pas
                $filename = "home.jpg";
                $return['picture_url'] = "/images/upload/home.jpg?src=" . uniqid();
            } elseif ($page == "menu") {
                $id_menu = $this->getParam('id_menu');
                $id_sous_menu = $this->getParam('id_sous_menu');

                $upload_dir = UPLOAD_PATH . "/";
                $this->_check_dir($upload_dir); // Crée le repertoire s'il n'existe pas
                $filename = uniqid() . "." . $extension;
                if ($id_sous_menu) {
                    $oSousMenu = new Aurel_Table_SousMenu();
                    $sousmenu = $oSousMenu->getById($id_sous_menu);
                    $sousmenu->picture = $filename;
                    $sousmenu->save();
                    $upload_dir .= "sousmenu$id_sous_menu/";
                    $this->_check_dir($upload_dir); // Crée le repertoire s'il n'existe pas
                    $return['picture_url'] = "/images/upload/sousmenu$id_sous_menu/$filename?src=" . uniqid();
                } else {
                    $oMenu = new Aurel_Table_Menu();
                    $menu = $oMenu->getById($id_menu);
                    $menu->picture = $filename;
                    $menu->save();
                    $upload_dir .= "menu$id_menu/";
                    $this->_check_dir($upload_dir); // Crée le repertoire s'il n'existe pas
                    $return['picture_url'] = "/images/upload/menu$id_menu/$filename?src=" . uniqid();
                }
            }

            $upload_filename = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_filename)) {
                include('SimpleImage.php');
                $img = new abeautifulsite\SimpleImage($upload_filename);
                $img->auto_orient();

                $img->best_fit(1200, 1200);
                $img->save($upload_filename, 80);

                $return['filename'] = $filename;
            }
        }

        echo json_encode($return);
    }

}
