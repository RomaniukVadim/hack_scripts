<?php
ini_set('display_errors', "Off");
//ignore_user_abort(1);
ini_set('max_execution_time', 0);

// =============================== SETTINGS =======================================================================

$donorsiteurl="donor.com";             // URL сайта-донора
$mysiteurl="vash-sayt.com";    // URL твоего сайта (со всеми папками, если не в корне)
$lang="uk";                                // язык, на который переводить

$myreplaces="То, что меняется в коде|||то на что меняется:::То, что меняется в коде|||то на что меняется";
// ====================================================================================================================






// =============================== Don't TOUCH =========================================================================
$nocache="yes";
$hash = md5($mysiteurl);
$cachedirname = dirname(__FILE__) . DIRECTORY_SEPARATOR."cache" . $hash;
$cachefilename = $cachedirname . DIRECTORY_SEPARATOR."cac" . substr($hash, 0, 6) . "he";
if (!is_dir($cachedirname)) {
    if (!mkdir($cachedirname, 0777)) {
       echo "Can't create work folder. Check permissions on script folder (need 777)";
        die();
    }
}
if (!is_dir("data")) {
    if (!mkdir($cachedirname, 0777)) {
       echo "Can't create data folder. Check permissions on script folder (need 777)";
        die();
    }
}

$inmyurl = $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
$urlhash=md5($inmyurl);





if (file_exists($cachefilename)) {
    $handle = fopen($cachefilename, "r");

    while (!feof($handle)) {
        $cacheline = fgets($handle);
        $cacheline = explode("::::", $cacheline);
        $cachedurl = trim($cacheline[0]);
        if ($cachedurl == trim($urlhash)) {
            $cacheddata = trim($cacheline[1]);
            break;
        }
    }
    fclose($handle);
    //var_dump($cacheddata);die();
    if (!empty($cacheddata)) {
        $cacheddata=urldecode($cacheddata);
        if(!empty($myreplaces)){
	        //$myreplaces=urlencode($myreplaces);
	       // var_dump($myreplaces);
	        //var_dump($cacheddata);
            $myreplaces=explode(":::", $myreplaces);
            foreach($myreplaces as $myreplace) {
                $myreplace=explode("|||", $myreplace);
                $myreplace[0]=preg_quote($myreplace[0]);
                $regular="|".$myreplace[0]."|imUs";
                $regular=str_ireplace("\"", "\\\"", $regular);
                $regular=str_ireplace("\r\n", ".*", $regular);
                $regular=str_ireplace("\n", ".*", $regular);
               // var_dump($regular);
    //preg_match_all($regular, $cacheddata, $matches);
    //var_dump($matches);die();
                //$cacheddata = str_ireplace($myreplace[0], $myreplace[1], $cacheddata);
                $cacheddata = preg_replace($regular, $myreplace[1], $cacheddata);
                //var_dump($cacheddata);
            }

        }
        
        echo $cacheddata;
        die();
        //$page=str_ireplace("[REDIRECT]", $redirect, $page);
        //$page=str_ireplace("[DEFISKEY]", str_ireplace(" ", "-", $q), $page);
    }
    else {
       $nocache="yes";
    }
}






if($nocache=="yes") {
    $donorcontent = getneedurltoopen($inmyurl, $mysiteurl, $donorsiteurl, $lang);


//var_dump($donorcontent);
    $donorcontent = changecssurl($donorcontent, $donorsiteurl);
//var_dump($donorcontent);die();
    $donorcontent = changeimgurl($donorcontent, $donorsiteurl);
//var_dump($donorcontent);die();
    $donorcontent = changealllocallinks($donorcontent, $donorsiteurl, $mysiteurl);
    if(!empty($donorcontent)) {
        if(!empty($myreplaces)){
	        //$myreplaces=urlencode($myreplaces);
	       // var_dump($myreplaces);
	        //var_dump($cacheddata);
            $myreplaces=explode(":::", $myreplaces);
            foreach($myreplaces as $myreplace) {
                $myreplace=explode("|||", $myreplace);
                $myreplace[0]=preg_quote($myreplace[0]);
                $regular="|".$myreplace[0]."|imUs";
                $regular=str_ireplace("\"", "\\\"", $regular);
                $regular=str_ireplace("\r\n", ".*", $regular);
                $regular=str_ireplace("\n", ".*", $regular);
               // var_dump($regular);
    //preg_match_all($regular, $cacheddata, $matches);
    //var_dump($matches);die();
                //$cacheddata = str_ireplace($myreplace[0], $myreplace[1], $cacheddata);
                $donorcontent = preg_replace($regular, $myreplace[1], $donorcontent);
                //var_dump($cacheddata);
            }

        }
        $fod = fopen($cachefilename, "a+");
        flock($fod, LOCK_EX);
        
        if(!stripos("qqq".$donorcontent, "To continue, please type the characters below")){
        fwrite($fod, $urlhash . "::::" . urlencode($donorcontent) . "\n");
        echo $donorcontent;
        }
        
        fclose($fod);
    }
    
    die();
//unlink("cookies.txt");
}

// ====================================================================================================================












//$inmyurl = $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

//http://viagra.com/sublogus/2016/05/off-label-or-off-limits-should-you-use-a-drug-for-an-unapproved-use



// =============================== FUNCTIONS =========================================================================

@unlink("cookies.txt");

function changecssurl($insitecontent, $donorurl){
    $donorurl=trim($donorurl);
    $donorurl=str_ireplace("http://", "", $donorurl);
    $donorurl=str_ireplace("https://", "", $donorurl);
    $donorurl=trim($donorurl, "/");
//var_dump($insitecontent);
    $regular="|<link.*rel=[\"\']?stylesheet[\"\']?.*href=[\"\']?(.*)[\"\'\s>]+.*>?|iUs";
    preg_match_all($regular, $insitecontent, $matches);
//var_dump($matches);
    if(!empty($matches[1][0])){
        $matches[1]=array_unique($matches[1]);
        foreach($matches[1] as $currcss){
            $currcss=trim($currcss);

            if(!stripos("qqq".$currcss, "http://") && !stripos("qqq".$currcss, "https://")){

                $howmany=@substr_count($insitecontent, $currcss);

                if($howmany>1) {
                    $currcssreg=str_ireplace("/", "\/", $currcss);
                    $currcssreg="href=[\"\']".str_ireplace(".", "\.", $currcssreg."[\"\']");
                    //var_dump($currcssreg);
                    for ($i = 0; $i <= $howmany; $i++) {
                        $insitecontent = preg_replace("/".$currcssreg."/", "href=\"http://" . $donorurl . "/" . trim($currcss, "/")."\"", $insitecontent, 1);
                    }
                }else {
                    $insitecontent = str_ireplace($currcss, "http://" . $donorurl . "/" . trim($currcss, "/"), $insitecontent);
                }

            }

        }

    }
//var_dump($insitecontent);
return $insitecontent;
}


function changeimgurl($insitecontent, $donorurl){
    //var_dump($insitecontent);
    $donorurl=trim($donorurl);
    $donorurl=str_ireplace("http://", "", $donorurl);
    $donorurl=str_ireplace("https://", "", $donorurl);
    $donorurl=trim($donorurl, "/");
//var_dump($insitecontent);
    // $regular="|<a.*href=[\"\']?(.*)[\"\'\s>]+.*<\/a>|iUs";
    $regular="|(<img.*src=[\"\']?)(.*)([\"\'\s]+.*/?>)|iUs";
    preg_match_all($regular, $insitecontent, $matches);
//var_dump($matches);die();
    if(!empty($matches[1][0]) && !empty($matches[2][0]) && !empty($matches[3][0])){
        // var_dump(count($matches[1]));
        //$matches[1]=array_unique($matches[1]);
        //var_dump(count($matches[1]));

        foreach($matches[2] as $k=>$currcss){
            $goodmyurl="";
            $currcss=trim($currcss);
            $currcss=trim($currcss, "/");
            if(!stripos("qqq".$currcss, "http")) {

                //var_dump($matchessub)
                //var_dump($goodmyurl);
                //var_dump($currsub);
                //var_dump($matches[1][$k].$matches[2][$k].$matches[3][$k]);
                // var_dump($matches[1][$k].$goodmyurl.$matches[3][$k]);
                $insitecontent=str_ireplace($matches[1][$k].$matches[2][$k].$matches[3][$k], $matches[1][$k]."http://".$donorurl."/".$currcss.$matches[3][$k], $insitecontent);
            }



        }
        //die();
    }
//var_dump($insitecontent);
    return $insitecontent;
}

function changealllocallinks($insitecontent, $donorurl, $myurl){
    //echo "HUY";
    //var_dump($insitecontent);
    $myurl=trim($myurl);
    $myurl=str_ireplace("http://", "", $myurl);
    $myurl=str_ireplace("https://", "", $myurl);
    $myurl=trim($myurl, "/");
    $donorurl=trim($donorurl);
    $donorurl=str_ireplace("http://", "", $donorurl);
    $donorurl=str_ireplace("https://", "", $donorurl);
    //$donorurl=trim($donorurl, "/");
    $donorurl=trim($donorurl, "/");
    $donorurlfrreg=str_ireplace("/", "\/", $donorurl);
    $donorurlfrreg=str_ireplace(".", "\.", $donorurlfrreg);
    $donorurlfrreg=str_ireplace("?", "\?", $donorurlfrreg);
//var_dump($insitecontent);
    $regular="|(<a.*href=[\"\']?)(.*)([\"\'\s>]+.*<\/a>)|iUs";
    preg_match_all($regular, $insitecontent, $matches);
//var_dump($matches);die();
    if(!empty($matches[1][0]) && !empty($matches[2][0]) && !empty($matches[3][0])){
       // var_dump(count($matches[1]));
        //$matches[1]=array_unique($matches[1]);
        //var_dump(count($matches[1]));

        foreach($matches[2] as $k=>$currcss){
            $goodmyurl="";
            $currcss=trim($currcss);
if(stripos("qqq".$currcss, $donorurl) || !stripos("qqq".$currcss, "http")) {
//var_dump($currcss);
    //var_dump($currcss);
    $currsub = "";
    //$donorurlfrreg="";

    //var_dump($currcss);
    $currcssfrseeksub = str_ireplace("http://", "", $currcss);
    $currcssfrseeksub = str_ireplace("https://", "", $currcssfrseeksub);
    $regular = "|(.*)\." . $donorurlfrreg . "|iUs";
    // var_dump($regular);
    preg_match_all($regular, $currcssfrseeksub, $matchessub);

    //var_dump($matchessub);
    if (!empty($matchessub[1][0])) {

        $currsub = str_ireplace("http://", "", $matchessub[1][0]);
        $currsub = str_ireplace("https://", "", $currsub);
       // var_dump($matches[2][$k]);
        //var_dump($currsub);
        //$matches[2][$k]=str_ireplace($currsub.".", "", $matches[2][$k]);
       // var_dump($matches[2][$k]);
        $goodmyurl=str_ireplace($currsub.".".$donorurl, "", $matches[2][$k]);
        if($currsub=="www"){
            $currsub="";
        }else {
            $currsub = "su" . $currsub . "us/";
        }

    }else{
        $goodmyurl=str_ireplace($donorurl, "", $matches[2][$k]);
    }

    $goodmyurl=str_ireplace("http://", "", $goodmyurl);
    $goodmyurl=str_ireplace("https://", "", $goodmyurl);
    $goodmyurl=trim($goodmyurl, "/");
    $goodmyurl="http://".$myurl."/".$currsub."".$goodmyurl;
    //var_dump($goodmyurl);
    //var_dump($currsub);
    //var_dump($matches[1][$k].$matches[2][$k].$matches[3][$k]);
   // var_dump($matches[1][$k].$goodmyurl.$matches[3][$k]);
    $insitecontent=str_ireplace($matches[1][$k].$matches[2][$k].$matches[3][$k], $matches[1][$k].$goodmyurl.$matches[3][$k], $insitecontent);
}



        }
        //die();
    }
//var_dump($insitecontent);
    return $insitecontent;
}

function getneedurltoopen($inmyurl, $mysiteurl, $donorurl, $lang){

    $sub="";
    $donorurl=trim($donorurl);
    $donorurl=str_ireplace("http://", "", $donorurl);
    $donorurl=str_ireplace("https://", "", $donorurl);
    $donorurl=trim($donorurl, "/");

    $inmyurl=trim($inmyurl);
    $inmyurl=str_ireplace("http://", "", $inmyurl);
    $inmyurl=str_ireplace("https://", "", $inmyurl);
   $inmyurl=trim($inmyurl, "/");

    $mysiteurl=trim($mysiteurl);
    $mysiteurl=str_ireplace("http://", "", $mysiteurl);
    $mysiteurl=str_ireplace("https://", "", $mysiteurl);
    $mysiteurl=trim($mysiteurl, "/");
   // var_dump($mysiteurl);

    $regular="|\/?su.*us\/?|iUs";
    // var_dump($regular);
    preg_match($regular, $inmyurl, $matchessub);
//var_dump($matchessub);
    if(!empty($matchessub[0])){
        $sub=$matchessub[0];

        $inmyurl=str_ireplace($sub, "/", $inmyurl);
        $sub=trim($sub, "us/");
        $sub=trim($sub, "/su");
        $sub=trim($sub, "us");
        $sub=trim($sub, "su");
        $sub=trim($sub, "/");
        $inmyurl=str_ireplace("//", "/", $inmyurl);
        //var_dump($sub);
        $inmyurl=str_ireplace($sub."/", "", $inmyurl);
    }
   // var_dump($mysiteurl);
    //var_dump($donorurl);die();
   //var_dump($inmyurl);
    $donorurl=str_ireplace($mysiteurl, $donorurl, $inmyurl);
    //var_dump($donorurl);die();
if(!empty($sub)){
    $donorurl=$sub.".".$donorurl;
}
    $donorurl=urlencode($donorurl);
    $uas=array("Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322; FDM)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0; Avant Browser [avantbrowser.com]; Hotbar 4.4.5.0)", "Mozilla/4.61 [en] (X11; U; ) - BrowseX (2.0.0 Windows)", "Mozilla/3.0 (x86 [en] Windows NT 5.1; Sun)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; KKman2.0)", "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; SV1; .NET CLR 2.0.50727)", "Mozilla/4.0 (compatible; MSIE 7.0b; Windows NT 6.0)", "Mozilla/4.0 (compatible; MSIE 7.0b; Windows NT 6.0 ; .NET CLR 2.0.50215; SL Commerce Client v1.0; Tablet PC 2.0", "Mozilla/6.0 (compatible; MSIE 7.0a1; Windows NT 5.2; SV1)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; TheFreeDictionary.com; .NET CLR 1.1.4322; .NET CLR 1.0.3705; .NET CLR 2.0.50727)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.2; SV1; .NET CLR 1.1.4322; .NET CLR 2.0.50727)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.2; WOW64; SV1; .NET CLR 2.0.50727)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.2; Win64; x64; SV1; .NET CLR 2.0.50727)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322; XMPP Tiscali Communicator v.10.0.2; .NET CLR 2.0.50727)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0; T312461)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1) Netscape/8.0.4", "Mozilla/4.0 (compatible; MSIE 6.0; America Online Browser 1.1; rev1.2; Windows NT 5.1; SV1; .NET CLR 1.1.4322)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows 98; Win 9x 4.90; Creative)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; FunWebProducts; .NET CLR 1.1.4322; PeoplePal 6.2)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows XP)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0; APC; .NET CLR 1.0.3705; .NET CLR 1.1.4322; .NET CLR 2.0.50215; InfoPath.1)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; Deepnet Explorer 1.5.0; .NET CLR 1.0.3705)", "Mozilla/4.0 (compatible- MSIE 6.0- Windows NT 5.1- SV1- .NET CLR 1.1.4322", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; Media Center PC", "Mozilla/4.0 (compatible; MSIE 5.01; Windows 95; MSIECrawler)", "Mozilla/4.0 (compatible; MSIE 6.0; AOL 9.0; Windows NT 5.1)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322; Alexa Toolbar; (R1 1.5))", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; Maxthon; .NET CLR 1.1.4322)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.2; Win64; AMD64)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.2; Win64; AMD64)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; Crazy Browser 2.0.0 Beta 1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)", "Mozilla/4.0 (compatible; MSIE 6.0; Update a; AOL 6.0; Windows 98)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; YPC 3.0.2; .NET CLR 1.1.4322; yplus 4.4.02b)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322; .NET CLR 2.0.40607)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322) Babya Discoverer 8.0:", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; Crazy Browser 1.0.5)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT) ::ELNSB50::000061100320025802a00111000000000507000 900000000", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; MyIE2; Deepnet Explorer)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0; .NET CLR 1.0.3705; .NET CLR 1.1.4322)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; FREE; .NET CLR 1.1.4322)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; Q312461)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.0.3705)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows 98; Win 9x 4.90)", "Mozilla/4.0 (compatible; MSIE 5.5; Windows 98)", "Mozilla/4.0 (compatible; MSIE 5.5; Windows NT 5.0; .NET CLR 1.1.4322)", "Mozilla/4.0 (compatible; MSIE 5.0; Windows NT; DigExt)", "Mozilla/4.0 (compatible; MSIE 5.5; Windows NT 5.0; T312461)", "Mozilla/4.0 (compatible; MSIE 5.5; Windows NT 4.0)", "Mozilla/4.0 (compatible; MSIE 5.5; Windows NT 4.0; .NET CLR 1.0.2914)", "Mozilla/4.0 (compatible; MSIE 5.5; Windows NT 5.0)", "Mozilla/4.0 (compatible; MSIE 5.5; Windows 95)", "Mozilla/4.0 (compatible; MSIE 5.5; Windows 95; BCD2000)", "Mozilla/4.0 (compatible; MSIE 4.01; Digital AlphaServer 1000A 4/233; Windows NT; Powered By 64-Bit Alpha Processor)", "Mozilla/2.0 (compatible; MSIE 3.02; Windows CE; 240x320)", "Mozilla/1.22 (compatible; MSIE 2.0; Windows 95)", "Mozilla/1.22 (compatible; MSIE 2.0d; Windows NT)", "Mozilla/4.0 (compatible; MSIE 5.0; Windows 3.1)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322) NS8/0.9.6", "Mozilla/4.79 [en] (Windows NT 5.0; U)", "Mozilla/4.76 [en] (Windows NT 5.0; U)", "Mozilla/0.91 Beta (Windows)", "Mozilla/0.6 Beta (Windows)", "Mozilla/4.7 (compatible; OffByOne; Windows 2000) Webster Pro V3.4", "Opera/9.00 (Windows NT 4.0; U; en)", "Opera/9.00 (Windows NT 5.1; U; en)", "Opera/9.0 (Windows NT 5.1; U; en)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; en) Opera 9.0", "Opera/8.01 (Windows NT 5.1)", "Mozilla/5.0 (Windows NT 5.1; U; en) Opera 8.01", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)", "Mozilla/5.0 (Windows NT 5.1; U; en) Opera 8.00", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; en) Opera 8.00", "Opera/8.00 (Windows NT 5.1; U; en)", "Opera/7.60 (Windows NT 5.2; U) [en] (IBM EVV/3.0/EAK01AG9/LE)", "Opera/7.54 (Windows NT 5.1; U) [pl]", "Opera/7.11 (Windows NT 5.1; U) [en]", "Mozilla/4.0 (compatible; MSIE 6.0; Windows ME) Opera 7.11 [en]", "Mozilla/4.0 (compatible; MSIE 6.0; MSIE 5.5; Windows NT 5.0) Opera 7.02 Bork-edition [en]", "Mozilla/4.0 (compatible; MSIE 6.0; MSIE 5.5; Windows NT 4.0) Opera 7.0 [en]", "Mozilla/4.0 (compatible; MSIE 5.0; Windows 2000) Opera 6.0 [en]", "Mozilla/4.0 (compatible; MSIE 5.0; Windows 95) Opera 6.01 [en]", "Mozilla/3.0 (compatible; WebCapture 2.0; Auto; Windows)", "Mozilla/4.0 (compatible; Powermarks/3.5; Windows 95/98/2000/NT)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; FREE; .NET CLR 1.1.4322)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0; KTXN)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0; .NET CLR 1.0.3705; .NET CLR 1.1.4322)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; ru) Opera 8.50", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; FunWebProducts)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; MRA 4.3 (build 01218); .NET CLR 1.1.4322)", "Opera/9.01 (Windows NT 5.1; U; en)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1) Opera 7.54 [en]", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322; .NET CLR 2.0.50727)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; InfoPath.1)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; MRA 4.6 (build 01425))", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; ru) Opera 8.01", "Opera/9.00 (Windows NT 5.1; U; ru)", "Opera/9.0 (Windows NT 5.1; U; en)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; FunWebProducts; MRA 4.6 (build 01425); .NET CLR 1.1.4322; .NET CLR 2.0.50727)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; ru) Opera 8.01", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; InfoPath.1", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; MRA 4.6 (build 01425); MRSPUTNIK 1, 5, 0, 19 SW)", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322; .NET CLR 2.0.50727)", "Mozilla/4.0 (compatible; MSIE 6.0; AOL 9.0; Windows NT 5.1)");
    srand((float)microtime() * 1000000);
    shuffle($uas);
    $currua=trim($uas[0]);

    $reffs=array("http://google.com", "http://bing.com", "http://yahoo.com", "http://yandex.ru");
    srand((float)microtime() * 1000000);
    shuffle($reffs);
    $curreff=trim($reffs[0]);
//var_dump($donorurl);
   $urltoopen="https://translate.googleusercontent.com/translate_c?depth=1&rurl=translate.google.com&sl=auto&tl=".$lang."&u=http://".$donorurl;
   // var_dump($urltoopen);die();
   // $urltoopen="https://translate.googleusercontent.com/translate_c?depth=2&rurl=translate.google.com&sl=auto&tl=ru&u=http://drugs.com/";
    //var_dump($urltoopen);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $urltoopen);
    
    curl_setopt($ch, CURLOPT_COOKIEFILE, "data/cookies.txt");
    if(!file_exists("data/cookies.txt")){
    curl_setopt($ch, CURLOPT_COOKIEJAR, "data/cookies.txt");
    }
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, $currua);
    curl_setopt($ch, CURLOPT_REFERER, $curreff);
    $content = curl_exec($ch);
    //var_dump($content);
    //echo curl_error($ch);

    curl_close($ch);
   
   
//echo "HUY";die();
$regular="|<img src=\"(\/sorry\/image[^\"]*)\"|iUs";
    //var_dump($regular);
    preg_match($regular, $content, $matches);
    $matches[1]=str_ireplace("&amp;", "&", $matches[1]);
//var_dump($matches[1]);
//var_dump($content);
if(!empty($matches[1])){
      
      
    $content= getcaptcha($matches[1]);
      //die();
    /*  $urltoopen="https://translate.googleusercontent.com/translate_c?depth=1&rurl=translate.google.com&sl=auto&tl=".$lang."&u=http://".$donorurl;
   // var_dump($urltoopen);die();
   // $urltoopen="https://translate.googleusercontent.com/translate_c?depth=2&rurl=translate.google.com&sl=auto&tl=ru&u=http://drugs.com/";
    //var_dump($urltoopen);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $urltoopen);
    curl_setopt($ch, CURLOPT_COOKIEFILE, "data/cookies.txt");
    curl_setopt($ch, CURLOPT_COOKIEJAR, "data/cookies.txt");
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, $currua);
    curl_setopt($ch, CURLOPT_REFERER, $curreff);*/
      }
//var_dump($content);die();

    //<iframe sandbox="allow-same-origin allow-forms allow-scripts" src="https://translate.googleusercontent.com/translate_p?rurl=translate.google.com&sl=auto&tl=ru&u=http://drugs.com/&depth=2&usg=ALkJrhgAAAAAV8A8vVddur-9m33aP7TGPZXKcW9Q9lhm"
    $regular="|<iframe sandbox=\"allow-same-origin allow-forms allow-scripts\" src=\"([^\"]*)\"|iUs";
    // var_dump($regular);
    preg_match($regular, $content, $matches);
   //var_dump($matches[1]);die();
//die();
if(!empty($matches[1])) {
	
	
	
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, trim($matches[1]));
    curl_setopt($ch, CURLOPT_COOKIEFILE, "data/cookies.txt");
    //curl_setopt($ch, CURLOPT_COOKIEJAR, "data/cookies.txt");
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, $currua);
    curl_setopt($ch, CURLOPT_REFERER, $curreff);
    $content = curl_exec($ch);
    //var_dump($content);
    curl_close($ch);
    $regular="|<img src=\"(\/sorry\/image[^\"]*)\"|iUs";
    //var_dump($regular);
    preg_match($regular, $content, $matches);
    $matches[1]=str_ireplace("&amp;", "&", $matches[1]);
//var_dump($matches[1]);
//var_dump($content);
if(!empty($matches[1])){
      
      
    $content= getcaptcha($matches[1]);
     
      }
    $regular="|<meta http-equiv=\"refresh\" content=\"0;URL=(.*)\">|iUs";
    // var_dump($regular);
    preg_match($regular, $content, $matches);

   //var_dump($matches[1]);die();
    if(!empty($matches[1])) {
        $matches[1]=str_ireplace("&amp;", "&", $matches[1]);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, trim($matches[1]));
        curl_setopt($ch, CURLOPT_COOKIEFILE, "data/cookies.txt");
        //curl_setopt($ch, CURLOPT_COOKIEJAR, "data/cookies.txt");
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, $currua);
        curl_setopt($ch, CURLOPT_REFERER, $curreff);
        $content = curl_exec($ch);
//var_dump($content);
        curl_close($ch);
         $regular="|<img src=\"(\/sorry\/image[^\"]*)\"|iUs";
    //var_dump($regular);
    preg_match($regular, $content, $matches);
    $matches[1]=str_ireplace("&amp;", "&", $matches[1]);
        if(!empty($matches[1])){
      
      
    $content= getcaptcha($matches[1]);
     
      }
      //@unlink("data/cookies.txt");
      @unlink("data/captcha.jpg");
        //var_dump($content);die();


       //$regular = "#(<script>\(function\(\){\(function\(\){function e.*<base href=.*>)#iUs";
        // var_dump($regular);
       //preg_match($regular, $content, $matches);
       $content = preg_replace("#(<script>\(function\(\){\(function\(\){function e.*<base href=.*>)#iUs", "", $content);
        //<span class="google-src-text"
        $content = preg_replace("#(<span class=\"google-src-text\".*<\/span>)#imUs", "", $content);
        //https://translate.googleusercontent.com/translate_c?depth=2&amp;rurl=translate.google.com&amp;sl=auto&amp;tl=ru&amp;u=
        $regular="#(href=|src=)(https://translate.googleusercontent.com/translate_c.*tl=".$lang."&amp;u=)(.*)(&amp;usg=.*)[\'\"\s>]+#imUs";
        // var_dump($regular);
        preg_match_all($regular, $content, $matches);
        //var_dump($matches);die();
        if(!empty($matches[3][0])){
            //$content=str_ireplace($matches[1][0], "", $content);
        foreach($matches[3] as $k=>$tochange){
            //$content=str_ireplace($matches[2][$k], "", $content);
            $content=str_ireplace($matches[2][$k], "", $content);
            $content=str_ireplace($matches[3][$k], urldecode($tochange), $content);
            $content=str_ireplace($matches[4][$k], "", $content);
        }

        }
        //var_dump($content);die();
       // $content = preg_replace("#(href=https://translate.googleusercontent.com/translate_c.*tl=".$lang."&amp;u=)(.*)[\'\"\s>]+#imUs", "href=".urldecode("\\2"), $content);
       // $content = preg_replace("#(src=https://translate.googleusercontent.com/translate_c.*tl=".$lang."&amp;u=)#imUs", "src=", $content);
        //$content=str_ireplace("https://translate.googleusercontent.com/translate_c?depth=2&amp;rurl=translate.google.com&amp;sl=auto&amp;tl=".$lang."&amp;u=", "", $content);
       //href=https://translate.googleusercontent.com/translate_c?depth=2&amp;pok=1&amp;rurl=translate.google.com&amp;sl=auto&amp;tl=ru&amp;u=http://blog.drugs.com/wp-content/themes/drugscom-theme/style.css%3Fver%3D1.1
        // $content = preg_replace("#(&amp;usg=.*)[>|\s]+#imUs", "", $content);
       // $regular="#(&amp;usg=.*)[>|\s]+#imUs";
        // var_dump($regular);
       // preg_match_all($regular, $content, $matches);
       // if(count($matches[1])>0){
       //     foreach($matches[1] as $curchastdel){

        //        $content = preg_replace('|' . preg_quote($curchastdel) . '|iU', "", $content, 1);

       //     }

       // }
//var_dump($matches);die();
//var_dump($content);
        //<meta charset=utf-8>
        $regular="#(<meta charset=[\"\']?.*[\"\']?>)#imUs";
        // var_dump($regular);
        preg_match_all($regular, $content, $matches);
        //var_dump($matches[1]);die();
        if(!empty($matches[1][0])){
            $content=str_ireplace($matches[1][0], "<meta charset=\"utf-8\">", $content);

        }else{
            $content=str_ireplace("<head>", "<head><meta charset=\"utf-8\">", $content);
        }
    }
   // var_dump($matches);
   // die();
   // $content = preg_replace("#(<script>\(function\(\){\(function\(\){function e.*<base href=.*>)#iUs", "", $content);
   //var_dump($content);
}

    $content = iconv(mb_detect_encoding($content), "utf-8//IGNORE", $content);
    $regular="|(<script.*</script>)|iUs";
    preg_match_all($regular, $content, $matches);
    if(!empty($matches[1])){
        foreach($matches[1] as $currgooglestat){
            if(stripos("qqq".$currgooglestat, "google-analytics.com")) {
                $content = str_ireplace($currgooglestat, "", $content);
            }

        }

    }

    $regular="|(<script.*</script>)|iUs";
    preg_match_all($regular, $content, $matches);
    if(!empty($matches[1])){
        foreach($matches[1] as $currgooglestat){
            if(stripos("qqq".$currgooglestat, "mc.yandex.ru/metrika/watch.js")) {
                $content = str_ireplace($currgooglestat, "", $content);
            }

        }

    }

    $regular="|(<script.*</script>)|iUs";
    preg_match_all($regular, $content, $matches);
    if(!empty($matches[1])){
        foreach($matches[1] as $currgooglestat){
            if(stripos("qqq".$currgooglestat, "www.liveinternet.ru/click")) {
                $content = str_ireplace($currgooglestat, "", $content);
            }

        }

    }

    //var_dump($content);die();
    return $content;
}

function getcaptcha($imgurl){
	$id="";
	$q="";
	$continue="";
	$ip46="ipv4";
	
	///sorry/image?id=14884129772703365907&q=CGMSECoCJ6gAAAABApwC__6oVE4YqvCavgUiGQDxp4NLqi6zIjDvEpIbyvzNXU6BRd-1kAk&hl=en&continue=https://translate.googleusercontent.com/translate_c%3Fdepth%3D1%26rurl%3Dtranslate.google.com%26sl%3Dauto%26tl%3Den%26u%3Dhttp://zaycev.net%252Fmsb%252Fmsb.php
	 $regular="#image\?id=([^&]*)&#iUs";
        // var_dump($regular);
        preg_match_all($regular, $imgurl, $matches);
        //var_dump($matches);die();
        if(!empty($matches[1][0])){
	        $id=$matches[1][0];
        }
        
        $regular="#&q=([^&]*)&#iUs";
        // var_dump($regular);
        preg_match_all($regular, $imgurl, $matches);
        //var_dump($matches);die();
        if(!empty($matches[1][0])){
	        $q=$matches[1][0];
        }
        
         $regular="#continue=(.*)$#iUs";
        // var_dump($regular);
        preg_match_all($regular, $imgurl, $matches);
        //var_dump($matches);die();
        if(!empty($matches[1][0])){
	        $continue=$matches[1][0];
        }
        
        
	$curlImage = curl_init();
      curl_setopt($curlImage, CURLOPT_URL, 			"https://".$ip46.".google.com".$imgurl); 
      curl_setopt($curlImage, CURLOPT_USERAGENT, 		'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/45.0.2454.101 Chrome/45.0.2454.101 Safari/537.36');
      curl_setopt($curlImage, CURLOPT_RETURNTRANSFER, true); 
      curl_setopt($curlImage, CURLOPT_COOKIEJAR, 		"data/cookies.txt"); 
      $ttt=curl_exec($curlImage);
     //var_dump($ttt);
     if(!empty($ttt) && !stripos("qqq".$ttt, "Error 403 (Forbidden)")){
      $fod = fopen("data/captcha.jpg", "w+");
        flock($fod, LOCK_EX);
        fwrite($fod, $urlhash . $ttt);
        fclose($fod);
        }
      curl_close($curlImage);
      
      
      if(stripos("qqq".$ttt, "Error 403 (Forbidden)")){
      $ip46="ipv6";
      $curlImage = curl_init();
      curl_setopt($curlImage, CURLOPT_URL, 			"https://".$ip46.".google.com".$imgurl); 
      curl_setopt($curlImage, CURLOPT_USERAGENT, 		'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/45.0.2454.101 Chrome/45.0.2454.101 Safari/537.36');
      curl_setopt($curlImage, CURLOPT_RETURNTRANSFER, true); 
      curl_setopt($curlImage, CURLOPT_COOKIEJAR, 		"data/cookies.txt"); 
      $ttt=curl_exec($curlImage);
    // var_dump($ttt);
     if(!empty($ttt)){
      $fod = fopen("data/captcha.jpg", "w+");
        flock($fod, LOCK_EX);
        fwrite($fod, $urlhash . $ttt);
        fclose($fod);
        }
      curl_close($curlImage);
      
      }
	//die();
	$text=recognize("data/captcha.jpg","728b40043d4869eae379136c9b98c3c7",false, "antigate.com"); 
	//var_dump($text);
	
	if(!empty($text)){
	$url = "https://".$ip46.".google.com/sorry/CaptchaRedirect?continue=".$continue
          ."&id=".$id
          ."&q=".$q
          ."&captcha=".$text
          ."&submit="."Submit";
         // var_dump($url);
      # Переходим по URL со всеми нужными данными
      $curlGoogleAntiCaptcha = curl_init();
      curl_setopt($curlGoogleAntiCaptcha, CURLOPT_URL, 			$url);
      curl_setopt($curlGoogleAntiCaptcha, CURLOPT_USERAGENT, 		'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/45.0.2454.101 Chrome/45.0.2454.101 Safari/537.36');
      curl_setopt($curlGoogleAntiCaptcha, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curlGoogleAntiCaptcha, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($curlGoogleAntiCaptcha, CURLOPT_COOKIEFILE, 	"data/cookies.txt");
      $result = curl_exec($curlGoogleAntiCaptcha);
      curl_close($curlGoogleAntiCaptcha);
     // var_dump($result);
      }
	return $result;
}



/*
$filename - file path to captcha
$apikey   - account's API key
$rtimeout - delay between captcha status checks
$mtimeout - captcha recognition timeout

$is_verbose - false(commenting OFF),  true(commenting ON)

additional custom parameters for each captcha:
$is_phrase - 0 OR 1 - captcha has 2 or more words
$is_regsense - 0 OR 1 - captcha is case sensetive
$is_numeric -  0 OR 1 - captcha has digits only
$min_len    -  0 is no limit, an integer sets minimum text length
$max_len    -  0 is no limit, an integer sets maximum text length
$is_russian -  0 OR 1 - with flag = 1 captcha will be given to a Russian-speaking worker

usage examples:
$text=recognize("/path/to/file/captcha.jpg","YOUR_KEY_HERE",true, "antigate.com");

$text=recognize("/path/to/file/captcha.jpg","YOUR_KEY_HERE",false, "antigate.com");  

$text=recognize("/path/to/file/captcha.jpg","YOUR_KEY_HERE",false, "antigate.com",1,0,0,5);  

*/

function recognize(
		$filename,
		$apikey,
		$is_verbose = true,
		$sendhost = "antigate.com",
		$rtimeout = 5,
		$mtimeout = 120,
		$is_phrase = 0,
		$is_regsense = 0,
		$is_numeric = 0,
		$min_len = 0,
		$max_len = 0,
		$is_russian = 0)
{
	if (!file_exists($filename))
	{
		if ($is_verbose) echo "file $filename not found\n";
		return false;
	}
	$fp=fopen($filename,"r");
	if ($fp!=false)
	{
		$body="";
		while (!feof($fp)) $body.=fgets($fp,1024);
		fclose($fp);
		$ext=substr($filename,strpos($filename,".")+1);
	}
	else
	{
		if ($is_verbose) echo "could not read file $filename\n";
		return false;
	}
    $postdata = array(
        'method'    => 'base64', 
        'key'       => $apikey, 
        'body'      => base64_encode($body), //������ ���� � �����
        'ext' 		=> $ext,
        'phrase'	=> $is_phrase,
        'regsense'	=> $is_regsense,
        'numeric'	=> $is_numeric,
        'min_len'	=> $min_len,
        'max_len'	=> $max_len,
        'is_russian'	=> $is_russian,
        
    );
    
    $poststr="";
    while (list($name,$value)=each($postdata))
    {
    	if (strlen($poststr)>0) $poststr.="&";
    	$poststr.=$name."=".urlencode($value);
    }
    
    if ($is_verbose) echo "connecting to antigate...";
    $fp=fsockopen($sendhost,80);
    if ($fp!=false)
    {
    	//echo "OK\n";
    	//echo "sending request...";
    	$header="POST /in.php HTTP/1.0\r\n";
    	$header.="Host: $sendhost\r\n";
    	$header.="Content-Type: application/x-www-form-urlencoded\r\n";
    	$header.="Content-Length: ".strlen($poststr)."\r\n";
    	$header.="\r\n$poststr\r\n";
    	//echo $header;
    	//exit;
    	fputs($fp,$header);
    	//echo "OK\n";
    	//echo "getting response...";
    	$resp="";
    	while (!feof($fp)) $resp.=fgets($fp,1024);
    	fclose($fp);
    	$result=substr($resp,strpos($resp,"\r\n\r\n")+4);
    	//echo "OK\n";
    }
    else 
    {
    	if ($is_verbose) echo "could not connect to antigate\n";
    	return false;
    }
    
    if (strpos($result, "ERROR")!==false)
    {
    	if ($is_verbose) echo "server returned error: $result\n";
        return false;
    }
    else
    {
        $ex = explode("|", $result);
        $captcha_id = $ex[1];
    	if ($is_verbose) echo "captcha sent, got captcha ID $captcha_id\n";
        $waittime = 0;
        if ($is_verbose) echo "waiting for $rtimeout seconds\n";
        sleep($rtimeout);
        while(true)
        {
            $result = file_get_contents("http://$sendhost/res.php?key=".$apikey.'&action=get&id='.$captcha_id);
            if (strpos($result, 'ERROR')!==false)
            {
            	if ($is_verbose) echo "server returned error: $result\n";
                return false;
            }
            if ($result=="CAPCHA_NOT_READY")
            {
            	if ($is_verbose) echo "captcha is not ready yet\n";
            	$waittime += $rtimeout;
            	if ($waittime>$mtimeout) 
            	{
            		if ($is_verbose) echo "timelimit ($mtimeout) hit\n";
            		break;
            	}
        		if ($is_verbose) echo "waiting for $rtimeout seconds\n";
            	sleep($rtimeout);
            }
            else
            {
            	$ex = explode('|', $result);
            	if (trim($ex[0])=='OK') return trim($ex[1]);
            }
        }
        
        return false;
    }
}
?> 