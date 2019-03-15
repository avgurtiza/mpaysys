<?php
/*
 * Payroll processing daemon
 *
 */

if (!function_exists('preprint')) {
    function preprint($mixed, $exit_after = false)
    {
        echo '<pre style="clear: both;">' . print_r($mixed, true) . '</pre>';
        if ($exit_after) die();
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
    function logger($message)
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

        $writer = new Zend_Log_Writer_Stream($logfile);

        $logger->addWriter($writer);

        $logger->log($message, Zend_Log::INFO);
    }
}


/** Zend_Application */
require_once dirname(APPLICATION_PATH) . '/vendor/autoload.php';

// require_once 'Zend/Application.php';

// Create application, bootstrap, and run
$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);

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

// $application->bootstrap()->run();

$application->getBootstrap()->bootstrap(['db', 'queue']);
$sleep_time = 10;

while (1) {
    $queue = Messerve_Model_Eloquent_Queue::where('queue_name', 'process-group-payroll')->first();

    if (!$queue) {
        throw new Exception('Queue process-group-payroll not found!');
    }

    $message = $queue->messages->first();

    if (!$message) {
        // print(PHP_EOL . "No job found, sleeping for $sleep_time seconds." . PHP_EOL);
        sleep($sleep_time);
    } else {
        $body = base64_decode($message->body);
        $serial = gzuncompress($body);
        $object = unserialize($serial);
        print(PHP_EOL . "Running job..." . PHP_EOL);

        $data = $object->getData();
        print_r($data);

        $index = realpath(dirname(APPLICATION_PATH) . '/public/index.php');

        $command = sprintf('php -f %s index payroll %d %s %s', $index, $data['group_id'], $data['date_start'], $data['date_end']);

        $result = exec($command);

        if (stripos($result, 'ok') !== false) {
            // if ($result === 'OK') {
            $pending_payroll = $message->pendingPayroll;

            if ($pending_payroll) {
                echo "Found payroll!" . PHP_EOL;
                $pending_payroll->is_done = true;
                $pending_payroll->save();
            }

        } else {
            echo 'RESULT NOT OK ' . $result;
            logger("Queued payroll processing failed with result string of $result.  Command was $command.  Queue message follows:");
            logger(json_encode($object));
        }

        $message->delete();

        sleep(2);

    }


}
