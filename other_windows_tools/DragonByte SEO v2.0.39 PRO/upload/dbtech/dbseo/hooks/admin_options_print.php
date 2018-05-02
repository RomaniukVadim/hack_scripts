<?php
switch($setting['optioncode'])
{
	case 'dbseo_forums:all':
	{
		$handled = true;
		$array = construct_forum_chooser_options(-1,$vbphrase['all']);
		$size = sizeof($array);

		$vbphrase['forum_is_closed_for_posting'] = $vbphrase['closed'];
		print_select_row($description, $name.'[]', $array, unserialize($setting['value']), false, ($size > 10 ? 10 : $size), true);
	}
	break;

	case 'dbseo_forums:none':
	{
		$handled = true;
		$array = construct_forum_chooser_options(0,$vbphrase['none']);
		$size = sizeof($array);

		$vbphrase['forum_is_closed_for_posting'] = $vbphrase['closed'];
		print_select_row($description, $name.'[]', $array, unserialize($setting['value']), false, ($size > 10 ? 10 : $size), true);
	}

}
?>