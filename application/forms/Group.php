<?php

class Application_Form_Group extends Zend_Form
{

    public function init()
    {
        $this->setName('group');

        $groupID = $this->getAttrib('groupID');
        $groupID = $groupID !== null ? $groupID : 0;

        $id = new Zend_Form_Element_Hidden('id');
        $id->addFilter('Int');

        $name = new Zend_Form_Element_Text('name');
        $name->setLabel('Название:')
             ->setRequired(true)
             ->addFilter('StripTags')
             ->addFilter('StringTrim')
             ->addValidator('NotEmpty')
             ->addValidator('Db_NoRecordExists', true,
                    array('table' => 'groups', 'field' => 'name',
                        'messages' => array('recordFound' => 'Указанное название группы уже используется.'),
                        'exclude' => array(
                            'field' => 'id',
                            'value' => $groupID
                        )
                    )
               )
              ->removeDecorator('htmlTag')
              ->removeDecorator('DtDdWrapper')
              ->removeDecorator('label');

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setAttrib('class', 'btn')
               ->removeDecorator('DtDdWrapper');

        $this->setDecorators(array(
            array('ViewScript', array(
                'viewScript' => '_form_group.phtml'
                )
            )
        ));

        $this->addElements(array($id, $name, $submit));
    }

}

