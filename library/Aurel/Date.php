<?php

/**
 * Extension for Zend_Date
 * @author Aurélien Cornu
 */
class Aurel_Date extends Zend_Date{
	
	/**
	 * Format for MYSQL_DATE
	 * @var string
	 */
	const 	MYSQL_DATE = 'yyyy-MM-dd';
	
	/**
	 * Format for MYSQL_DATETIME
	 * @var string
	 */
	const 	MYSQL_DATETIME = 'yyyy-MM-dd HH:mm:ss';
}