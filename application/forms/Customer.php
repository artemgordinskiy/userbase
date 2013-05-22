<?php

class Application_Form_Customer extends Zend_Form
{

    public function init()
    {
        $this->setName('customer')
             ->setAttribs(array('class'=>'form-horizontal'));

        $id = new Zend_Form_Element_Hidden('id');
        $id->addFilter('Int');

        $group_id = new Zend_Form_Element_Text('group_id');
        $group_id->setLabel('ID группы')
                ->addFilter('Int')
                ->setRequired(true)
                ->addFilter('StripTags')
                ->addFilter('StringTrim')
                ->addValidator('NotEmpty')
                ->addValidator('StringLength', false, array(1, 11));

        $acc_exp_date = new Zend_Form_Element_Text('acc_exp_date');
        $acc_exp_date->setLabel('Действует до:')
                     ->setValue('9999-12-31 23:59:59')
                     ->setRequired(true)
                     ->addFilter('StripTags')
                     ->addFilter('StringTrim')
                     ->addValidator('NotEmpty')
                     ->addValidator('StringLength', false, array(19, 19));

        $pass = new Zend_Form_Element_Password('password');
        $pass->setLabel('Пароль:')
             ->setRequired(false)
             ->addFilter('StripTags')
             ->addFilter('StringTrim')
             ->addValidator('NotEmpty')
             ->addValidator('StringLength', false, array(5, 64));

        $login = new Zend_Form_Element_Text('login');
        $login->setLabel('Логин:')
              ->setRequired(true)
              ->addFilter('StripTags')
              ->addFilter('StringTrim')
              ->addValidator('NotEmpty')
              ->addValidator('StringLength', false, array(3, 64));

        $email = new Zend_Form_Element_Text('email');
        $email->setLabel('Почта:')
              ->setRequired(true)
              ->addFilter('StripTags')
              ->addFilter('StringTrim')
              ->addValidator('NotEmpty')
              ->addValidator('EmailAddress')
              ->addValidator('StringLength', false, array(5, 64));

        $image = new Zend_Form_Element_File('userpic');
        $image->setLabel('Аватар:')
              ->setRequired(false)
              ->addValidator('Count', array(1))
              ->addValidator('IsImage', false)
              ->addValidator('Size', false, array(1048576 * 5))
              ->addValidator('Extension', false, array('jpg,png,gif,jpeg'));

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Отправить')
               ->setAttribs(array('class'=>'btn'));

        $this->addElements(array($id, $group_id, $acc_exp_date, $login, $pass, $email, $image, $submit));


    }


}

