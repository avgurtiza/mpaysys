<?php

/**
 * Created by PhpStorm.
 * User: slide
 * Date: 14/03/2019
 * Time: 2:30 PM
 */
class Messervelib_Router_Cli extends Zend_Controller_Router_Abstract
{
    public function route(Zend_Controller_Request_Abstract $dispatcher)
    {
        $getopt = new Zend_Console_Getopt ([]);

        $arguments = $getopt->getRemainingArgs();

        if ($arguments) {
            $command = array_shift($arguments);

            if (!preg_match('~\W~', $command)) {
                $dispatcher->setControllerName($command);
                $dispatcher->setActionName('cli');
                unset ($_SERVER ['argv'] [1]);

                $command = array_shift($arguments);
                if ($command) {
                    $dispatcher->setModuleName($command);
                } else {
                    $dispatcher->setModuleName('default');
                }

                unset ($_SERVER ['argv'] [0]);
                unset ($_SERVER ['argv'] [2]);

                $dispatcher->setParams([
                    'params' => array_values($_SERVER ['argv'])
                ]);

                return $dispatcher;
            }

            echo "Invalid command.\n", exit;

        }

        echo "No command given.\n", exit;
    }


    public function assemble($userParams, $name = null, $reset = false, $encode = true)
    {
        echo "Not implemented\n", exit;
    }
}