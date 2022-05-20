<?php

/**
 * Class Aurel_Table_Page
 * @author aurelien.cornu <aurelien.cornu@gmail.com>
 * @copyright Copyright (c) 2008,MagicBegin
 * @version 0.1
 */
class Aurel_Table_Invitation extends Aurel_Table_Abstract
{

    public const TYPE_INIT = 0;
    public const TYPE_READYTOSEND = 1;
    public const TYPE_READYTORESEND = 5;
    public const TYPE_SENT = 2;
    public const TYPE_RESENT = 3;
    public const TYPE_VALIDATED = 4;

    /**
     * The table name.
     *
     * @var string
     */
    protected $_name = 'invitation';

    /**
     * Classname for row
     *
     * @var string
     */
    protected $_rowClass = 'Aurel_Table_Row_Invitation';

    public function getAll($state = null)
    {
        $select = $this->select()
            ->order('date_creation ASC');
        if ($state && is_array($state)) {
            $select->where('state in (?)', $state);
        } elseif ($state) {
            $select->where('state = ?', $state);
        }
        return $this->fetchAll($select);
    }

    public function getByUser($id_user)
    {
        $select = $this->select()
            ->where('id_user_creation = ?', $id_user)
            ->order('date_creation ASC');
        return $this->fetchAll($select);
    }

    public function getReadyToSend()
    {
        $select = $this->select()
            ->where('state = ?', self::TYPE_READYTOSEND);
        return $this->fetchAll($select);
    }

    public function getReadyToSendByUser($id_user)
    {
        $select = $this->select()
            ->where('id_user_creation = ?', $id_user)
            ->where('state = ?', self::TYPE_READYTOSEND);
        return $this->fetchAll($select);
    }

    public function getToRelance()
    {
        $select = $this->select()
            ->where('state in (?)', [self::TYPE_SENT, self::TYPE_RESENT]);
        return $this->fetchAll($select);
    }

    public function getByMail($email)
    {
        $select = $this->select()
            ->where('email = ?', $email);
        return $this->fetchAll($select);
    }

    public function getOneReadyToSend()
    {
        $select = $this->select()
            ->where('state = ?', self::TYPE_READYTOSEND)
            ->limit(1);

        return $this->fetchRow($select);
    }

    public function getReadyToReSend()
    {
        $select = $this->select()
            ->where('state = ?', self::TYPE_READYTORESEND);
        return $this->fetchAll($select);
    }

    public function getOneReadyToReSend()
    {
        $select = $this->select()
            ->where('state = ?', self::TYPE_READYTORESEND)
            ->limit(1);

        return $this->fetchRow($select);
    }

    public function findcomHash($comHash)
    {
        $select = $this->select()
            ->where('md5(id_invitation) = ?', $comHash);

        return $this->fetchRow($select);
    }
}
