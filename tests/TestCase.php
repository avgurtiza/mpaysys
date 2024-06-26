<?php

namespace Tests;

use PDO;
use PHPUnit\DbUnit\Database\DefaultConnection;
use PHPUnit\DbUnit\DataSet\ArrayDataSet;
use Zend_Application;

use PHPUnit\Framework\TestCase as PHPUnit_Framework_TestCase;
use PHPUnit\DbUnit\TestCaseTrait as DatabaseTestCase;

abstract class TestCase extends PHPUnit_Framework_TestCase
{
    use DatabaseTestCase;

    // Only instantiate pdo once for test clean-up/fixture load
    static private $pdo = null;

    // Only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
    private $connection = null;

    public function setUp() : void
    {
        error_reporting(E_ERROR || E_WARNING);

        // Define path to application directory
        defined('APPLICATION_PATH')
        || define('APPLICATION_PATH', getcwd() . '/application');

        // Define application environment
        defined('APPLICATION_ENV')
        || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'testing'));


        require_once(APPLICATION_PATH . DIRECTORY_SEPARATOR . "helpers.php");

        $this->bootstrap = new Zend_Application(
            'testing',
            APPLICATION_PATH . '/configs/application.test.ini'
        );;

        parent::setUp();
    }

    final protected function getConnection(): DefaultConnection
    {
        if ($this->connection === null) {
            if (self::$pdo == null) {
                self::$pdo = new PDO('sqlite::memory:');
            }
            $this->connection = $this->createDefaultDBConnection(self::$pdo, ':memory:');
        }

        return $this->connection;
    }

    protected function getDataSet(): ArrayDataSet
    {
        return $this->createArrayDataSet([]);
    }

}