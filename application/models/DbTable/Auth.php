<?php

class Application_Model_DbTable_Auth extends Zend_Db_Table_Abstract {
    protected $_name = 'groups';
    protected $_login = null;
    protected $_password = null;

    public function __construct($login, $password) {
        $this->_login = $login;
        $this->_password = $password;
    }

    public function authorize() {
        $staticSalt = ADMIN_STATIC_PASS_SALT;
        $adapter = new Zend_Auth_Adapter_DbTable(
            null,
            'admins',
            'username',
            'password',
            "MD5(CONCAT('" . $staticSalt . "', ?, pass_salt))"
            );

        $adapter->setIdentity($this->_login);
        $adapter->setCredential($this->_password);

        $auth   = Zend_Auth::getInstance();
        $result = $auth->authenticate($adapter);
        $isValid = $result->isValid();
        return $isValid;
    }
}

?>