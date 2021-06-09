<?php
/**
* Class Aurel_Table_Photo
* @author aurelien.cornu <aurelien.cornu@gmail.com>
* @copyright Copyright (c) 2008,MagicBegin
* @version 0.1
*/
class Aurel_Table_Photo extends Aurel_Table_Abstract 
{
	/**
	 * The table name.
	 *
	 * @var string
	 */
	protected $_name = 'photo';
	
	/**
	 * Classname for row
	 *
	 * @var string
	 */
	protected $_rowClass = 'Aurel_Table_Row_Photo';
	
	/**
	 * Get By Serie
	 * @param int $id_serie_photos
	 * @return Zend_Db_Table_Rowset
	 */
	public function getByArticle($id_article){
		$select = $this->select()
		->where('id_article = ?',$id_article)
		->order('order ASC');
		return $this->fetchAll($select);
	}
	
	/**
	 * Get By Serie
	 * @param int $id_serie_photos
	 * @return Zend_Db_Table_Rowset
	 */
	public function getByAnnuaireFiche($id_annuaire_fiche){
		$select = $this->select()
		->where('id_annuaire_fiche = ?',$id_annuaire_fiche)
		->order('order ASC');
		return $this->fetchAll($select);
	}
	


	/**
	 * Get By Serie
	 * @param int $id_serie_photos
	 * @return Zend_Db_Table_Rowset
	 */
	public function getAllOrder(){
		$select = $this->select()
		->order('id_article ASC')
		->order('order ASC');
		
		return $this->fetchAll($select);
	}
}