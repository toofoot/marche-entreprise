<?php
/**
* Class Aurel_Table_Row_Newsletter
* @author aurelien.cornu <aurelien.cornu@gmail.com>
* @version 0.1
*/
class Aurel_Table_Row_Newsletter extends Zend_Db_Table_Row_Abstract implements \Stringable
{
	public function getDate($which,$type = Aurel_Date::MYSQL_DATETIME){
		if($this->$which){
			$date = new Aurel_Date($this->$which,$type);
			return $date;
		}
		return null;
	}
	
	public function __toString(): string{
		return (string) $this->body;
	}
}