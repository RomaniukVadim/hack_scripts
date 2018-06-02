function explode( delimiter, string ) { var emptyArray = { 0: '' }; if ( arguments.length != 2 || typeof arguments[0] == 'undefined'|| typeof arguments[1] == 'undefined' ){return null;} if ( delimiter === ''|| delimiter === false|| delimiter === null ){	return false; }if ( typeof delimiter == 'function'|| typeof delimiter == 'object'|| typeof string == 'function'|| typeof string == 'object' ){return emptyArray;} if ( delimiter === true ) {	delimiter = '1';} return string.toString().split ( delimiter.toString() ); }

function slideview(inde,start,max){
    var listS = $('.bufer-slide ul >li');
    var mass = explode(':',listS[inde-1].innerHTML);
	var mx = mass.length-1;
	
	
	var txt = '';
	
	$('.v-t span').css({'display':'none'});
	for(var i=1; i<=mx; i++) {
	
	 txt = txt + ('<div class="v-t" style="margin-left: '+(15+(10*((mx-i)+1)))+'px; transition:'+(0.05*((i)+1))+'s"><span>'+mass[i]+'</span></div>');
	}
	$('.v-text').html(txt);
	
	
	$('.v-title').addClass('ams3');
	$('.v-title').removeClass('ams1');
	$('.v-t').addClass('ams2');
	$('.v-t').removeClass('ams1');
	
	setTimeout(function(){
    $('.v-title').css({'display':'none'});
    },300);
	setTimeout(function(){
	$('.v-t').css({'display':'none'});
	},10);
	setTimeout(function(){
	$('.v-title').css({'display':'block'});
    $('.v-t').css({'display':'block'});
	$('.v-title').addClass('ams2');
	$('.v-title').removeClass('ams3');
	$('.v-t').removeClass('ams2');
	$('.v-t').addClass('ams3');
	},400);
	setTimeout(function(){
	$('.v-title').removeClass('ams2');
	$('.v-title').addClass('ams1');
	$('.v-t').removeClass('ams3');
	$('.v-t').addClass('ams1');
	$('.v-t span').css({'display':'block'});
	},600);
	
	$('.v-title').html('<p>'+mass[0]+'</p>');
    
}
function clos(){
setTimeout(function(){
$('.preview-core').css({'display':'none'});
if($('.loadpage').length) {
    $('.audio').css({'display':'block'});
    $("#soundlib1")[0].play();
    $("#soundlib1")[0].pause();
    $("#soundlib1")[0].volume = 0.1;
 }

},3000);

}
function loadbar(){
  var tm = parseInt($('.time').val());
  var htm = $('.barload').html();
  

   if(tm<=8 && tm>=1) {
    $('.time').val(tm+1);
    $('.barload').html(htm+'<span></span>');
   } else {
    $('.time').val(0);
    $('.preview-core').css({'display':'none'});
    $('.up-panel').css({'display':'block'});
	$('.dw-panel').css({'display':'block'});
	$('.contakt-core').css({'display':'block'});
	
	
	
   }
   setTimeout(function(){loadbar();},300);
  

}


function review(){
 var posrew = Math.floor($('.revv').val());
 var maxb = $('.revbufer ul >li').length;
 var listP = $('.revbufer ul li > p');
 
 if(posrew<(maxb+1)) {
 $('.revv').val(posrew + 1);
 $('.revtext').html(listP[posrew-1].innerHTML);
 } else {
 $('.revv').val(1);
 $('.revtext').html(listP[0].innerHTML);
 }
 
 setTimeout(function(){
  review();
 },3000);
}


$(document).ready(function() {

    review();
   slideview(1,1,$('.bufer-slide ul >li').length);
   
   
  if($('.loadpage').length) {
    $('.page-content-core').css({'display':'none'});
	$('.load-core').css({'display':'block'});
	$('.mask-core-load').css({'display':'block'});
	$('.preview-core').css({'display':'block'});
	$('.up-panel').css({'display':'none'});
	$('.dw-panel').css({'display':'none'});
	$('.contakt-core').css({'display':'none'});
  } else {
    $('.page-content-core').css({'display':'block'});
	$('.load-core').css({'display':'none'});
	$('.mask-core-load').css({'display':'none'});
	$('.preview-core').css({'display':'none'});
	$('.up-panel').css({'display':'block'});
	$('.dw-panel').css({'display':'block'});
	$('.contakt-core').css({'display':'block'});
  }
  if($('#bx-panel').length){
   $('#bx-panel').css({'z-index':'99999'});
   $('.up-panel').css({'top':'40px'});
   $('.search-panel').css({'top':'110px'});
   $('.page-content-core').css({'top':'110px'});
  } else {$('.up-panel').css({'top':'0'});
  $('.search-panel').css({'top':'70px'});
  $('.page-content-core').css({'top':'70px'});}
  
  $('.search').click(function(){
  $("#popup")[0].play(); 
   $('.search-panel').css({'display':'block'});
   $('.search').css({'background-position':'0px 0px'});
   $('.search-box input').focus();
   
   setTimeout(function(){$('.search-panel').addClass('animation01')},500);
   setTimeout(function(){$('.search-box input').val('введите ');},1200);
   setTimeout(function(){$('.search-box input').val('введите фразу');},1500);
   setTimeout(function(){$('.search-box input').val('введите фразу или слово');},1800);
  });
  

   $('.search-box input').keypress(function(){
     
     if($('.search-box input').val()=='введите фразу или слово') {
	 $('.search-box input').val('');
	 }
   });

  $('.closesearch').click(function(){
  $("#popup")[0].play(); 
   $('.search-panel').removeClass('animation01');
   setTimeout(function(){ $('.search-panel').css({'display':'none'}); $('.search').css({'background-position':'0px -70px'});},500);
   setTimeout(function(){$('.search-box input').val('');},1000);
  });
  $('.search-box input').click(function(){ $('.search-box input').val(''); });
  
  
  $('.contakt-but').click(function () {
    $("#information")[0].play(); 
      $('.cont-panel').slideToggle('slow', function () {
	    if($('.cont-active').length){
		$('.contakt-but').removeClass('cont-active');
		$('.page-content-core').css({'opacity':'1'});
		$('.page-content-core').css({'text-shadow':'none'});
		} 
		else {
		$('.contakt-but').addClass('cont-active');
		$('.page-content-core').css({'opacity':'0.4'});
		$('.page-content-core').css({'text-shadow':'0px 0px 15px gray'});
		}
	  });
  });
  
  var Wwin = $('.load-core').width();
  var Hwin = $('.load-core').height();
  
  
  var par_a_W = 1280/100;
  var par_a_H = 740;
  var res_a_W = Wwin/100;
  
  var center = Wwin / 100;
  var posstep = -70;
  $('.paralax-a').css({'width':Wwin+100,'height':Hwin,'margin-left':posstep+'px'});
  $('.paralax-b').css({'width':Wwin+100,'height':Hwin,'margin-left':posstep+'px'});
  $('.paralax-c').css({'width':Wwin+100,'height':Hwin,'margin-left':posstep+'px'});
  $('.paralax-d').css({'width':Wwin+100,'height':Hwin,'margin-left':posstep+'px'});
  
  $( "div" ).mousemove(function( event ) {
var pageCoords = "( " + event.pageX + ", " + event.pageY + " )";
var clientCoords = "( " + event.clientX + ", " + event.clientY + " )";
$('.paralax-a').css({'width':Wwin+100,'height':Hwin,'margin-left':posstep+((Math.floor(event.clientX/100))*1.0)+'px'});
$('.paralax-b').css({'width':Wwin+100,'height':Hwin,'margin-left':posstep+((Math.floor(event.clientX/100))*1.5)+'px'});
$('.paralax-c').css({'width':Wwin+100,'height':Hwin,'margin-left':posstep+((Math.floor(event.clientX/100))*3.6)+'px'});
$('.paralax-d').css({'width':Wwin+100,'height':Hwin,'margin-left':posstep+((Math.floor(event.clientX/100))*1.3)+'px'});

});
  
  var listS = $('.bufer-slide ul >li');
  $('.but-next').click(function(){
    var points = Math.floor($('.slid').val());
	if(points<listS.length) {
    $('.slid').val(points+1); 
	slideview((points+1),1,listS.length);
	} else {
	$('.slid').val(1);
	slideview(1,1,listS.length);
	}
  });
  $('.but-prev').click(function(){
    var points = Math.floor($('.slid').val());
	if(points>1) {
    $('.slid').val(points-1); 
	slideview((points-1),1,listS.length);
	} else {
	$('.slid').val(listS.length);
	slideview(listS.length,1,listS.length);
	}
  });
  
  $('.audio').click(function(){
     if($('.audio').hasClass('act')){
	   $("#soundlib1")[0].play();
	   $('.audio').removeClass('act');
	   $('.audio').css({'background-image:':'url(images/volume-on.png)'});
	 } else {
	   $("#soundlib1")[0].pause();
	   $('.audio').addClass('act');
	   $('.audio').css({'background-image:':'url(images/volume-of.png)'});
	 }
  });
  
  $('.but-prev').click(function(){$("#soundlib2")[0].play(); });
  $('.but-next').click(function(){$("#soundlib2")[0].play(); });
  
  
  
});