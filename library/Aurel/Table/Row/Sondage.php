<?php
/**
* Class Aurel_Table_Page
* @author aurelien.cornu <aurelien.cornu@gmail.com>
* @copyright Copyright (c) 2008,MagicBegin
* @version 0.1
*/
class Aurel_Table_Row_Sondage extends Zend_Db_Table_Row_Abstract
{
    public function getDate($which,$type = Aurel_Date::MYSQL_DATE){
        $date = new Aurel_Date($this->$which,$type);
        return $date;
    }

    public function getQuestions(){
        $oSondageQuestion = new Aurel_Table_SondageQuestion();
        return $oSondageQuestion->getBySondage($this->id_sondage);
    }

    public function getSynthese(){
        $oSondageReponseQuestion = new Aurel_Table_SondageReponseQuestion();
        return $oSondageReponseQuestion->getSynthese($this->id_sondage);
    }
}