<?php

class Messerve_Form_EditEmployeeRateSchedule extends Zend_Form
{
    public function init() {
        /*
        $this->addElement(
            $this->createElement('hidden', 'id')

        );
        */

        $this->addElement(
            $this->createElement('text', 'date_active')
                ->setLabel('Date active')
                // ->setValue(date("Y-m-d"))
                ->setRequired(true)
                ->addFilter(new Zend_Filter_StringTrim())
                ->setOptions(array('class'=>'datepicker'))
        );

        $this->addElement(
            $this->createElement('select', 'group_id')
                ->setLabel('Group')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Int())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('select', 'rate_id')
                ->setLabel('Employee rate')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Int())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('textarea', 'notes')
                ->setLabel('Notes')
                ->setRequired(true)
                ->addFilter(new Zend_Filter_StringTrim())
                ->setOptions(array("cols"=>32,"rows"=>5))
        );

        $this->addElement(
            $this->createElement('button', 'submit')
                ->setLabel('Save rate schedule')
                ->setAttrib('type', 'submit')
        );


    }

}