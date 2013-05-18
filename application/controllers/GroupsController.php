<?php

class GroupsController extends Zend_Controller_Action
{

    public function init()
    {
        $request = $this->getRequest();
        $auth = Zend_Auth::getInstance();
        if (!$auth->hasIdentity()){
          $this->_redirect('/auth/index/from/groups');
        }
    }

    public function indexAction()
    {
        $groups = new Application_Model_DbTable_Groups();
        $resultSet = $groups->fetchAllGroups($this->_getParam('page', 1), $this->_getParam('sort', 'id'), 'ASC');
        $this->view->groups = $resultSet;
    }

    public function addAction()
    {
        $form = new Application_Form_Group();
        $form->submit->setLabel('Сохранить');
        $this->view->form = $form;

        // Если форма была отправлена...
        if ($this->getRequest()->isPost()) {
            $formData = $this->getRequest()->getPost();
            // Если форма прошла проверки...
            if ($form->isValid($formData)) {
                $name = $form->getValue('name');
                $groups = new Application_Model_DbTable_Groups();
                $groups->addGroup($name);

                $this->_helper->redirector('index');
            } else {
                $form->populate($formData);
            }
        }
    }

    public function editAction()
    {
        $form = new Application_Form_Group();
        $form->submit->setLabel('Сохранить');
        $this->view->form = $form;

        if($this->getRequest()->isPost()) {
            $formData = $this->getRequest()->getPost();
            if ($form->isValid($formData)) {
                $id = (int)$form->getValue('id');
                $name = $form->getValue('name');
                $groups = new Application_Model_DbTable_Groups();
                $groups->editGroup($id, $name);

                $this->_helper->redirector('index');
            } else {
                $form->populate($formData);

            }
        } else {
            $id = (int)$this->_getParam('id', 0);
            if($id > 0) {
                $groups = new Application_Model_DbTable_Groups();
                $form->populate($groups->getGroup($id));
            }
        }
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $del = $this->getRequest()->getPost('del');
            if($del === 'Да') {
                $id = $this->getRequest()->getPost('id');
                $groups = new Application_Model_DbTable_Groups();
                $groups->deleteGroup($id);
            }
            $this->_helper->redirector('index');
        } else {
            $id = $this->_getParam('id', 0);
            $groups = new Application_Model_DbTable_Groups();
            $this->view->group = $groups->getGroup($id);
        }
    }


}







