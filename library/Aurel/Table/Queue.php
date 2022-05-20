<?php

/**
 * Classe user
 * @author aurelien.cornu <aurelien.cornu@gmail.com>
 * @copyright Copyright (c) 2008,MagicBegin
 * @version 0.1
 */
class Aurel_Table_Queue extends Aurel_Table_Abstract
{

    protected $_name = 'queue';
    protected $_rowClass = 'Aurel_Table_Row_Queue';

    public const STATUS_INIT = 0;
    public const STATUS_READYTOSEND = 1;
    public const STATUS_SENT = 2;

    public function getAll()
    {
        $select = $this->select()
            ->order('date_sent DESC');

        return $this->fetchAll($select);
    }

    public function getReadyToSend()
    {
        $select = $this->select()
            ->where('status = ?', self::STATUS_READYTOSEND);
        return $this->fetchAll($select);
    }

    public function getOneReadyToSend()
    {
        $select = $this->select()
            ->where('status = ?', self::STATUS_READYTOSEND)
            ->limit(1);

        return $this->fetchRow($select);
    }
}
