<?php
/**
* Class Aurel_Table_Page
* @author aurelien.cornu <aurelien.cornu@gmail.com>
* @copyright Copyright (c) 2008,MagicBegin
* @version 0.1
*/
class Aurel_Table_SondageInArticle extends Aurel_Table_Abstract
{

	
	/**
	 * The table name.
	 *
	 * @var string
	 */
	protected $_name = 'sondage_in_article';
	
	/**
	 * Classname for row
	 *
	 * @var string
	 */
	protected $_rowClass = 'Aurel_Table_Row_SondageInArticle';

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