<?php
/**
* Class Aurel_Table_Page
* @author aurelien.cornu <aurelien.cornu@gmail.com>
* @copyright Copyright (c) 2008,MagicBegin
* @version 0.1
*/
class Aurel_Table_Row_SondageQuestion extends Zend_Db_Table_Row_Abstract
{


    /**
     *
     */
    public function getOptions(){
        $oSondageOption = new Aurel_Table_SondageOption();
        return $oSondageOption->getByQuestion($this->id_sondage_question);
    }

    public function getTypeOption(){
        switch($this->type) {
            case Aurel_Table_SondageQuestion::TYPE_CHECKBOX:
                return "checkbox";
                break;
            case Aurel_Table_SondageQuestion::TYPE_RADIO:
                return "radio";
                break;
            default:
                return "";
                break;
        }
    }

    public function deleteOptions(){
        $options = $this->getOptions();

        foreach($options as $option){
            $option->delete();
        }
    }
}