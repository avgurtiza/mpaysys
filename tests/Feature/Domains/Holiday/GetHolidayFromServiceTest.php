<?php


namespace Tests\Feature\Domains\Holiday;

use Domains\Attendance\Actions\GetHistoryChanges;
use Domains\Holiday\Actions\GetHolidayFromService;
use Tests\TestCase;

class GetHolidayFromServiceTest extends TestCase
{

    /** @test
     * @throws \Zend_Http_Client_Exception
     * @throws \Zend_Exception
     */
    public function test_holiday_is_fetched() {
        \Zend_Registry::getInstance()->set('config', new \Zend_Config_Ini('application/configs/application.test.ini', 'testing'));

        $config = \Zend_Registry::getInstance()->get('config');
        $base_url = $config->get('magistrate')->api->base_url;

        $result = (new GetHolidayFromService($base_url))('2023-06-28');

        $this->assertIsObject($result);
        $this->assertObjectHasAttribute('date', $result);
        $this->assertObjectHasAttribute('rest_day', $result);
    }
}