<?php

/**
 * Form definition for table group.
 *
 * @package Messerve
 * @author Slide Gurtiza
 * @version $Id$
 *
 */
class Messerve_Form_EditGroup extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');

        $this->addElement(
            $this->createElement('hidden', 'id')
                
        );

        /** @var Zend_Form_Element_Select $region_element */
        $region_element = $this->createElement('select', 'region')
            ->setLabel('Region')
            ->setAttrib("maxlength", 128)
            ->setRequired(true)
            ->addValidator(new Zend_Validate_StringLength(array("max" => 128)))
            ->addFilter(new Zend_Filter_StringTrim())
            ->addFilter(new Zend_Filter_StringToUpper());

        $region_array = [
            ""=>"",
            "NCR" => "NCR",
            "CAR" => "CAR",
            "REGION I" => "REGION I",
            "REGION II" => "REGION II",
            "REGION III" => "REGION III",
            "REGION IV-A" => "REGION IV-A",
            "REGION IV-B" => "REGION IV-B",
            "REGION IX" => "REGION IX",
            "REGION V" => "REGION V",
            "REGION VI" => "REGION VI",
            "REGION VII" => "REGION VII",
            "REGION VII-A" => "REGION VII-A",
            "REGION VII-B" => "REGION VII-B",
            "REGION VIII" => "REGION VIII",
            "REGION X" => "REGION X",
            "REGION X-I" => "REGION X-I",
            "REGION X-II" => "REGION X-II",
            "REGION XI" => "REGION XI",
            "REGION XII" => "REGION XII"
        ];

        $region_element->setMultiOptions($region_array);

        $this->addElement($region_element);


        $this->addElement(
            $this->createElement('text', 'name')
                ->setLabel('Group Name')
                ->setAttrib("maxlength", 128)
                ->setRequired(true)
                ->addValidator(new Zend_Validate_StringLength(array("max" => 128)))
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'billing_name')
                ->setLabel('Billing Name')
                ->setAttrib("maxlength", 128)
                //->setRequired(true)
                ->addValidator(new Zend_Validate_StringLength(array("max" => 128)))
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
        		$this->createElement('text', 'code')
        		->setLabel('Group code')
        		->setAttrib("maxlength", 32)
        		->setRequired(true)
        		->addValidator(new Zend_Validate_StringLength(array("max" => 32)))
        		->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('checkbox', 'non_vat')
                ->setLabel('Non-VAT billing')
                ->setCheckedValue('1')
                ->setUncheckedValue('0')
        );

        $this->addElement(
            $this->createElement('textarea', 'address')
                ->setLabel('Billing address')
                ->setOptions(array('rows'=>4, 'cols'=>52, 'style'=>'width:300px;'))
        );

        $this->addElement(
            $this->createElement('text', 'tin')
                ->setLabel('TIN')
                ->setAttrib("maxlength", 16)
                ->setRequired(true)
                ->addValidator(new Zend_Validate_StringLength(array("max" => 16)))
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('select', 'client_id')
                ->setLabel('Client')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Int())
                ->addFilter(new Zend_Filter_StringTrim())
        );
        
        $this->addElement(
            $this->createElement('select', 'rate_id')
                ->setLabel('Default Employee Rate')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Int())
                ->addFilter(new Zend_Filter_StringTrim())
        );
        
        $this->addElement(
            $this->createElement('select', 'rate_client_id')
                ->setLabel('Default Client Rate')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Int())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'fuelperhour')
                ->setLabel('Fuel per hour')
                ->setAttrib("maxlength", 16)
                ->setRequired(true)
                ->addValidator(new Zend_Validate_StringLength(array("max" => 16)))
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
        		$this->createElement('multiCheckbox', 'calendars')
        		->setLabel('Calendars')
        );        

/*        $this->addElement(
        		$this->createElement('checkbox', 'round_off_10')
        		->setLabel('Round off attendance to 10 minutes')
        		->setDescription('Mang Inasal setting')
        		->setCheckedValue('yes')
        		->setUncheckedValue('no')
        );*/



        
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
        
        $this->addElement(
        		$this->createElement('button', 'cancel')
        		->setLabel('Cancel')
        		->setAttrib('type', 'button')
        		->setAttrib('class', 'cancel')
        );
        
        parent::init();
    }
}