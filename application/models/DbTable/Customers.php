<?php

class Application_Model_DbTable_Customers extends Zend_Db_Table_Abstract
{

    protected $_name = 'customers';

    /**
     * Выдает всех пользователей; Постранично, и с сортировкой;
     * @param  [INT]   $page             Страница, для которой нужно выдать результаты
     * @param  [STR]   $orderBy          Имя столбца, по которому будем сортировать
     * @param  [STR]   $orderDirection   Направление сортировки
     * @param  [INT]   $resultCount      Количество результатов
     * @return [ARR]                     Массив с результатами
     */
    public function fetchAllCustomers($page = 1, $orderTerm = 'id_a', $resultCount = 10, $filterById = false, $filterByDate = false) {
        $orderTerms = array(
            'id_a' => 'id ASC',
            'id_d' => 'id DESC',
            'lg_a' => 'login ASC',
            'lg_d' => 'login DESC',
            'em_a' => 'email ASC',
            'em_d' => 'email DESC',
            'gr_a' => 'group_name ASC',
            'gr_d' => 'group_name DESC',
            'exp_a' => 'acc_exp_date ASC',
            'exp_d' => 'acc_exp_date DESC'
        );

        $page = (int)$page;
        $resultCount = (int)$resultCount;
        $filterById = (int)$filterById;

        $query = $this->select();
        $query->setIntegrityCheck(false)
              ->from('customers', array('customers.group_id', 'customers.id', 'login', 'email', 'acc_exp_date'))
              ->joinLeft('groups', 'customers.group_id = groups.id', 'name as group_name');

        if($filterById > 0) {
            $query->where('customers.group_id = ?', $filterById);
        }

        if(array_key_exists($orderTerm, $orderTerms)) {
            $query->order($orderTerms[$orderTerm]);
        } else {
            $query->order('id ASC');
        }

        if($filterByDate !== false) {
            if($filterByDate === 'expired') {
                $query->where('customers.acc_exp_date < NOW()');
            } else {
                $query->where('customers.acc_exp_date > NOW()');
            }
        }



        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbTableSelect($query));
        $paginator->setItemCountPerPage((int)$resultCount);
        $paginator->setCurrentPageNumber((int)$page);
        return $paginator;
    }

    /**
     * Запрос данных пользователя из БД
     * @param  [INT]    $id    ID пользователя;
     * @return [ARR]           Массив данных пользователя, в случае успешного запроса. Иначе — Exception;
     */
    public function getCustomer($id) {

        if(!$this->isRealID($id)) {
            throw new Exception('Ошибка в Customers.php/getCustomer(). Неверный/несуществующий ID пользователя: ' . print_r($id));
        }

        $select = $this->select();
        $select->where('id = ?', (int)$id);
        $row = $this->_fetch($select);
        $row = $row[0];
        // Если запрос прошел удачно, в $row будет массив с данными
        if(!is_array($row)) {
            throw new Exception('Ошибка в Customers.php/getCustomer(). $id = ' . $id);
        }

        return $row;

    }

    /**
     * Добавляет нового пользователя
     * @param  [INT]   $group_id      ID группы, к которой принадлежит пользователь
     * @param  [STR]   $acc_exp_date Дата и время просрочки аккаунта в формате '9999-12-31 23:59:59'
     * @param  [STR]   $pass         Пароль, до 64 символов;
     * @param  [STR]   $login        Логин, до 64 символов;
     * @param  [STR]   $email        Электропочта, до 64 символов;
     * @return [BOOL]                True, если запрос прошел удачно, иначе кидает Exception;
     */
    public function addCustomer($group_id = null, $acc_exp_date = null, $pass = null, $login, $email, $userpicExt = null) {

        $groups = new Application_Model_DbTable_Groups();
        if(!$groups->isRealID($group_id)) {
            throw new Exception('Ошибка в Customers.php/addCustomer(). Неверный/несуществующий ID группы: ' . print_r($group_id));
        }

        $data = array(
            'group_id' => $group_id,
            'acc_exp_date' => $acc_exp_date,
            'login' => $login,
            'email' => $email
        );

        if(strlen($pass) > 0) {
            $generatedSalt = $this->generatePassSalt(50);
            $data['password'] = md5(USER_STATIC_PASS_SALT . $pass . $generatedSalt);
            $data['pass_salt'] = $generatedSalt;
        }

        if(strlen($userpicExt) > 0) {
            $data['userpic_ext'] = $userpicExt;
        }

        // insert() возвращает Primary Key новой записи, если запрос прошел удачно;
        // «parameter binding» производится автоматически, по кажому столбцу.
        $result = $this->insert($data);
        $result = (int)$result;

        if($result === 0) {
            throw new Exception('Ошибка в Customers.php/addCustomer(). $result = ' . $result);
        }

        return $result;

    }

    /**
     * Редактирует информацию пользователя по ID
     * @param [INT] $id           ID пользователя
     * @param [INT] $group_id      ID группы, к которой принадлежит пользователь
     * @param [STR] $acc_exp_date Дата и время просрочки аккаунта в формате '9999-12-31 23:59:59'
     * @param [STR] $pass         Пароль, до 64 символов;
     * @param [STR] $login        Логин, до 64 символов;
     * @param [STR] $email        Электропочта, до 64 символов;
     * @return[BOOL]              True, при успехе; Иначе — Exception
     */
    public function editCustomer($id, $group_id, $acc_exp_date, $pass, $login, $email, $userpicExt) {

        if(!$this->isRealID($id)) {
            throw new Exception('Ошибка в Customers.php/editCustomer(). Неверный/несуществующий ID пользователя: ' . print_r($id));
        }

        $groups = new Application_Model_DbTable_Groups();
        if(!$groups->isRealID($group_id)) {
            throw new Exception('Ошибка в Customers.php/editCustomer(). Неверный/несуществующий ID группы: ' . print_r($group_id));
        }

        $data = array(
            'acc_exp_date' => $acc_exp_date,
            'login' => $login,
            'email' => $email,
            'group_id' => $group_id
        );



        if(strlen($pass) > 0) {
            $generatedSalt = $this->generatePassSalt(50);
            $data['password'] = md5(USER_STATIC_PASS_SALT . $pass . $generatedSalt);
            $data['pass_salt'] = $generatedSalt;
        }

        if(strlen($userpicExt) > 0) {
            $data['userpic_ext'] = $userpicExt;
        }

        // update() возвращает количество затронутых рядов, сохраним его для проверки.
        // «parameter binding» производится автоматически, по кажому столбцу.
        $rowsAffected = (int)$this->update($data, 'id=' . (int)$id);
        if($rowsAffected >= 0) {
            return true;
        } else {
            throw new Exception('Не получилось изменить информацию клиента №' . $id . '. Вероятнее всего, его не существует.');
        }
    }

    /**
     * Удаляет пользователя по ID
     * @param  [INT]   $id    ID пользователя;
     * @return [BOOL]         True, при успехе; Иначе — Exception
     */
    public function deleteCustomer($id) {

        if(!$this->isRealID($id)) {
            throw new Exception('Ошибка в Customers.php/deleteCustomer(). Неверный/несуществующий ID пользователя: ' . print_r($id));
        }

        // метод delete() возвращает количество затронутых рядов, сохраним его для проверки.
        $rowsAffected = (int)$this->delete('id = ' . $id);
        if($rowsAffected === 1) {
            // Удаляем юзерпик
            array_map('unlink', glob(PUBLIC_PATH . '/images/uploads/' . $id . '.*'));
            return true;
        } else {
            throw new Exception('Не получилось удалить клиента №' . $id . '. Вероятнее всего, его не существует.');
        }
    }

    /**
     * Генерирует строку из случайных символов, чтобы использовать
     * в качестве «соли» при хешировании.
     * @param  integer   $length    Необходимая длина
     * @return string               Строка указанной длины
     */
    public function generatePassSalt($length) {
        $characters = '}{[]"/\|;!@#$%^&*()_+0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    /**
     * Удаляет всех пользователей в группе, по ID.
     * Сделано как замена/дополнение к каскадному удалению в БД,
     * т.к. нужно удалять и пользовательские изображения.
     * @param  [INT]    $groupId   ID группы
     * @return [BOOL]              Возвращает true, если никаких ошибок не произошло.
     */
    public function deleteEverybodyInAGroup($groupId) {

        $groups = new Application_Model_DbTable_Groups();
        if(!$groups->isRealID($groupId)) {
            throw new Exception('Ошибка в Customers.php/deleteEverybodyInAGroup(). Неверный/несуществующий ID группы: ' . print_r($groupId));
        }

        try {
            $query = $this->select();
            $query->from('customers', array('id'))
                  ->where('group_id = ?', $groupId);

            $resultSet = $this->_fetch($query);
        } catch(PDOException $e) {
            throw new Exception('Ошибка в Customers.php/deleteEverybodyInAGroup(): ' . $e->getMessage());
        } catch(Exception $e) {
            throw new Exception('Ошибка в Customers.php/deleteEverybodyInAGroup(): ' . $e->getMessage());
        }

        if(!is_array($resultSet)) {
            throw new Exception('Ошибка в Customers.php/deleteEverybodyInAGroup(): неправильный результат из БД:' . $resultSet);
        }

        foreach ($resultSet as $group => $_id) {
            $this->deleteCustomer($_id['id']);
        }

        return true;

    }

    /**
     * Производит проверку данного ID клиента, сверяя его с БД.
     * @param  [INT]  $id   ID группы
     * @return [BOOL]       Результат проверки.
     */
    public function isRealID($id) {
        $id = (int)$id;

        if($id <= 0) {
            return false;
        }

        try {
            $query = $this->select();
            $query->from('customers', array('id'))->order('id');
            $rowSet = $this->_fetch($query);
            $IDs = array();
        } catch(PDOException $e) {
            throw new Exception('Ошибка в Customers.php/isRealID(): ' . $e->getMessage());
        } catch(Exception $e) {
            throw new Exception('Ошибка в Customers.php/isRealID(): ' . $e->getMessage());
        }

        if(!is_array($rowSet)) {
            throw new Exception('Ошибка в Customers.php/isRealID(). Неправильный тип переменной. Содержимое: ' . print_r($rowSet));
        }

        foreach ($rowSet as $group => $_id) {
            array_push($IDs, $_id['id']);
        }

        if(!in_array($id, $IDs)){
            return false;
        }

        return true;
    }

    public function getUserpicExt($id) {
        $id = (int)$id;

        if($id <= 0) {
            return false;
        }

        if(!$this->isRealID($id)) {
            throw new Exception('Ошибка в Customers.php/getUserpicExt(). Неверный/несуществующий ID пользователя: ' . print_r($id));
        }

        try {
            $query = $this->select();
            $query->from('customers', array('userpic_ext'))->where('id = ?', $id);
            $userpic_ext = $this->_fetch($query);
        } catch(PDOException $e) {
            throw new Exception('Ошибка в Customers.php/getUserpicExt(): ' . $e->getMessage());
        } catch(Exception $e) {
            throw new Exception('Ошибка в Customers.php/getUserpicExt(): ' . $e->getMessage());
        }

        if(!is_array($userpic_ext)) {
            throw new Exception('Ошибка в Customers.php/getUserpicExt(). Неправильный тип переменной. Содержимое: ' . print_r($userpic_ext));
        }

        return $userpic_ext[0]['userpic_ext'];
    }

}