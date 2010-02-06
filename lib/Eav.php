<?php

require_once 'Eav/Row.php';

/**
 * Eav class
 *
 * @author Taras Taday
 */
class Eav
{
    /*
     * Name of primary table
     */
    protected $_entityTableName = 'eav_entity';
    protected $_entityFieldId = 'id';

    /*
     * Name of option table
     */
    protected $_optionTableName = 'eav_option';
    protected $_optionFieldId   = 'id';
    protected $_optionFieldType = 'type';
    protected $_optionFieldName = 'name';

    /**
     * Options model
     * @var Zend_Db_Table_Abstract
     */
    protected $_optionModel;
    protected $_options;

    protected $_cache = false;
    protected $_cacheData = array();

    /*
     * Eav model objects
     */
    protected $_eavModels = array();

    public function  __construct(Zend_Db_Table_Abstract $table)
    {
        $this->_entityTableName = $this->getTableName($table);
        $this->_entityModel = $table;
        $this->_optionModel = new Zend_Db_Table($this->_optionTableName);
    }

    public function getTableName($table)
    {
        return $table->info('name');
    }
    
    public function getEavTableName($type)
    {
        return $this->_entityTableName . '_' . strtolower($type);
    }

    public function getEavModel($option)
    {
        $type = $this->getOptionType($option);
        if (!isset($this->_eavModels[$type])) {
            $eavTable = new Zend_Db_Table($this->getEavTableName($type));
            $this->_eavModels[$type] = $eavTable;
        }

        return $this->_eavModels[$type];
    }

    public function getEavModels($options)
    {
        $models = array();
        foreach ($options as $option) {
            $type = $this->getOptionType($option);
            if (!isset($models[$type])) {
                $models[$type] = $this->getEavModel($option);
            }
        }
        return $models;
    }

    public function getOptionType($option)
    {
        return $option->{$this->_optionFieldType};
    }

    public function getOptionId($option)
    {
        return $option->{$this->_optionFieldId};
    }

    public function getOptionName($option)
    {
        return $option->{$this->_optionFieldName};
    }

    public function getEntityId($row)
    {
        return $row->{$this->_entityFieldId};
    }

    public function getOption($id)
    {
        if (isset($this->_options[$id])) {
            return $this->_options[$id];
        } elseif (is_numeric($id)) {
            $option = $this->_optionModel->find($id)->current();
        } else {
            $where = $this->_optionModel->select()->where($this->_optionFieldName . ' = ?', $id);
            $option = $this->_optionModel->fetchRow($where);
        }
        $this->cacheOption($option);
        return $option;
    }

    public function cacheOption($option)
    {
        $this->_options[$this->getOptionId($option)] = $option;
        $this->_options[$this->getOptionName($option)] = $option;
    }

    public function cacheOptions($options)
    {
        foreach ($options as $option) {
            $this->cacheOption($option);
        }
    }

    /**
     * Return option value
     * 
     * @param Zend_Db_Table_Row $row entity object
     * @param Zend_Db_Table_Row $option option object
     * @param boolean $reload set true to force reload entity value
     * @return mixed
     */
    public function getOptionValue($row, $option, $reload = false)
    {
        if (is_string($option)) {
            $option = $this->getOption($option);
        }
        $optionId = $this->getOptionId($option);
        if (!$reload && $row instanceof Eav_RowInterface && $row->hasOptionValue($optionId)) {
            return $row->getOptionValue($optionId);
        }
        $eavModel = $this->getEavModel($option);
        $where = $eavModel->select()
                          ->where('entity_id = ?', $this->getEntityId($row))
                          ->where('option_id = ?', $optionId);
        $valueRow = $eavModel->fetchRow($where);

        $value = $valueRow ? $valueRow->value : '';

        if ($row instanceof Eav_RowInterface) {
            $row->setOptionValue($optionId, $value);
        }

        return $value;
    }

    /**
     * Set option value
     * 
     * @param Zend_Db_Table_Row $row entity object
     * @param Zend_Db_Table_Row $option option object
     * @param mixed $value option value
     */
    public function setOptionValue($row, $option, $value)
    {
        $eavModel = $this->getEavModel($option);
        $rows = $eavModel->find($this->getOptionId($option));
        if ($rows->valid()) {
            $row = $rows->current();
        } else {
            $row = $eavModel->creatRow();
            $row->option_id = $this->getOptionId($option);
            $row->entity_id = $this->getEntityId($row);
        }

        $row->value = $value;
        $row->save();
        if ($row instanceof Eav_RowInterface) {
            $row->setOptionValue($option, $value);
        }
    }

    /**
     * Load options values with single query
     * 
     * @param Zend_Db_Table_Rowset $rows
     * @param Zend_Db_Table_Rowset $options
     * @return array
     */
    public function loadOptions($rows, $options)
    {
        if (!$rows->valid()) {
            return;
        }
        $this->cacheOptions($options);

        $result = array();
        $entities = array();
        foreach ($rows as $row) {
            $entities[$this->getEntityId($row)] = $row;
        }

        $eavModels = $this->getEavModels($options);

        $queries = array();
        $entityIds = array_keys($entities);
        foreach ($eavModels as $type => $eavModel) {
            $select = $eavModel->select();
            $select->where('entity_id IN(?)', $entityIds);

            $optionIds = array();
            foreach ($options as $option) {
                if ($type == $this->getOptionType($option)) {
                    $optionIds = $this->getOptionId($option);
                }
            }
            $select->where('option_id IN(?)', $optionIds);
            $queries[] = $select->__toString();
        }

        /* build query */
        $query = '(' . implode(') UNION ALL (', $queries) . ')';

        $db = Zend_Db_Table::getDefaultAdapter();
        $rows = $db->fetchAll($query);
        foreach ($rows as $row) {
            $optionId = $this->getOptionId($this->getOption($row['option_id']));
            $entities[$row['entity_id']]->setOptionValue($optionId, $row['value']);
        }
        return $rows;
    }
}