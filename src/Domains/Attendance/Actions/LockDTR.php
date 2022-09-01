<?php


namespace Domains\Attendance\Actions;


use Zend_Registry;

class LockDTR
{
    /**
     * @throws \Zend_Exception
     */
    public function __invoke()
    {
        $cache = Zend_Registry::get('Cache');
        $cache->save(true, 'dtr_locked');
    }
}