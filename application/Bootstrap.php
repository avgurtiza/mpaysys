<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    protected function _initTimeZone() {
        date_default_timezone_set('Asia/Manila');
    }

	protected  function _init_settings() {
		$config = new Zend_Config($this->getOptions(), true);
		Zend_Registry::set('config', $config);
		return $config;
	}
}

