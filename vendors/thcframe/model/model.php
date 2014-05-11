<?php

namespace THCFrame\Model;

use THCFrame\Core\Base as Base;
use THCFrame\Registry\Registry as Registry;
use THCFrame\Core\Inspector as Inspector;
use THCFrame\Core\StringMethods as StringMethods;
use THCFrame\Model\Exception as Exception;

/**
 * Description of Model
 *
 * @author Tomy
 */
class Model extends Base
{

    /**
     * @readwrite
     */
    protected $_table;

    /**
     * @readwrite
     */
    protected $_alias = '';

    /**
     * @readwrite
     */
    protected $_connector;

    /**
     * @read
     */
    protected $_types = array(
        'auto_increment',
        'text',
        'integer',
        'tinyint',
        'decimal',
        'boolean',
        'datetime',
    );

    /**
     * @read
     */
    protected $_validators = array(
        'required' => array(
            'handler' => '_validateRequired',
            'message' => 'The {0} field is required'
        ),
        'alpha' => array(
            'handler' => '_validateAlpha',
            'message' => 'The {0} field can only contain letters'
        ),
        'numeric' => array(
            'handler' => '_validateNumeric',
            'message' => 'The {0} field can only contain numbers'
        ),
        'alphanumeric' => array(
            'handler' => '_validateAlphaNumeric',
            'message' => 'The {0} field can only contain letters and numbers'
        ),
        'max' => array(
            'handler' => '_validateMax',
            'message' => 'The {0} field must contain less than {2} characters'
        ),
        'min' => array(
            'handler' => '_validateMin',
            'message' => 'The {0} field must contain more than {2} characters'
        ),
        'email' => array(
            'handler' => '_validateEmail',
            'message' => 'The {0} field must contain valid email address'
        ),
        'url' => array(
            'handler' => '_validateUrl',
            'message' => 'The {0} field must contain valid url'
        ),
        'datetime' => array(
            'handler' => '_validateDatetime',
            'message' => 'The {0} field must contain valid date and time (yyyy-mm-dd hh:mm)'
        ),
        'date' => array(
            'handler' => '_validateDate',
            'message' => 'The {0} field must contain valid date (yyyy-mm-dd)'
        ),
        'time' => array(
            'handler' => '_validateTime',
            'message' => 'The {0} field must contain valid time (hh:mm / hh:mm:ss)'
        ),
        'html' => array(
            'handler' => '_validateHtml',
            'message' => 'The {0} field can contain these tags only (span,strong,em,s,p,div,a,ol,ul,li,img,table,caption,thead,tbody,tr,td)'
        ),
        'path' => array(
            'handler' => '_validatePath',
            'message' => 'The {0} field can contain filesystem path'
        )
    );

    /**
     * @read
     */
    protected $_errors = array();
    protected $_columns;
    protected $_primary;

    /**
     * 
     * @param type $method
     * @return \THCFrame\Model\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }

    /**
     * 
     * @param type $where
     * @return type
     */
    protected function _count($where = array())
    {
        $query = $this
                ->connector
                ->query()
                ->from($this->table);

        foreach ($where as $clause => $value) {
            $query->where($clause, $value);
        }

        return $query->count();
    }

    /**
     * 
     * @param type $value
     * @return type
     */
    protected function _validateRequired($value)
    {
        return !empty($value);
    }

    /**
     * 
     * @param type $value
     * @return type
     */
    protected function _validateAlpha($value)
    {
        if ($value == '') {
            return true;
        } else {
            return StringMethods::match($value, '#^([a-zA-Zá-žÁ-Ž_-\s\?\.,!]*)$#');
        }
    }

    /**
     * 
     * @param type $value
     * @return type
     */
    protected function _validateNumeric($value)
    {
        if ($value == '') {
            return true;
        } else {
            return StringMethods::match($value, '#^([0-9-]*)$#');
        }
    }

    /**
     * 
     * @param type $value
     * @return type
     */
    protected function _validateAlphaNumeric($value)
    {
        if ($value == '') {
            return true;
        } else {
            return StringMethods::match($value, '#^([a-zA-Zá-žÁ-Ž0-9_-\s\?\.,!:()+=\"&@\*]*)$#');
        }
    }

    /**
     * 
     * @param type $value
     * @return type
     */
    protected function _validateHtml($value)
    {
        if ($value == '') {
            return true;
        } else {
            return StringMethods::match($value, '#(<|&lt;)(strong|em|s|p|div|a|img|table|tr|td|thead|tbody|ol|li|ul|caption|span)(.*)(>|&gt;)'
                            . '([a-zA-Zá-žÁ-Ž0-9_-\s\?\.,!:()+=\"&@\*]*)</\2>#');
        }
    }

    protected function _validatePath($value)
    {
        if ($value == '') {
            return true;
        } else {
            return StringMethods::match($value, '#^([a-zA-Z0-9_-\\\/:]*)\.([a-z]{2,4})$#');
        }
    }

    /**
     * 
     * @param type $value
     * @param type $number
     * @return type
     */
    protected function _validateMax($value, $number)
    {
        return strlen($value) <= (int) $number;
    }

    /**
     * 
     * @param type $value
     * @param type $number
     * @return type
     */
    protected function _validateMin($value, $number)
    {
        return strlen($value) >= (int) $number;
    }

    /**
     * 
     * @param type $value
     * @return type
     */
    protected function _validateEmail($value)
    {
        if ($value == '') {
            return true;
        } else {
            return filter_var($value, FILTER_VALIDATE_EMAIL);
        }
    }

    /**
     * 
     * @param type $value
     * @return type
     */
    protected function _validateUrl($value)
    {
        return filter_var($value, FILTER_VALIDATE_URL);
    }

    /**
     * 
     * @param type $value
     * @return type
     */
    protected function _validateDatetime($value)
    {
        list($date, $time) = explode(' ', $value);

        $validDate = $this->_validateDate($date);
        $validTime = $this->_validateTime($time);

        if ($validDate && $validTime) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 
     * @param type $value
     * @return boolean 
     */
    protected function _validateDate($value)
    {
        $format = Registry::get('dateformat');

        if (strlen($value) >= 6 && strlen($format) == 10) {

            $separator_only = str_replace(array('m', 'd', 'y'), '', $format);
            $separator = $separator_only[0]; // separator is first character 

            if ($separator && strlen($separator_only) == 2) {
                $regexp = str_replace('mm', '(0?[1-9]|1[0-2])', $format);
                $regexp = str_replace('dd', '(0?[1-9]|[1-2][0-9]|3[0-1])', $regexp);
                $regexp = str_replace('yyyy', '(19|20)?[0-9][0-9]', $regexp);
                //$regexp = str_replace($separator, "\\" . $separator, $regexp);

                if ($regexp != $value && preg_match('/' . $regexp . '\z/', $value)) {
                    $arr = explode($separator, $value);
                    $day = $arr[2];
                    $month = $arr[1];
                    $year = $arr[0];

                    if (@checkdate($month, $day, $year)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * 
     * @param type $value
     * @return type
     */
    protected function _validateTime($value)
    {
        return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $value);
    }

    /**
     * 
     * @param type $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);
        $this->load();
    }

    /**
     * 
     * @throws Exception\Primary
     */
    public function load()
    {
        $primary = $this->primaryColumn;

        $raw = $primary['raw'];
        $name = $primary['name'];

        if (!empty($this->$raw)) {
            $previous = $this->connector
                    ->query()
                    ->from($this->table)
                    ->setTableAlias($this->alias)
                    ->where("{$name} = ?", $this->$raw)
                    ->first();

            if ($previous == null) {
                throw new Exception\Primary('Primary key value invalid');
            }

            foreach ($previous as $key => $value) {
                $prop = "_{$key}";
                if (!empty($previous->$key) && !isset($this->$prop)) {
                    $this->$key = $previous->$key;
                }
            }
        }
    }

    /**
     * 
     * @return type
     */
    public function delete()
    {
        $primary = $this->primaryColumn;

        $raw = $primary['raw'];
        $name = $primary['name'];

        if (!empty($this->$raw)) {
            $this->connector->beginTransaction();

            $state = $this->connector
                    ->query()
                    ->from($this->table)
                    ->where("{$name} = ?", $this->$raw)
                    ->delete();

            if ($state != -1) {
                $this->connector->commitTransaction();
                return $state;
            } else {
                $this->connector->rollbackTransaction();
                return $state;
            }
        }
    }

    /**
     * 
     * @param type $where
     * @return type
     */
    public static function deleteAll($where = array())
    {
        $instance = new static();

        $query = $instance->connector
                ->query()
                ->from($instance->table);

        foreach ($where as $clause => $value) {
            $query->where($clause, $value);
        }

        $instance->connector->beginTransaction();

        $state = $query->delete();

        if ($state != -1) {
            $instance->connector->commitTransaction();
            return $state;
        } else {
            $instance->connector->rollbackTransaction();
            return $state;
        }
    }

    /**
     * 
     */
    public function preSave()
    {
        
    }

    /**
     * 
     * @return type
     */
    public function save()
    {
        $this->preSave();

        $primary = $this->primaryColumn;

        $raw = $primary['raw'];
        $name = $primary['name'];

        $query = $this->connector
                ->query()
                ->from($this->table);

        if (!empty($this->$raw)) {
            $query->where("{$name} = ?", $this->$raw);
        }

        $data = array();
        foreach ($this->columns as $key => $column) {
            if (!$column['read']) {
                $prop = $column['raw'];
                $data[$key] = $this->$prop;
                continue;
            }

            if ($column != $this->primaryColumn && $column) {
                $method = 'get' . ucfirst($key);
                $data[$key] = $this->$method();
                continue;
            }
        }

        $result = $query->save($data);

        if ($result > 0) {
            $this->$raw = $result;
        }

        $this->postSave();

        return $result;
    }

    /**
     * 
     */
    public function postSave()
    {
        
    }

    /**
     * 
     * @return type
     */
    public function getTable()
    {
        if (empty($this->_table)) {
            list($module, $type, $name) = explode('_', get_class($this));

            if (strtolower($type) == 'model' && !empty($name)) {
                $tablePrefix = Registry::get('config')->database->default->tablePrefix;
                $this->_table = strtolower($tablePrefix . $name);
            } else {
                throw new Exception\Implementation('Model is not valid THCFrame\Model\Model');
            }
        }

        return $this->_table;
    }

    /**
     * 
     * @return type
     * @throws Exception\Connector
     */
    public function getConnector()
    {

        if (empty($this->_connector)) {
            $database = Registry::get('database');

            if (!$database) {
                throw new Exception\Connector('No connector availible');
            }

            $this->_connector = $database->initialize();
        }

        return $this->_connector;
    }

    /**
     * 
     * @return type
     * @throws Exception\Type
     * @throws Exception\Primary
     */
    public function getColumns()
    {
        if (empty($this->_columns)) {
            $primaries = 0;
            $columns = array();
            $class = get_class($this);
            $types = $this->_types;

            $inspector = new Inspector($this);
            $properties = $inspector->getClassProperties();

            $first = function($array, $key) {
                if (!empty($array[$key]) && count($array[$key]) == 1) {
                    return $array[$key][0];
                }
                return null;
            };

            foreach ($properties as $property) {
                $propertyMeta = $inspector->getPropertyMeta($property);

                if (!empty($propertyMeta['@column'])) {
                    $name = preg_replace('#^_#', '', $property);
                    $primary = !empty($propertyMeta['@primary']);
                    $type = $first($propertyMeta, '@type');
                    $length = $first($propertyMeta, '@length');
                    $index = !empty($propertyMeta['@index']);
                    $unique = !empty($propertyMeta['@unique']);
                    $readwrite = !empty($propertyMeta['@readwrite']);
                    $read = !empty($propertyMeta['@read']) || $readwrite;
                    $write = !empty($propertyMeta['@write']) || $readwrite;

                    $validate = !empty($propertyMeta['@validate']) ? $propertyMeta['@validate'] : false;
                    $label = $first($propertyMeta, '@label');

                    if (!in_array($type, $types)) {
                        throw new Exception\Type(sprintf('%s is not a valid type', $type));
                    }

                    if ($primary) {
                        $primaries++;
                    }

                    $columns[$name] = array(
                        'raw' => $property,
                        'name' => $name,
                        'primary' => $primary,
                        'type' => $type,
                        'length' => $length,
                        'index' => $index,
                        'unique' => $unique,
                        'read' => $read,
                        'write' => $write,
                        'validate' => $validate,
                        'label' => $label
                    );
                }
            }

            if ($primaries !== 1) {
                throw new Exception\Primary(sprintf('%s must have exactly one @primary column', $primary));
            }

            $this->_columns = $columns;
        }

        return $this->_columns;
    }

    /**
     * 
     * @param type $name
     * @return null
     */
    public function getColumn($name)
    {
        if (!empty($this->_columns[$name])) {
            return $this->_columns[$name];
        }
        return null;
    }

    /**
     * 
     * @return type
     */
    public function getPrimaryColumn()
    {
        if (!isset($this->_primary)) {
            $primary;

            foreach ($this->columns as $column) {
                if ($column['primary']) {
                    $primary = $column;
                    break;
                }
            }

            $this->_primary = $primary;
        }

        return $this->_primary;
    }

    /**
     * 
     * @param type $where
     * @param type $fields
     * @param type $order
     * @param type $direction
     * @return type
     */
    public static function first($where = array(), $fields = array('*'), $order = array())
    {
        $model = new static();
        return $model->_first($where, $fields, $order);
    }

    /**
     * 
     * @param type $where
     * @param type $fields
     * @param type $order
     * @param type $direction
     * @return \THCFrame\class|null
     */
    protected function _first($where = array(), $fields = array('*'), $order = array())
    {
        $query = $this->connector
                ->query()
                ->from($this->table, $fields)
                ->setTableAlias($this->alias);

        foreach ($where as $clause => $value) {
            $query->where($clause, $value);
        }

        if (!empty($order)) {
            foreach ($order as $filed => $direction) {
                $query->order($filed, $direction);
            }
        }

        $first = $query->first();
        $class = get_class($this);

        if ($first) {
            return new $class($query->first());
        }

        return null;
    }

    /**
     * 
     * @param type $where
     * @param type $fields
     * @param type $order
     * @param type $direction
     * @param type $limit
     * @param type $page
     * @return type
     */
    public static function all($where = array(), $fields = array('*'), $order = array(), $limit = null, $page = null, $group = null, $having = array())
    {
        $model = new static();
        return $model->_all($where, $fields, $order, $limit, $page, $group, $having);
    }

    /**
     * 
     * @param type $where
     * @param type $fields
     * @param type $order
     * @param type $direction
     * @param type $limit
     * @param type $page
     * @return \THCFrame\class
     */
    protected function _all($where = array(), $fields = array('*'), $order = array(), $limit = null, $page = null, $group = null, $having = array())
    {
        $query = $this->connector
                ->query()
                ->from($this->table, $fields)
                ->setTableAlias($this->alias);

        foreach ($where as $clause => $value) {
            $query->where($clause, $value);
        }

        if ($group != null) {
            $query->groupby($group);

            if (!empty($having)) {
                foreach ($having as $clause => $value) {
                    $query->having($clause, $value);
                }
            }
        }

        if (!empty($order)) {
            foreach ($order as $filed => $direction) {
                $query->order($filed, $direction);
            }
        }

        if ($limit != null) {
            $query->limit($limit, $page);
        }

        $rows = array();
        $class = get_class($this);

        foreach ($query->all() as $row) {
            $rows[] = new $class($row);
        }

        return $rows;
    }

    /**
     * 
     * @return type
     */
    public static function getQuery($fields)
    {
        $model = new static();
        return $model->_getQuery($fields);
    }

    /**
     * 
     * @return type
     */
    protected function _getQuery($fields)
    {
        return $this->connector
                        ->query()
                        ->from($this->table, $fields)
                        ->setTableAlias($this->alias);
    }

    /**
     * 
     * @param \THCFrame\Database\Query $query
     * @return \THCFrame\Model\class
     */
    public static function initialize(\THCFrame\Database\Query $query)
    {
        $model = new static();
        $rows = array();
        $class = get_class($model);

        foreach ($query->all() as $row) {
            $rows[] = new $class($row);
        }

        if (empty($rows)) {
            return null;
        } else {
            return $rows;
        }
    }

    /**
     * 
     * @param type $where
     * @return type
     */
    public static function count($where = array())
    {
        $model = new static();
        return $model->_count($where);
    }

    /**
     * 
     * @return type
     * @throws Exception\Validation
     */
    public function validate()
    {
        $this->_errors = array();

        foreach ($this->columns as $column) {
            if ($column['validate']) {
                $pattern = '#[a-z]+\(([a-zA-Z0-9, ]+)\)#';

                $raw = $column['raw'];
                $name = $column['name'];
                $validators = $column['validate'];
                $label = $column['label'];

                $defined = $this->getValidators();

                foreach ($validators as $validator) {
                    $function = $validator;
                    $arguments = array(
                        $this->$raw
                    );

                    $match = StringMethods::match($validator, $pattern);

                    if (count($match) > 0) {
                        $matches = StringMethods::split($match[0], ',\s*');
                        $arguments = array_merge($arguments, $matches);
                        $offset = StringMethods::indexOf($validator, '(');
                        $function = substr($validator, 0, $offset);
                    }

                    if (!isset($defined[$function])) {
                        throw new Exception\Validation(sprintf('The %s validator is not defined', $function));
                    }

                    $template = $defined[$function];

                    if (!call_user_func_array(array($this, $template['handler']), $arguments)) {
                        $replacements = array_merge(array(
                            $label ? $label : $raw
                                ), $arguments);

                        $message = $template['message'];

                        foreach ($replacements as $i => $replacement) {
                            $message = str_replace("{{$i}}", $replacement, $message);
                        }

                        if (!isset($this->_errors[$name])) {
                            $this->_errors[$name] = array();
                        }

                        $this->_errors[$name][] = $message;
                    }
                }
            }
        }

        return !count($this->errors);
    }

}
