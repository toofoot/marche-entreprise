<?php
/**
* Class Aurel_Table_Row_Article
* @author aurelien.cornu <aurelien.cornu@gmail.com>
* @version 0.1
*/
class Aurel_Table_Row_Article extends Zend_Db_Table_Row_Abstract 
{
	public function getPhotos(){
		$oPhotos = new Aurel_Table_Photo();
		
		return $oPhotos->getByArticle($this->id_article);
	}
	
	public function getIdPhotos($number = null){
		if($this->id_photos){
			$list = explode(",", (string) $this->id_photos);
			if($number)
				$list = array_slice($list, 0, $number, true);
			return $list;
		}
	}
	
	public function getDate($which,$type = Aurel_Date::MYSQL_DATETIME){
		$date = new Aurel_Date($this->$which,$type);
		return $date;
	}
	
	public function getHour($which){
		$hour = substr((string) $this->$which, 0, 5);
		$hour = str_replace(":", "h", $hour);
		return $hour;
	}
	
	public function getClassAnnonce(){
		if($this->state_annonce == Aurel_Table_Article::STATE_ANNONCE_WAITING)
			return 'warning';
		if($this->state_annonce == Aurel_Table_Article::STATE_ANNONCE_SUCCESS)
			return 'success';
		if($this->state_annonce == Aurel_Table_Article::STATE_ANNONCE_REFUSED)
			return 'danger';
	}
	
	public function getStatusAnnonce(){
		if($this->state_annonce == Aurel_Table_Article::STATE_ANNONCE_WAITING)
			return 'En attente';
		if($this->state_annonce == Aurel_Table_Article::STATE_ANNONCE_SUCCESS)
			return 'Validée';
		if($this->state_annonce == Aurel_Table_Article::STATE_ANNONCE_REFUSED)
			return 'Refusée';
	}
}