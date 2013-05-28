<?php

class Application_Model_DbTable_Groups extends Zend_Db_Table_Abstract
{

    protected $_name = 'groups';

    /**
     * Выдает все группы; Постранично, и с сортировкой;
     * @param  [INT]   $page             Страница, для которой нужно выдать результаты
     * @param  [STR]   $orderBy          Имя столбца, по которому будем сортировать
     * @param  [STR]   $orderDirection   Направление сортировки
     * @param  [INT]   $resultCount      Количество результатов
     * @return [ARR]                     Массив с результатами
     */
    public function fetchAllGroups($page = 1, $orderBy = 'id', $orderDirection = 'ASC', $resultCount = 10) {
        $tableColumns = $this->info(Zend_Db_Table_Abstract::COLS);
        array_push($tableColumns, 'memberCount');
        $page = (int)$page;
        $resultCount = (int)$resultCount;

        $query = $this->select();
        $query->setIntegrityCheck(false)
              ->from('groups', array('groups.id', 'groups.name', 'COUNT(customers.id) as memberCount'))
              ->joinLeft('customers', 'groups.id = customers.group_id', null)
              ->group('groups.name');

        if(in_array($orderBy, $tableColumns)) {
            $order = $orderBy . ' ';
            $order .= $orderDirection === 'ASC' ? 'ASC' : 'DESC';
            $query->order($order);
        };

        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbTableSelect($query));
        $paginator->setItemCountPerPage($resultCount);
        $paginator->setCurrentPageNumber($page);
        return $paginator;
    }

    /**
     * Запрос данных группы из БД
     * @param  [INT] $id    ID группы;
     * @return [ARR]        Массив данных группы, в случае успешного запроса. Иначе — Exception;
     */
    public function getGroup($id) {
        $id = (int)$id;

        if($id <= 0) {
            throw new Exception('Указан несуществующий ID группы: ' . $id . '. Groups.php/getGroup()');
        }

        $row = $this->fetchRow('id=' . $id);
        // Если запрос прошел удачно, в $row будет либо массив, либо объект
        if(is_array($row) || is_object($row)) {
            return $row->toArray();
        } else {
            throw new Exception('Группы с ID #' . $id . ' не существует.');
        }
    }

    /**
     * Добавляет новую группу
     * @param   [STR]   $login        Название группы, до 64 символов;
     * @return  [BOOL]  $name         True, если запрос прошел удачно, иначе — Exception;
     */
    public function addGroup($name) {
        $data = array(
            'name' => $name,
        );
        // Insert возвращает Primary Key новой записи, если запрос прошел удачно;
        // «parameter binding» производится «под капотом»
        $result = $this->insert($data);
        $result = (int)$result;
        if(is_int($result)) {
            return true;
        } else {
            throw new Exception('Не получилось добавить группу.' . $result);
        }
    }

    /**
     * Редактирует информацию группы по ID
     * @param   [INT]   $id        ID группы
     * @param   [STR]   $login     Название группы, до 64 символов;
     * @return  [BOOL]             True, при успехе; Иначе — Exception
     */
    public function editGroup($id, $name) {
        $id = (int)$id;

        if($id <= 0) {
            throw new Exception('Указан несуществующий ID группы: ' . $id . '. Groups.php/editGroup()');
        }

        $data = array(
            'name' => $name,
        );
        // функция update() возвращает количество затронутых рядов. Сохраняем его для проверки.
        // «parameter binding» производится «под капотом»
        $result = $this->update($data, 'id=' . $id);
        $result = (int)$result;
        if($result >= 0) {
            return true;
        } else {
            throw new Exception('Не получилось изменить информацию группы №' . $id . '. Вероятнее всего, ее не существует.');
        }
    }

    /**
     * Удаляет группы по ID
     * @param  [INT]    $id    ID группы;
     * @return [BOOL]          True, при успехе; Иначе — Exception
     */
    public function deleteGroup($id) {
        // (int) переваривает что угодно, даже буквы (в «0»). Так что, с безопасностью должно быть всё ок.
        $id = (int)$id;

        if($groupID <= 0) {
            throw new Exception('Указан несуществующий ID группы: ' . $id . '. Groups.php/deleteGroup()');
        }

        // функция delete() возвращает количество затронутых рядов. Сохраняем его для проверки.
        $rowsAffected = $this->delete('id=' . $id);
        if($rowsAffected === 1) {
            return true;
        } else {
            throw new Exception('Не получилось удалить группу №' . $id . '. Вероятнее всего, ее не существует.');
        }
    }

    public function getAllGroupsWithMembers() {
        $query = $this->select();
        $query->setIntegrityCheck(false)
              ->from('groups', array('groups.id', 'groups.name'))
              ->joinRight('customers', 'groups.id = customers.group_id', null)
              ->group('groups.name')
              ->order('name ASC');

        if(is_array($query) || is_object($query)) {
            return $this->_fetch($query);
        } else {
            throw new Exception('Ошибка при работе с DB, в функции getAllGroupsWithMembers()');
        }
    }

    public function getMemberCount($groupID) {
        $groupID = (int)$groupID;

        if($groupID <= 0) {
            throw new Exception('Указан несуществующий ID группы: ' . $groupID . '. Groups.php/getMemberCount()');
        }

        $query = $this->select();
        $query->setIntegrityCheck(false)
              ->from('groups', array('COUNT(customers.id) as memberCount'))
              ->joinLeft('customers', 'groups.id = customers.group_id', null)
              ->whereId($groupID);

        if(is_array($query) || is_object($query)) {
            return $this->_fetch($query);
        } else {
            throw new Exception('Ошибка при работе с DB, в функции getMemberCount()');
        }
    }
}