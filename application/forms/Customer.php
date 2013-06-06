<?php

class Application_Form_Customer extends Zend_Form
{

    public function init()
    {
        $customerID = $this->getAttrib('customerID');
        $userpicPath = $this->getAttrib('userpicPath');
        $userpicPath = $userpicPath ? $userpicPath : false;
        $groupsArr = $this->getAttrib('groups');

        // «По-кошерному» включить файл со своим валидатором не получилось, поэтому пока так
        require APPLICATION_PATH . '/forms/validators/Expiration_Time.php';
        $exp_time_validator = new Validator_Expiration_Time();

        // По умолчанию, срок действия аккаунта равен трем месяцам с момента регистрации.
        $dateInThreeMonths = date("Y-m-d H:i:s", strtotime('+3 months', strtotime(date("Y-m-d H:i:s"))));

        $this->setName('customer')
             ->setAttribs(array('class'=>'form-horizontal'));

        $id = new Zend_Form_Element_Hidden('id');
        $id->addFilter('Int')
           ->removeDecorator('htmlTag')
           ->removeDecorator('DtDdWrapper')
           ->removeDecorator('label');

        $userpic_ext = new Zend_Form_Element_Hidden('userpic_ext');
        $userpic_ext->addFilter('Alpha')
                    ->removeDecorator('htmlTag')
                    ->removeDecorator('DtDdWrapper')
                    ->removeDecorator('label');


        $group = new Zend_Form_Element_Select('group_id');
        $group->addMultiOptions($groupsArr)
              ->addFilter('Int')
              ->addFilter('StripTags')
              ->addFilter('StringTrim')
              ->addValidator('NotEmpty', true)
              ->addValidator('Db_RecordExists', true,
                    array('table' => 'groups', 'field' => 'id',
                        'messages' => array('noRecordFound' => 'Указанной группы не существует')
                    )
                )
              ->removeDecorator('htmlTag')
              ->removeDecorator('DtDdWrapper')
              ->removeDecorator('label');


        $acc_exp_date = new Zend_Form_Element_Text('acc_exp_date');
        $acc_exp_date->setValue($dateInThreeMonths)
                     ->setRequired(true)
                     ->addFilter('StripTags')
                     ->addFilter('StringTrim')
                     ->addValidator('NotEmpty', true)
                     ->addValidator('StringLength', false, array(19, 19))
                     ->addValidator($exp_time_validator)
                     ->removeDecorator('htmlTag')
                     ->removeDecorator('DtDdWrapper')
                     ->removeDecorator('label');

        $pass = new Zend_Form_Element_Password('password');
        $pass->setRequired(false)
             ->addFilter('StripTags')
             ->addFilter('StringTrim')
             ->addValidator('StringLength', false, array(5, 64))
             ->removeDecorator('htmlTag')
             ->removeDecorator('DtDdWrapper')
             ->removeDecorator('label');

        $login = new Zend_Form_Element_Text('login');
        $login->setRequired(true)
              ->addFilter('StripTags')
              ->addFilter('StringTrim')
              ->addValidator('NotEmpty', true)
              ->addValidator('StringLength', false, array(3, 64))
              ->removeDecorator('htmlTag')
              ->removeDecorator('DtDdWrapper')
              ->removeDecorator('label');



        $email = new Zend_Form_Element_Text('email');
        $email->setRequired(true)
              ->addFilter('StripTags')
              ->addFilter('StringTrim')
              ->addValidator('NotEmpty', true)
              ->addValidator('EmailAddress')
              ->addValidator('StringLength', false, array(5, 64))
              ->addValidator('Db_NoRecordExists', true,
                    array('table' => 'customers', 'field' => 'email',
                        'messages' => array('recordFound' => 'Указанный email уже используется.'),
                        'exclude' => array(
                            'field' => 'id',
                            'value' => $customerID
                        )
                    )
                )
              ->removeDecorator('htmlTag')
              ->removeDecorator('DtDdWrapper')
              ->removeDecorator('label');

        $image = new Zend_Form_Element_File('userpic');
        $image->setRequired(false)
              ->addValidator('Count', array(1))
              ->addValidator('IsImage', true)
              ->addValidator('Size', true, array(1048576 * 5))
              ->addValidator('Extension', false, array('jpg,png,gif,jpeg'))
              ->removeDecorator('htmlTag')
              ->removeDecorator('DtDdWrapper')
              ->removeDecorator('label');

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Отправить')
               ->setAttribs(array('class'=>'btn'))
               ->removeDecorator('DtDdWrapper');

        $this->setDecorators(array(
            array('ViewScript', array(
                'viewScript' => '_form_customer.phtml',
                'userpicPath' => $userpicPath
                )
            )
        ));

        $this->addElements(array($id, $login, $pass, $email, $group, $acc_exp_date, $image, $submit));

    }


}

