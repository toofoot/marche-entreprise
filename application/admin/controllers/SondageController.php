<?php
require_once "AbstractController.php";
/**
 * Class Admin_ArticlesController
 *
 * @author Aurel
 *
 */
class Admin_SondageController extends Admin_AbstractController
{

    public function indexAction(){
        $oSondage = new Aurel_Table_Sondage();

        $sondages = $oSondage->getAll();

        $dateToday = Aurel_Date::now();
        $dateToday->setTime("00:00:00");
        $tabSortie = [];
        $tabSortieHistorique = [];
        foreach($sondages as $sondage){
            $end_date = $sondage->getDate('end_date');

            if($dateToday->get() <= $end_date->get()){
                $tabSortie[$sondage->id_sondage] = $sondage;
            } else {
                $tabSortieHistorique[$sondage->id_sondage] = $sondage;
            }
        }
        $this->view->tabSortie = $tabSortie;
        $this->view->tabSortieHistorique = $tabSortieHistorique;
    }

    public function editAction(){
        $oSondage = new Aurel_Table_Sondage();
        $id_sondage = $this->getParam('id_sondage');

        if($id_sondage){
            $sondage = $oSondage->getById($id_sondage);
        } else {
            $sondage = $oSondage->createRow();
            $sondage->id_user_creation = $this->_getUser()->id_user;
            $sondage->date_creation = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
            $sondage->start_date = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATE);
            $sondage->end_date = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATE);
            $sondage->several_answers = 0;
            $sondage->login_required = 0;
        }

        $this->view->sondage = $sondage;

        $formData = $this->getRequest()->getPost();
        if($formData){
                $sondage->name = $formData["name"];
                $sondage->basename = $oSondage->getBasename($formData["name"]);
                $sondage->description = $formData["description"];
                $sondage->id_user_modification = $this->_getUser()->id_user;
                $sondage->date_modification = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
                $start_date = new Aurel_Date($formData['start_date']);
                $sondage->start_date = $start_date->get(Aurel_Date::MYSQL_DATE);
                $end_date = new Aurel_Date($formData['end_date']);
                $sondage->end_date = $end_date->get(Aurel_Date::MYSQL_DATE);
                $sondage->login_required = $formData['login_required'];
                $sondage->several_answers = $formData['several_answers'];
                $sondage->link_new_answer = $formData['link_new_answer'];

                $sondage->save();

            if(!$id_sondage){
                $this->_redirect($this->view->url(['id_sondage'=>$sondage->id_sondage]));
            } else {
                $this->_redirect($this->view->url(['id_sondage'=>null, 'action'=>'index']));
            }
        }

        if($sondage->id_sondage) {
            $questions = $sondage->getQuestions();
            $this->view->questions = $questions;
        }
    }

    public function editQuestionAction()
    {
        $oSondage = new Aurel_Table_Sondage();
        $oSondageQuestion = new Aurel_Table_SondageQuestion();
        $oSondageOption = new Aurel_Table_SondageOption();

        $id_sondage_question = $this->getParam('id_sondage_question');
        $id_sondage = $this->getParam('id_sondage');

        $select = ["-1" => "-- Choisir le type de la question --", Aurel_Table_SondageQuestion::TYPE_CHECKBOX => "Choix multiples (Cases à cocher)", Aurel_Table_SondageQuestion::TYPE_RADIO => "Choix unique parmi plusieurs propositions (Case d'option)", Aurel_Table_SondageQuestion::TYPE_SELECT => "Choix unique parmi plusieurs propositions (Menu déroulant)", Aurel_Table_SondageQuestion::TYPE_TEXT => "Réponse libre"];

        if ($id_sondage_question) {
            $sondage_question = $oSondageQuestion->getById($id_sondage_question);
        } else {
            $sondage_question = $oSondageQuestion->createRow();
            $sondage_question->id_user_creation = $this->_getUser()->id_user;
            $sondage_question->date_creation = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);
            $sondage_question->id_sondage = $id_sondage;
        }
        $tabOptions = [];
        $optionsLibre = null;
        if ($sondage_question->id_sondage_question && $sondage_question->type != Aurel_Table_SondageQuestion::TYPE_TEXT) {
            $options = $sondage_question->getOptions();
            foreach ($options as $option) {
                if ($option->type == Aurel_Table_SondageOption::REPONSE_LIBRE)
                    $optionsLibre = $option;
                else
                    $tabOptions[] = $option;
            }
        }
        $this->view->optionsLibre = $optionsLibre;
        $this->view->options = $tabOptions;
        $this->view->sondage_question = $sondage_question;
        $this->view->select = $select;

        $formData = $this->getRequest()->getPost();
        if($formData) {
            $this->_disableLayout();
            $this->_disableView();

            $continue = true;
            $return = [];
            if($formData["type"] == "-1"){
                $continue = false;
                $return["errors"][] = "#type";
                $return["message"] = "Erreur";
            }
            if($formData["question"] == ""){
                $continue = false;
                $return["errors"][] = "#question";
                $return["message"] = "Erreur";
            }
            $filter = array_filter($formData["option"]);
            if($formData['type'] != Aurel_Table_SondageQuestion::TYPE_TEXT && empty($filter)){
                $continue = false;
                $return["errors"][] = ".options .checkbox";
                $return["message"] = "Erreur";
            }
            if($continue) {

                $sondage_question->question = $formData['question'];
                $sondage_question->type = $formData['type'];
                $sondage_question->id_user_modification = $this->_getUser()->id_user;
                $sondage_question->date_modification = Aurel_Date::now()->get(Aurel_Date::MYSQL_DATETIME);

                $sondage_question->save();

                if ($sondage_question->type == Aurel_Table_SondageQuestion::TYPE_TEXT) {
                    $options = $sondage_question->getOptions();
                    $optionExiste = null;
                    foreach($options as $option){
                        if($option->type != Aurel_Table_SondageOption::REPONSE_LIBRE){
                            $option->delete();
                        } else {
                            $optionExiste = $option;
                        }
                    }
                    unset($option);
                    if(!$optionExiste) {
                        $option = $oSondageOption->createRow();
                    } else {
                        $option = $optionExiste;
                    }
                    $option->type = Aurel_Table_SondageOption::REPONSE_LIBRE;
                    $option->name = '';
                    $option->id_sondage_question = $sondage_question->id_sondage_question;
                    $option->order = 0;
                    $option->save();
                } else {
                    $order = 0;
                    $options = $sondage_question->getOptions();
                    foreach ($options as $option) {
                        if($option->type == Aurel_Table_SondageOption::REPONSE_LIBRE && !isset($formData["option"]["utilisateur"])){
                            $option->delete();
                        } elseif(!isset($formData["option"]["id_" . $option->id_sondage_option])){
                            $option->delete();
                        }
                    }
                    foreach ($formData["option"] as $key => $value) {
                        if ($key == 'utilisateur') {
                            $optionRowset = $oSondageOption->getByQuestionAndType($sondage_question->id_sondage_question, Aurel_Table_SondageOption::REPONSE_LIBRE);
                            $option = $optionRowset->current();
                            if(!$option){
                                $option = $oSondageOption->createRow();
                                $option->type = Aurel_Table_SondageOption::REPONSE_LIBRE;
                            }
                        } else {
                            if(str_contains((string) $key,'id_')){
                                $id = str_replace('id_','',(string) $key);
                                $option = $oSondageOption->getById($id);
                            } else {
                                $option = $oSondageOption->createRow();
                                $option->type = Aurel_Table_SondageOption::REPONSE_INT;
                            }
                        }
                        $option->name = $value;
                        $option->id_sondage_question = $sondage_question->id_sondage_question;
                        $option->order = $order;
                        $option->save();
                        $order++;
                    }
                }
                $this->view->question = $sondage_question;

                //$return['formData'] = $formData;
                $return['id_sondage_question'] = $sondage_question->id_sondage_question;
                $return['question'] = $this->view->render("sondage/show-question.phtml");
            }
            echo json_encode($return, JSON_THROW_ON_ERROR);
        }
    }

    public function deleteQuestionAction(){
        $oSondageQuestion = new Aurel_Table_SondageQuestion();

        $id_sondage_question = $this->getParam('id_sondage_question');

        $question = $oSondageQuestion->getById($id_sondage_question);

        if($this->getRequest()->isPost()){
            $this->_disableLayout();
            $this->_disableView();

            $return = [];
            if($question){
                $return['id_sondage_question'] = $question->id_sondage_question;
                $question->delete();
            }
            echo json_encode($return, JSON_THROW_ON_ERROR);
        }
    }

    public function downloadSyntheseAction(){
        $this->_disableLayout();
        $this->_disableView();

        if($this->hasParam('id_sondage'))
            $id_sondage = $this->getParam('id_sondage');
        else
            throw new Zend_Acl_Exception();

        $data = "Date;Heure;";

        $oSondage = new Aurel_Table_Sondage();
        $oUser = new Aurel_Table_User();

        $sondage = $oSondage->getById($id_sondage);
        $synthese = $sondage->getSynthese();
        $questions = $sondage->getQuestions();

        foreach($questions as $question){
            $data .= $question->question . ";";
        }
        if($sondage->login_required){
            $data .= "Emetteur;Email emetteur;";
        }
        $data .= "\n";

        $tab = [];
        foreach($synthese as $row){
            $date = new Aurel_Date($row->date,Aurel_Date::MYSQL_DATETIME);
            $tab[$row->sessid]['date'] = $date;
            $tab[$row->sessid][$row->id_sondage_question] = $row->reponse;
            if($sondage->login_required) {
                $tab[$row->sessid]['user'] = $row->id_user_reponse;
            }
        }

        foreach($tab as $sessid => $reponses){
            $data .= $reponses["date"]->get(Aurel_Date::DATE_SHORT) . ";";
            $data .= $reponses["date"]->get(Aurel_Date::TIME_SHORT) . ";";
            foreach($questions as $question){
                $data .= $reponses[$question->id_sondage_question] . ";";
            }
            if($sondage->login_required) {
                $user = $oUser->getById($reponses['user']);
                $data .= $user->getFullName() . ";";
                $data .= $user->email . ";";
            }
            $data .= "\n";
        }

        $this->getResponse()->setHeader('Content-Type','text/csv',true);
        $this->getResponse()->setHeader("Content-disposition","attachment; filename=synthese.csv",true);
        print_r(utf8_decode($data));
    }

    public function sortAction(){
        $this->_disableLayout();
        $this->_disableView();
        $oSondageQuestion = new Aurel_Table_SondageQuestion();
        $order = $this->getParam('order');

        $tabOrdre = explode(',',(string) $order);

        if (is_array($tabOrdre))
        {
            foreach($tabOrdre as $key=>$value){
                $id = str_replace('question_','',$value);
                $ligne = $oSondageQuestion->getById($id);
                if($ligne){
                    $ligne->order = $key;
                    $ligne->save();
                }
            }
        }
    }

    public function linkAction(){

        if($this->hasParam('id_sondage'))
            $id_sondage = $this->getParam('id_sondage');
        else
            throw new Zend_Acl_Exception();

        $oSondage = new Aurel_Table_Sondage();

        $sondage = $oSondage->getById($id_sondage);

        $this->view->sondage = $sondage;
    }
}
