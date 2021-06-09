<?php
/**
* Class Aurel_Table_Row_AnnuaireCategorie
* @author aurelien.cornu <aurelien.cornu@gmail.com>
* @version 0.1
*/
class Aurel_Table_Row_AnnuaireCategorie extends Zend_Db_Table_Row_Abstract 
{
	public function getSousCategories(){
		$oAnnuaireSousCategorie = new Aurel_Table_AnnuaireSousCategorie();
		return $oAnnuaireSousCategorie->getByAnnuaireCategorie($this->id_annuaire_categorie);
	}
}