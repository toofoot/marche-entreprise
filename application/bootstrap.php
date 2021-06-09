<?php

/**
 * Le Petit Charsien
 * 
 * @author  Aurélien Cornu
 * @version 1.0
 */
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {

    
    /**
     * INIT CACHE
     */
    public function _initCache() {
        $this->bootstrap('cachemanager');
        $cache = $this->getResource('cachemanager')->getCache('database');
        Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);
        Zend_Locale_Data::setCache($cache);

        return $cache;
    }

    /**
     * INIT ROUTES
     */
    protected function _initRoutes() {
        // Ensure front controller instance is present, and fetch it
        $this->bootstrap('FrontController');
        $front = $this->getResource('FrontController');

        $router = $front->getRouter();
        $configRouter = new Zend_Config_Ini(CONFIG_PATH . '/routes.ini', 'routes');
        $router->addConfig($configRouter, 'routes');

        // Add it to the front controller
        $front->setRouter($router);

        // Bootstrap will store this value in the 'request' key of its container
        return $router;
    }

    /**
     * INIT BDD
     */
    protected function _initDatabase() {
        $this->bootstrapDb();
        $db = $this->getResource('db');
        Zend_Registry::set("db", $db);
        return $db;
    }

    /**
     * INIT TRANSLATE
     */
    protected function _initTranslate() {
        $translate = new Zend_Translate("ini", TRANSLATE_PATH . "/fr.ini", "fr");
        $translate->addTranslation(TRANSLATE_PATH . "/en.ini", "en");
        Zend_Registry::set("Zend_Translate", $translate);
        return $translate;
    }

}

?>