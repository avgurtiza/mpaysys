<?php

class Default_IndexController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
    		
        // action body
        $this->_redirect('/manager/client');
    }

    public function cliAction() {
    }

}

