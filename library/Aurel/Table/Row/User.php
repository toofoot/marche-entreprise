<?php

/**
 * Class Aurel_Table_Row_User
 * @author aurelien.cornu <aurelien.cornu@gmail.com>
 * @version 0.1
 */
class Aurel_Table_Row_User extends Zend_Db_Table_Row_Abstract implements Zend_Acl_Role_Interface, \Stringable {

    public function __toString(): string {
        return $this->getRoleId();
    }

    public function getFullName() {
        return $this->firstname . ' ' . $this->lastname;
    }

    public function getRoleId() {
        return $this->type;
    }

    public function getDate($which, $type = Aurel_Date::MYSQL_DATETIME) {
        if ($this->$which) {
            $date = new Aurel_Date($this->$which, $type);
            return $date;
        }
        return null;
    }

    /**
     * @todo Implement dotSpacedParse().
     */
    public function decompose() {
        $somme = $this->type;
        $result = [];
        $i = 0;
        while ($somme != 0) {
            if ($somme % 2 != 0) {
                $result[] = 2 ** $i;
            }
            $i++;
            $somme = $somme >> 1;
        }
        return($result);
    }

    public function hasRight($right) {
        $result = $this->decompose();

        return in_array($right, $result);
    }

    /**
     * @todo Implement dotSpacedParse().
     */
    public function rights() {
        $libelles = [
            Aurel_Acl::RESSOURCE_ADMIN_ARTICLES => "REDACTEUR EN CHEF",
            Aurel_Acl::RESSOURCE_ADMIN_REDACTEUR => "REDACTEUR",
            Aurel_Acl::RESSOURCE_ADMIN_MENUS => "ADMIN RUBRIQUAGE",
            Aurel_Acl::RESSOURCE_ADMIN_HEADER => "ADMIN HEADER",
            Aurel_Acl::RESSOURCE_ADMIN_FOOTER => "ADMIN FOOTER",
            Aurel_Acl::RESSOURCE_ADMIN_ACCESSRAPIDE => "ADMIN ACCES RAPIDE",
            //			Aurel_Acl::RESSOURCE_ADMIN_NEWSLETTER => "ADMIN NEWSLETTER",
            //			Aurel_Acl::RESSOURCE_ADMIN_ANNONCES => "ADMIN ANNONCES",
            Aurel_Acl::RESSOURCE_ADMIN_MEMBRES => "ADMIN UTILISATEURS",
        ];
        if (isset($this->type_binaire)) {
            $length = strlen((string) $this->type_binaire);
            $string = "";
            for ($i = 0; $i < $length; $i++) {
                $puissance = 2 ** $i;
                if ($this->type_binaire[$i] && isset($libelles[$puissance])) {
                    $string .= "<div class='badge' style='background-color:#357ebd;font-size:10px'>" . $libelles[$puissance] . "</div> ";
                }
            }
            return($string);
        } else {
            $result = $this->decompose();

            $string = "";
            foreach ($result as $right) {
                $string .= "<div class='badge' style='background-color:#357ebd;font-size:10px'>" . $libelles[$right] . "</div> ";
            }
            return($string);
        }
    }

}
