<?php
use dbPDO\Schema;

/** PDO wrapper
 *
 * PDO: http://www.phpro.org/tutorials/Introduction-to-PHP-PDO.html
 */
class dbPDO extends PDO {
    #region Singleton
    /** @var dbPDO */
    static protected $_connection = null;

    /** Singleton connection that uses using config.php
     * @return dbPDO
     */
    static function singleton(){
        if (is_null(static::$_connection)){
            $dsn = sprintf('mysql:host=%s;dbname=%s;port=%d', $GLOBALS['config']['mysql_host'], $GLOBALS['config']['mysql_db'], 3306);
            static::$_connection = new dbPDO($dsn, $GLOBALS['config']['mysql_user'], $GLOBALS['config']['mysql_pass']);
        }
        return static::$_connection;
    }

    /**
     * @var array ($mapper, $connector, $manager)
     */
    static protected $_amiss = null;

    /** Get Amiss singleton
     * @return array[] array($mapper, $connector, $manager)
     */
    static function amiss(){
        if (is_null(static::$_amiss)){
            require_once __DIR__."/amiss/src/Loader.php";
            Amiss\Loader::register();

            $connector = new Amiss\Connector('?');
            $connector->pdo = static::singleton(); # assign manually
            $mapper = new Amiss\Mapper\Note;
            $manager = new Amiss\Manager($connector, $mapper);

            static::$_amiss = array($mapper, $connector, $manager);
        }

        return static::$_amiss;
    }
    #endregion

    #region PDO
    /** Connect to the DB
     * @param string $dsn Connection DSN: 'mysql:host=localhost;dbname=test;port=3333', 'mysql:dbname=testdb;unix_socket=/path/to/socket'
     * @param string $username Login
     * @param string $passwd Password
     * @param array $options DB-specific options
     * @throws PDOException
     */
    function __construct($dsn = 'mysql:host=localhost;dbname=test', $username = null, $passwd = null, $options = array()) {
        if (strncasecmp($dsn, 'mysql:', 6) === 0)
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES utf8;';
        parent::__construct($dsn, $username, $passwd, $options);

        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /** Query Prepare-Bind-Execute shortcut
     * @param string $statement
     * @param array  $input_parameters
     * @return PDOStatement
     */
    function query($statement, array $input_parameters = array()){
        $query = $this->prepare($statement);
        $query->execute($input_parameters);
        return $query;
    }

    /** Prepared statement for found_rows
     * @var PDOStatement
     */
    protected $_fr_stmt = null;

    /** SELECT FOUND_ROWS() after query with SQL_CALC_FOUND_ROWS
     * @return int
     */
    function found_rows(){
        if (is_null($this->_fr_stmt))
            $this->_fr_stmt = $this->prepare('SELECT FOUND_ROWS();');
        $this->_fr_stmt->execute();
        $rows_count = $this->_fr_stmt->fetchColumn(0);
        $this->_fr_stmt->closeCursor();
        return $rows_count;
    }
    #endregion

    #region Helpers
    /** List all available report tables. Cached.
     * @return string[] array( yymmdd => botnet_reports_yymmdd, ... )
     */
    function report_tables(){
        static $dates = null;
        if (!is_null($dates)) return $dates; # Cache

        $dates = array();
        $R = mysql_query("SHOW TABLES LIKE 'botnet_reports_%';");
        while ($R && !is_bool($r = mysql_fetch_row($R)))
            $dates[ (int)substr($r[0], 15) ] = $r[0];
        return $dates;
    }
    #endregion

    #region dbPDO-Schema
    /** Database schema for the current DB
     * @var Schema\Database[]
     */
    protected $_db_schemas = array();

    /** Get the database Schema object for the current database
     * @param string|null $db_name
     *      Specify another database name to get the schema for it
     * @return Schema\Database
     */
    function schema($db_name = null){
        if (is_null($this->_db_schemas[$db_name]))
            $this->_db_schemas[$db_name] = new Schema\Database($this, $db_name);
        return $this->_db_schemas[$db_name];
    }
    #endregion
}
