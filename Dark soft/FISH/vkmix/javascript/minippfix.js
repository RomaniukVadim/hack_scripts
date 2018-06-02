$(document).ready(function() {
	
	/* Force placeholder support */
	if(!Modernizr.input.placeholder){
	  $("input").each( function(){
	    
	    thisPlaceholder = $(this).attr("placeholder");
				
	    if(thisPlaceholder!=""){
			
         $(this).val(thisPlaceholder);
         $(this).focus(function(){ if($(this).val() == thisPlaceholder) $(this).val(""); });
         $(this).blur(function(){ if($(this).val()=="") $(this).val(thisPlaceholder); });
       }
     });
	}
	
   /* Prefix  */
	$('.ppfix.pre').each(function() {
	  
	  var className, preElem;
	  
	  className  = $(this).attr('class').replace('pre', '').replace('ppfix', '').trim();
	  preElem    = '<span class="prefix ' + className + '">  </span>';
	  
	  $(this).before(preElem);
	});
	
	/* Postfix */
	$('.ppfix.post').each(function() {
	  
	  var className, preElem;
	  
	  className  = $(this).attr('class').replace('post', '').replace('ppfix', '').trim();
	  preElem    = '<span class="postfix ' + className + '">  </span>';
	  
	  $(this).after(preElem);
	});
	
});