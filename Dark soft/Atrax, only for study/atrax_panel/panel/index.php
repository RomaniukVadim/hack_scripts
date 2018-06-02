<?php

  if(isset($_GET['action'])) {
      require_once('classes/core.class.php');

      session_start();
      
      $Core = new Core();
      $Content = '';
      
      switch ($_GET['action']) {
          case 'statistics':
              if(isset($_GET['pluginstats'])) {
                  $Content = $Core->getPluginStatistic();
              } else {
                  $Content = $Core->getStatistics();
              }

              break;
          case 'tasks':
              if(isset($_GET['addtask'])) {
                  $Content = $Core->setTask();
              } elseif(isset($_GET['deltask'])) {
                  $Core->deleteTask($_GET['deltask']);
              } elseif(isset($_GET['edittask'])) {
                  $Content = $Core->editTask($_GET['edittask']);
              } elseif(isset($_GET['delalltasks'])) {
                  $Content = $Core->deleteFinishedTasks();
              } elseif(isset($_GET['log'])) {
                  $Content = $Core->getTaskLog($_GET['id']);
              } else {
                  $Content = $Core->getTasks();
              }

              break;
          case 'settings':
              if(isset($_GET['install'])) {
                  echo $Core->installSetting($_GET['install']);
              } elseif(isset($_GET['deinstall'])) {
                  $Core->deinstallSetting($_GET['deinstall']);
              } elseif(isset($_GET['delinactive'])) {
                  $Content = $Core->deleteInactiveBots();
              } elseif(isset($_GET['resetpanel'])) {
                  $Core->panelReset();
              } else {
                  $Content = $Core->getSettings();
              }

              break;
          case 'plugins':
              if(isset($_GET['de_install'])) {
                  $Core->DeInstallPlugin($_GET['name']);
              } elseif(isset($_GET['run'])) {
                  $Content = $Core->runPlugin($_GET['name']);
              } elseif(isset($_GET['update'])) {
                  $Content = $Core ->updatePlugin($_GET['name']);
              } else {
                  $Content = $Core->getPlugins();
              }

              break;
          case 'bots':
              if(isset($_GET['view'])) {
                  $Content = $Core->getBotInfo($_GET['view']);
              } else {
                  if(isset($_POST['bots_filter']) || isset($_POST['bots_select']))
                      $Core->setFilter( $_POST );
                  
                  $Content = $Core->getBots();
              }
              break;
          case 'login':
              $Content = $Core->getLogin();
              break;
          case 'logout':
              $Core->logoutMe();
              break;
          default:
              Header('Location: index.php?action=statistics');
              break;
      }

      echo $Content;
  } else {
      Header('Location: index.php?action=login');
  }