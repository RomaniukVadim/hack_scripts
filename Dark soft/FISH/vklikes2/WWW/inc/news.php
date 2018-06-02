<center><h3>Новости</h3>
<?
$kolvo=10;
$sqlz=mysql_query("SELECT id FROM tb_news");
$vsego=intval(mysql_num_rows($sqlz)/$kolvo);
$gg=$vsego*$kolvo;
if($gg<mysql_num_rows($sqlz)) { $vsego=$vsego+1; }
if(isset($_GET["s"])) { $page=intval($_GET["s"]); }else{ $page=1; }
$pages=$vsego/$kolvo;
$pages1=floor($pages);
if($pages>$pages1)
{
	$pages=$pages1+1;
}
if($page>$vsego)
{
echo "<div class='w_warning'><div class='wmsg'>Ошибка</div></div>";
}else{
$p1=$kolvo*($page-1);
$p2=$kolvo;
$sql=mysql_query("SELECT * FROM tb_news order by id desc limit $p1,$p2");
while($row=mysql_fetch_array($sql))
{
?>
<strong style="color:#2b587a;"><?=$row['nazv'];?></strong> <?=$row['data'];?><br>
<?=$row['newstext'];?><br><br>

<? }
if($numnews>10)
{
$u=1;
while($u<=$vsego)
{
if($u!=$vsego)
{
?>
<a href="index.php?page=news&s=<?=$u ?>"><?=$u ?></a> | 
<?
}else{
?><a href="index.php?page=news&s=<?=$u ?>"><?=$u ?></a>
<?
}
$u++;
}
}}?></center>