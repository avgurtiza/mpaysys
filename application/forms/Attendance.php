<?php

/**
 * Form definition for attendance search.
 *
 * @package Messerve
 * @author Slide Gurtiza
 * @version $Id$
 *
 */
use Carbon\Carbon;

class Messerve_Form_Attendance extends Zend_Form
{
    public function init()
    {
        $this->setMethod('get');

        $carbon_start = Carbon::today();

        $period_options = [];

        if(Carbon::today()->day >= 16) {
            $period_options[Carbon::today()->format('Y-m') . '-1_15'] = Carbon::today()->format('Y F 01-15');
        }

        for($i = 0; $i <= 12; $i++) {
        	$this_date = $carbon_start->subMonth(1);

        	if( Carbon::today() > $this_date->copy()->endOfMonth()) {
                $period_options[$this_date->format('Y-m') . '-16_31' ] = $this_date->format('Y F 16-' . $this_date->copy()->endOfMonth()->day);
            }

            if( Carbon::today() > $this_date->copy()->day(15)) {
                $period_options[$this_date->format('Y-m') . '-1_15'] = $this_date->format('Y F 01-15');
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