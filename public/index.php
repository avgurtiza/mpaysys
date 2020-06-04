<?php
error_reporting(E_ERROR || E_WARNING);

ini_set('display_errors', 'On');

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
        if (!is_array($allowed_types)) ('Invalid user types list.');

        if (!in_array($usertype, $allowed_types)) {
            return false;
        }

        return true;
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
|| define('APPLICATION_PATH', __DIR__ . '/../application');

// Define application environment
defined('APPLICATION_ENV')
|| define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    dirname(APPLICATION_PATH) . '/../library',
    get_include_path(),
)));


if (!function_exists('logger')) {
    function logger($message, $priority = 'info')
    {
        $logger = new Zend_Log();
        $logpath = APPLICATION_PATH . '/../logs';

        if (!file_exists($logpath)) {
            !is_dir($logpath) && !mkdir($logpath) && !is_dir($logpath);
        }

        $logfile = $logpath . '/zf.log';

        if (!file_exists($logfile)) {
            touch($logfile);
        }

        switch ($priority) {
            case 'notice':
                $priority = Zend_Log::NOTICE;
                break;
            case 'warn':
                $priority = Zend_Log::WARN;
                break;
            case 'info':
            default:
                $priority = Zend_Log::INFO;
                break;
        }

        $writer = new Zend_Log_Writer_Stream($logfile);


        $logger->addWriter($writer);

        $logger->log($message, $priority);
    }
}


require_once dirname(APPLICATION_PATH) . '/vendor/autoload.php';

/** Zend_Application */

$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);

$application->bootstrap()
    ->run();