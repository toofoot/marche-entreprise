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
	public const 	MYSQL_DATE = 'yyyy-MM-dd';
	
	/**
	 * Format for MYSQL_DATETIME
	 * @var string
	 */
	public const 	MYSQL_DATETIME = 'yyyy-MM-dd HH:mm:ss';
}