<?php

/**
 * CompteController - The default controller class
 * 
 * @author
 * @version 
 */
class SondageController extends Aurel_Controller_Abstract {

    /**
     *
     */
    public function indexAction() {
        $tabResponses = [];
        $oSondage = new Aurel_Table_Sondage();
        $oSondageQuestion = new Aurel_Table_SondageQuestion();
        $oSondageOption = new Aurel_Table_SondageOption();
        $oSondageReponseQuestion = new Aurel_Table_SondageReponseQuestion();

        $basename_sondage = $this->getParam('basename_sondage');

        $sondage = $oSondage->getByBasename($basename_sondage);
        if (!$sondage)
            throw new Zend_Exception();

        if ($sondage) {
            $questions = $sondage->getQuestions();

            $this->view->questions = $questions;
        }
        $this->view->sondage = $sondage;

        if ($sondage->login_required && $sondage->several_answers && $this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_MEMBRE)) {
            $responses = $oSondageReponseQuestion->getBySondageAndUser($sondage->id_sondage, $this->_getUser()->id_user);

            $tabResponses = array();
            foreach ($responses as $response) {
                $tabResponses[$response->sessid][$response->id_sondage_question][$response->id_sondage_option] = $response;
            }

            $this->view->tabResponses = $tabResponses;

            if ($this->hasParam('sessid')) {
                $sessid = $this->getParam('sessid');
                $reponses = $tabResponses[$sessid];
                $this->view->reponses = $reponses;
            }
        } elseif ($sondage->login_required && !$sondage->several_answers && $this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_MEMBRE)) {
            $responses = $oSondageReponseQuestion->getBySondageAndUser($sondage->id_sondage, $this->_getUser()->id_user);

            $tabResponses = array();
            foreach ($responses as $response) {
                $tabResponses[$response->id_sondage_question][$response->id_sondage_option] = $response;
            }

            $this->view->reponses = $tabResponses;
        }

        $dateToday = Aurel_Date::now();
        $dateToday->setTime("00:00:00");
        $end_date = $sondage->getDate('end_date');
        if ($dateToday->get() > $end_date->get()) {
            $this->view->apres = true;
        }
        $start_date = $sondage->getDate('start_date');
        if ($dateToday->get() < $start_date->get()) {
            $this->view->avant = true;
        }

        $formData = $this->_request->getPost();
        if ($this->_request->isPost()) {
            $this->_disableView();
            $this->_disableLayout();

            $sessid = uniqid();
            $date = Aurel_Date::now();

            $return = array();
            $id_errors = array();
            $continue = true;
            if ($this->hasParam('sessid')) {
                $sessid = $this->getParam('sessid');
                $reponses = $tabResponses[$sessid];
                foreach ($reponses as $reponse) {
                    foreach ($reponse as $r) {
                        $r->delete();
                    }
                }
            } elseif ($sondage->login_required && !$sondage->several_answers) {
                foreach ($responses as $reponse) {
                    $reponse->delete();
                }
            }

            foreach ($questions as $question) {
                if ($question->type == Aurel_Table_SondageQuestion::TYPE_TITLE) {
                    /* $response = current($formData['text'][$question->id_sondage_question]);
                      if (empty($response)) {
                      $continue = false;
                      $id_errors[$question->id_sondage_question] = "Veuillez répondre à cette question";
                      } */
                } elseif (!isset($formData['question'][$question->id_sondage_question])) {
                    $continue = false;
                    $id_errors[$question->id_sondage_question] = "Veuillez cocher 1 option de réponse";
                } else {
                    if ($question->type == Aurel_Table_SondageQuestion::TYPE_CHECKBOX) {
                        $message = "";
                        $continueThis = false;
                        foreach ($formData['question'][$question->id_sondage_question] as $id_reponse) {
                            if ($id_reponse != "0") {
                                $continueThis = true;
                            }
                        }
                        if (!$continueThis)
                            $message .= "Veuillez cocher au moins 1 option de réponse<br/>";
                        if (isset($formData['text'][$question->id_sondage_question])) {
                            foreach ($formData['text'][$question->id_sondage_question] as $id_option => $id_reponse_text) {
                                if ($id_reponse_text == "" && $formData['question'][$question->id_sondage_question][$id_option] != "0") {
                                    $continueThis = false;
                                    $message .= "Si vous cochez 'Autres', vous devez saisir une réponse<br/>";
                                }
                            }
                        }
                        if (!$continueThis) {
                            $continue = false;
                            $id_errors[$question->id_sondage_question] = $message;
                        }
                    } elseif ($question->type == Aurel_Table_SondageQuestion::TYPE_RADIO) {
                        if (isset($formData['text'][$question->id_sondage_question])) {
                            foreach ($formData['text'][$question->id_sondage_question] as $id_option => $id_reponse_text) {
                                if (current($formData['question'][$question->id_sondage_question]) == $id_option) {
                                    $continue = false;
                                    $id_errors[$question->id_sondage_question] = "Si vous cochez 'Autres', vous devez saisir une réponse";
                                }
                            }
                        }
                    }
                }
            }
            $return['errors'] = $id_errors;
            if ($continue) {
                foreach ($formData['question'] as $id_question => $responses) {
                    $question = $oSondageQuestion->getById($id_question);

                    foreach ($responses as $id_option) {
                        if ($id_option != "0") {
                            $option = $oSondageOption->getById($id_option);

                            $row = $oSondageReponseQuestion->createRow();
                            $row->id_sondage_question = $question->id_sondage_question;
                            $row->id_sondage_option = $option->id_sondage_option;
                            $row->sessid = $sessid;
                            $row->date = $date->get(Aurel_Date::MYSQL_DATETIME);
                            $row->question = $question->question;
                            if ($option->type == Aurel_Table_SondageOption::REPONSE_LIBRE && isset($formData['text'][$id_question][$id_option])) {
                                $buffer = $formData['text'][$id_question][$id_option];
                                $row->reponse = $buffer;
                            } else {
                                $row->reponse = $option->name;
                            }
                            $row->count_response = 1;
                            if ($sondage->login_required) {
                                $row->id_user_reponse = $this->_getUser()->id_user;
                            }
                            $row->save();
                        }
                    }
                }
                $return['returncode'] = true;
            }
            echo json_encode($return);
        } elseif ($this->hasParam('sessid')) {
            $this->render('questionnaire');
        }
    }

    public function questionnaireAction() {
        
    }

    public function deleteAction() {
        $oSondage = new Aurel_Table_Sondage();
        $oSondageQuestion = new Aurel_Table_SondageQuestion();
        $oSondageOption = new Aurel_Table_SondageOption();
        $oSondageReponseQuestion = new Aurel_Table_SondageReponseQuestion();

        $basename_sondage = $this->getParam('basename_sondage');

        $sondage = $oSondage->getByBasename($basename_sondage);

        if ($sondage->several_answers && $this->_getAcl()->isAllowed($this->_role, Aurel_Acl::RESSOURCE_MEMBRE)) {
            $responses = $oSondageReponseQuestion->getBySondageAndUser($sondage->id_sondage, $this->_getUser()->id_user);

            $tabResponses = array();
            foreach ($responses as $response) {
                $tabResponses[$response->sessid][$response->id_sondage_question] = $response;
            }

            $this->view->tabResponses = $tabResponses;

            if ($this->hasParam('sessid')) {
                $sessid = $this->getParam('sessid');
                $reponses = $tabResponses[$sessid];
            }
        }

        $formData = $this->getRequest()->getPost();
        if ($formData) {
            foreach ($reponses as $reponse) {
                $reponse->delete();
            }
            $this->redirect($this->view->url_redirect);
        }
    }

}
