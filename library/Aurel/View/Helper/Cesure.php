<?php

require_once 'Zend/View/Helper/Abstract.php';
/**
 */
class Aurel_View_Helper_Cesure extends Zend_View_Helper_Abstract
{

    public function __construct()
    {
    	
    }

    /**
     * 
     * @return Zend_Acl
     */
    public function cesure($string,$nbCar = 17) {
    	$cesure = wordwrap($string, $nbCar, "#CESURE#");
    	$array = explode("#CESURE#", $cesure);
    	$first_ligne = $array[0];
    	$reste = str_replace($first_ligne, "", $string);
    	$return = $array[0] . "<br/>\n" . $reste;
        return $return;
    }
}