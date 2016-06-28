<?php

/**
 * Form definition for attendance search.
 *
 * @package Messerve
 * @author Slide Gurtiza
 * @version $Id$
 *
 */
class Messerve_Form_Attendance extends Zend_Form
{
    public function init()
    {
        $this->setMethod('get');

        $start_date = strtotime(date('Y-m-d'));
        
        $period_options = array();
        
        for($i = 0; $i <= 12; $i++) {
        	$this_date = strtotime("-$i months", $start_date);
        	
        	if(date('Ymd') > date('Ym30', $this_date)) {
        		$period_options[date('Y-m', $this_date) . '-16_31'] = date('Y F 16-31', $this_date);
        	}
        	
        	if(date('Ymd') > date('Ym15', $this_date)) {
        		$period_options[date('Y-m', $this_date) . '-1_15'] = date('Y F 01-15', $this_date);
        	}
        }
        
        
        
		$this->addElement(
				$this->createElement('select','pay_period')
					->setLabel('Pay period')
	                ->setRequired(true)
	                ->addValidator(new Zend_Validate_Int())
	                ->addFilter(new Zend_Filter_StringTrim())
					->setMultiOptions($period_options)
					->setOptions(array('class'=>'big-select'))
				);

		$this->addElement(
				$this->createElement('select','group_id')
				->setLabel('Client group')
				->setRequired(true)
				->addValidator(new Zend_Validate_Int())
				->addFilter(new Zend_Filter_StringTrim())
				->setOptions(array('class'=>'big-select'))
		);		
		
		$this->addElement(
				$this->createElement('button', 'submit')
				->setLabel('Go')
				->setAttrib('type', 'submit')
		);
		
        parent::init();
    }
}