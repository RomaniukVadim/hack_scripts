<?php
/** Amiss initializer
 */
class Amiss {
    static protected $_amiss;

    /** Get Amiss singleton
     * @return Amiss
     */
    static function singleton(){
        if (is_null(static::$_amiss))
            static::$_amiss = new static();
        return static::$_amiss;
    }

    /** Connector
     * @var Amiss\Connector
     */
    public $conn;

    /** Mapper (Annotations)
     * @var Amiss\Mapper\Note
     */
    public $map;

    /** Manager
     * @var Amiss\Manager
     */
    public $man;

    protected function __construct(){
        require_once __DIR__."/src/Loader.php";

        Amiss\Loader::register();

        # Connector
        $dsn = sprintf('mysql:host=%s;dbname=%s;port=%d', $GLOBALS['config']['mysql_host'], $GLOBALS['config']['mysql_db'], 3306);
        $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES utf8;';
        $this->conn = new Amiss\Connector($dsn, $GLOBALS['config']['mysql_user'], $GLOBALS['config']['mysql_pass'], $options);

        # Mapper
        $this->map = new Amiss\Mapper\Note; # Annotation mapper
        $this->map->objectNamespace = 'Citadel\\Models';

        # Register custom handlers
        require_once __DIR__.'/ext/Type/Serialized.php';
        require_once __DIR__.'/ext/Type/JSON.php';
        require_once __DIR__.'/ext/Type/ReportReference.php';
        require_once __DIR__.'/ext/Type/StupidSQLarray.php';
        require_once __DIR__.'/ext/Type/IntTimestamp.php';
        $this->map->addTypeHandler(new Amiss\Type\Date, array('datetime', 'timestamp'));
        $this->map->addTypeHandler(new AmissExt\Type\Serialized(), array('serialized'));
        $this->map->addTypeHandler(new AmissExt\Type\JSON(), array('json'));
        $this->map->addTypeHandler(new AmissExt\Type\ReportReference(), array('report_ref'));
        $this->map->addTypeHandler(new AmissExt\Type\StupidSQLarray(), array('stupidsqlarray'));
        $this->map->addTypeHandler(new AmissExt\Type\IntTimestamp(), array('int-timestamp'));

        # Manager
        $this->man = new Amiss\Manager($this->conn, $this->map); # Entity manager

        # Models
        require_once __DIR__.'/models/all.php';
        require_once __DIR__.'/models/neurostat.php';
        require_once __DIR__.'/models/tokenspy.php';
    }

    /** Create an object using custom input
     * @param \Amiss\Meta $meta
     *      Meta object for the class.
     *      $this->man->getMeta('Model');
     * @param array $row
     *      Assoc data array from the DB
     * @return object
     */
    function makeObject(\Amiss\Meta $meta, array $row){
        $object = $this->map->createObject($meta, $row);
        $this->map->populateObject($meta, $object, $row);
        return $object;
    }

    /** Load array of models from a custom statement
     * @param string $model
     * @param PDOStatement $q
     * @param string[]|null $extraFields
     *      List of extra field names to copy
     */
    function loadObjects($model, \PDOStatement $q, $extraFields = null){
        $meta = $this->man->getMeta($model);

        $objects = array();
        while ($row = $q->fetch(\PDO::FETCH_ASSOC)){
            $objects[] = $object = $this->makeObject($meta, $row);

            # Add extra fields
            if (!empty($extraFields))
                foreach ($extraFields as $n)
                    $object->$n = $row[$n];
        }

        return $objects;
    }

    /** CRUD helper method
     * SELECT:  $id = 1;
     * INSERT:  $data = array(...)
     * REPLACE: $id = 1, $data = array(...)
     * DELETE:  $id = 1, $del = true
     * @param mixed|null $id
     * @param array|null $data
     * @param bool $del
     * @throws \Exception
     * @return object|null
     */
    function crudHelper($className, $id = null, $data = null, $del = false){
        $man = $this->man;

        # Delete?
        if ($del) {
            $man->deleteByPk($className, $id);
            return null;
        }

        # Create | Load
        $Obj = $id? $man->getByPk($className, $id) : new $className;
        if (!$Obj)
            throw new \Exception('Not found by pk');

        # Save?
        if (!empty($data)){
            foreach ($man->getMeta($className)->getColumnToPropertyMap() as $p)
                if (isset($data[$p]))
                    $Obj->$p = $data[$p];
            $man->save($Obj);
        }

        return $Obj;
    }
}
