<?php

require '../library/Zend/Validate/Abstract.php';

class Validator_Expiration_Time extends Zend_Validate_Abstract
{
    const TOO_FAR_IN_FUTURE      = 'expTimeTooFarInFuture';
    const ALREADY_PAST = 'expTimeAlreadyPast';
    const INVALID = 'expTimeInvalid';

    protected $_messageTemplates = array(
        self::TOO_FAR_IN_FUTURE      => "Указанная дата находится чересчур далеко в будущем. Не дальше 5 лет, пожалуйста.",
        self::ALREADY_PAST      => "Указанная дата уже прошла.",
        self::INVALID      => "Неправильный формат даты. Правильный формат: 2013-12-31 23:59:59",
    );


    /**
     * Производит валидацию времени. Разрешает только дату/время
     * не раньше, чем текущие, и не позже, чем через 5 лет.
     * @param  string  $dateTime Дата и время в формате "Y-m-d H:i:s"
     * @return boolean           Валидно ли время.
     */
    function isValid($dateTime) {
        $dateTimeInUnixTime = strtotime($dateTime);
        $nowInUnixTime = strtotime(date("Y-m-d H:i:s"));
        $nowPlus5Years = $nowInUnixTime + 157784630;

        if (!$dateTimeInUnixTime) {
            $this->_error(self::INVALID);
            return false;
        }

        if($dateTimeInUnixTime < $nowInUnixTime){
            $this->_error(self::ALREADY_PAST);
            return false;
        }

        if($dateTimeInUnixTime > $nowPlus5Years) {
            $this->_error(self::TOO_FAR_IN_FUTURE);
            return false;
        };

        return true;

    }

}
