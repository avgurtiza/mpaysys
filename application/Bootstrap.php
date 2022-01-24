<?php


class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    protected function _initRouter()
    {
        if (PHP_SAPI === 'cli') {
            $this->bootstrap('frontcontroller');
            $front = $this->getResource('frontcontroller');
            $front->setRouter(new Messervelib_Router_Cli());
            $front->setRequest(new Zend_Controller_Request_Simple ());
            $front->setResponse(new Zend_Controller_Response_Cli());
        }
    }

    protected function _initError()
    {

        if (PHP_SAPI === 'cli') {
            $frontcontroller = $this->getResource('frontcontroller');
            $error = new Zend_Controller_Plugin_ErrorHandler ();
            $error->setErrorHandlerController('error');
            $error->setErrorHandlerAction('cli');
            $frontcontroller->registerPlugin($error, 100);

        }
        // return $error;
    }


    protected function _initRoutes()
    {
        $router = Zend_Controller_Front::getInstance()->getRouter();
        if ($router instanceof Zend_Controller_Router_Rewrite) {
            // put your web-interface routes here, so they do not interfere
        }
    }

    protected function _initTimeZone()
    {
        date_default_timezone_set('Asia/Manila');
    }

    protected function _init_settings()
    {
        $config = new Zend_Config($this->getOptions(), true);
        Zend_Registry::set('config', $config);
        return $config;
    }

    protected function _initQueue()
    {
        $options = $this->getOptions();

        $queueAdapter = new Zend_Queue_Adapter_Db($options['queue']);
        Zend_Registry::getInstance()->queueAdapter = $queueAdapter;

    }

    protected function _initEloquent()
    {
        $config = Zend_Registry::get('config');
        $db_params = $config->resources->db->params;
        $capsule = new Illuminate\Database\Capsule\Manager();

        $capsule->addConnection([
            'driver' => 'mysql',
            'host' => $db_params->host,
            'database' => $db_params->dbname,
            'username' => $db_params->username,
            'password' => $db_params->password,
            'charset' => 'utf8',
            'collation' => 'utf8_general_ci',
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

    protected function _initHelpers() {
        require_once(APPLICATION_PATH . DIRECTORY_SEPARATOR . "helpers.php");
    }
}

