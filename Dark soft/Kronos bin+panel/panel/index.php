<?php

if(!file_exists('conf.php')) { header ("location: setup.php"); exit ('<script>location.href="setup.php"</script>'); }

require_once('inc/require.php');

if(LoggedUser()==false)
{

header ("location: login.php");
exit;
}



?>
<script>
location.href="stats.php";
</script>