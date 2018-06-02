<?php

  /*
   *  @author /-_-\
   *  @project liphyra
   */

  class Plugin {
      private $MySQL;

      public function __construct() {
          $this->MySQL = new MySQL(HOST, USER, PASS, DB);
      }

      public function runPlugin($plugin) {
          if($this->isPluginInstalled($plugin)) {
              require_once('plugins/'.$plugin.'/main.php');
              $Object_Plugin = new main;
              return $Object_Plugin->run();
          } else {
              return 'Plugin not installed';
          }
      }

      public function DeInstallPlugin($plugin) {
          if($this->existPlugin($plugin)) {
              include('plugins/'.$plugin.'/de_install.php');
              $Object_Plugin = new de_install;
              $this->isPluginInstalled($plugin) ? $Object_Plugin->deinstall() : $Object_Plugin->install();
          }
      }

      public function updatePlugin($plugin) {
          if($this->existPlugin($plugin)) {
              $code = '';
              if( isset( $_POST['update_plugin'] )) {
                  if( !empty( $_FILES['32bit_binary']['tmp_name'] ) && !empty( $_FILES['64bit_binary']['tmp_name'] )) {
                      if( preg_match( '!application/.*!', $_FILES['32bit_binary']['type'] )) {
                          move_uploaded_file( $_FILES['32bit_binary']['tmp_name'], 'plugins/'.$plugin.'/bin/'.$plugin.'32' );
                          $code .= '32bit update successful! <br />';
                          $check = true;
                      } else
                          $code .= 'The file has to be a binary! <a href="'.$_SERVER['REQUEST_URI'].'"><u>&laquo; Try again</u></a>';

                      if( preg_match( '!application/.*!', $_FILES['64bit_binary']['type'] )) {
                          move_uploaded_file( $_FILES['64bit_binary']['tmp_name'], 'plugins/'.$plugin.'/bin/'.$plugin.'64' );
                          $code .= '64bit update successful! <br />';
                          $check = true;
                      } else
                          $code .= 'The file has to be a binary! <a href="'.$_SERVER['REQUEST_URI'].'"><u>&laquo; Try again</u></a>';

                      if( isset( $check ))
                          $code .= '<a href="index.php?action=plugins"><u>All done. Back to Overview &raquo; </u></a>';
                  } else {
                      $code .= 'You have to select a 32bit binary as well as a 64bit one! <a href="'.$_SERVER['REQUEST_URI'].'"><u>&laquo; Try again</u></a>';
                  }
              } else {
                  $code .= '<form action="" method="post" enctype="multipart/form-data">';
                  $code .= '<table id="tablecss">';
                  $code .= '<tr><th colspan="2">Update Plugin</th></tr>';
                  $code .= '<tr><td><b>32bit Binary:</b></td><td><input type="file" name="32bit_binary" /></td></tr>';
                  $code .= '<tr><td><b>64bit Binary:</b></td><td><input type="file" name="64bit_binary" /></td></tr>';
                  $code .= '<tr><td colspan="2"><input type="submit" name="update_plugin" value="Upload & Update" /></td></tr>';
                  $code .= '</table></form>';
              }

              return $code;
          }
          else
              echo "Plugin doesn't exist!";
      }

      public function existPlugin($plugin) {
          return file_exists('plugins/'.$plugin.'/main.php') ? true : false;
      }

      public function isPluginInstalled($plugin) {
          if(file_exists('plugins/'.$plugin.'/info.php')) {
              include('plugins/'.$plugin.'/info.php');
              $PluginName = $this->extractName($PLUGIN_NAME);
              $this->MySQL->doQuery('SHOW TABLES LIKE \'plugin_'.$PluginName.'\'');
              return $this->MySQL->numResult() ? true : false;
          } else {
              return false;
          }
      }

      public function extractName($plugin) {
          return str_replace(' ', '', trim(strtolower($plugin)));
      }
  }