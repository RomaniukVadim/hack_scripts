<?php

  /*
   *  @author /-_-\
   *  @project liphyra
   */

  class MySQL {
      private $host;
      private $user;
      private $pass;
      private $db;
      private $query;
      private $connection;

      public function __construct($host, $user, $pass, $db) {
          $this->host = $host;
          $this->user = $user;
          $this->pass = $pass;
          $this->db = $db;
          
          $this->connection = mysql_connect($this->host, $this->user, $this->pass);

          if($this->connection) {
              mysql_select_db($this->db);
          }
      }

      public function doQuery($query) {
          $this->query = mysql_query($query);
		  
		  if (!$this->query) {
    die(mysql_error());
}
      }
	  
	  public function rowExists($guid) {
          
		$result = mysql_query("SELECT * FROM victims WHERE GUID='$guid'");
		$num_rows = mysql_num_rows($result);

		if ($num_rows > 0) 
		{
			return true;
		}
		else 
		{
			return false;
		}
      }

      public function arrayResult() {
          return mysql_fetch_array($this->query);
      }

      public function numResult() {
          return mysql_num_rows($this->query);
      }
	  

      public function freeResult() {
          return mysql_free_result($this->query);
      }

      public function closeConnection() {
          if(is_resource($this->connection)) {
              mysql_close($this->connection);
          }
      }

      public function escapeString($string) {
          return mysql_real_escape_string($string);
      }
  }