<?php

/**
 * Class Aurel_Table_Row_Inscription
 * @author aurelien.cornu <aurelien.cornu@gmail.com>
 * @version 0.1
 */
class Aurel_Table_Row_Invitation extends Zend_Db_Table_Row_Abstract
{

    public function getCreator()
    {
        $oUser = new Aurel_Table_User();

        $user = $oUser->getById($this->id_user_creation);

        return $user;
    }
    public function getDate($date)
    {
        $dateObject = null;
        if ($this->$date)
            $dateObject = new Aurel_Date($this->$date, Aurel_Date::MYSQL_DATETIME);

        return $dateObject;
    }

    public function getState()
    {
        switch ($this->state) {
            case Aurel_Table_Invitation::TYPE_INIT:
                return 'Initialisation';
                break;
            case Aurel_Table_Invitation::TYPE_READYTOSEND:
                return "L'invitation sera envoyée dans quelques instants";
                break;
            case Aurel_Table_Invitation::TYPE_READYTORESEND:
                return "L'invitation sera envoyée dans quelques instants";
                break;
            case Aurel_Table_Invitation::TYPE_RESENT:
                return 'Relancé';
                break;
            case Aurel_Table_Invitation::TYPE_SENT:
                return 'Invité';
                break;
            case Aurel_Table_Invitation::TYPE_VALIDATED:
                return 'Compte créé';
                break;
        }
    }
}
