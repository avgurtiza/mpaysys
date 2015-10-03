<?php

/**
 * Application Model DbTables
 *
 * @package Messerve_Model
 * @subpackage DbTable
 * @author Slide Gurtiza
 * @copyright Slide Gurtiza
 * @license All rights reserved
 */

/**
 * Table definition for deduction_attendance
 *
 * @package Messerve_Model
 * @subpackage DbTable
 * @author Slide Gurtiza
 */
class Messerve_Model_DbTable_DeductionAttendance extends Messerve_Model_DbTable_TableAbstract
{
    /**
     * $_name - name of database table
     *
     * @var string
     */
    protected $_name = 'deduction_attendance';

    /**
     * $_id - this is the primary key name
     *
     * @var array
     */
    protected $_id = array('deduction_schedule_id', 'attendance_id');

    protected $_sequence = false;

    
    



}
