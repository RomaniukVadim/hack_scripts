<?php
class db {
 public $link;
 
 public function __construct($host, $user, $password, $dbName) {
  $this->link = @mysqli_connect($host, $user, $password, $dbName) or die('Access Denied2');
  @mysqli_query($this->link, "SET NAMES utf8");
 }
 
 public function query($query = null) {
  return @mysqli_query($this->link, $query);
 }
 
 public function escape($query = null) {
  return @mysqli_real_escape_string($this->link, $query);
 }
 
 public function num($query = null) {
  return @mysqli_num_rows($query);
 }
 
 public function fetch($query = null) {
  return @mysqli_fetch_array($query);
 }
 
 public function assoc($query = null) {
  return @mysqli_fetch_assoc($query);
 }
 
 public function insert_id() {
  return @mysqli_insert_id($this->link);
 }
 
 public function error() {
  return @mysqli_error($this->link);
 }
 
 public function __destruct() {
  mysqli_close($this->link);
 }
}

$db = new db('localhost', 'almir', 'mirik50327', 'piar');

error_reporting(0);
?>