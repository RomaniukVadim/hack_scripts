<?php

namespace dbPDO\Schema;

/**
 * Database schema
 */
class Database {
    /** PDO connection
     * @var \PDO
     */
    protected $pdo;

    /** Database name
     * @var string|null
     */
    protected $dbname;

    /** Cached list of all table names
     * @var string[]
     */
    protected $_table_names = NULL;

    /** Table schema cache
     * @var Table[] array( table_name => Table )
     */
    protected $_tables = array();

    /** Database schema
     * @param \PDO $pdo
     * @param string|null $db_name
     *      Database name to work with. `null` will use the current database
     * @param bool $lazy
     */
    function __construct($pdo, $db_name = NULL){
        $this->pdo = $pdo;
        $this->dbname = $db_name;
    }

    /** Clear the internal cache of this object
     */
    function refresh(){
        $this->_table_names = array();
        $this->_tables = null;
    }

    /** Get the list of all tables in the database
     * @return string[]
     * @throws \PDOException
     */
    function tableNames(){
        if (is_null($this->_table_names)){
            $q = $this->pdo->query("SHOW TABLES ".(is_null($this->dbname)? '' : "FROM `{$this->dbname}`").";");
            $q->execute();
            $this->_table_names = $q->fetchAll(\PDO::FETCH_COLUMN, 0);
        }
        return $this->_table_names;
    }

    /** Get table schema by name
     * @param string $tableName
     * @return Table
     * @throws \PDOException
     */
    function table($tableName){
        if (!isset($this->_tables[$tableName])){
            # Fetch column info
            $q = $this->pdo->query("SHOW FULL COLUMNS FROM `{$tableName}` ".(is_null($this->dbname)? '' : "FROM `{$this->dbname}`").";");
            $q->execute();
            $fields = $q->fetchAll(\PDO::FETCH_OBJ);

            # Create the Table instance
            $this->_tables[$tableName] = new Table($this, $tableName, $fields);
        }
        return $this->_tables[$tableName];
    }
}



/**
 * Table schema
 */
class Table {
    /** Parent Database
     * @var Database
     */
    public $database;

    /** Name of this Table
     * @var string
     */
    public $name;

    /** The original fields info
     * @var object[]
     */
    protected $_fields_info = array();

    /** The original indexes info
     * @var object[]
     */
    #protected $_indexes_info = array(); # unused

    /** Fields schema
     * @var Field[]
     */
    protected $fields = array();

    /** Primary key members
     * @var string[]
     */
    public $pk = array();

    /**
     * @param Database $database
     *      The parent Database object
     * @param string $tableName
     *      The name of the table
     * @param object[] $fields
     *      The result of "SHOW COLUMNS" | "SHOW FULL COLUMNS" query
     */
    function __construct($database, $tableName, $fields){
        $this->database = $database;
        $this->tableName = $tableName;
        foreach ($fields as $field){
            $this->_fields_info[  $field->Field  ] = $field;
            # PK?
            if ($field->Key && strncasecmp($field->Key, 'PRI', 3) === 0)
                $this->pk[] = $field->Field;
        }
    }

    /** Get the list of all table keys
     * @return string[]
     */
    function fieldNames(){
        return array_keys($this->_fields_info);
    }

    /** Get field schema by name
     * @param string $fieldName
     * @return Field
     * @throws \PDOException
     */
    function field($fieldName){
        # Existence
        if (!isset($this->_fields_info[$fieldName]))
            throw new \PDOException("Field `{$this->tableName}`.`{$fieldName}` not found");

        # Process (Lazy)
        if (!isset($this->fields[$fieldName]))
            $this->fields[$fieldName] = new Field($this, $this->_fields_info[$fieldName]);

        # Return
        return $this->fields[$fieldName];
    }
}



/**
 * Table field schema
 */
class Field {
    /** Parent Table
     * @var Table
     */
    public $table;

    /** Name of this field
     * @var string
     */
    public $name;

    /** Field type, original: "int(11)"
     * @var string
     */
    public $type;

    /** Nullable?
     * @var bool
     */
    public $null;

    /** Default value
     * @var mixed|null
     */
    public $default;

    /** Extra data, original: "auto_increment"
     * @var string
     */
    public $extra;

    /** Field comment
     * @var string|null
     */
    public $comment;

    /**
     * @param Table $tableName
     *      The parent table
     * @param object $field_info
     *      A row from "SHOW COLUMNS" | "SHOW FULL COLUMNS"
     */
    function __construct($table, $field_info){
        $this->table = $table;
        $this->name = $field_info->Field;
        $this->type = $field_info->Type;
        $this->null = strncasecmp($field_info->Null, 'yes', 3) === 0;
        $this->default = $field_info->Default;
        $this->extra = $field_info->Extra;
        $this->comment = isset($field_info->Comment)? $field_info->Comment : null;
    }
}
