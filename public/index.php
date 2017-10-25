<?php
if (!function_exists('preprint')) {
    function preprint($mixed, $exit_after = false)
    {
        echo '<pre style="clear: both;">' . print_r($mixed, true) . '</pre>';
        if ($exit_after) die();
    }
}

if (!function_exists('checkaccess')) {  // TODO:  Primitive acl,  replace with Zend ACL
    function checkaccess($usertype, $allowed_types)
    {
        if (!is_array($allowed_types)) die('Invalid user types list.');

        if (!in_array($usertype, $allowed_types)) {
            return false;
        } else {
            return true;
        }
    }
}

if (!function_exists('zend_enum_from_info')) {
    function zend_enum_from_info($info, $field)
    {
        $enum = $info['metadata'][$field]['DATA_TYPE'];

        $start_enum = strpos($enum, "'");
        $end_enum = strrpos($enum, "'");

        $end_enum -= $start_enum;

        $enum = substr($enum, $start_enum, $end_enum + 1);
        $enum = str_replace("'", '', $enum);

        $enum = explode(",", $enum);

        $out = array();

        foreach ($enum as $value) {
            $out[$value] = ucfirst(strtolower($value));
        }

        return $out;
    }
}

if (!function_exists('decimal_to_time')) {
    function decimal_to_time($decimal_time)
    {
        $hour = floor($decimal_time);
        $min = round(60 * ($decimal_time - $hour));
        $min = str_pad($min, 2, '0', STR_PAD_LEFT);
        return "{$hour}:{$min}";
    }
}

// Define path to application directory
defined('APPLICATION_PATH')
|| define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

// Define application environment
defined('APPLICATION_ENV')
|| define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/../library'),
    get_include_path(),
)));

/** Zend_Application */
require_once '../vendor/autoload.php';
// require_once 'Zend/Application.php';

// Create application, bootstrap, and run
$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);


// TODO:  get db creds from config

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Configure the database and boot Eloquent
 */

$capsule = new Capsule;

$config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);

$db_params = $config->resources->db->params;

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

$application->bootstrap()
    ->run();