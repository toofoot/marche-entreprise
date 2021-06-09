<?php
/**
* Class Aurel_Table_Page
* @author aurelien.cornu <aurelien.cornu@gmail.com>
* @copyright Copyright (c) 2008,MagicBegin
* @version 0.1
*/
class Aurel_Table_SondageReponseQuestion extends Aurel_Table_Abstract
{

	
	/**
	 * The table name.
	 *
	 * @var string
	 */
	protected $_name = 'sondage_reponse_question';
	
	/**
	 * Classname for row
	 *
	 * @var string
	 */
	protected $_rowClass = 'Aurel_Table_Row_SondageReponseQuestion';

    /**
     * @param $strToClean
     * @return mixed|string
     */
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

    /**
     * @param $id_sondage
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getSynthese($id_sondage){
        $select = $this->select()
            ->from(array('srq'=>'sondage_reponse_question'),array('sessid','date','id_sondage_question','id_user_reponse','reponse'=>new Zend_Db_Expr('GROUP_CONCAT(reponse)')))
            ->joinInner(array('sq'=>'sondage_question'),'sq.id_sondage_question = srq.id_sondage_question',array())
            ->joinInner(array('s'=>'sondage'),'s.id_sondage = sq.id_sondage',array())
            ->where('s.id_sondage = ?',$id_sondage)
            ->group("sessid")
            ->group("id_sondage_question")
                ->order('sq.order');;

        return $this->fetchAll($select);
    }

    /**
     * @param $id_sondage
     * @param $id_user
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getBySondageAndUser($id_sondage, $id_user){
        $select = $this->select()
            ->from(array('srq'=>'sondage_reponse_question'))
            ->joinInner(array('sq'=>'sondage_question'),'sq.id_sondage_question = srq.id_sondage_question',array())
            ->joinInner(array('s'=>'sondage'),'s.id_sondage = sq.id_sondage',array())
            ->where('s.id_sondage = ?',$id_sondage)
            ->where('srq.id_user_reponse = ?',$id_user)
                ->order('sq.order');

        $result = $this->fetchAll($select);

        return $result;
    }
}