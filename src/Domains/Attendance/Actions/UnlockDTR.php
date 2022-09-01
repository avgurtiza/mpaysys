<?php


namespace Domains\Attendance\Actions;


use Zend_Registry;

class UnlockDTR
{
    /**
     * @throws \Zend_Exception
     */
    public function __invoke()
    {
        $cache = Zend_Registry::get('Cache');
        $cache->remove('dtr_locked');
    }
}