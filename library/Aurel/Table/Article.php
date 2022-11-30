<?php
/**
* Class Aurel_Table_Page
* @author aurelien.cornu <aurelien.cornu@gmail.com>
* @copyright Copyright (c) 2008,MagicBegin
* @version 0.1
*/
class Aurel_Table_Article extends Aurel_Table_Abstract 
{
	/**
	 * The table name.
	 * @var string
	 */
	protected $_name = 'article';
	
	final public const STATUS_INACTIF = 0;
	final public const STATUS_ACTIF = 1;
	final public const STATUS_CORBEILLE = 2;
	
	final public const STATE_ANNONCE_WAITING = 0;
	final public const STATE_ANNONCE_SUCCESS = 1;
	final public const STATE_ANNONCE_REFUSED = 2;
	
	/**
	 * Classname for row
	 * @var string
	 */
	protected $_rowClass = 'Aurel_Table_Row_Article';

    /**
     * @param $id_sous_menu
     * @param bool $admin
     * @param null $user
     * @return Zend_Db_Table_Rowset_Abstract
     */
	public function getAllBySousMenu($id_sous_menu,$admin = false,$user = null){
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(['a'=>'article'])
		->joinLeft(['f'=>'file'],'a.id_article = f.id_article',['id_files'=>new Zend_Db_Expr('GROUP_CONCAT(distinct f.id_file ORDER BY f.order)'), 'names'=>new Zend_Db_Expr('GROUP_CONCAT(distinct CONCAT(f.name,".",f.extension) ORDER BY f.order)')])
		->joinLeft(['p'=>'photo'],'a.picture = p.id_photo',['extension'])
		->joinLeft(['p2'=>'photo'],'a.id_article = p2.id_article',['nbPhotos'=>new Zend_Db_Expr('COUNT(DISTINCT CONCAT(p2.id_photo,".",p2.extension))'), 'id_photos'=>new Zend_Db_Expr('GROUP_CONCAT(DISTINCT CONCAT(p2.id_photo,".",p2.extension) ORDER BY p2.order ASC,p2.id_photo ASC)')])
		->where('a.id_sous_menu = ?',$id_sous_menu)
		->where('a.annonce = ?',0)
		->order('a.order_rubrique ASC')
		->order('a.date_creation DESC')
		->group('a.id_article');
		if($admin){
			$select->where('a.status <> ?',self::STATUS_CORBEILLE);
		} else {
			if($user && $user != 'guest'){
				$select->where("(a.id_user_creation = {$user->id_user} AND a.status <> ".self::STATUS_CORBEILLE.") OR (a.id_user_creation <> {$user->id_user} AND a.status = ".self::STATUS_ACTIF.")");
			} else {
				$select->where('a.status = ?',self::STATUS_ACTIF);
			}
		}
		return $this->fetchAll($select);
	}

    /**
     * @param $id_menu
     * @param bool $admin
     * @param null $user
     * @return Zend_Db_Table_Rowset_Abstract
     */
	public function getAllByMenu($id_menu,$admin = false,$user = null){
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(['a'=>'article'])
		->joinLeft(['f'=>'file'],'a.id_article = f.id_article',['id_files'=>new Zend_Db_Expr('GROUP_CONCAT(distinct f.id_file ORDER BY f.order)'), 'names'=>new Zend_Db_Expr('GROUP_CONCAT(distinct CONCAT(f.name,".",f.extension) ORDER BY f.order)')])
		->joinLeft(['p'=>'photo'],'a.picture = p.id_photo',['extension'])
		->joinLeft(['p2'=>'photo'],'a.id_article = p2.id_article',['nbPhotos'=>new Zend_Db_Expr('COUNT(DISTINCT CONCAT(p2.id_photo,".",p2.extension))'), 'id_photos'=>new Zend_Db_Expr('GROUP_CONCAT(DISTINCT CONCAT(p2.id_photo,".",p2.extension) ORDER BY p2.order ASC,p2.id_photo ASC)')])
		->where('a.id_menu = ?',$id_menu)
		->where('a.annonce = ?',0)
		->order('a.order_rubrique ASC')
		->order('a.date_creation DESC')
		->group('a.id_article');
		
		if($admin){
			$select->where('a.status <> ?',self::STATUS_CORBEILLE);
		} else {
			if($user && $user != 'guest'){
				$select->where("(a.id_user_creation = {$user->id_user} AND a.status <> ".self::STATUS_CORBEILLE.") OR (a.id_user_creation <> {$user->id_user} AND a.status = ".self::STATUS_ACTIF.")");
			} else {
				$select->where('a.status = ?',self::STATUS_ACTIF);
			}
		}
		
		return $this->fetchAll($select);
	}

    /**
     * @param null $limit
     * @param null $offset
     * @param bool $home
     * @return Zend_Db_Table_Rowset_Abstract
     */
	public function getAll($limit = null,$offset = null,$home = false){
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(['a'=>'article'])
		->joinLeft(['f'=>'file'],'a.id_article = f.id_article',['id_files'=>new Zend_Db_Expr('GROUP_CONCAT(distinct f.id_file ORDER BY f.order)'), 'names'=>new Zend_Db_Expr('GROUP_CONCAT(distinct CONCAT(f.name,".",f.extension) ORDER BY f.order)')])
		->joinLeft(['p'=>'photo'],'a.picture = p.id_photo',['extension'])
		->joinLeft(['p2'=>'photo'],'a.id_article = p2.id_article',['nbPhotos'=>new Zend_Db_Expr('COUNT(DISTINCT CONCAT(p2.id_photo,".",p2.extension))'), 'id_photos'=>new Zend_Db_Expr('GROUP_CONCAT(DISTINCT CONCAT(p2.id_photo,".",p2.extension) ORDER BY p2.order ASC,p2.id_photo ASC)')])
		->joinLeft(['m'=>'menu'],'a.id_menu = m.id_menu',['menu_name'=>'m.name'])
		->joinLeft(['s'=>'sous_menu'],'a.id_sous_menu = s.id_sous_menu',['sous_menu_name'=>'s.name'])
		->where('a.status = ?',self::STATUS_ACTIF)
		->order('a.order ASC')
		->where('a.hide_home = ?',0)
		->order('a.date_creation DESC')
		->group('a.id_article');
		
		if(!$home){
			$select
			->where('a.annonce = ?',0);
		} else {
            $select->joinLeft(['m2'=>'menu'],'m2.id_menu = s.id_menu',['menu_name2'=>'m2.name']);
        }
		
		$select->limit($limit,$offset);
		return $this->fetchAll($select);
	}

    /**
     * @param $search
     * @param null $limit
     * @param null $offset
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function search($search, $limit = null,$offset = null){
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['a'=>'article'])
            ->joinLeft(['f'=>'file'],'a.id_article = f.id_article',['id_files'=>new Zend_Db_Expr('GROUP_CONCAT(distinct f.id_file ORDER BY f.order)'), 'names'=>new Zend_Db_Expr('GROUP_CONCAT(distinct CONCAT(f.name,".",f.extension) ORDER BY f.order)')])
            ->joinLeft(['p'=>'photo'],'a.picture = p.id_photo',['extension'])
            ->joinLeft(['p2'=>'photo'],'a.id_article = p2.id_article',['nbPhotos'=>new Zend_Db_Expr('COUNT(DISTINCT CONCAT(p2.id_photo,".",p2.extension))'), 'id_photos'=>new Zend_Db_Expr('GROUP_CONCAT(DISTINCT CONCAT(p2.id_photo,".",p2.extension) ORDER BY p2.order ASC,p2.id_photo ASC)')])
            ->joinLeft(['m'=>'menu'],'a.id_menu = m.id_menu',['menu_name'=>'m.name'])
            ->joinLeft(['s'=>'sous_menu'],'a.id_sous_menu = s.id_sous_menu',['sous_menu_name'=>'s.name'])
            ->where('a.status = ?',self::STATUS_ACTIF)
            ->group('a.id_article');

        $searchTab = explode(" ",(string) $search);
        foreach($searchTab as &$word){
            $word = Aurel_Phonetique::convert($word);
        }
        $search = implode(" ",$searchTab);

        $select->order("match(a.content_soundex,a.title_soundex) against ('$search') DESC");
        $select->where("match(a.content_soundex,a.title_soundex) against ('$search')");
        $select->limit($limit,$offset);

        return $this->fetchAll($select);
    }

    /**
     * @param null $limit
     * @param null $offset
     * @param bool $home
     * @return Zend_Db_Table_Rowset_Abstract
     */
	public function getAllIntegrity($limit = null,$offset = null,$home = false){
		$select = $this->select()
		->from(['a'=>'article'])
		->where('a.status = ?',self::STATUS_ACTIF)
		->where('a.hide_home = ?',0)
		->order('a.order ASC')
		->order('a.date_creation DESC');
		
		if(!$home){
			$select
			->where('a.annonce = ?',0);
		}
		
		$select->limit($limit,$offset);
		return $this->fetchAll($select);
	}

    /**
     * @param null $limit
     * @param null $offset
     * @return Zend_Db_Table_Rowset_Abstract
     */
	public function getAllCorbeille($limit = null,$offset = null){
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(['a'=>'article'])
		->joinLeft(['f'=>'file'],'a.id_article = f.id_article',['id_files'=>new Zend_Db_Expr('GROUP_CONCAT(distinct f.id_file ORDER BY f.order)'), 'names'=>new Zend_Db_Expr('GROUP_CONCAT(distinct CONCAT(f.name,".",f.extension) ORDER BY f.order)')])
		->joinLeft(['p'=>'photo'],'a.picture = p.id_photo',['extension'])
		->joinLeft(['p2'=>'photo'],'a.id_article = p2.id_article',['nbPhotos'=>new Zend_Db_Expr('COUNT(DISTINCT CONCAT(p2.id_photo,".",p2.extension))'), 'id_photos'=>new Zend_Db_Expr('GROUP_CONCAT(DISTINCT CONCAT(p2.id_photo,".",p2.extension) ORDER BY p2.order ASC,p2.id_photo ASC)')])
		->joinLeft(['m'=>'menu'],'a.id_menu = m.id_menu',['menu_name'=>'m.name'])
		->joinLeft(['s'=>'sous_menu'],'a.id_sous_menu = s.id_sous_menu',['sous_menu_name'=>'s.name'])
		->where('a.status = ?',self::STATUS_CORBEILLE)
		->where('a.annonce = ?',0)
		->order('a.date_creation DESC')
		->group('a.id_article');
		
		$select->limit($limit,$offset);
		
		return $this->fetchAll($select);
	}

    /**
     * @param null $limit
     * @param null $offset
     * @return Zend_Db_Table_Rowset_Abstract
     */
	public function getAllAnnoncesWaiting($limit = null,$offset = null){
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(['a'=>'article'])
		->joinLeft(['f'=>'file'],'a.id_article = f.id_article',['id_files'=>new Zend_Db_Expr('GROUP_CONCAT(distinct f.id_file ORDER BY f.order)'), 'names'=>new Zend_Db_Expr('GROUP_CONCAT(distinct CONCAT(f.name,".",f.extension) ORDER BY f.order)')])
		->joinLeft(['p'=>'photo'],'a.picture = p.id_photo',['extension'])
		->joinLeft(['p2'=>'photo'],'a.id_article = p2.id_article',['nbPhotos'=>new Zend_Db_Expr('COUNT(DISTINCT CONCAT(p2.id_photo,".",p2.extension))'), 'id_photos'=>new Zend_Db_Expr('GROUP_CONCAT(DISTINCT CONCAT(p2.id_photo,".",p2.extension) ORDER BY p2.order ASC,p2.id_photo ASC)')])
		->joinLeft(['m'=>'menu'],'a.id_menu = m.id_menu',['menu_name'=>'m.name'])
		->joinLeft(['s'=>'sous_menu'],'a.id_sous_menu = s.id_sous_menu',['sous_menu_name'=>'s.name'])
		//->where('a.status = ?',self::STATUS_CORBEILLE)
		->where('a.annonce = ?',1)
		->where('a.state_annonce = ?',Aurel_Table_Article::STATE_ANNONCE_WAITING)
		->order('a.date_creation DESC')
		->group('a.id_article');
	
		$select->limit($limit,$offset);
	
		return $this->fetchAll($select);
	}

    /**
     * @param null $days
     * @return Zend_Db_Table_Rowset_Abstract
     */
	public function getNumberAnnoncesBySousMenu($days = null){
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(['a'=>'article'],['a.id_sous_menu', 'num'=> new Zend_Db_Expr('COUNT(a.id_article)')])
		->where('a.annonce = ?',1)
		->where('a.state_annonce = ?',Aurel_Table_Article::STATE_ANNONCE_SUCCESS)
		->group('a.id_sous_menu');
		
		if($days){
			$date = Aurel_Date::now()->subDay($days - 1)->setTime("00:00");
			$select->where('a.date_validation > ?',$date->get(Aurel_Date::MYSQL_DATETIME));
		}
	
		return $this->fetchAll($select);
	}

    /**
     * @return Zend_Db_Table_Rowset_Abstract
     */
	public function getAllCorbeilleForDelete(){
		$select = $this->select()
		->from(['a'=>'article'])
		->where('a.status = ?',self::STATUS_CORBEILLE)
		->where('a.annonce = ?',0);
		
		return $this->fetchAll($select);
	}

    /**
     * @param null $limit
     * @return Zend_Db_Table_Rowset_Abstract
     */
	public function getAvenir($limit = null){
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(['a'=>'article'])
		->joinLeft(['m'=>'menu'],'a.id_menu = m.id_menu',['menu_name'=>'m.name'])
		->joinLeft(['s'=>'sous_menu'],'a.id_sous_menu = s.id_sous_menu',['sous_menu_name'=>'s.name'])
		->where('link_event = ?',1)
		->where('a.annonce = ?',0)
		->where('a.status = ?',self::STATUS_ACTIF)
		->where('a.start_date >= ?',Aurel_Date::now()->get(Aurel_Date::MYSQL_DATE))
		->order('a.start_date ASC')
		->order('a.id_article DESC');
		
		if($limit)
			$select->limit($limit);
		return $this->fetchAll($select);
	}

    /**
     * @param null $limit
     * @return Zend_Db_Table_Rowset_Abstract
     */
	public function getPasse($limit = null){
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(['a'=>'article'])
		->joinLeft(['m'=>'menu'],'a.id_menu = m.id_menu',['menu_name'=>'m.name'])
		->joinLeft(['s'=>'sous_menu'],'a.id_sous_menu = s.id_sous_menu',['sous_menu_name'=>'s.name'])
		->where('link_event = ?',1)
		->where('a.annonce = ?',0)
		->where('a.status = ?',self::STATUS_ACTIF)
		->where('a.start_date < ?',Aurel_Date::now()->get(Aurel_Date::MYSQL_DATE))
		->order('a.start_date DESC')
		->order('a.id_article DESC');
		
		if($limit)
			$select->limit($limit);
		return $this->fetchAll($select);
	}

    /**
     * @return null|Zend_Db_Table_Row_Abstract
     */
	public function getPortrait(){
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(['a'=>'article'])
		->joinLeft(['p'=>'photo'],'a.picture = p.id_photo',['extension'])
		->joinLeft(['p2'=>'photo'],'a.id_article = p2.id_article',['nbPhotos'=>new Zend_Db_Expr('COUNT(DISTINCT CONCAT(p2.id_photo,".",p2.extension))'), 'id_photos'=>new Zend_Db_Expr('GROUP_CONCAT(DISTINCT CONCAT(p2.id_photo,".",p2.extension) ORDER BY p2.order ASC,p2.id_photo ASC)')])
		->where('a.status = ?',self::STATUS_ACTIF)
		->where('a.portrait = ?',1)
		->where('a.annonce = ?',0)
		->order('a.date_creation DESC')
		->order('a.id_article DESC')
		->group('a.id_article')
		->limit(1);
		
		return $this->fetchRow($select);
	}

    /**
     * @param $strToClean
     * @return mixed|string
     */
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

    /**
     * @param $page
     * @return null|Zend_Db_Table_Row_Abstract
     */
	public function getByTitle($page){
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(['a'=>'article'])
		->joinLeft(['f'=>'file'],'a.id_article = f.id_article',['id_files'=>new Zend_Db_Expr('GROUP_CONCAT(distinct f.id_file ORDER BY f.order)'), 'names'=>new Zend_Db_Expr('GROUP_CONCAT(distinct CONCAT(f.name,".",f.extension) ORDER BY f.order)')])
		->joinLeft(['p'=>'photo'],'a.picture = p.id_photo',['extension'])
		->joinLeft(['p2'=>'photo'],'a.id_article = p2.id_article',['nbPhotos'=>new Zend_Db_Expr('COUNT(DISTINCT CONCAT(p2.id_photo,".",p2.extension))'), 'id_photos'=>new Zend_Db_Expr('GROUP_CONCAT(DISTINCT CONCAT(p2.id_photo,".",p2.extension) ORDER BY p2.order ASC,p2.id_photo ASC)')])
		->where('a.basename = ?',$page)
		//->where('a.annonce = ?',0)
		->order('a.date_creation DESC')
		->group('a.id_article');
		
		return $this->fetchRow($select);
	}

    /**
     * @return Zend_Db_Table_Rowset_Abstract
     */
	public function getJ7Articles(){
		$oNewsletter = new Aurel_Table_Newsletter();
		$last_newsletter = $oNewsletter->getLastNewsletterWithArticles();
		
		if($last_newsletter){
			$date = new Aurel_Date($last_newsletter->date_envoi,Aurel_Date::MYSQL_DATETIME);
		} else {
			$date = Aurel_Date::now();
			$date->subDay(7)->setTime("12:00");
		}

        $dateEventsNow = Aurel_Date::now();
        $dateEventsNow->setTime("00:00");
        $dateEvents7 = Aurel_Date::now();
        $dateEvents7->addDay(7)->setTime("00:00");
		
		$select = $this->select()
		//->where("date_modification >= ?",$date->get(Aurel_Date::MYSQL_DATETIME))
		->where("date_modification >= '{$date->get(Aurel_Date::MYSQL_DATETIME)}' OR (link_event = 1 AND start_date BETWEEN '{$dateEventsNow->get(Aurel_Date::MYSQL_DATE)}' AND '{$dateEvents7->get(Aurel_Date::MYSQL_DATE)}')")
		->where('status = ?',self::STATUS_ACTIF)
		->where('annonce = ?',0)
		->order('date_creation DESC')
		->order('id_article DESC')
		->group('id_article');

		return $this->fetchAll($select);
	}

    /**
     * @return Zend_Db_Table_Rowset_Abstract
     */
	public function getJ7Annonces(){
		$oNewsletter = new Aurel_Table_Newsletter();
		$last_newsletter = $oNewsletter->getLastNewsletterWithAnnonces();
	
		if($last_newsletter){
			$date = new Aurel_Date($last_newsletter->date_envoi,Aurel_Date::MYSQL_DATETIME);
		} else {
			$date = Aurel_Date::now();
			$date->subDay(7)->setTime("12:00");
		}
	
		$select = $this->select()
		->where('date_creation >= ?',$date->get(Aurel_Date::MYSQL_DATETIME))
		->where('status = ?',self::STATUS_ACTIF)
		->where('annonce = ?',1)
		->where('state_annonce = ?',self::STATE_ANNONCE_SUCCESS)
		->order('date_creation DESC')
		->order('id_article DESC')
		->group('id_article');
	
		return $this->fetchAll($select);
	}

    /**
     * @param $id_sous_menu
     * @param null $days
     * @return Zend_Db_Table_Rowset_Abstract
     */
	public function getAllAnnoncesBySousMenu($id_sous_menu,$days = null){
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(['a'=>'article'])
		->joinLeft(['f'=>'file'],'a.id_article = f.id_article',['id_files'=>new Zend_Db_Expr('GROUP_CONCAT(distinct f.id_file ORDER BY f.order)'), 'names'=>new Zend_Db_Expr('GROUP_CONCAT(distinct CONCAT(f.name,".",f.extension) ORDER BY f.order)')])
		->joinLeft(['p'=>'photo'],'a.picture = p.id_photo',['extension'])
		->joinLeft(['p2'=>'photo'],'a.id_article = p2.id_article',['nbPhotos'=>new Zend_Db_Expr('COUNT(DISTINCT CONCAT(p2.id_photo,".",p2.extension))'), 'id_photos'=>new Zend_Db_Expr('GROUP_CONCAT(DISTINCT CONCAT(p2.id_photo,".",p2.extension) ORDER BY p2.order ASC,p2.id_photo ASC)')])
		->where('a.id_sous_menu = ?',$id_sous_menu)
		->where('a.annonce = ?',1)
		->order('a.order_rubrique ASC')
		->order('a.date_creation DESC')
		->group('a.id_article');
		
		$select->where('a.state_annonce = ?',self::STATE_ANNONCE_SUCCESS);
		
		if($days){
			$date = Aurel_Date::now()->subDay($days - 1)->setTime("00:00");
			$select->where('a.date_validation > ?',$date->get(Aurel_Date::MYSQL_DATETIME));
		}
		
		return $this->fetchAll($select);
	}

    /**
     * @param null $days
     * @return Zend_Db_Table_Rowset_Abstract
     */
	public function getAllAnnoncesLimit($days = null){
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(['a'=>'article'])
		->joinLeft(['u'=>'user'],'a.id_user_creation = u.id_user',['u.email'])
		->joinLeft(['s'=>'sous_menu'],'a.id_sous_menu = s.id_sous_menu',['sous_menu_name'=>'s.name'])
		->joinLeft(['f'=>'file'],'a.id_article = f.id_article',['id_files'=>new Zend_Db_Expr('GROUP_CONCAT(distinct f.id_file ORDER BY f.order)'), 'names'=>new Zend_Db_Expr('GROUP_CONCAT(distinct CONCAT(f.name,".",f.extension) ORDER BY f.order)')])
		->joinLeft(['p'=>'photo'],'a.picture = p.id_photo',['extension'])
		->joinLeft(['p2'=>'photo'],'a.id_article = p2.id_article',['nbPhotos'=>new Zend_Db_Expr('COUNT(DISTINCT CONCAT(p2.id_photo,".",p2.extension))'), 'id_photos'=>new Zend_Db_Expr('GROUP_CONCAT(DISTINCT CONCAT(p2.id_photo,".",p2.extension) ORDER BY p2.order ASC,p2.id_photo ASC)')])
		->where('a.annonce = ?',1)
		->order('a.order_rubrique ASC')
		->order('a.date_creation DESC')
		->group('a.id_article');
		
		$select->where('a.state_annonce = ?',self::STATE_ANNONCE_SUCCESS);
		
		if($days){
			$date = Aurel_Date::now()->subDay($days - 1)->setTime("00:00");
			$select->where('a.date_validation > ?',$date->get(Aurel_Date::MYSQL_DATETIME));
		}
		return $this->fetchAll($select);
	}

    /**
     * @param $id_user
     * @return Zend_Db_Table_Rowset_Abstract
     */
	public function getAllAnnoncesByUser($id_user){
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(['a'=>'article'])
		->joinLeft(['f'=>'file'],'a.id_article = f.id_article',['id_files'=>new Zend_Db_Expr('GROUP_CONCAT(distinct f.id_file ORDER BY f.order)'), 'names'=>new Zend_Db_Expr('GROUP_CONCAT(distinct CONCAT(f.name,".",f.extension) ORDER BY f.order)')])
		->joinLeft(['p'=>'photo'],'a.picture = p.id_photo',['extension'])
		->joinLeft(['p2'=>'photo'],'a.id_article = p2.id_article',['nbPhotos'=>new Zend_Db_Expr('COUNT(DISTINCT CONCAT(p2.id_photo,".",p2.extension))'), 'id_photos'=>new Zend_Db_Expr('GROUP_CONCAT(DISTINCT CONCAT(p2.id_photo,".",p2.extension) ORDER BY p2.order ASC,p2.id_photo ASC)')])
		->where('a.id_user_creation = ?',$id_user)
		->where('a.annonce = ?',1)
		->order('a.order_rubrique ASC')
		->order('a.date_creation DESC')
		->group('a.id_article');
		
		return $this->fetchAll($select);
	}

    /**
     * @return Zend_Db_Table_Rowset_Abstract
     */
	public function getAllAnnonces(){
		$select = $this->select()
		->setIntegrityCheck(false)
		->from(['a'=>'article'])
		->joinLeft(['u'=>'user'],'a.id_user_creation = u.id_user',['u.email'])
		->joinLeft(['s'=>'sous_menu'],'a.id_sous_menu = s.id_sous_menu',['sous_menu_name'=>'s.name'])
		->where('a.annonce = ?',1)
		->order('a.order_rubrique ASC')
		->order('a.date_creation DESC')
		->group('a.id_article');
	
		return $this->fetchAll($select);
	}

    /**
     * @param $order
     * @return int
     */
	public function updateSupOrder($order){
		return $this->update(["order" => new Zend_Db_Expr("IFNULL(`order`,0) + $order")], "`annonce` = 0 AND `hide_home` = 0");
	}

    /**
     * @param $order
     * @return int
     */
	public function updateSupOrderRubrique($order){
		return $this->update(["order_rubrique" => "IFNULL(`order_rubrique`,0) + $order"], "`annonce` = 0 AND `hide_home` = 0");
	}

    /**
     * @param null $limit
     * @param null $offset
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getAllNews($limit = null,$offset = null, $delaimonth = null){
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(['a'=>'article'])
            ->joinLeft(['m'=>'menu'],'a.id_menu = m.id_menu',['menu_name'=>'m.name'])
            //->where('a.status = ?',self::STATUS_CORBEILLE)
            ->where('m.news = ?',1)
            ->where('status = ?',self::STATUS_ACTIF)
            ->order('a.order_rubrique ASC')
            ->order('a.date_creation DESC');

        $select->limit($limit,$offset);

		if($delaimonth){
			$date = new Aurel_Date();
			$date->subMonth($delaimonth);
			$select->where("a.date_creation > ?",$date->get(Aurel_Date::MYSQL_DATETIME));
		}

        return $this->fetchAll($select);
    }
}