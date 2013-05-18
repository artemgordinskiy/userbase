<?php

class CustomersController extends Zend_Controller_Action
{

    public function init()
    {
        $request = $this->getRequest();
        $auth = Zend_Auth::getInstance();
        if (!$auth->hasIdentity()){
          $this->_redirect('/auth/index/from/customers');
        }
    }

    public function indexAction()
    {
        $customers = new Application_Model_DbTable_Customers();
        $pageNum = (int)$this->_getParam('page', 1);
        $sortBy = $this->_getParam('sort', 'id');
        $filterById = $this->_getParam('filterByID', false);
        $expiration = $this->_getParam('expiration', false);
        $resultSet = $customers->fetchAllCustomers($pageNum, $sortBy, 'ASC', 10, $filterById, $expiration);
        $this->view->customers = $resultSet;

        // Пришлось еще раз дергать ДБ, чтобы получить полный список групп для фильтра.
        $groups = new Application_Model_DbTable_Groups();
        $groupsResultSet = $groups->fetchAll(null, 'name ASC');
        $this->view->groups = $groupsResultSet;
    }

    public function addAction()
    {
        $form = new Application_Form_customer();
        $form->submit->setLabel('Сохранить');
        $this->view->form = $form;

        // Если форма была отправлена...
        if ($this->getRequest()->isPost()) {
            $formData = $this->getRequest()->getPost();
            // Если форма прошла проверки...
            if ($form->isValid($formData)) {
                $group_id = (int)$form->getValue('group_id');
                $acc_exp_date = $form->getValue('acc_exp_date');
                $pass = $form->getValue('password');
                $login = $form->getValue('login');
                $email = $form->getValue('email');
                $customers = new Application_Model_DbTable_Customers();
                $customers->addCustomer($group_id, $acc_exp_date, $pass, $login, $email);

                $this->_helper->redirector('index');
            } else {
                $form->populate($formData);
            }
        }
    }

    public function editAction()
    {
        $form = new Application_Form_customer();
        $form->submit->setLabel('Сохранить');
        $this->view->form = $form;

        if($this->getRequest()->isPost()) {
            $formData = $this->getRequest()->getPost();
            if ($form->isValid($formData)) {
                $id = (int)$form->getValue('id');
                $group_id = (int)$form->getValue('group_id');
                $acc_exp_date = $form->getValue('acc_exp_date');
                $pass = $form->getValue('password');
                $login = $form->getValue('login');
                $email = $form->getValue('email');

                $customers = new Application_Model_DbTable_Customers();
                $customers->editCustomer($id, $group_id, $acc_exp_date, $pass, $login, $email);

                $this->_helper->redirector('index');
            } else {
                $form->populate($formData);

            }
        } else {
            $id = (int)$this->_getParam('id', 0);
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
            }
            $this->_helper->redirector('index');
        } else {
            $id = $this->_getParam('id', 0);
            $customers = new Application_Model_DbTable_Customers();
            $this->view->customer = $customers->getCustomer($id);
        }
    }


}