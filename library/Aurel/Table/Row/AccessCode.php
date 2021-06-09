<?php

/**
 * Class Aurel_Table_Row_AccessCode
 * @author aurelien.cornu <aurelien.cornu@gmail.com>
 * @version 0.1
 */
class Aurel_Table_Row_AccessCode extends Zend_Db_Table_Row_Abstract {
    
    
    public function getDate($date) {
        $dateObject = null;
        if ($this->$date)
            $dateObject = new Aurel_Date($this->$date, Aurel_Date::MYSQL_DATE);

        return $dateObject;
    }
}
