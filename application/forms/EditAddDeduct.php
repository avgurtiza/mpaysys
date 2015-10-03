<?php

/**
 *
 * @package Messerve
 * @author Slide Gurtiza
 * @version $Id$
 *
 */
class Messerve_Form_EditAddDeduct extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        
        $this->setOptions(array('id'=>'add-deduct'));
        
		$this->addElement(
				$this->createElement('hidden','attendance_id')
					->removeDecorator('DtDd')
					->removeDecorator('Label')
		);		
		/*
		$this->addElement(
				$this->createElement('text', 'sss_loan')
				->setLabel('SSS Loan')
				//->setRequired(true)
				//->addValidator(new Zend_Validate_Float())
				->addFilter(new Zend_Filter_StringTrim())
		);
				
		$this->addElement(
				$this->createElement('text', 'hdmf_loan')
				->setLabel('HDMF Loan')
				//->setRequired(true)
				//->addValidator(new Zend_Validate_Float())
				->addFilter(new Zend_Filter_StringTrim())
		);
		
		
		
		$this->addElement(
				$this->createElement('text', 'fuel_overage')
				->setLabel('Fuel overage')
				//->setRequired(true)
				//->addValidator(new Zend_Validate_Float())
				->addFilter(new Zend_Filter_StringTrim())
		);
				
		$this->addElement(
				$this->createElement('text', 'maintenance_deduct')
				->setLabel('Maintenance')
				//->setRequired(true)
				//->addValidator(new Zend_Validate_Float())
				->addFilter(new Zend_Filter_StringTrim())
		);
				
		$this->addElement(
				$this->createElement('text', 'accident')
				->setLabel('Accident')
				//->setRequired(true)
				//->addValidator(new Zend_Validate_Float())
				->addFilter(new Zend_Filter_StringTrim())
		);
		
		$this->addElement(
				$this->createElement('text', 'uniform')
				->setLabel('Uniform')
				//->setRequired(true)
				//->addValidator(new Zend_Validate_Float())
				->addFilter(new Zend_Filter_StringTrim())
		);
		
		$this->addElement(
				$this->createElement('text', 'lost_card')
				->setLabel('Lost fuel card')
				//->setRequired(true)
				//->addValidator(new Zend_Validate_Float())
				->addFilter(new Zend_Filter_StringTrim())
		);
		
		$this->addElement(
				$this->createElement('text', 'food')
				->setLabel('Food charge')
				//->setRequired(true)
				//->addValidator(new Zend_Validate_Float())
				->addFilter(new Zend_Filter_StringTrim())
		);
		
		$this->addElement(
				$this->createElement('text', 'communication')
				->setLabel('Communication')
				//->setRequired(true)
				//->addValidator(new Zend_Validate_Float())
				->addFilter(new Zend_Filter_StringTrim())
		);
		
		$this->addDisplayGroup(array(
				'sss_loan'
				, 'hdmf_loan'
				, 'fuel_overage'
				, 'maintenance_deduct'
				, 'accident'
				, 'uniform'
				, 'lost_card'
				, 'food'
				, 'communication'
		) , 'deduct', array('legend'=>'Additional deductions'));
		*/
		
		
		$this->addElement(
				$this->createElement('text', 'thirteenth_month_pay')
				->setLabel('13th-month pay')
				//->setRequired(true)
				//->addValidator(new Zend_Validate_Float())
				->addFilter(new Zend_Filter_StringTrim())
		);
		/*
		$this->addElement(
				$this->createElement('text', 'gasoline')
				->setLabel('Gasoline')
				//->setRequired(true)
				//->addValidator(new Zend_Validate_Float())
				->addFilter(new Zend_Filter_StringTrim())
		);
		
		$this->addElement(
				$this->createElement('text', 'maintenance_income')
				->setLabel('Maintenance')
				//->setRequired(true)
				//->addValidator(new Zend_Validate_Float())
				->addFilter(new Zend_Filter_StringTrim())
		);
		

		$this->addElement(
				$this->createElement('text', 'bike_ownership')
				->setLabel('Bike ownership')
				//->setRequired(true)
				//->addValidator(new Zend_Validate_Float())
				->addFilter(new Zend_Filter_StringTrim())
		);
		*/
		$this->addElement(
				$this->createElement('text', 'incentives')
				->setLabel('Incentives')
				//->setRequired(true)
				//->addValidator(new Zend_Validate_Float())
				->addFilter(new Zend_Filter_StringTrim())
		);
		
		$this->addElement(
				$this->createElement('text', 'paternity')
				->setLabel('Paternity')
				//->setRequired(true)
				//->addValidator(new Zend_Validate_Float())
				->addFilter(new Zend_Filter_StringTrim())
		);
		
		$this->addElement(
				$this->createElement('text', 'misc_income')
				->setLabel('Misc. income')
				//->setRequired(true)
				//->addValidator(new Zend_Validate_Float())
				->addFilter(new Zend_Filter_StringTrim())
		);
		
		$this->addDisplayGroup(array(
        		'thirteenth_month_pay'
        		// , 'gasoline'
        		// , 'maintenance_income'
        		// , 'bike_ownership'
        		, 'incentives'
        		, 'paternity'
        		, 'misc_income'
        ) , 'add', array('legend'=>'Additional income'));

		$this->addElement(
				$this->createElement('button', 'submit')
				->setLabel('Save additional income')
				->setAttrib('type', 'submit')
		);
		
		
		parent::init();
    }
}