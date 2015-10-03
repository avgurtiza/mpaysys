<?php

/**
 * Form definition for table rate.
 *
 * @package Messerve
 * @author Zodeken
 * @version $Id$
 *
 */
class Messerve_Form_EditRate extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setOptions(array('class'=>'bigform'));
        
        $this->addElement(
            $this->createElement('hidden', 'id')
                
        );
        
        $this->addElement(
        		$this->createElement('text', 'name')
        		->setLabel('Name')
        		->setAttrib("maxlength", 64)
        		->setRequired(true)
        		->addValidator(new Zend_Validate_StringLength(array("max" => 128)))
        		->addFilter(new Zend_Filter_StringTrim())
        );        

        $this->addElement(
            $this->createElement('text', 'reg')
                ->setLabel('Regular hours')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'reg_nd')
                ->setLabel('Regular hours - night')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'reg_ot')
                ->setLabel('Regular OT')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'reg_nd_ot')
                ->setLabel('Regular OT - night')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'sun')
                ->setLabel('Sunday')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'sun_nd')
                ->setLabel('Sunday - night')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'sun_ot')
                ->setLabel('Sunday OT')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'sun_nd_ot')
                ->setLabel('Sunday OT - night')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'spec')
                ->setLabel('Special holiday')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'spec_nd')
                ->setLabel('Special holiday - night')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'spec_ot')
                ->setLabel('Special holiday OT')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'spec_nd_ot')
                ->setLabel('Special holiday OT - night')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'legal')
                ->setLabel('Legal holiday')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'legal_nd')
                ->setLabel('Legal holiday - night')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'legal_ot')
                ->setLabel('Legal holiday OT')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'legal_nd_ot')
                ->setLabel('Legal holiday OT - night')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'legal_unattend')
                ->setLabel('Unworked legal holiday')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );
        
		/*
        $this->addElement(
            $this->createElement('text', 'rest_ot')
                ->setLabel('Restday OT')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'rest_nd_ot')
                ->setLabel('Restday OT - night')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );
        */
        
        $this->addDisplayGroup(array(
        			'reg', 'reg_nd', 'reg_ot', 'reg_nd_ot'
        			, 'sun', 'sun_nd', 'sun_ot', 'sun_nd_ot'
        			, 'spec', 'spec_nd', 'spec_ot', 'spec_nd_ot'
        			, 'legal', 'legal_nd', 'legal_ot', 'legal_nd_ot', 'legal_unattend'
        			// , 'rest_ot', 'rest_nd_ot'
        		) , 'hourly', array('legend'=>'Per hour values'));
        
        $this->addElement(
            $this->createElement('text', 'thirteenth_month_pay')
                ->setLabel('13th-month Pay')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'incentive_5_day_leave')
                ->setLabel('Incentive 5-Day Leave')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'ecola')
                ->setLabel('E-cola')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'sss_employee')
                ->setLabel('SSS - Employee share')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'sss_employer')
                ->setLabel('SSS - Employer share')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );
        
        $this->addElement(
            $this->createElement('text', 'philhealth_employee')
                ->setLabel('Philhealth - Employee share')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'philhealth_employer')
                ->setLabel('Philhealth - Employer share')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );
        
        $this->addElement(
            $this->createElement('text', 'ec')
                ->setLabel('Ec')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'hdmf_employee')
                ->setLabel('Hdmf - Employee share')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'hdmf_employer')
                ->setLabel('Hdmf - Employer share')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'admin_fee')
                ->setLabel('Admin Fee')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );
        
        /*
        $this->addElement(
        		$this->createElement('text', 'fuel_allocation')
        		->setLabel('Fuel allocation (L)')
        		->setRequired(true)
        		->addValidator(new Zend_Validate_Float())
        		->addFilter(new Zend_Filter_StringTrim())
        );
        */
        
        /*
        $this->addElement(
        		$this->createElement('text', 'bike_rehab')
        		->setLabel('Bike rehab')
        		->setRequired(true)
        		->addValidator(new Zend_Validate_Float())
        		->addFilter(new Zend_Filter_StringTrim())
        );


        $this->addElement(
        		$this->createElement('text', 'bike_insurance_reg')
        		->setLabel('Bike insurance and registration')
        		->setRequired(true)
        		->addValidator(new Zend_Validate_Float())
        		->addFilter(new Zend_Filter_StringTrim())
        );        
        */

        $this->addElement(
        		$this->createElement('text', 'cash_bond')
        		->setLabel('Cash bond')
        		->setRequired(true)
        		->addValidator(new Zend_Validate_Float())
        		->addFilter(new Zend_Filter_StringTrim())
        );
        
        $this->addDisplayGroup(array(
        		'thirteenth_month_pay'
        		, 'incentive_5_day_leave'
        		, 'admin_fee'
        		, 'bike_rental'
        		, 'sss_employee'
        		, 'sss_employer'
        		, 'philhealth_employee'
        		, 'philhealth_employer'
        		, 'ec'
        		, 'hdmf_employee'
        		, 'hdmf_employer'
        		
        ) , 'monthly', array('legend'=>'Monthly values'));

        /*
        $this->addDisplayGroup(array(
        		'bike_rehab'
        		, 'bike_insurance_reg'
        ) , 'semi_monthly', array('legend'=>'Semi-monthly values'));
        */
              
        $this->addDisplayGroup(array(
        		// 'fuel_allocation' ,
        		'ecola'
        		, 'cash_bond'
        ) , 'daily', array('legend'=>'Daily values'));
                
        $this->addElement(
            $this->createElement('button', 'submit')
                ->setLabel('Save')
                ->setAttrib('type', 'submit')
        );

        $this->addElement(
        		$this->createElement('button', 'cancel')
        		->setLabel('Cancel')
        		->setAttrib('type', 'button')
        		->setAttrib('class', 'cancel')
        );
        
        parent::init();
    }
}