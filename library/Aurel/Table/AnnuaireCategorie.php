<?php
/**
* Class Aurel_Table_Menu
* @author aurelien.cornu <aurelien.cornu@gmail.com>
* @copyright Copyright (c) 2008,MagicBegin
* @version 0.1
*/
class Aurel_Table_AnnuaireCategorie extends Aurel_Table_Abstract 
{
	/**
	 * The table name.
	 *
	 * @var string
	 */
	protected $_name = 'annuaire_categorie';
	
	/**
	 * Classname for row
	 *
	 * @var string
	 */
	protected $_rowClass = 'Aurel_Table_Row_AnnuaireCategorie';
	
	/**
	 * (non-PHPdoc)
	 * @see Aurel_Table_Abstract::getAll()
	 */
	public function getAll($admin = false){
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(array('c'=>'annuaire_categorie'))
		->joinLeft(array('sc'=>'annuaire_sous_categorie'), 'c.id_annuaire_categorie = sc.id_annuaire_categorie',array('sous_categorie_basename'=>new Zend_Db_Expr('GROUP_CONCAT(DISTINCT sc.basename ORDER BY `sc`.`order` ASC,`sc`.`id_annuaire_sous_categorie` DESC)'),'sous_categorie_name'=>new Zend_Db_Expr('GROUP_CONCAT(DISTINCT sc.name ORDER BY `sc`.`order` ASC,`sc`.`id_annuaire_sous_categorie` DESC)'),'sous_categorie_id'=>new Zend_Db_Expr('GROUP_CONCAT(DISTINCT sc.id_annuaire_sous_categorie ORDER BY `sc`.`order` ASC,`sc`.`id_annuaire_sous_categorie` DESC)'),'id_creation'=>new Zend_Db_Expr('GROUP_CONCAT(DISTINCT concat(sc.id_annuaire_sous_categorie,\':\',sc.id_user_creation) ORDER BY `sc`.`order` ASC,`sc`.`id_annuaire_sous_categorie` DESC)')))
		->order('c.order ASC')
		->order('sc.order ASC')
		->group('c.id_annuaire_categorie');
		
		return $this->fetchAll($select);
	}
	
	public function getByTitle($page){
		$select = $this->select()
		->where('basename = ?',$page);
		return $this->fetchRow($select);
	}
	
	public function getBasename($strToClean)
	{
		$strToClean = html_entity_decode($strToClean);
		$strToClean = mb_strtolower($strToClean, 'UTF-8');
		$strToClean = str_replace(
				array('à','â','ä','á','ã','å','î','ï','ì','í','ô','ö','ò','ó','õ','ø','ù','û','ü','ú','é','è','ê','ë','ç','ÿ','ñ',),
				array('a','a','a','a','a','a','i','i','i','i','o','o','o','o','o','o','u','u','u','u','e','e','e','e','c','y','n',),
				$strToClean
		);
		$strToClean = preg_replace("#[^A-Z0-9\_]#i", "-", $strToClean);
		$strToClean = preg_replace("#-{2,}#", '-', $strToClean);
		$strToClean = preg_replace("#^-|-$#", '', $strToClean);
		return $strToClean;
	}
}