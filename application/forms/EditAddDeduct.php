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

		$this->addElement(
				$this->createElement('text', 'thirteenth_month_pay')
				->setLabel('13th-month pay')
				->addFilter(new Zend_Filter_StringTrim())
		);

		$this->addElement(
				$this->createElement('text', 'incentives')
				->setLabel('Incentives')
				->addFilter(new Zend_Filter_StringTrim())
		);
		
		$this->addElement(
				$this->createElement('text', 'paternity')
				->setLabel('Paternity')
				->addFilter(new Zend_Filter_StringTrim())
		);

        $this->addElement(
            $this->createElement('text', 'misc_income')
                ->setLabel('Misc. income')
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'solo_parent_leave')
                ->setLabel('Solo parent leave')
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'tl_allowance')
                ->setLabel('TL allowance')
                ->addFilter(new Zend_Filter_StringTrim())
        );
		
		$this->addDisplayGroup(array(
        		'thirteenth_month_pay'
        		, 'incentives'
        		, 'paternity'
        		, 'misc_income'
                , 'solo_parent_leave'
                , 'tl_allowance'
        ) , 'add', array('legend'=>'Additional income'));

		$this->addElement(
				$this->createElement('button', 'submit')
				->setLabel('Save additional income')
				->setAttrib('type', 'submit')
		);
		
		
		parent::init();
    }
}