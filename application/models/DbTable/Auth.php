<?php

class Application_Model_DbTable_Auth {
    protected $_login = null;
    protected $_password = null;

    public function __construct($login, $password) {
        $this->_login = $login;
        $this->_password = $password;
    }

    public function authorize() {
        $staticSalt = mysql_real_escape_string(ADMIN_STATIC_PASS_SALT);
        $adapter = new Zend_Auth_Adapter_DbTable();
        $adapter->setTableName('admins')
                ->setIdentityColumn('username')
                ->setIdentity($this->_login)
                ->setCredentialColumn('password')
                ->setCredential($this->_password)
                ->setCredentialTreatment('MD5(CONCAT("' . $staticSalt . '", ?, pass_salt))');

        $auth = Zend_Auth::getInstance();
        $result = $auth->authenticate($adapter);
        $isValid = $result->isValid();
        return $isValid;
    }
}

?>