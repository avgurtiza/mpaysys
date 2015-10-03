<?php

/**
 *
 * @package Messerve
 * @author Slide Gurtiza
 * @version $Id$
 *
 */
class Messerve_Form_EditDeduction extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        
        $this->setOptions(array('id'=>'edit-deduction'));

        $this->addElement(
        		$this->createElement('text', 'amount')
        		->setLabel('Amount')
        		->setRequired(true)
        		->addValidator(new Zend_Validate_Float())
        		->addFilter(new Zend_Filter_StringTrim())
        );
        
        $this->addElement(
        		$this->createElement('select', 'type')
        		->setLabel('Type')
        		->setMultiOptions(array(
        					'sss_loan'=>'SSS loan'
        					,'hdmf_loan' => 'HDMF loan'
        					,'fuel_overage' => 'Fuel overage'
        					,'maintenance_deduct' => 'Maintenance'
        					,'accident' => 'Accident'
        					,'uniform' => 'Uniform'
        					,'lost_card' => 'Lost fleet card'
        					,'food' => 'Food'
        					,'communication' => 'Communication'
        					,'adjustment' => 'Adjustment'
        					,'misc' => 'Miscellaneous'
        				
        				))
        );
        
        $this->addElement(
        		$this->createElement('text', 'deduction')
        		->setLabel('Deduction per cutoff')
        		->setRequired(true)
        		->addValidator(new Zend_Validate_Float())
        		->addFilter(new Zend_Filter_StringTrim())
        );
                
        $this->addElement(
        		$this->createElement('select', 'cutoff')
        		->setLabel('Cut-off')
        		->setMultiOptions(array(
        				'1'=>'1-15'
        				,'2'=>'16-31'
        				, '3'=>'Both'
        		))
        );
        
        /*
		$this->addElement(
				$this->createElement('text', 'balance')
				->setLabel('Balance')
				// ->setRequired(true)
				// ->addValidator(new Zend_Validate_Float())
				->addFilter(new Zend_Filter_StringTrim())
		);
		*/
        
		$this->addDisplayGroup(array(
				'amount'
				, 'type'
				, 'deduction'
				, 'cutoff'
				// , 'balance'
		) , 'deductions', array('legend'=>'Add new deduction schedule
				'));
		
		$this->addElement(
				$this->createElement('button', 'submit')
				->setLabel('Save')
				->setAttrib('type', 'submit')
		);
		
		
		parent::init();
    }
}