<?php
/**
* Class Aurel_Table_Abstract
* @author aurelien.cornu <aurelien.cornu@gmail.com>
* @copyright Copyright (c) 2013,Odalisques
* @version 0.1
*/
abstract class Aurel_Table_Abstract extends Zend_Db_Table_Abstract 
{
	
	/**
     * @return Zend_Db_Table_Row
     */
	public function getById($id)
    {
        $id = (int)$id;
        return $this->get($id);
    }
    
    /**
     * @return Zend_Db_Table_Rowset
     */
    public function getAll()
    {
    	return $this->fetchAll();
    }
    
    /**
     * @return Zend_Db_Table_Row
     */
	protected function _getByIdCached($id)
    {
        return $this->get($id);
    	$cache = $this->_getCache();
        $cacheId = $this->_name . '_row_' . $id;
        if(!$ret = $cache->load($cacheId)) {
            $ret = $this->get($id);
            $cache->save($ret, $cacheId,array(),3600);
        } else {
            // pour reconecter a la base de donn�e
            $ret->setTable($this);
        }
        return $ret;
    }
    
    /**
     * @return Zend_Db_Table_Row
     */
	public function get($id)
	{
        return $this->find($id)->current();
	}
	
	/**
	 * get Cache from registry
	 *
	 * @return Zend_Cache
	 */
	protected function _getCache()
	{
		return $this->_getBootstrap()->getContainer()->getService('db');
	}
}