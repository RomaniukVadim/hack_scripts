<?PHP
#
class db {

    var $db_type;
    var $db_server;
    var $db_name;
    var $db_user;
    var $db_pass;
    var $db_persistent;
    var $dbh;

    function db() {
//заплоните настройки соединения с базой данных mysql (узнайте у хостера параметры подключения)
        $this->db_server = "localhost"; //адрес базы данных
        $this->db_name = "a0181684_belka"; //имя базы данных
        $this->db_user = "a0181684_belka"; //имя пользователя
        $this->db_pass = "Sharuhanchik"; //пароль к базе данных
//конец настроек
        $this->db_type = 1; //1 - mysql
        $this->db_persistent = 0; //постоянное соединение с базой
        $this->db_connect();

    } //end constructor

    function db_connect () {

        // mySQL
        if($this->db_type == 1) {
            if ($this->db_persistent)
                $this->dbh = @mysql_pconnect($this->db_server, $this->db_user, $this->db_pass);
            else
                $this->dbh = @mysql_connect($this->db_server, $this->db_user, $this->db_pass);

            if (!$this->dbh)
                die("Error: Connection to MySQL server");

            if (!@mysql_select_db($this->db_name, $this->dbh))
              die("Error: Connection to MySQL database");
        }
        //end mySQL
    } //end db_connect()

    function db_query ($query) {

        // mySQL
        if($this->db_type == 1) {
            $result = mysql_query($query, $this->dbh)or die(mysql_error());

            return $result;
        }
        //end mySQL
    } //end db_query()

    function db_numrows (&$result) {

        switch($this->db_type) {
            case 1: //mySQL
                return mysql_num_rows($result);

        } //end switch
    } // end db_numrows()

    function db_fetch_array (&$result) {

        switch($this->db_type) {
            case 1: //mySQL
                return mysql_fetch_array($result);
        } //end switch
    } //end db_fetch_array()


 function db_fetch_row (&$result) {

        switch($this->db_type) {
            case 1: //mySQL
                return mysql_fetch_row($result);
        } //end switch
    } //end db_fetch_array()
    function db_insert_id() {

        switch($this->db_type) {
            case 1: //mySQL
                return mysql_insert_id($this->dbh);
        } //end switch
    } //end db_insert_id()

    //перевод суммы в число с двумя десятичными знаками
    function to_float($sum) {
      if (strpos($sum, ".")) {
        $sum = round($sum, 2);
      } else {
        $sum = $sum.".0";
      }
      return $sum;
    }

} //end class db

$db = new db();
?>