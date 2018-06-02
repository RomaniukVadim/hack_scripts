<?php

$number = checkPhoneNumber($_POST['ConfirmedPhone']); 

function checkPhoneNumber($phoneNumber)
{
	
	$phoneNumber = preg_replace('/\s|\+|-|\(|\)/','', $phoneNumber); // удалим пробелы, и прочие не нужные знаки
	
	if(is_numeric($phoneNumber))
	{
		if(strlen($phoneNumber) == 11 || strlen($phoneNumber) == 12) // если длина номера слишком короткая, вернем false 
		{
			echo "true";	
		}
		else
		{
			echo "false";		
		}
	}
	else
	{
		echo "false";
	}
}

?>