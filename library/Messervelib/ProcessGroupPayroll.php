<?php

/**
 * Created by PhpStorm.
 * User: slide
 * Date: 14/03/2019
 * Time: 9:39 AM
 */
class Messervelib_ProcessGroupPayroll
{
    protected $_data = [], $group_id, $pay_period;

    public function setData($data)
    {
        $this->_data = $data;
    }

    public function getData()
    {
        return $this->_data;
    }

    public function setGroupId($group_id)
    {
        $this->group_id = $group_id;
    }

    public function getGroupId()
    {
        return $this->group_id;
    }

    public function setPayPeriod($pay_period)
    {
        $this->_data = $pay_period;
    }

    public function getPayPeriod()
    {
        return $this->pay_period;
    }

    public function handle()
    {
        logger('Running job Process Group payroll on ' . \Carbon\Carbon::now()->toDateTimeString());
        sleep(10);

        $controller = new Payroll_IndexController($this->_request, $this->_response);

        logger('Done at ' . \Carbon\Carbon::now()->toDateTimeString());
    }


}