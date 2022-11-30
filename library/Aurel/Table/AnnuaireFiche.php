<?php
/**
* Class Aurel_Table_Page
* @author aurelien.cornu <aurelien.cornu@gmail.com>
* @copyright Copyright (c) 2008,MagicBegin
* @version 0.1
*/
class Aurel_Table_AnnuaireFiche extends Aurel_Table_Abstract 
{
	final public const STATUS_INACTIF = 0;
	final public const STATUS_ACTIF = 1;
	final public const STATUS_CORBEILLE = 2;
	final public const STATUS_WAITING = 3;
	final public const STATUS_WAITING_PROPRIETAIRE = 4;
	/**
	 * The table name.
	 *
	 * @var string
	 */
	protected $_name = 'annuaire_fiche';
	
	/**
	 * Classname for row
	 *
	 * @var string
	 */
	protected $_rowClass = 'Aurel_Table_Row_AnnuaireFiche';
	
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
	
	public function getAllByAnnuaireSousCategorie($id_annuaire_sous_categorie,$admin = false,$user = null,$search = null,$waiting_proprio = false){
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(['a'=>'annuaire_fiche'])
		->joinInner(['sc'=>'annuaire_sous_categorie'], 'a.id_annuaire_sous_categorie = sc.id_annuaire_sous_categorie',['basename_sous_categorie'=>'sc.basename', 'name_sous_categorie'=>'sc.name'])
		->joinInner(['c'=>'annuaire_categorie'], 'sc.id_annuaire_categorie = c.id_annuaire_categorie',['id_annuaire_categorie', 'basename_categorie'=>'c.basename', 'color_code'=>'color_code'])
		->joinLeft(['p'=>'photo'],'a.picture = p.id_photo',['extension'])
		->joinLeft(['p2'=>'photo'],'a.id_annuaire_fiche = p2.id_annuaire_fiche',['nbPhotos'=>new Zend_Db_Expr('COUNT(DISTINCT CONCAT(p2.id_photo,".",p2.extension))'), 'id_photos'=>new Zend_Db_Expr('GROUP_CONCAT(DISTINCT CONCAT(p2.id_photo,".",p2.extension) ORDER BY p2.order ASC,p2.id_photo ASC)')])
		->where('a.id_annuaire_sous_categorie = ?',$id_annuaire_sous_categorie)
		->order('a.nom_etablissement ASC')
		->group('a.id_annuaire_fiche');
		if($admin){
			$select->where('a.status <> ?',self::STATUS_CORBEILLE);
		} else {
			if($user && $user != 'guest'){
                if(!$waiting_proprio){
                    $select->where("
                        (a.`id_user_proprietaire` = {$user->id_user} AND a.`status` <> ".self::STATUS_CORBEILLE.")
                    OR (a.`id_user_proprietaire` <> {$user->id_user} AND a.`status` = ".self::STATUS_ACTIF.")
                    OR (a.`id_user_proprietaire` IS NULL AND a.`status` = ".self::STATUS_ACTIF.")
                    ");
                } else {
                        $select->where("
                        (a.`id_user_proprietaire` = {$user->id_user} AND a.`status` <> ".self::STATUS_CORBEILLE.")
                    OR (a.`id_user_proprietaire` <> {$user->id_user} AND a.`status` = ".self::STATUS_ACTIF.")
                    OR (a.`id_user_proprietaire` IS NULL AND a.`status` = ".self::STATUS_WAITING_PROPRIETAIRE.")
                    OR (a.`id_user_proprietaire` IS NULL AND a.`status` = ".self::STATUS_ACTIF.")
                    ");
                }
			} else {
                if(!$waiting_proprio) {
                    $select->where("a.status = " . self::STATUS_ACTIF);
                } else {
                    $select->where("a.status = ".self::STATUS_ACTIF." OR a.status = ".self::STATUS_WAITING_PROPRIETAIRE);
                }
			}
		}
		if($search){
			$where = "
			a.nom_etablissement like '%{$search}%'
			OR a.adresse_1 like '%{$search}%'
			OR a.adresse_2 like '%{$search}%'
			OR a.mail like '%{$search}%'
			OR a.website like '%{$search}%'
			OR a.descriptif like '%{$search}%'
			OR a.horaires like '%{$search}%'
			OR a.code_postal like '%{$search}%'
			OR a.ville like '%{$search}%'
			OR a.contact_nom like '%{$search}%'
			OR a.contact_prenom like '%{$search}%'
			";
			$select->where($where);
		}
		return $this->fetchAll($select);
	}
	
	public function getAllByState($state,$search = null){
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(['a'=>'annuaire_fiche'])
		->joinInner(['sc'=>'annuaire_sous_categorie'], 'a.id_annuaire_sous_categorie = sc.id_annuaire_sous_categorie',['basename_sous_categorie'=>'sc.basename', 'name_sous_categorie'=>'sc.name'])
		->joinInner(['c'=>'annuaire_categorie'], 'sc.id_annuaire_categorie = c.id_annuaire_categorie',['id_annuaire_categorie', 'basename_categorie'=>'c.basename', 'color_code'=>'color_code'])
		->joinLeft(['p'=>'photo'],'a.picture = p.id_photo',['extension'])
		->joinLeft(['p2'=>'photo'],'a.id_annuaire_fiche = p2.id_annuaire_fiche',['nbPhotos'=>new Zend_Db_Expr('COUNT(DISTINCT CONCAT(p2.id_photo,".",p2.extension))'), 'id_photos'=>new Zend_Db_Expr('GROUP_CONCAT(DISTINCT CONCAT(p2.id_photo,".",p2.extension) ORDER BY p2.order ASC,p2.id_photo ASC)')])
		->order('a.nom_etablissement ASC')
		->group('a.id_annuaire_fiche');
		switch ($state){
			case 'waiting':
				$select->where('a.status = ?',self::STATUS_WAITING);
				break;
			case 'offline':
				$select->where('a.status = ?',self::STATUS_INACTIF);
				break;
			case 'online':
				$select->where('a.status = ?',self::STATUS_ACTIF)->where('id_user_proprietaire IS NOT NULL');
				break;
			case 'corbeille':
				$select->where('a.status = ?',self::STATUS_CORBEILLE);
				break;
            case 'waiting_proprio':
                $select->where('a.status = ?',self::STATUS_WAITING_PROPRIETAIRE);
                break;
		}
		if($search){
			$where = "
			a.nom_etablissement like '%{$search}%'
			OR a.adresse_1 like '%{$search}%'
			OR a.adresse_2 like '%{$search}%'
			OR a.mail like '%{$search}%'
			OR a.website like '%{$search}%'
			OR a.descriptif like '%{$search}%'
			OR a.horaires like '%{$search}%'
			OR a.code_postal like '%{$search}%'
			OR a.ville like '%{$search}%'
			OR a.contact_nom like '%{$search}%'
			OR a.contact_prenom like '%{$search}%'
			";
			$select->where($where);
		}
		return $this->fetchAll($select);
	}
	
	public function getAllByAnnuaireCategorie($id_annuaire_categorie,$admin = false,$user = null,$search = null,$waiting_proprio = false){
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(['a'=>'annuaire_fiche'])
		->joinInner(['sc'=>'annuaire_sous_categorie'], 'a.id_annuaire_sous_categorie = sc.id_annuaire_sous_categorie',['basename_sous_categorie'=>'sc.basename', 'name_sous_categorie'=>'sc.name'])
		->joinInner(['c'=>'annuaire_categorie'], 'sc.id_annuaire_categorie = c.id_annuaire_categorie',['id_annuaire_categorie', 'basename_categorie'=>'c.basename', 'color_code'=>'color_code'])
		->joinLeft(['p'=>'photo'],'a.picture = p.id_photo',['extension'])
		->joinLeft(['p2'=>'photo'],'a.id_annuaire_fiche = p2.id_annuaire_fiche',['nbPhotos'=>new Zend_Db_Expr('COUNT(DISTINCT CONCAT(p2.id_photo,".",p2.extension))'), 'id_photos'=>new Zend_Db_Expr('GROUP_CONCAT(DISTINCT CONCAT(p2.id_photo,".",p2.extension) ORDER BY p2.order ASC,p2.id_photo ASC)')])
		->where('c.id_annuaire_categorie = ?',$id_annuaire_categorie)
		->order('a.nom_etablissement ASC')
		->group('a.id_annuaire_fiche');
		if($admin){
			$select->where('a.status <> ?',self::STATUS_CORBEILLE);
		} else {
			if($user && $user != 'guest'){
                if(!$waiting_proprio){
                    $select->where("
                        (a.`id_user_proprietaire` = {$user->id_user} AND a.`status` <> ".self::STATUS_CORBEILLE.")
                    OR (a.`id_user_proprietaire` <> {$user->id_user} AND a.`status` = ".self::STATUS_ACTIF.")
                    OR (a.`id_user_proprietaire` IS NULL AND a.`status` = ".self::STATUS_ACTIF.")
                    ");
                } else {
                    $select->where("
                        (a.`id_user_proprietaire` = {$user->id_user} AND a.`status` <> ".self::STATUS_CORBEILLE.")
                    OR (a.`id_user_proprietaire` <> {$user->id_user} AND a.`status` = ".self::STATUS_ACTIF.")
                    OR (a.`id_user_proprietaire` IS NULL AND a.`status` = ".self::STATUS_WAITING_PROPRIETAIRE.")
                    OR (a.`id_user_proprietaire` IS NULL AND a.`status` = ".self::STATUS_ACTIF.")
                    ");
                }
			} else {
                if(!$waiting_proprio) {
                    $select->where("a.status = " . self::STATUS_ACTIF);
                } else {
                    $select->where("a.status = ".self::STATUS_ACTIF." OR a.status = ".self::STATUS_WAITING_PROPRIETAIRE);
                }
			}
		}
		if($search){
			$where = "
			a.nom_etablissement like '%{$search}%'
			OR a.adresse_1 like '%{$search}%'
			OR a.adresse_2 like '%{$search}%'
			OR a.mail like '%{$search}%'
			OR a.website like '%{$search}%'
			OR a.descriptif like '%{$search}%'
			OR a.horaires like '%{$search}%'
			OR a.code_postal like '%{$search}%'
			OR a.ville like '%{$search}%'
			OR a.contact_nom like '%{$search}%'
			OR a.contact_prenom like '%{$search}%'
			";
			$select->where($where);
		}
		return $this->fetchAll($select);
	}
	
	public function getAll($admin = false,$user = null,$search = null,$waiting_proprio = false){
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(['a'=>'annuaire_fiche'])
		->joinInner(['sc'=>'annuaire_sous_categorie'], 'a.id_annuaire_sous_categorie = sc.id_annuaire_sous_categorie',['basename_sous_categorie'=>'sc.basename', 'name_sous_categorie'=>'sc.name'])
		->joinInner(['c'=>'annuaire_categorie'], 'sc.id_annuaire_categorie = c.id_annuaire_categorie',['id_annuaire_categorie', 'basename_categorie'=>'c.basename', 'color_code'=>'color_code'])
		->joinLeft(['p'=>'photo'],'a.picture = p.id_photo',['extension'])
		->joinLeft(['p2'=>'photo'],'a.id_annuaire_fiche = p2.id_annuaire_fiche',['nbPhotos'=>new Zend_Db_Expr('COUNT(DISTINCT CONCAT(p2.id_photo,".",p2.extension))'), 'id_photos'=>new Zend_Db_Expr('GROUP_CONCAT(DISTINCT CONCAT(p2.id_photo,".",p2.extension) ORDER BY p2.order ASC,p2.id_photo ASC)')])
		->order('a.nom_etablissement ASC')
		->group('a.id_annuaire_fiche');
		if($admin){
			$select->where('a.status <> ?',self::STATUS_CORBEILLE);
		} else {
			if($user && $user != 'guest'){
                if(!$waiting_proprio){
                    $select->where("
                        (a.`id_user_proprietaire` = {$user->id_user} AND a.`status` <> ".self::STATUS_CORBEILLE.")
                    OR (a.`id_user_proprietaire` <> {$user->id_user} AND a.`status` = ".self::STATUS_ACTIF.")
                    OR (a.`id_user_proprietaire` IS NULL AND a.`status` = ".self::STATUS_ACTIF.")
                    ");
                } else {
                    $select->where("
                        (a.`id_user_proprietaire` = {$user->id_user} AND a.`status` <> ".self::STATUS_CORBEILLE.")
                    OR (a.`id_user_proprietaire` <> {$user->id_user} AND a.`status` = ".self::STATUS_ACTIF.")
                    OR (a.`id_user_proprietaire` IS NULL AND a.`status` = ".self::STATUS_WAITING_PROPRIETAIRE.")
                    OR (a.`id_user_proprietaire` IS NULL AND a.`status` = ".self::STATUS_ACTIF.")
                    ");
                }
			} else {
                if(!$waiting_proprio) {
                    $select->where("a.status = " . self::STATUS_ACTIF);
                } else {
                    $select->where("a.status = ".self::STATUS_ACTIF." OR a.status = ".self::STATUS_WAITING_PROPRIETAIRE);
                }
			}
		}
		if($search){
			$where = "
			a.nom_etablissement like '%{$search}%'
			OR a.adresse_1 like '%{$search}%'
			OR a.adresse_2 like '%{$search}%'
			OR a.mail like '%{$search}%'
			OR a.website like '%{$search}%'
			OR a.descriptif like '%{$search}%'
			OR a.horaires like '%{$search}%'
			OR a.code_postal like '%{$search}%'
			OR a.ville like '%{$search}%'
			OR a.contact_nom like '%{$search}%'
			OR a.contact_prenom like '%{$search}%'
			";
			$select->where($where);
		}
		return $this->fetchAll($select);
	}
	
	public function getByProprietaire($user){
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(['a'=>'annuaire_fiche'])
		->joinInner(['sc'=>'annuaire_sous_categorie'], 'a.id_annuaire_sous_categorie = sc.id_annuaire_sous_categorie',['basename_sous_categorie'=>'sc.basename', 'name_sous_categorie'=>'sc.name'])
		->joinInner(['c'=>'annuaire_categorie'], 'sc.id_annuaire_categorie = c.id_annuaire_categorie',['id_annuaire_categorie', 'basename_categorie'=>'c.basename', 'color_code'=>'color_code'])
		->joinLeft(['p'=>'photo'],'a.picture = p.id_photo',['extension'])
		->joinLeft(['p2'=>'photo'],'a.id_annuaire_fiche = p2.id_annuaire_fiche',['nbPhotos'=>new Zend_Db_Expr('COUNT(DISTINCT CONCAT(p2.id_photo,".",p2.extension))'), 'id_photos'=>new Zend_Db_Expr('GROUP_CONCAT(DISTINCT CONCAT(p2.id_photo,".",p2.extension) ORDER BY p2.order ASC,p2.id_photo ASC)')])
		->order('a.nom_etablissement ASC')
		->group('a.id_annuaire_fiche');
		
		$select->where("a.id_user_proprietaire = ?",$user->id_user);
		return $this->fetchRow($select);
	}
	
	public function getSommeStatus(){
		$results = $this->fetchAll();
		
		$tab = [self::STATUS_ACTIF => 0, self::STATUS_WAITING => 0, self::STATUS_INACTIF => 0, self::STATUS_CORBEILLE => 0, self::STATUS_WAITING_PROPRIETAIRE => 0];
		foreach($results as $result){
            $tab[$result->status] += 1;
		}
		return $tab;
	}
}