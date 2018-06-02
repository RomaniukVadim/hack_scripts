#!/usr/bin/php
<?php
$id="29171";
$token="499fc92bf1338c31f025";
$url='http://scan4you.net/remote.php';
//$url='http://scan4you.org/remote.php'; // Try this if .net dosn`t work

$type='file';
$options=getopt('t:d:e:l');
$file=$argv[$argc-1];
$link=0;
$format='txt'; // json - for JSON return
$disable='';
$enable='';
if (@$options['t']) $type = $options['t'];
if (@$options['l'] === false) $link = 1;
if (@$options['d']) $disable = @$options['d'];
if (@$options['e']) $enable = @$options['e'];

if (($type!='file' && $type!='domain' && $type!='url' && $type!='exploit') || $file==''){
    print "\n./upload.php [-t type] [-d av1,av2,av3] filename\n".
	    "    -t		type of check may be `file', `url', or `domain'\n".
	    "		    file - make virus check on file (default)\n".
	    "		    url - make virus check on\n".
	    "		    domain - make check in blacklist from domain/url/IP\n".
	    "		    exploit - make check of Exploit Pack\n".
	    "    -d		disable some av for this check, full list of av you\n".
	    "		    can see at: http://scan4you.biz/version.php\n".
	    "    -e		enable only this av for this check ('all' if you want to enable all av),\n".
	    "			full list of av you can see at: http://scan4you.biz/version.php\n".
	    "    -l		add url to result page to the end of results".
	    "\n";
    exit();
}
if (($type == 'file') && (!file_exists($file))) die ("$file dosn`t exist.\n");
$ch = curl_init();
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_VERBOSE, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
$post = array('id'=>$id,'token'=>$token,'action'=>$type);
if ($disable) $post['av_disable']=$disable;
if ($enable) $post['av_enabled']=$enable;
if ($link) $post['link']=1;
if ($type != 'file') $post[$type]=$file;
else {
    if (class_exists('CURLFile')){
	$cfile = new CURLFile($file,'application/octet-stream',$type);
	$post['uppload']=$cfile;
    } else {
	$post['uppload']='@'.$file;
    }
}
$post['frmt']=$format;
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
$response = curl_exec($ch);
if ($response === false || curl_errno($ch) || curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200){
    echo 'ERROR:'.curl_error($ch);
    exit;
}
echo $response;
?>