<?php

namespace Domains\Holiday\Actions;

use Domains\Holiday\Data\HolidayData;
use Zend_Config_Exception;
use Zend_Exception;
use Zend_Http_Client_Exception;
use Zend_Registry;

class GetHolidayFromService
{

    /**
     * @var mixed
     */
    private $base_url;

    /**
     * @throws Zend_Exception
     * @throws Zend_Config_Exception
     */
    public function __construct($base_url = null)
    {
        // TODO:  Hacky!  Fix this!
        if ($base_url === null) {
            $ini = new \Zend_Config_Ini('application/configs/application.ini', 'production');

            \Zend_Registry::getInstance()->set('config', $ini);

            $config = \Zend_Registry::getInstance()->get('config');

            $base_url = $config->get('magistrate')->api->base_url;
        }

        $this->base_url = $base_url;
    }

    /**
     * @throws Zend_Http_Client_Exception|Zend_Exception
     */
    public function __invoke($date)
    {
        $timestamp = strtotime($date);
        $date = date('Y-m-d', $timestamp);

        $cache = Zend_Registry::get('Cache');

        if (!$holiday_data = $cache->load('holidays_' . $timestamp)) {
            $client = new \Zend_Http_Client($this->base_url . '/api/v1.0/holidays?date=' . $date);

            $response = $client->request();

            if ($response->isSuccessful()) {
                $data = json_decode($response->getBody());

                $holiday_data = new HolidayData($date, $data->rest_day);

                $cache->save($holiday_data, 'holidays_' . $timestamp);

                return $holiday_data;
            } else {
                return false;
            }
        }


        return $holiday_data;
    }
}