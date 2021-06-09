<?php

/**
 * Class Aurel_Table_AccessCode
 * @author aurelien.cornu <aurelien.cornu@gmail.com>
 * @copyright Copyright (c) 2008,MagicBegin
 * @version 0.1
 */
class Aurel_Table_AccessCode extends Aurel_Table_Abstract {

    protected $_name = 'access_code';
    protected $_rowClass = 'Aurel_Table_Row_AccessCode';
    
    /**
     * 
     * @param type $code
     * @return type
     */
    public function getByCode($code){
        $select = $this->select()->where('code = ?',$code);
        
        return $this->fetchRow($select);
    }

}
