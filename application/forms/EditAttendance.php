<?php

/**
 * Form definition for table attendance.
 *
 * @package Messerve
 * @author Zodeken
 * @version $Id$
 *
 */
class Messerve_Form_EditAttendance extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setOptions(array('class'=>'bigform'));
        
        $this->addElement(
            $this
        		->createElement('hidden', 'id')
				->removeDecorator('label')
				->removeDecorator('DtDd')
        );

        $this->addElement(
            $this->createElement('hidden', 'employee_id')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Int())
                ->addFilter(new Zend_Filter_StringTrim())
        		->removeDecorator('label')
        		->removeDecorator('DtDd')
        );

        $this->addElement(
            $this->createElement('text', 'datetime_start')
                ->setLabel('Date')
                ->setValue(date("Y-m-d"))
                ->setRequired(true)
                ->addFilter(new Zend_Filter_StringTrim())
        		->setOptions(array('readonly'=>true))
        );
        
        $this->addElement(
        		$this->createElement('text', 'start_1')
        		->setLabel('IN AM')
        		// ->setValue(date("Y-m-d"))
        		->setRequired(true)
        		->addFilter(new Zend_Filter_StringTrim())
        );
        
        $this->addElement(
        		$this->createElement('text', 'end_1')
        		->setLabel('OUT AM')
        		// ->setValue(date("Y-m-d"))
        		// ->setRequired(true)
        		->addFilter(new Zend_Filter_StringTrim())
        );
        
        $this->addElement(
        		$this->createElement('text', 'start_2')
        		->setLabel('IN PM')
        		// ->setValue(date("Y-m-d"))
        		// ->setRequired(true)
        		->addFilter(new Zend_Filter_StringTrim())
        );
        
        $this->addElement(
        		$this->createElement('text', 'end_2')
        		->setLabel('OUT PM')
        		// ->setValue(date("Y-m-d"))
        		// ->setRequired(true)
        		->addFilter(new Zend_Filter_StringTrim())
        );
        
        $this->addElement(
        		$this->createElement('text', 'start_3')
        		->setLabel('IN OT')
        		// ->setValue(date("Y-m-d"))
        		// ->setRequired(true)
        		->addFilter(new Zend_Filter_StringTrim())
        );
        
        $this->addElement(
        		$this->createElement('text', 'end_3')
        		->setLabel('OUT OT')
        		// ->setValue(date("Y-m-d"))
        		// ->setRequired(true)
        		->addFilter(new Zend_Filter_StringTrim())
        );
        
/*
        $this->addElement(
            $this->createElement('text', 'datetime_end')
                ->setLabel('Date/Time end')
                ->setValue(date("Y-m-d"))
                // ->setRequired(true)
                ->addFilter(new Zend_Filter_StringTrim())
        );
        
        $this->addElement(
        		$this->createElement('text', 'fuel_overage')
        		->setLabel('Fuel overage (L)')
        		->setRequired(true)
        		->addValidator(new Zend_Validate_Float())
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
            $this->createElement('text', 'reg_ot_nd')
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