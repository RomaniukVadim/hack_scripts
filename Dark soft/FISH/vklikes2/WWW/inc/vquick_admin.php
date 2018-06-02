<? 
if($_SESSION['user_id']==$admin_uid){
if (isset($_GET['s']) == false)
{
include("admin/index.php");
$_GET['s'] = 'index';
}
else 
{
if (file_exists("inc/admin/".$_GET['s'].".php") == true)
{ 
include("inc/admin/".$_GET['s'].".php");
}
else 
{ 
include("404.php"); 
}
}

}else{
    include "404.php";
}
?>