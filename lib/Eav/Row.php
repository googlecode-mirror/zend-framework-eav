<?php

require_once 'Zend/Db/Table/Row/Abstract.php';
require_once 'Eav/Row/Interface.php';

class Eav_Row extends Zend_Db_Table_Row_Abstract implements Eav_Row_Interface
{
    protected $_options = array();

    public function setOptionValue($optionId, $value)
    {
        $this->_options[$optionId] = $value;
    }

    public function hasOptionValue($optionId)
    {
        return isset($this->_options[$optionId]);
    }

    public function getOptionValue($optionId)
    {
        return $this->_options[$optionId];
    }
}