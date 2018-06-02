<?php

  /*
   *  @author /-_-\
   *  @project liphyra
   */

  class Template {
      private $file;
      private $values = array();

      public function __construct($file) {
          $this->file = 'style/templates/'.$file;
      }

      public function set($key, $value) {
          $this->values[$key] = $value;
      }

      public function result() {
          if(!file_exists($this->file)) {
              return 'Template "'.$this->file.' " konnte nicht gefunden werden..';
          }

          $result = file_get_contents($this->file);

          preg_match_all('/%(.*)%/', $result, $file);
          foreach($file[1] as $files){
              $result = str_replace('%'.$files.'%', file_get_contents('style/'.$files), $result);
          }

          foreach ($this->values as $key => $value) {
              $result = str_replace('{'.$key.'}', $value, $result);
          }

          while(preg_match_all('/{(.*)}/', $result, $file)) {
              $result = str_replace($file[0][0], '', $result);
          }

          return $result;
      }
  }