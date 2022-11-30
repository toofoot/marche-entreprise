<?php

/**
 * Classe user
 * @author aurelien.cornu <aurelien.cornu@gmail.com>
 * @copyright Copyright (c) 2008,MagicBegin
 * @version 0.1
 */
class Aurel_Table_User extends Aurel_Table_Abstract
{

    protected $_name = 'user';
    protected $_rowClass = 'Aurel_Table_Row_User';

    final public const STATUS_ACTIF = 1;
    final public const STATUS_INACTIF = 2;

    /**
     * Genere un mot de passe de 8 caractères aléatoirement
     * @return string
     */
    public function generePassword()
    {
        // On définit le password à blanc
        $password = "";
        // on définit les caractères possibles
        $possible = "0123456789bcdfghjkmnpqrstvwxyzBCDFGHJKMNPQRSTVWXYZ";
        // compteur
        $i = 0;
        // on choisis au hasard un des caractères et on l'ajoute à password 8 fois
        while ($i < 8) {
            // On choisis un chiffre au hasard et on regarde le caractere correspondant dans $possible
            $nbr = random_int(0, 49);
            $char = $possible[$nbr];
            // si ce caractère est déjà dans le password, on ne l'insere pas
            if (!strstr($password, $char)) {
                $password .= $char;
                $i++;
            }
        }
        // On a fini, on enregistre et on retourne le password
        return $password;
    }

    public function findcomHash($comHash)
    {
        $select = $this->select()
            ->where('hash = ?', $comHash);
        $result = $this->fetchRow($select);

        return $result;
    }

    /**
     * 
     * @return type
     */
    public function getAllForExtract()
    {
        $select = $this->select()
            ->from($this->_name, ['email', 'firstname', 'lastname', 'societe', 'fonction', 'date_last_connexion']);
        return $this->fetchAll($select);
    }

    /**
     * 
     * @param type $status
     * @param type $search
     * @param type $orderby
     * @param type $order
     * @param type $typeBinaire
     * @return type
     */
    public function getAll($status = null, $search = null, $orderby = 'status', $order = 'ASC', $typeBinaire = null)
    {
        $select = $this->select();
        if ($typeBinaire !== null) {
            $select->from(["u" => "user"], ['*', 'type_binaire' => new Zend_Db_Expr("REVERSE(RIGHT(concat('0000000000',BIN(`type`)),11))")]);
            $select->setIntegrityCheck(false);
            $select->joinLeft(['q' => 'queue'], 'q.id_user = u.id_user', ['date_send' => new Zend_Db_Expr('MAX(q.date_creation)')]);
            $select->group('u.id_user');
        } else {
            $select->from(["u" => "user"]);
        }
        if ($status !== null)
            $select->where("u.status = ?", $status);

        if ($search) {
            $select->where("u.name like ? OR u.lastname like ? OR u.firstname like ? OR u.email like ?", '%' . $search . '%');
        }
        $select->order("$orderby $order");
        return $this->fetchAll($select);
    }

    public function getAllForNewsletter($admin = false)
    {
        $select = $this->select();
        $select->where("newsletter = ?", 1);
        $select->where("status = ?", Aurel_Table_User::STATUS_ACTIF);

        if ($admin) {
            $right = Aurel_Acl::RESSOURCE_ADMIN_NEWSLETTER;
            $rightPosition = log($right, 2) + 1;

            $select->where(new Zend_Db_Expr("SUBSTRING(REVERSE(RIGHT(concat('0000000000',BIN(`type`)),11)),$rightPosition,1) = 1"));
        }
        return $this->fetchAll($select);
    }

    public function getByEmail($email)
    {
        $select = $this->select()->where('email = ?', $email);
        return $this->fetchRow($select);
    }

    public function getUsersWithRight($right)
    {
        $rightPosition = log($right, 2) + 1;
        $select = $this->select();
        $select->where(new Zend_Db_Expr("SUBSTRING(REVERSE(RIGHT(concat('0000000000',BIN(`type`)),11)),$rightPosition,1) = 1"));

        return $this->fetchAll($select);
    }

    public function getReportingRedacteurs($start_date, $end_date)
    {
        $select = $this->select()
            ->setIntegrityCheck(false)
            ->distinct()
            ->from(["u" => "user"], ["email"])
            ->joinLeft(["a" => "article"], "a.id_user_creation = u.id_user", ["count" => new Zend_Db_Expr("COUNT(a.id_article)")])
            ->joinLeft(["sm" => "sous_menu"], "sm.id_sous_menu = a.id_sous_menu", ["sous_menu_name" => "name"])
            ->joinLeft(["m" => "menu"], "m.id_menu = sm.id_menu", ["menu_name" => "name"])
            ->where(new Zend_Db_Expr("SUBSTRING(REVERSE(RIGHT(concat('0000000000',BIN(u.`type`)),11)),1,1) = 1"))
            ->where("a.annonce = 0 OR a.annonce IS NULL")
            ->where("a.date_creation IS NULL OR DATE(a.date_creation) >= ?", $start_date->get(Aurel_Date::MYSQL_DATE))
            ->where("a.date_creation IS NULL OR DATE(a.date_creation) <= ?", $end_date->get(Aurel_Date::MYSQL_DATE))
            ->group("u.id_user")
            ->group("sm.id_menu")
            ->group("a.id_sous_menu");

        return $this->fetchAll($select);
    }

    public function getByEncodedEmail($encodedEmail)
    {
        $select = $this->select()->where('md5(email) = ?', $encodedEmail);
        return $this->fetchRow($select);
    }

    /**
     * @todo Implement dotSpacedParse().
     */
    public static function decompose($somme)
    {
        $result = [];
        $i = 0;
        while ($somme != 0) {
            if ($somme % 2 != 0) {
                $result[] = 2 ** $i;
            }
            $i++;
            $somme = $somme >> 1;
        }
        return ($result);
    }
}
