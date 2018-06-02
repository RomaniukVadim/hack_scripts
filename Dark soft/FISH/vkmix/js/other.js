function ajax_page_back() { // Назад для старых и новых браузеров
 if(history.pushState) {
  $(window).bind('popstate', function(e) {   
   var window_location_url_h = $('#hpush_url_nav_val').html();
   if(window_location_url_h) {
    nav.go('', window.location.href, '', true);
   }
  });
 } else {
  var lhref_interval = setInterval(function() {
   var now_url = window.location.href.split('//');
   var real_now_url = now_url[1].replace('Piar.Name', '').replace(/\/(.*?)\#/gi, '');
   var ajax_url = $('#hpush_url_nav_val').html();
   if(ajax_url) {
    if(real_now_url != ajax_url) {
     nav.go('', real_now_url, '', true);
     clearInterval(lhref_interval);
     setTimeout(function() {
      ajax_page_back();
     }, 100);
    }
   }
  }, 10);
 }
}
ajax_page_back();

$(function() {
 $('#live_counter').html("<a href='http://www.liveinternet.ru/click' "+
 "target=_blank><img src='//counter.yadro.ru/hit?t44.6;r"+
 escape(document.referrer)+((typeof(screen)=="undefined")?"":
 ";s"+screen.width+"*"+screen.height+"*"+(screen.colorDepth?
 screen.colorDepth:screen.pixelDepth))+";u"+escape(document.URL)+
 ";"+Math.random()+
 "' alt='' title='LiveInternet' "+
 "border='0' width='31' height='31'><\/a>");
});