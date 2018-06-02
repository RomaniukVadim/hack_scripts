<?
function type($url){
				if(substr($url,0,5)=='http:')
				{
				preg_match("/^(http:\/\/vk.com\/)?([^0-9-]+)/i", $url, $matches);
				}
				else
				{
				preg_match("/^(https:\/\/vk.com\/)?([^0-9-]+)/i", $url, $matches);
				}
				if($matches[2]=="wall"){
				   $matches[2] = "post";
				}
				return $matches[2];
}
function first($url){
  $url = str_replace(type($url),'',$url);
  $url = str_replace("wall",'',$url);
  if(substr($url,0,5)=='http:'){
  preg_match("/^(http:\/\/vk.com\/)?([^_]+)/i", $url, $matches);
  }
  else
  {
  preg_match("/^(https:\/\/vk.com\/)?([^_]+)/i", $url, $matches);
  }
  return intval($matches[2]);
}
function second($url){
  $pos = strpos($url, "_");
  $url = substr($url, $pos+1);
  return intval($url);
}

function validateURL($url)  
{   
return preg_match('/^(([\w]+:)?\/\/)?(([\d\w]|%[a-fA-f\d]{2,2})+(:([\d\w]|%[a-fA-f\d]{2,2})+)?@)?([\d\w][-\d\w]{0,253}[\d\w]\.)+[\w]{2,4}(:[\d]+)?(\/([-+_~.\d\w]|%[a-fA-f\d]{2,2})*)*(\?(&?([-+_~.\d\w]|%[a-fA-f\d]{2,2})=?)*)?(#([-+_~.\d\w]|%[a-fA-f\d]{2,2})*)?$/', $url);  
} 

function valid_vk_url($url){
   if(strlen($url)<201 and $url!=NULL and is_numeric(first($url)) and is_numeric(second($url)) and validateurl($url)!=false){
      if(type($url)=="post" or type($url)=="video" or type($url)=="photo"){
	      return true;
	  }else{
	    return false;
	  }
   }else{
     return false;
   }
}
?>