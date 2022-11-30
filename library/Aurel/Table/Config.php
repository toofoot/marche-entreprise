<?php

/**
 * Class Corporate_Table_Marque
 * @author aurelien.cornu <aurelien.cornu@gmail.com>
 * @copyright Copyright (c) 2015
 * @version 0.1
 */
class Aurel_Table_Config extends Aurel_Table_Abstract
{
    /**
     * Undocumented variable
     *
     * @var string
     */
    protected $_name = 'config';

    /**
     * Undocumented variable
     *
     * @var string
     */
    protected $_rowClass = 'Aurel_Table_Row_Config';

    /**
     * Undocumented function
     *
     * @return Config
     */
    public static function getConfig()
    {
        $oConfig = new self;
        $results = $oConfig->fetchAll();

        $config = new Config();
        foreach ($results as $result) {
            $key = $result->key;
            $value = $result->value;
            $config->$key = $value;
        }

        return $config;
    }

    /**
     * Undocumented function
     *
     * @param  string $key key for finding things
     * @return void
     */
    public function getByKey($key)
    {
        $select = $this->select()
            ->where("`key` = ?", $key);

        return $this->fetchRow($select);
    }

    /**
     * Undocumented function
     *
     * @param [type] $key
     * @param [type] $value
     * @return void
     */
    public function insertOrUpdate($key, $value)
    {
        $row = $this->getByKey($key);
        if (!$row) {
            $row = $this->createRow();
            $row->key = $key;
        }
        $row->value = $value;
        $row->save();

        return $row;
    }
}

/**
 * Undocumented class
 */
class Config
{
    /**
     * Undocumented variable
     *
     */
    private array $data = [];

    /**
     * Undocumented function
     *
     * @param string $name 
     * @param string $value 
     */
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * Undocumented function
     *
     * @param  string $name 
     * @return void
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        /* $trace = debug_backtrace();
          trigger_error(
          'Propriété non-définie via __get() : ' . $name .
          ' dans ' . $trace[0]['file'] .
          ' à la ligne ' . $trace[0]['line'],
          E_USER_NOTICE); */
        return null;
    }

    /**
     * Undocumented function
     *
     * @param  string $name 
     * @return boolean
     */
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    /**
     * Undocumented function
     *
     * @param  string $name Name of config
     * @return void
     */
    public function __unset($name)
    {
        unset($this->data[$name]);
    }
}
