<?php 
require_once 'Zend/Application.php';

/**
 * Extension for Zend_Application
 * @author Aurélien Cornu
 */
class Aurel_Application extends Zend_Application {
    
	/**
     * Load configuration file of options
     *
     * @param  string $file
     * @throws Zend_Application_Exception When invalid configuration file is provided
     * @return array
     */
    protected function _loadConfig($file) {
    	
    	
    	$environment = $this->getEnvironment();
        $suffix      = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        $config = match ($suffix) {
									'ini' => new Zend_Config_Ini($file, null, ['allowModifications' => true]),
									default => throw new Zend_Application_Exception('Invalid configuration file provided; unknown config type'),
								};

        // and the configuration file (by environement)
        if (!empty($environment)) {
            $appConfig = new Zend_Config_Ini(CONFIG_PATH . DIRECTORY_SEPARATOR . $environment . '.ini', null, ['allowModifications' => true]);
            $config = $config->merge($appConfig);
        }
        
        if(isset($config->additional_config)){
        	$appConfig = new Zend_Config_Ini($config->additional_config->path, null, ['allowModifications' => true]);
            $config = $config->merge($appConfig);
        }

        return $config->toArray();
    }
}
?>
