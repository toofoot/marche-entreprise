<?php
/**
* Class Aurel_Table_Row_AnnuaireFiche
* @author aurelien.cornu <aurelien.cornu@gmail.com>
* @version 0.1
*/
class Aurel_Table_Row_AnnuaireFiche extends Zend_Db_Table_Row_Abstract 
{
	public function getPhotos(){
		$oPhotos = new Aurel_Table_Photo();
	
		return $oPhotos->getByAnnuaireFiche($this->id_annuaire_fiche);
	}
	
	public function getIdPhotos($number = null){
		if($this->id_photos){
			$list = explode(",", $this->id_photos);
			if($number)
				$list = array_slice($list, 0, $number, true);
			return $list;
		}
	}
	
	public function getAnnuaireSousCategorie(){
		$oAnnuaireSousCategorie = new Aurel_Table_AnnuaireSousCategorie();
		
		return $oAnnuaireSousCategorie->getById($this->id_annuaire_sous_categorie);
	}
	
	public function getProprietaire(){
		$oUser = new Aurel_Table_User();
		
		return $oUser->getById($this->id_user_proprietaire);
	}
}