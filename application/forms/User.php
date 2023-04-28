<?php

/**
 * Application_Form_Users form file.
 */
class Messerve_Form_User extends Zend_Form
{

    public function __construct($options = null)
    {
        parent::__construct($options);
        
        $this->setName('frmUsers');
        $this->setMethod('post');
        
        $id = new Zend_Form_Element_Hidden('id');
        $id->setRequired(true);
        $id->addValidator(new Zend_Validate_NotEmpty());
        $id->addValidator(new Zend_Validate_Int());
        $this->addElement($id);
        
        $username = new Zend_Form_Element_Text('username');
        $username->setLabel('Username');
        $username->setAttrib('maxlength', 32);
        $username->setRequired(true);
        $username->addValidator(new Zend_Validate_NotEmpty());
        $this->addElement($username);
        
        $password = new Zend_Form_Element_Password('password');
        $password->setLabel('Password');
        $password->setAttrib('maxlength', 32);
        $password->setRequired(true);
        $password->addValidator(new Zend_Validate_NotEmpty());
        $this->addElement($password);
        
        $passwordSalt = new Zend_Form_Element_Text('password_salt');
        $passwordSalt->setLabel('Password salt');
        $passwordSalt->setAttrib('maxlength', 32);
        $this->addElement($passwordSalt);
        
        $realName = new Zend_Form_Element_Text('real_name');
        $realName->setLabel('Real Name');
        $realName->setAttrib('maxlength', 128);
        $realName->setRequired(true);
        $realName->addValidator(new Zend_Validate_NotEmpty());
        $this->addElement($realName);
        
        $Staff = new Messerve_Model_User();
        
        $info = $Staff->getMapper()->getDbTable()->info();
        
        // $types_options = zend_enum_from_info($info, 'type');

        $types_options = [
            'supervisor' => 'Supervisor',
            'accounting' => 'Accounting',
            'manager' => 'Manager',
            'admin' => 'Admin',
            'encoder' => 'Encoder',
            'rider_details' => 'Rider details',
            'bop' => 'BOP',
            'employee_editor' => 'Employee editor'
        ];
        
        $type = new Zend_Form_Element_Select('type');
        $type->setLabel('Type');
        $type->setRequired(true);
        $type->addValidator(new Zend_Validate_NotEmpty());
        $type->setMultiOptions($types_options);
        $this->addElement($type);
                
        $status_options = zend_enum_from_info($info, 'status');
        
        $status = new Zend_Form_Element_Select('status');
        $status->setLabel('Status');
        $status->setRequired(true);
        $status->addValidator(new Zend_Validate_NotEmpty());
        $status->setMultiOptions($status_options);
        $this->addElement($status);
        
        $submit = new Zend_Form_Element_Submit('bt_submit');
        $submit->setLabel('Save');
        $this->addElement($submit);
    }


}
