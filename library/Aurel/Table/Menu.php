<?php
/**
* Class Aurel_Table_Menu
* @author aurelien.cornu <aurelien.cornu@gmail.com>
* @copyright Copyright (c) 2008,MagicBegin
* @version 0.1
*/
class Aurel_Table_Menu extends Aurel_Table_Abstract 
{
	/**
	 * The table name.
	 *
	 * @var string
	 */
	protected $_name = 'menu';
	
	/**
	 * Classname for row
	 *
	 * @var string
	 */
	protected $_rowClass = 'Aurel_Table_Row_Menu';
	
	/**
	 * (non-PHPdoc)
	 * @see Aurel_Table_Abstract::getAll()
	 */
	public function getAll($admin = false){
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(['m'=>'menu'])
		->joinLeft(['s'=>'sous_menu'], 'm.id_menu = s.id_menu',['sous_menus_basename'=>new Zend_Db_Expr('GROUP_CONCAT(DISTINCT s.basename ORDER BY `s`.`order` ASC,`s`.`id_sous_menu` DESC)'), 'sous_menus_name'=>new Zend_Db_Expr('GROUP_CONCAT(DISTINCT s.name ORDER BY `s`.`order` ASC,`s`.`id_sous_menu` DESC)'), 'sous_menus_id'=>new Zend_Db_Expr('GROUP_CONCAT(DISTINCT s.id_sous_menu ORDER BY `s`.`order` ASC,`s`.`id_sous_menu` DESC)'), 'id_creation'=>new Zend_Db_Expr('GROUP_CONCAT(DISTINCT concat(s.id_sous_menu,\':\',s.id_user_creation) ORDER BY `s`.`order` ASC,`s`.`id_sous_menu` DESC)'), 'sous_menu_annonces'=>new Zend_Db_Expr('GROUP_CONCAT(DISTINCT concat(s.id_sous_menu,\':\',s.sous_menu_annonce) ORDER BY `s`.`order` ASC,`s`.`id_sous_menu` DESC)')])
		->order('m.order ASC')
		->order('s.order ASC')
		->group('m.id_menu');
		
		if(!$admin){
			$select
			->joinLeft(['a1'=>'article'], 'a1.id_sous_menu = s.id_sous_menu', ["sous_menus_articles"=>new Zend_Db_Expr("GROUP_CONCAT(distinct a1.id_article)")])
			->joinLeft(['a2'=>'article'], 'a2.id_menu = s.id_menu', ["menus_articles"=>new Zend_Db_Expr("GROUP_CONCAT(distinct a2.id_article)")])
			;
		}
		
		return $this->fetchAll($select);
	}
	
	public function getByTitle($page){
		$select = $this->select()
		->where('basename = ?',$page);
		return $this->fetchRow($select);
	}
	
	public function getBasename($strToClean)
	{
		$strToClean = html_entity_decode((string) $strToClean);
		$strToClean = mb_strtolower($strToClean, 'UTF-8');
		$strToClean = str_replace(
				['à', 'â', 'ä', 'á', 'ã', 'å', 'î', 'ï', 'ì', 'í', 'ô', 'ö', 'ò', 'ó', 'õ', 'ø', 'ù', 'û', 'ü', 'ú', 'é', 'è', 'ê', 'ë', 'ç', 'ÿ', 'ñ'],
				['a', 'a', 'a', 'a', 'a', 'a', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'e', 'e', 'e', 'e', 'c', 'y', 'n'],
				$strToClean
		);
		$strToClean = preg_replace("#[^A-Z0-9\_]#i", "-", $strToClean);
		$strToClean = preg_replace("#-{2,}#", '-', $strToClean);
		$strToClean = preg_replace("#^-|-$#", '', $strToClean);
		return $strToClean;
	}
	
	public function getMenuAnnonces(){
		$select = $this->select()
		->where('annonces = ?',1);
		return $this->fetchRow($select);
	}
}