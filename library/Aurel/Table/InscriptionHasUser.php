<?php
/**
* Class Aurel_Table_Page
* @author aurelien.cornu <aurelien.cornu@gmail.com>
* @copyright Copyright (c) 2008,MagicBegin
* @version 0.1
*/
class Aurel_Table_InscriptionHasUser extends Aurel_Table_Abstract 
{
	/**
	 * The table name.
	 *
	 * @var string
	 */
	protected $_name = 'inscription_has_user';
	
	/**
	 * Classname for row
	 *
	 * @var string
	 */
	protected $_rowClass = 'Aurel_Table_Row_InscriptionHasUser';
	
	public function getByUser($id_user){
		$select = $this->select()
		->from(array('ihu'=>'inscription_has_user'))
		->joinInner(array('i'=>'inscription'), 'ihu.id_inscription = i.id_inscription', array())
		->where('id_user = ?',$id_user)
		->group('id_article');
		return $this->fetchAll($select);
	}
	
	public function getByArticle($id_article,$with_names = false){
		$select = $this->select()
		->from(array('ihu'=>'inscription_has_user'))
		->joinInner(array('i'=>'inscription'), 'ihu.id_inscription = i.id_inscription', array())
		->where('id_article = ?',$id_article);
		
		if($with_names){
			$select
			->setIntegrityCheck(false)
			->joinInner(array('u'=>'user'), 'ihu.id_user = u.id_user', array('*'));
		}
		
		return $this->fetchAll($select);
	}
	
	public function getByArticleAndInscription($id_article,$id_inscription,$with_names = false){
		$select = $this->select()
		->from(array('ihu'=>'inscription_has_user'))
		->joinInner(array('i'=>'inscription'), 'ihu.id_inscription = i.id_inscription', array())
		->where('i.id_article = ?',$id_article)
		->where('ihu.id_inscription = ?',$id_inscription);
		
		if($with_names){
			$select
			->setIntegrityCheck(false)
			->joinInner(array('u'=>'user'), 'ihu.id_user = u.id_user', array('*'));
		}
		
		return $this->fetchAll($select);
	}
	
	public function getByUserAndArticle($id_user,$id_article){
		$select = $this->select()
		->from(array('ihu'=>'inscription_has_user'))
		->joinInner(array('i'=>'inscription'), 'ihu.id_inscription = i.id_inscription', array())
		->where('id_user = ?',$id_user)
		->where('id_article = ?',$id_article);
		return $this->fetchAll($select);
	}
}