<?php

class CustomersController extends Zend_Controller_Action
{

    public function init()
    {
        $this->view->messages = $this->_helper->flashMessenger->getMessages();
        $request = $this->getRequest();
        $auth = Zend_Auth::getInstance();
        if (!$auth->hasIdentity()){
            $this->_redirect('/auth/login/from/customers');
        }
    }

    public function indexAction()
    {
        $customers = new Application_Model_DbTable_Customers();
        $pageNum = (int)$this->_getParam('p', 1);
        $sortBy = $this->_getParam('sort', 'id');
        $filterById = $this->_getParam('filterByID', false);
        $expiration = $this->_getParam('expiration', false);
        $resultSet = $customers->fetchAllCustomers($pageNum, $sortBy, 'ASC', 10, $filterById, $expiration);
        $this->view->customers = $resultSet;

        // Пришлось еще раз дергать ДБ, чтобы получить полный список групп для фильтра.
        $groups = new Application_Model_DbTable_Groups();
        $groupsResultSet = $groups->getAllGroupsWithMembers();
        $this->view->groups = $groupsResultSet;
    }

    public function addAction()
    {
        $id = (int)$this->_getParam('id', 0);
        $groups = new Application_Model_DbTable_Groups();
        $groupsArr = $groups->getGroupsForTheForm();
        $form = new Application_Form_Customer(array('customerID' => $id, 'groups' => $groupsArr));
        $form->submit->setLabel('Сохранить');
        $this->view->form = $form;

        // Если форма была отправлена...
        if ($this->getRequest()->isPost()) {
            $formData = $this->getRequest()->getPost();
            // Если форма прошла проверки...
            if ($form->isValid($formData)) {
                $upload = new Zend_File_Transfer_Adapter_Http();
                $userpicExt = null;

                if($upload->isUploaded()) {
                    $userpicName = $upload -> getFileName('userpic');
                    $userpicExt = pathinfo($userpicName, PATHINFO_EXTENSION);
                }

                $group_id = (int)$form->getValue('group_id');
                $group_id = $group_id !== 0 ? $group_id : null;
                $acc_exp_date = $form->getValue('acc_exp_date');
                $pass = $form->getValue('password');
                $login = $form->getValue('login');
                $email = $form->getValue('email');
                $customers = new Application_Model_DbTable_Customers();
                $newID = $customers->addCustomer($group_id, $acc_exp_date, $pass, $login, $email, $userpicExt);

                if($upload->isUploaded()) {
                    $upload->addFilter('Rename', array('target' => PUBLIC_PATH . '/images/uploads/' . $newID . '.' . $userpicExt, 'overwrite' => true));
                    $upload->receive();
                }
                $this->_helper->flashMessenger->addMessage('Клиент был успешно добавлен в базу');
                $this->_helper->redirector('index');
            } else {
                $form->populate($formData);
            }
        }
    }

    public function editAction()
    {
        $id = (int)$this->_getParam('id', 0);
        $groups = new Application_Model_DbTable_Groups();
        $groupsArr = $groups->getGroupsForTheForm();
        $form = new Application_Form_Customer(array('customerID' => $id, 'groups' => $groupsArr));

        $form->submit->setLabel('Сохранить');
        $this->view->form = $form;

        if($this->getRequest()->isPost()) {
            $formData = $this->getRequest()->getPost();
            if ($form->isValid($formData)) {
                $userpicExt = null;
                $id = (int)$form->getValue('id');
                $upload = new Zend_File_Transfer_Adapter_Http();

                if($upload->isUploaded()) {
                    // Удаляем предыдущий юзерпик
                    array_map('unlink', glob(PUBLIC_PATH . '/images/uploads/' . $id . '.*'));
                    $userpicName = $upload -> getFileName('userpic');
                    $userpicExt = pathinfo($userpicName, PATHINFO_EXTENSION);
                    $upload->addFilter('Rename', array('target' => PUBLIC_PATH . '/images/uploads/' . $id . '.' . $userpicExt, 'overwrite' => true));
                    $upload->receive();
                }

                $group_id = (int)$form->getValue('group_id');
                $group_id = $group_id !== 0 ? $group_id : null;
                $acc_exp_date = $form->getValue('acc_exp_date');
                $pass = $form->getValue('password');
                $login = $form->getValue('login');
                $email = $form->getValue('email');

                $customers = new Application_Model_DbTable_Customers();
                $customers->editCustomer($id, $group_id, $acc_exp_date, $pass, $login, $email, $userpicExt);
                $this->_helper->flashMessenger->addMessage('Информация клиента сохранена');
                $this->_helper->redirector('index');
            } else {
                $form->populate($formData);

            }
        } else {
            if($id > 0) {
                $customers = new Application_Model_DbTable_Customers();
                $form->populate($customers->getCustomer($id));
            }
        }
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $del = $this->getRequest()->getPost('del');
            if($del === 'Да') {
                $id = $this->getRequest()->getPost('id');
                $customers = new Application_Model_DbTable_Customers();
                $customers->deleteCustomer($id);
                $this->_helper->flashMessenger->addMessage('Клиент успешно удален');
            }
            $this->_helper->redirector('index');
        } else {
            $id = $this->_getParam('id', 0);
            $customers = new Application_Model_DbTable_Customers();
            $this->view->customer = $customers->getCustomer($id);
        }
    }


}