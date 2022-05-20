<?php
/**
* Class Aurel_Table_Page
* @author aurelien.cornu <aurelien.cornu@gmail.com>
* @copyright Copyright (c) 2008,MagicBegin
* @version 0.1
*/
class Aurel_Table_Mail extends Aurel_Table_Abstract 
{
	public const STATUS_INIT = 0;
	public const STATUS_WAIT = 1;
	public const STATUS_SENT = 2;
	public const STATUS_ERROR = 3;
	
	/**
	 * The table name.
	 *
	 * @var string
	 */
	protected $_name = 'mail';
	
	/**
	 * Classname for row
	 *
	 * @var string
	 */
	protected $_rowClass = 'Aurel_Table_Row_Mail';
	
	public function getByComHash($comHash){
		$select = $this->select()
		->where('comHash = ?',$comHash);
		
		return $this->fetchRow($select);
	}
}