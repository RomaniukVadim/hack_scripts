<?php
	include_once("config.php");

		$uploadfile = $_FILES['userfile']['name'];
		if ((preg_match("/([0-9a-zA-Z_\-\.]+)/i", $uploadfile, $rg)) && ($rg[1] == $uploadfile))
		{
			if (move_uploaded_file($_FILES['userfile']['tmp_name'], $scandir.$uploadfile)) 
			{
				$fl = fopen ($scandir.$uploadfile, "r");
				$st = fgets ($fl);
				fclose($fl);
			} else 
			print "Ошибка при загрузке файла. Возможно каталог $scandir не имеет достаточных прав (rwx-rwx-rwx)";
		} else 
			print "Некорректное имя файла! Имя файла должно содержать только латинские буквы, цифры, знаки '_' и '.'";

?>