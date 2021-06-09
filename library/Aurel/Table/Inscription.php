<?php
/**
* Class Aurel_Table_Page
* @author aurelien.cornu <aurelien.cornu@gmail.com>
* @copyright Copyright (c) 2008,MagicBegin
* @version 0.1
*/
class Aurel_Table_Inscription extends Aurel_Table_Abstract 
{
	/**
	 * The table name.
	 *
	 * @var string
	 */
	protected $_name = 'inscription';
	
	/**
	 * Classname for row
	 *
	 * @var string
	 */
	protected $_rowClass = 'Aurel_Table_Row_Inscription';
	
	public function getByArticle($id_article){
		$select = $this->select()
		->where('id_article = ?',$id_article)
		->order('id_inscription ASC');
		return $this->fetchAll($select);
	}
	
	public function getByUser($id_user){
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(array('i'=>'inscription'))
		->joinInner(array('a'=>'article'), 'a.id_article = i.id_article', array('a.title','a.start_date','a.basename'))
		->joinInner(array('ihu'=>'inscription_has_user'), 'ihu.id_inscription = i.id_inscription', array('*'))
		->where('id_user = ?',$id_user);
		return $this->fetchAll($select);
	}
	
	public function getAllReservations(){
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(array('i'=>'inscription'))
		->joinInner(array('a'=>'article'), 'a.id_article = i.id_article', array('a.title','a.start_date','a.basename','a.id_user_creation','a.inscription_quantite_limite'))
		->joinInner(array('ihu'=>'inscription_has_user'), 'ihu.id_inscription = i.id_inscription', array('*'));
		return $this->fetchAll($select);
	}
}