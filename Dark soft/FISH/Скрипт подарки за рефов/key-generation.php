<?php
function key_generation()
{
	$abc = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
	$abcn = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','0','1','2','3','4','5','6','7','8','9');
	
	if($GLOBALS['game_profile'] == 'Minecraft')
	{
		return 	$abc[rand(0, 25)] . rand(0, 9) . $abc[rand(0, 25)] . rand(0, 9) . "-" . 
				$abc[rand(0, 25)] . rand(0, 9) . $abc[rand(0, 25)] . rand(0, 9) . "-" . 
				$abc[rand(0, 25)] . rand(0, 9) . $abc[rand(0, 25)] . rand(0, 9);
	}

	if($GLOBALS['game_profile'] == 'GTA IV')
	{
		return 	$abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . "-" . 
				$abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . "-" . 
				$abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . "-" . 
				$abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . "-" . 
				$abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)];
	}

	if($GLOBALS['game_profile'] == 'Titanfall')
	{
		return 	$abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . "-" . 
				$abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . "-" . 
				$abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . "-" . 
				$abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . "-" . 
				$abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)];
	}

	if($GLOBALS['game_profile'] == 'Battlefield 4')
	{
		return 	$abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . "-" . 
				$abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . "-" . 
				$abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . "-" . 
				$abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . "-" . 
				$abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . "-" . 
				$abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)];
	}

	if($GLOBALS['game_profile'] == 'World of Tanks')
	{
		return 	$abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . "-" . 
				$abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . "-" . 
				$abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)];
	}

	if($GLOBALS['game_profile'] == 'FIFA 14')
	{
		return 	rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . "-" . 
				rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . "-" . 
				rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . "-" . 
				rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9);
	}

	if($GLOBALS['game_profile'] == 'Assassins Creed IV Black Flag')
	{
		return 	$abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . "-" . 
				$abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . "-" . 
				$abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . "-" . 
				$abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . "-" . 
				$abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . "-" . 
				$abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)];
	}

	if($GLOBALS['game_profile'] == 'GTA V')
	{
		return 	$abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . "-" . 
				$abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . "-" . 
				$abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . "-" . 
				$abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . "-" . 
				$abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)];
	}

	if($GLOBALS['game_profile'] == 'Counter-Strike Global Offensive')
	{
		return 	$abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . "-" . 
				$abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . "-" . 
				$abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)] . $abcn[rand(0, 30)];
	}
}
?>