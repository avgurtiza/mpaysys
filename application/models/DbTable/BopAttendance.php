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
 * Table definition for bop_attendance
 *
 * @package Messerve_Model
 * @subpackage DbTable
 * @author Slide Gurtiza
 */
class Messerve_Model_DbTable_BopAttendance extends Messerve_Model_DbTable_TableAbstract
{
    /**
     * $_name - name of database table
     *
     * @var string
     */
    protected $_name = 'bop_attendance';

    /**
     * $_id - this is the primary key name
     *
     * @var array
     */
    protected $_id = array('bop_id', 'attendance_id');

    protected $_sequence = false;

    
    



}
