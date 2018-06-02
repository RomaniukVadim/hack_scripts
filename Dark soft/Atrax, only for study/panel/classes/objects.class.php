<?php

  require_once('mysql.class.php');
  require_once('plugin.class.php');
  require_once('template.class.php');
  require_once('config.php');

  class Objects {
      public $MySQL;
      public $Plugin;
      public $Template;
      public $Password;

      public function __construct() {
          $this->MySQL = new MySQL(HOST, USER, PASS, DB);
          $this->Template = new Template('standard.tpl');
          $this->Plugin = new Plugin();
          $this->Password = PASSWORD;
      }
  }