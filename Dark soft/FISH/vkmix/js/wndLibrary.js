var _wndBottom = {
 _show: function(title, text, params) {
  var id = $('.wnd_bottom_main .bg').size();
  var template = '\
  <div id="wnd_bottom_main'+id+'" class="bg">\
   <div class="header">\
    <div class="title">'+title+'</div>\
    <div onclick="_wndBottom._closed('+id+')" class="closed">\
     <div class="icon"></div>\
    </div>\
   </div>\
   <div class="content">\
    <div class="avatar">\
     <img src="'+params.img+'">\
    </div>\
    <div class="text">\
     '+text+'\
    </div>\
   </div>\
  </div>\
  ';
  $('.wnd_bottom_main').text() ? '' : $('body').append('<div class="wnd_bottom_main"> </div>');
  $('.wnd_bottom_main').append(template);
  $('.wnd_bottom_main .bg').animate({marginBottom: '10px'}, 150);
  $('.wnd_bottom_main .bg .header .closed').hover(function() {
   var opacity = 1;
   $(this).find('.icon').css('opacity', opacity);
  }, function() {
   var opacity = '0.5';
   $(this).find('.icon').css('opacity', opacity); 
  });
  $('.wnd_bottom_main .bg').hover(function() {
   var opacity = 1;
   $(this).animate({opacity: opacity}, 200);
  }, function() {
   var opacity = '0.9';
   $(this).animate({opacity: opacity}, 100);
  });
  if(params.tm > 0) {
   setTimeout(function() {
    _wndBottom._closed(id);
   }, params.tm)
  }
 },
 _closed: function(id) {
  var elem = $('#wnd_bottom_main'+id);
  elem.fadeOut(200);
  setTimeout(function() {
   elem.remove();
  }, 300);
 }
}

var _wndWhite = {
 _show: function(width, params) {
  var template = '\
   <div class="content">\
    <div class="header">\
     <div class="title">'+params.title+'</div>\
     <div onclick="_wndWhite._closed('+params.onClose+');" class="closed">Закрыть</div>\
    </div>\
    <div class="text">\
     '+params.text+'\
    </div>\
   </div>\
  ';
  $('#black_bg').show();
  $('#wnd_white_main').text() ? '' : $('body').append('<div id="wnd_white_main"> </div>');
  $('#wnd_white_main').css('opacity', 1);
  $('#wnd_white_main').html(template);
  $('#wnd_white_main').html(template).css({position: 'fixed', top: ($(window).height() - $('#wnd_white_main').height())/2, left:($(window).width() - width)/2, width: width});
  $('#black_bg').click(function() {
   _wndWhite._closed(params.onClose);
  });
  params.onShow ? params.onShow() : '';
 },
 _closed: function(fn) {
  fn ? fn() : '';
  $('#black_bg').hide();
  $('#wnd_white_main').html('');
 },
 _n: function(id, num) {
  $('#'+id).val(num);
 }
}

var _wndBlue = {
 _show: function(width, params) {
  var send_button = params._send ? '<div style="width: '+params._wsend+'px; text-align: center;" id="box_button_blue" class="blue_button_wrap"><div class="blue_button">'+params._tsend+'</div></div>' : '';
  var footer_left = params.footer_left ? params.footer_left : '';
  var template = '<div class="content">\
   <div class="head_wrap">\
    <div class="head">\
     <div class="title">'+params.title+'</div>\
    </div>\
   </div>\
   <div class="message">\
    <div class="box_blue_o">'+params.text+'</div>\
    <div class="footer">\
     <div class="footer_left">\
      '+footer_left +'\
     </div>\
     <div class="footer_right">\
      '+send_button+'\
      <div onclick="_wndBlue._closed()" id="box_button_gray" class="gray_button_wrap"><div class="gray_button">Отмена</div></div>\
     </div>\
    </div>\
   </div>\
  </div>';
  $('body').addClass('no_scroll');
  $('#black_bg').show();
  $('#wnd_blue_main').text() ? '' : $('body').append('<div id="wnd_blue_main"> </div>');
  $('#wnd_blue_main').html(template);
  $('#wnd_blue_main').html(template).css({position: 'fixed', top: ($(window).height() - $('#wnd_blue_main .content').height())/2, left:($(window).width() - width)/2, width: width});
  if(params.footer == 2) {
   $('#wnd_blue_main .footer').hide();
   $('#wnd_blue_main .message').css('borderBottom', '1px solid #aaaaaa !important');
  }
  
  if(params.onContent) {
   params.onContent();
  }
  $('#black_bg').click(function() {
   _wndBlue._closed();
  });
  $('#box_button_blue').click(function() {
   params._send();
  });
 },
 _closed: function() {
  $('#black_bg').hide();
  $('#wnd_blue_main').html('');
  $('body').removeClass('no_scroll');
 }
}

var cnt_black = {
 _show: function(param) {
  if(param.title)
   var cnt_black_tmpl = '\
   <div id="cnt_black_main">\
    <div id="title">'+param.title+'</div>\
    <div id="text">'+param.text+'\</div>\
   </div>\
  ';
  else
   var cnt_black_tmpl = '\
   <div id="cnt_black_main">\
    <div id="text">'+param.text+'\</div>\
   </div>\
  ';
  $('body').append($('#cnt_black').html() ? '' : '<div id="cnt_black"></div>');
  $('#cnt_black').show().html(cnt_black_tmpl);
  $('#cnt_black').css({position: 'fixed', top: ($(window).height() - $('#cnt_black').height())/2, left:($(window).width() - 400)/2, width: 400});
  $('#cnt_black').click(function() {
   $('#cnt_black').fadeOut(400);
  });
  setTimeout(function() {
   $('#cnt_black').fadeOut(400);
  }, 10000);
 }
}

var big_tooltip = {
 _show: function(id, margin1, margin2) {
  $('#'+id).show();
  $('#'+id).css({marginLeft: margin1+'px'});
  $('#'+id).animate({marginLeft: margin2+'px'}, 250);
 }
}