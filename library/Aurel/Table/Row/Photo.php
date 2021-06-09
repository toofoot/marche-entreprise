<?php
/**
* Class Aurel_Table_Row_Photo
* @author aurelien.cornu <aurelien.cornu@gmail.com>
* @version 0.1
*/
class Aurel_Table_Row_Photo extends Zend_Db_Table_Row_Abstract  {
	/**
	 * delete photo (BDD + files)
	 */
	public function deletePhoto(){
		$filename = UPLOAD_PATH.'/'.$this->id_article.'/'.$this->id_photo.'.'.$this->extension;
		$filenameThumb = UPLOAD_PATH.'/'.$this->id_article.'/thumb'.$this->id_photo.'.'.$this->extension;
		if(is_file($filename))
		unlink($filename);
		if(is_file($filenameThumb))
		unlink($filenameThumb);
		$this->delete();
	}
}