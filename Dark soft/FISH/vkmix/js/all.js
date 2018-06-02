/*!

 * all.js

*/

var users = {
 box_reg: function(params) {
  var _title = 'Регистрация нового пользователя';
  var _size = 600;
  var _url = '/f.min.reg';
  var _wnd_open = $('#ureg_wnd_open').val() * 1;
  nav.loader(1);
  _wndBlue._closed();
  $.get(_url, function(data) {
   nav.loader();
   _wndWhite._show(_size, {title: _title, text: data, onShow: function() {
    _placeholder('#ureg_login');
    _placeholder('#ureg_password');
    _placeholder('#ureg_email');
    // регистрация по нажатию на Enter
    $('#ureg_login, #ureg_password, #ureg_email').keydown(function(event) {
     var keyCode = event.which;
     if(keyCode == 13) users._post_reg();
    });
   }});
  });
 },
 _post_login: function() {
  var ulogin = _val('#ulogin') ? $('#ulogin').val() : '';
  var upassword = _val('#upassword') ? $('#upassword').val() : ''; 

  if(!ulogin) {
   $('#ulogin').focus();
   return;
  } else if(upassword.length < 6) {
   $('#upassword').focus();
   return;
  }

  if($('#login_in').html()) {
   $('#login_in .blue_button').html('<div class="upload"></div>');
  } else {
   $('#login_form_error').html('');
   $('#new_main_header_logo_form_overflow_right_button').html('<div class="upload"></div>');
  }
  $.post('/auth', {
   ulogin: ulogin,
   upassword: upassword
  }, function(data) {
   var response = $.parseJSON(data);
   var error_title = response.error_title;
   var error_text = response.error_text;
   if($('#login_in').html()) {
    $('#login_in .blue_button').html('Войти в систему');
   } else {
    $('#new_main_header_logo_form_overflow_right_button').html('Войти');
   }
   if(error_text) {
    $('#login_form_error').html(error_text);
   } else if(response.response == 1) {
    $('#new_main_header_logo_form_overflow_right_button').html('<div class="upload"></div>');
    nav.go('', '/tasks');
   }
  });
 },
 _post_reg: function() {
  $('#whiteb_info .system').hide();
  $('#whiteb_info .other').hide();
  $('#ureg_login, #ureg_password, #ureg_email').removeClass('field_error_i');
  var ulogin = $('#ureg_login').val();
  var upassword = $('#ureg_password').val();
  var uemail = $('#ureg_email').val();
  var ucaptcha_code = $('#captcha_code').val();
  var uref = $('#ureg_ref').val();
  if(ulogin.length < 1 || upassword.length < 1 || ulogin == 'Введите логин' || upassword == 'Введите пароль') { 
   $('#whiteb_info .other').hide();
   $('#whiteb_info .system').show();
   $('#whiteb_info').removeClass('error_red').addClass('error');
   setTimeout(function() {
    $('#whiteb_info').removeClass('error');
   }, 1500);
  } else if(!ulogin.match(/^([@a-zA-Z0-9]){1,30}$/i)) {
    $('#whiteb_info .system').hide();
    $('#whiteb_info .other').show();
    $('#whiteb_info').addClass('error_red');
    $('#ureg_login').focus();
    $('#ureg_login').addClass('field_error_i');
    $('#whiteb_info .other').html('Неправильный <b>логин</b>. Поле «логин» может содержать только латинские символы или цифры и не превышать 30 символов.');
    return false;
  } else if(upassword.length < 6) {
    $('#whiteb_info .system').hide();
    $('#whiteb_info .other').show();
    $('#ureg_password').focus();
    $('#whiteb_info').addClass('error_red');
    $('#ureg_password').addClass('field_error_i');
    $('#whiteb_info .other').html('Слишком <b>короткий пароль</b>. Пароль должен состоять не менее, чем из 6 символов.');
    return false;
  } else if(!upassword.match(/^([a-zA-Z0-9]){6,32}$/i)) {
    $('#whiteb_info .system').hide();
    $('#whiteb_info .other').show();
    $('#ureg_password').focus();
    $('#ureg_password').addClass('field_error_i');
    $('#whiteb_info').addClass('error_red');
    $('#whiteb_info .other').html('Неправильный <b>пароль</b>. Поле «пароль» может содержать только латинские символы или цифры и не превышать 32 символа.');
    return false;
  } else if(!uemail.match(/^[a-zA-Z0-9_\.\-]+@([a-zA-Z0-9\-]+\.)+[a-zA-Z]{2,6}$/i)) {
    $('#whiteb_info .system').hide();
    $('#whiteb_info .other').show();
    $('#ureg_email').focus();
    $('#ureg_email').addClass('field_error_i');
    $('#whiteb_info').addClass('error_red');
    $('#whiteb_info .other').html('Неправильный <b>email</b>. Поле «e-mail» имеет неправильный формат.');
    return false;
  } else {
   $('#reg_form_white_breg .blue_button').html('<div align="center"><div class="upload"></div></div>');
   $.post('/reg', {
    ulogin: ulogin,
    upassword: upassword,
    uemail: uemail,
    uref: uref,
    ucaptcha_code: ucaptcha_code,
    ucaptcha_key: $('#captcha_key').val()
   }, function(data) {
    var response = $.parseJSON(data);
    var error_text = response.error_text;
    $('#reg_form_white_breg .blue_button').html('Зарегистрироваться <div id="reg_form_white_breg_right"></div>');
    if(response.error_text == 'Неверно введен код безопасности.') {
     $('#wnd_white_main').css('opacity', 0);
     captcha_box._show(function(){
      $('#captcha_code').val($('#captcha_text').val());
      setTimeout(function() {
       _wndBlue._closed();
       $('#black_bg').show();
       $('#wnd_white_main').css('opacity', 1);
       $('#reg_form_white_breg').click();
      }, 10);
     });
    } else if(response.error_text) {
     $('#whiteb_info .system').hide();
     $('#whiteb_info .other').show();
     $('#whiteb_info').addClass('error_red');
     $('#whiteb_info .other').html(error_text);
    } else if(response.response == 1) {
     _wndWhite._closed();
     nav.loader(1);
     nav.go('', '/tasks', function() {
      cnt_black._show({text: 'На Ваш e-mail <span>'+uemail+'</span> отправлена ссылка для подтверждения регистрации.'});
      setTimeout(function() {
       big_tooltip._show('support_start_message', 100, 95);
      }, 300);
     });
    }
   });
  }
 },
 change_password: function() {
  $('#my_settings_change_password_error').hide();
  $('#my_settings_old_password, #my_settings_new_password, #my_settings_new_password2').removeClass('field_error_i');
  
  if($('#my_settings_old_password').val().length < 6) {
   $('#my_settings_change_password_error').show().html('<div class="error_msgm">Пароль должен состоять не менее, чем из 6 символов.</div>');
   $('#my_settings_old_password').focus();
   $('#my_settings_old_password').addClass('field_error_i');
   return;
  } else if($('#my_settings_new_password').val().length < 6) {
   $('#my_settings_change_password_error').show().html('<div class="error_msgm">Пароль должен состоять не менее, чем из 6 символов.</div>');
   $('#my_settings_new_password').focus();
   $('#my_settings_new_password').addClass('field_error_i');
   return;
  } else if($('#my_settings_new_password2').val().length < 6) {
   $('#my_settings_change_password_error').show().html('<div class="error_msgm">Пароль должен состоять не менее, чем из 6 символов.</div>');
   $('#my_settings_new_password2').focus();
   $('#my_settings_new_password2').addClass('field_error_i');
   return;
  }
  
  $('#settings_password_button .blue_button').html('<div class="upload"></div>');
  $.post('/settings/change.password', {
   old_password: $('#my_settings_old_password').val(),
   new_password: $('#my_settings_new_password').val(),
   new_password2: $('#my_settings_new_password2').val(),
   ssid: $('#ssid').val()
  }, function(data) {
   var response = $.parseJSON(data);
   var error_text = response.error_text;
   $('#settings_password_button .blue_button').html('Изменить пароль');
   if(error_text == 'login') {
    nav.go('', '/');
    return;
   }
   if(error_text) {
    $('#my_settings_change_password_error').show().html('<div class="error_msgm">'+error_text+'</div>');
   } else if(response.success == 1) {
    $('#my_settings_change_password_error').show().html('<div class="msg">Пароль успешно изменен.</div>');
   } else {
    $('#my_settings_change_password_error').show().html('<div class="error_msgm">Неизвестная ошибка.</div>');
   }
  });
 },
 change_login: function() {
  $('#my_settings_account_error').hide();
  $('#my_settings_account_login_field').removeClass('field_error_i');
  
  if($('#my_settings_account_login_field').val().length < 1) {
   $('#my_settings_account_error').show().html('<div class="error_msgm">Слишком короткий логин.</div>');
   $('#my_settings_account_login_field').focus();
   $('#my_settings_account_login_field').addClass('field_error_i');
   return;
  } 
  
  $('#settings_login_button .blue_button').html('<div class="upload"></div>');
  $.post('/settings/change.login', {
   login: $('#my_settings_account_login_field').val(),
   ssid: $('#ssid').val()
  }, function(data) {
   var response = $.parseJSON(data);
   var error_text = response.error_text;
   $('#settings_login_button .blue_button').html('Изменить логин');
   if(error_text == 'login') {
    nav.go('', '/');
    return;
   }
   if(error_text) {
    $('#my_settings_account_error').show().html('<div class="error_msgm">'+error_text+'</div>');
   } else if(response.success == 1) {
    $('#my_settings_account_error').show().html('<div class="msg">Логин успешно изменен.</div>');
   } else {
    $('#my_settings_account_error').show().html('<div class="error_msgm">Неизвестная ошибка.</div>');
   }
  });
 },
 delete_account: function() {
  nav.loader_page();
  $.getJSON('/settings/delete.account', {
   ssid: $('#ssid').val()
  }, function(data) {
   var response = data;
   if(response.success == 1) {
    if(response.type == 'return') {
      nav.go('', '/tasks');
    } else {
     nav.go('', '/deleted');
    }
   }
  });
 },
 _add_vk: function() {
  _wndBlue._closed();
  nav.loader(1);
  $.get('/f.vk.add', function(data) {
   nav.loader();
   _wndWhite._show(600, {title: 'Прикрепление страницы ВКонтакте', text: data, onShow: function() {
    $('#done_white_box_add_comment').click(function() {
     $('#done_white_box_add_comment .blue_button').html('<div class="upload"></div>');
     $('#add_vkontakte_white_box_error').hide();
     $.post('/f.vk.add', {
      add: 1
     }, function(data) {
      var response = $.parseJSON(data);
      var error_text = response.error_text;
      $('#done_white_box_add_comment .blue_button').html('<b>Нажмите сюда</b>, если оставили комментарий');
      if(error_text == 'login') {
       nav.go('', '/');
       return;
      }
      if(error_text) {
       $('#add_vkontakte_white_box_error').show().html(error_text);
      } else if(response.success == 1) {
       _wndWhite._closed();
       $('#info_msg_header').remove();
       cnt_black._show({title: 'Страница ВКонтакте прикреплена.', text: response.text});
      } else {
       $('#add_vkontakte_white_box_error').show().html('Неизвестная ошибка.');
      }
     });
    });
   }});
  });
 },
 reemail: function() {
  $('#reemail_a').hide();
  cnt_black._show({text: 'Письмо со ссылкой для подтверждения выслано повторно.'});
  $.getJSON('/reemail', {}, function(data) {
   var response = data;
   var error_text = response.error_text;
   if(error_text == 'login') {
    nav.go('', '/');
    return;
   }
  });
 }
}

var _head = {
 _up: function() {
  $('body, html').animate({scrollTop: 0}, 200);
 }
}

var _money = {
 _plus: function(n) {
  var money = $('.balance_menu_left_num').text() * 1;
  var money_plus = money + (n * 1);
  $('.balance_menu_left_num').text(money_plus);
  $('.balance_menu_left_num_desc').text(declOfNum(money_plus, ['балл', 'балла', 'баллов']));
  if($('#balance_counter').html()) {
   var balance_settings_num = $('#balance_counter').find('b').text().match(/([0-9]+)/);
   var balance_settings_num_result = balance_settings_num[1] * 1;
   var balance_settings_num_sum_result = balance_settings_num_result + (n * 1);
   $('#balance_counter').find('b').html(balance_settings_num_sum_result+' '+declOfNum(balance_settings_num_sum_result, ['балл', 'балла', 'баллов']));
  }
 },
 _minus: function(n) {
  var money = $('.balance_menu_left_num').text() * 1;
  var money_plus = money - (n * 1);
  $('.balance_menu_left_num').text(money_plus);
  $('.balance_menu_left_num_desc').text(declOfNum(money_plus, ['балл', 'балла', 'баллов']));
  if($('#balance_counter').html()) {
   var balance_settings_num = $('#balance_counter').find('b').text().match(/([0-9]+)/);
   var balance_settings_num_result = balance_settings_num[1] * 1;
   var balance_settings_num_sum_result = balance_settings_num_result - (n * 1);
   $('#balance_counter').find('b').html(balance_settings_num_sum_result+' '+declOfNum(balance_settings_num_sum_result, ['балл', 'балла', 'баллов']));
  }
 }
}
var cupons = {
 _add: function(obj) { 
 $('#task_add_error').hide();
 $('#add_task_url, #add_task_amount, #add_task_count').removeClass('field_error_i');
 $('.big_tooltip_wrap').hide();
 

  $('#add_task_button .blue_button').html('<div class="upload"></div>');
 

  $.post('/cupons/add.cupons', {
   points: $('#add_cup_amount').val(),
   type: '1',
   ssid: obj.ssid
  }, function(data) {
	//alert(data);
   var response = $.parseJSON(data);
   var error_text = response.error_text;
   $('#add_task_button .blue_button').html('Создать купон');
   if(error_text == 'login') {
    nav.go('', '/');
    return;
   }
   if(error_text == 'captcha') {
    captcha_box._show(function(){
     $('#captcha_code').val($('#captcha_text').val());
     $('#add_task_button').click();
     return false;
    });
    return;
   }
   if(error_text) {
    $('body, html').animate({scrollTop: 0}, 200);
    $('#task_add_error').show().html(error_text);
   } else if(response.success) {
    var response_points = response.points+' '+declOfNum(response.points, ['балл', 'балла', 'баллов']);
    $('#form_add_task input[type="text"]').val('');
    _wndBlue._closed();
    nav.go('', '/cupons/?section=my');
    _wndBottom._show(
     'С Вас списаны баллы.', 
     'С Вашего счета '+declOfNum(response.points, ['списан', 'списано', 'списано'])+' <a href="/settings/balance" onclick="nav.go(this); return false"><b>'+response_points+'</b></a> за создание купона.', {
      img: '/images/wnd_bottom/coins.png?1'
     });
   } else {
    $('#task_add_error').show().html('Неизвестная ошибка.');
   }
  });
 },
  _actv: function(obj) { 
 $('#task_add_error').hide();
 $('#add_task_url, #add_task_amount, #add_task_count').removeClass('field_error_i');
 $('.big_tooltip_wrap').hide();
 

  $('#add_task_button .blue_button').html('<div class="upload"></div>');


  $.post('/cupons/add.cupons', {
   code: $('#add_cup_code').val(),
   type: '2',
   ssid: obj.ssid
  }, function(data) {
	//alert(data);
   var response = $.parseJSON(data);
   var error_text = response.error_text;
   $('#add_task_button .blue_button').html('Активировать');
   if(error_text == 'login') {
    nav.go('', '/');
    return;
   }
   if(error_text == 'captcha') {
    captcha_box._show(function(){
     $('#captcha_code').val($('#captcha_text').val());
     $('#add_task_button').click();
     return false;
    });
    return;
   }
   if(error_text) {
    $('body, html').animate({scrollTop: 0}, 200);
    $('#task_add_error').show().html(error_text);
   } else if(response.success) {
    var response_points = response.points+' '+declOfNum(response.points, ['балл', 'балла', 'баллов']);
    $('#form_add_task input[type="text"]').val('');
    _wndBlue._closed();
    nav.go('', '/cupons/?section=active');
    _wndBottom._show(
     'Вам зачисленны баллы.', 
     'На ваш счет '+declOfNum(response.points, ['начислен', 'начисленно', 'начисленно'])+' <a href="/settings/balance" onclick="nav.go(this); return false"><b>'+response_points+'</b></a> за активацию купона.', {
      img: '/images/wnd_bottom/coins.png?1'
     });
   } else {
    $('#task_add_error').show().html('Неизвестная ошибка.');
   }
  });
 },
}
var task_window_open = '';
var tasks = {
 _add: function(section, obj) { 
 $('#task_add_error').hide();
 $('#add_task_url, #add_task_amount, #add_task_count').removeClass('field_error_i');
 $('.big_tooltip_wrap').hide();
 
  if(!$('#add_task_url').val().match(/^http(s)?\:\/\/vk\.com\//) && !$('#add_task_url').val().match(/^vk\.com\//)) {
   $('body, html').animate({scrollTop: 0}, 200);
   $('#task_add_error').show().html('Проверьте правильность введенной ссылки.');
   $('#add_task_url').focus();
   $('#add_task_url').addClass('field_error_i');
   $('.big_tooltip_wrap').hide();
   big_tooltip._show('tooltip_task_add_url', 260, 250);
   return;
  } else if(!$('#add_task_amount').val().match(/([0-9]+)/)) {
   $('#add_task_amount').focus();
   $('#add_task_amount').addClass('field_error_i');
   $('.big_tooltip_wrap').hide();
   big_tooltip._show('tooltip_task_add_amount', 70, 60);
   return;
  } else if(!$('#add_task_count').val().match(/([0-9]+)/)) {
   $('#add_task_count').focus();
   $('#add_task_count').addClass('field_error_i');
   $('.big_tooltip_wrap').hide();
   big_tooltip._show('tooltip_task_add_count', 70, 60);
   return;
  }
  
  $('#add_task_button .blue_button').html('<div class="upload"></div>');
  if(section == 'polls') {
   
  } else {
   $('#add_task_comments_value').val('');
  }
  $('.add_task_comment').each(function() {
   var comment = $(this).val() ? $(this).val()+'`' : '';
   var values = $('#add_task_comments_value').val();
   $('#add_task_comments_value').val(values+''+comment);
  });
  $.post('/tasks/add.post', {
   section: section,
   cat: $('#select_value_task_add_categories').val(),
   url: $('#add_task_url').val(),
   amount: $('#add_task_amount').val(),
   count: $('#add_task_count').val(),
   comments: $('#add_task_comments_value').val(),
   captcha_code: $('#captcha_code').val(),
   captcha_key: $('#captcha_key').val(),
   ssid: obj.ssid
  }, function(data) {
   var response = $.parseJSON(data);
   var error_text = response.error_text;
   $('#add_task_button .blue_button').html('Создать задание');
   if(error_text == 'login') {
    nav.go('', '/');
    return;
   }
   if(error_text == 'captcha') {
    captcha_box._show(function(){
     $('#captcha_code').val($('#captcha_text').val());
     $('#add_task_button').click();
     return false;
    });
    return;
   }
   if(error_text) {
    $('body, html').animate({scrollTop: 0}, 200);
    $('#task_add_error').show().html(error_text);
   } else if(response.success) {
    var response_points = response.points+' '+declOfNum(response.points, ['балл', 'балла', 'баллов']);
    $('#form_add_task input[type="text"]').val('');
    _wndBlue._closed();
    nav.go('', '/tasks/my');
    _wndBottom._show(
     'С Вас списаны баллы.', 
     'С Вашего счета '+declOfNum(response.points, ['списан', 'списано', 'списано'])+' <a href="/settings/balance" onclick="nav.go(this); return false"><b>'+response_points+'</b></a> за размещение задания.', {
      img: '/images/wnd_bottom/coins.png?1'
     });
   } else {
    $('#task_add_error').show().html('Неизвестная ошибка.');
   }
  });
 },
 _delete: function(id) {
   var text = 'Вы действительно хотите удалить задание? Это действие нельзя будет отменить. <br /> <br />\
   На Ваш счет будут возвращены оставшиеся баллы.\
   ';
  _wndBlue._show(430, {title: 'Удаление задания', text: '<div id="task_del_box">'+text+'</div>', _wsend: 80, _tsend: 'Удалить', _send: function() {
   $('#box_button_blue .blue_button').html('<div class="upload"></div>');
   $.getJSON('/tasks/delete.task', {
    id: id,
    ssid: $('#ssid').val()
   }, function(data) {
    var response = data;
    var error_text = response.error_text;
    $('#box_button_blue .blue_button').html('Удалить');
    _wndBlue._closed();
    if(error_text == 'login') {
     nav.go('', '/');
     return;
    }
    if(error_text) {
     $('#task'+id).hide();
     $('#del_table'+id).show().html(error_text);
     _wndBlue._closed();
    } else if(response.success) {
     if(response.points > 0) {
      _money._plus(response.points);
      var response_points = response.points+' '+declOfNum(response.points, ['балл', 'балла', 'баллов']);
      var text = $('#task'+id+' .task_my_mini_description').html();
      $('#tasks_bar_num').text($('#tasks_bar_num').text() * 1 - 1);
      $('#tasks_bar_word').text(declOfNum($('#tasks_bar_num').text(), ['задание', 'задания', 'заданий']));
     _wndBottom._show(
      'Вы получили баллы.', 
      'На Ваш счет '+declOfNum(response.points, ['возвращен', 'возвращено', 'возвращено'])+' <a href="/settings/balance" onclick="nav.go(this); return false"><b>'+response_points+'</b></a> за удаление задания «'+text+'».', {
       img: '/images/wnd_bottom/coins.png?1'
      });
      $('#task'+id).hide();
     }
    } else {
     $('#task'+id).hide();
     $('#del_table'+id).show().html('Неизвестная ошибка.'); 
    }
   });
  }});
 },
 _ignored: function(id) {
  $('#task'+id).find('.image_section').hide();
  $('#task'+id).find('.image .ajax_loader').css('opacity', 1);
  $.getJSON('/tasks/ignored.task', {
   id: id,
   ssid: $('#ssid').val()
  }, function(data) {
   var response = data;
   var error_text = response.error_text;
   if(error_text == 'login') {
    nav.go('', '/');
    return;
   }
   if(error_text) {
    $('#task'+id).hide();
    $('#del_table'+id).show().html(error_text);
   } else if(response.success) {
    $('#task'+id).hide();
    $('#tasks_bar_num').text($('#tasks_bar_num').text() * 1 - 1);
    $('#tasks_bar_word').text(declOfNum($('#tasks_bar_num').text(), ['задание', 'задания', 'заданий']));
   } else {
    $('#task'+id).hide();
    $('#del_table'+id).show().html('Неизвестная ошибка.'); 
   }
  });
 },
 _edit_form: function(id) {
  nav.loader(1);
  $.get('/tasks/edit.form', {
   id: id,
   section: section
  }, function(data) {
   var response = data;
   nav.loader();
   if(response == 'login') {
    nav.go('', '/');
    return;
   }
   _wndBlue._show(530, {title: 'Редактирование задания', text: '<div id="task_edit_box">'+response+'</div>', onContent: function() {
    _placeholder('#add_task_count');
    var section_id = $('#task_edit_box #form_edit_task_section').text() * 1;
    var cat_id = $('#task_edit_box #form_edit_task_cat').text();
    tasks._tcategories($('#tasks_my_categories_hide').html(), cat_id);
    $('#add_task_count').keyup(function() {
     var _count = $(this).val() * 1;
     if(section_id == 1) {
      $('#count_right').text(declOfNum(_count, ['отметка «Мне нравится»', 'отметки «Мне нравится»', 'отметок «Мне нравится»']));
     } else if(section_id == 2) {
      $('#count_right').text(declOfNum(_count, ['репост', 'репоста', 'репостов']));
     } else if(section_id == 3) {
      $('#count_right').text(declOfNum(_count, ['комментарий', 'комментария', 'комментариев']));
     } else if(section_id == 4) {
      $('#count_right').text(declOfNum(_count, ['подписчик', 'подписчика', 'подписчиков']));
     } else if(section_id == 5) {
      $('#count_right').text(declOfNum(_count, ['вступивший', 'вступивших', 'вступивших']));
     } else if(section_id == 6) {
      $('#count_right').text(declOfNum(_count, ['голос', 'голоса', 'голосов']));
     } else {
      setInterval(function() {$('#box_button_blue').hide()}, 0);
     }
    });
    $('#form_add_task input[type="text"]').keydown(function(event) {
     var keyCode = event.which;
     if(keyCode == 13) {
      $('#box_button_blue').click();
      return false;
     }
    });
    $('#add_task_count').keyup(function() {
     if(!$(this).val().match(/^([0-9]+)$/)) {
      $(this).val('');
     }
    });
   }, _wsend: 110, _tsend: 'Редактировать', _send: function() {
    $('.form_edit_task .error').hide();
    $('#tasks_edit_captcha').hide();
    $('#box_button_blue .blue_button').html('<div class="upload"></div>');
    $.post('/tasks/edit.task', {
     id: id,
     count: $('#add_task_count').val(),
     cat: $('#select_value_task_add_categories').val(),
     captcha_code: $('#tasks_edit_captcha_field').val(),
     captcha_key: $('#captcha_key').val(),
     ssid: $('#ssid').val()
    }, function(data) {
     var response = $.parseJSON(data);
     var error_text = response.error_text;
     $('#box_button_blue .blue_button').html('Редактировать');
     if(error_text == 'login') {
      nav.go('', '/');
      return;
     }
     if(error_text == 'captcha') {
      $('.form_edit_task .error').show().html('Неверно введен код безопасности.');
      var start_rand = myrand(1111111111, 999999999);
      $('#captcha_key').val(start_rand);
      $('#tasks_edit_captcha_field').val('');
      $('#tasks_edit_captcha').show();
      $('#tasks_edit_captcha').find('img').attr('src', '/secure?captcha_key='+start_rand);
      $('#tasks_edit_captcha').find('img').click(function() {
       var reload_rand = myrand(1111111111, 999999999);
       $('#captcha_key').val(reload_rand);
       $('#tasks_edit_captcha').find('img').attr('src', '/secure?captcha_key='+reload_rand);
      });
      return false;
     }
     if(error_text) {
      $('.form_edit_task .error').show().html(error_text);
     } else if(response.success) {
      _wndBlue._closed();
      $('#task'+id).find('.count_tcount').text(response.new_count);
      var text = $('#task'+id+' .task_my_mini_description').html();
      var response_points = response.points+' '+declOfNum(response.points, ['балл', 'балла', 'баллов']);
      if(response.points > 0) {
       _money._minus(response.points);
       _wndBottom._show(
        'С Вас списаны баллы.', 
        'С Вашего счета '+declOfNum(response.points, ['списан', 'списано', 'списано'])+' <a href="/settings/balance" onclick="nav.go(this); return false"><b>'+response_points+'</b></a> за редактирование задания «'+text+'».', {
         img: '/images/wnd_bottom/coins.png?1'
        });
       }
     } else {
      $('.form_edit_task .error').show().html('Неизвестная ошибка');
     }
    });
   }});
  });
 },
 _add_categories_form: function() {
  _wndBlue._show(400, {title: 'Создание новой категории', footer: 2, text: '<div id="categories_create_box_e"><div id="categories_create_helper">Вы можете создавать категории и перемещать задания в них.<br /> Максимальное количество категорий — <b>20</b></div> <br /> <input iplaceholder="Введите название" maxlength="60" type="text" id="categories_create_field"><div id="categories_create_control_button"><div style="width: 80px; text-align: center;" id="box_button_blue" class="blue_button_wrap"><div class="blue_button">Создать</div></div> <div id="categories_create_error"></div></div></div>', onContent:function() {
   _placeholder('#categories_create_field');
   $('#categories_create_field').keydown(function(event) {
    var keyCode = event.which;
    if(keyCode == 13) {
     $('#box_button_blue').click();
     return false;
    }
   });
  }, _wsend: 80, _tsend: 'Создать', _send: function() {
  
   var cat_name = _val('#categories_create_field') ? $('#categories_create_field').val() : '';
   $('#categories_create_error').hide();
   $('#categories_create_field').removeClass('field_error_i');
   
   if(cat_name.length < 1) {
    $('#categories_create_field').focus();
    $('#categories_create_field').addClass('field_error_i');
    $('#categories_create_error').show().html('Название слишком короткое.');
    return;
   }  

   $('#box_button_blue .blue_button').html('<div class="upload"></div>');
   $.post('/categories/add.post', {
    name: cat_name,
    ssid: $('#ssid').val()
   }, function(data) {
    var response = $.parseJSON(data);
    var error_text = response.error_text;
    $('#box_button_blue .blue_button').html('Создать');
    if(error_text == 'login') {
      nav.go('', '/');
      return;
    }
     
    if(error_text) {
     $('#categories_create_error').show().html(error_text);
    } else if(response.success) {
     _wndBlue._closed();
     nav.go('', '/tasks/my?list='+response.cid);
    } else {
     $('#categories_create_error').show().html('Неизвестная ошибка.');
    }
   });
  }});
 },
 _add_comment_field: function() {
  var id = $('.add_task_comment').size();
  $('#add_task_comment_form').append('\
   <div id="add_task_comment'+id+'">\
    <div style="position: relative"><div style="padding: 2px;"></div><input class="add_task_comment" type="text"></div>\
    <div class="tcommentdel_absolute"><div onclick="tasks._del_comment_field('+id+')" class="icons_tab icons_tab_del1"></div></div>\
   </div>\
  ');
 },
 _del_comment_field: function(id) {
  $('#add_task_comment'+id).remove();
 },
 _next: function(section, cat, my, sort) {
  var page_num_id = $('#next_page_small_d');
  var page_num = page_num_id.text();
  var search = _val('#input_tasks_search') ? $('#input_tasks_search').val() : '';
  $('#next_page_small_t').show().html('<div class="upload"></div>');
  $.get('/tasks/all.next', {
   section: section,
   page: page_num,
   list: cat,
   search: search,
   my: my,
   sort: sort
  }, function(data) {
   var response = data;
   $('#next_page_small_t').html('Показать еще задания');
   if(response) {
    $('#next_page_small_c').append(response);
    page_num_id.text(page_num_id.text() * 1 + 1);
   } else {
    $('#next_page_small_t').hide();
   }
  });
 },
 _tcategories: function(list, active) {
  var tcategories = '['+list+', '+active+']';
  select._new('task_add_categories', {width: 240}, $.parseJSON(tcategories));
 },
 _tcatecories_del: function(id) {
   var text = 'Вы действительно хотите удалить категорию? Это действие нельзя будет отменить. <br /> <br />\
   Задания останутся в основной категории.\
   '
  _wndBlue._show(430, {title: 'Удаление категории', text: '<div id="task_del_box">'+text+' <div id="categories_create_error"></div></div>', _wsend: 80, _tsend: 'Удалить', _send: function() {
   
   $('#box_button_blue .blue_button').html('<div class="upload"></div>');
   $('#categories_create_error').hide();
   $.post('/categories/delete', {
    id: id
   }, function(data) {
    var response = $.parseJSON(data);
    var error_text = response.error_text;
    $('#box_button_blue .blue_button').html('Удалить');
    if(error_text == 'login') {
      nav.go('', '/');
      return;
    }
     
    if(error_text) {
     $('#categories_create_error').show().html(error_text);
    } else if(response.success) {
     _wndBlue._closed();
     nav.go('', '/tasks/my');
     $('#categorie_id'+id).hide();
     $('.delete_categorie_bar, #tasks_bar_word').hide();
     $('#tasks_bar_num').text('Ничего не найдено');
     $('#tasks_list').html('<div id="tasks_none">Категория удалена.</div>');
     $('#tasks_none').html('Категория удалена.');
    } else {
     $('#categories_create_error').show().html('Неизвестная ошибка.');
    }
   });
  }});
 },
 _search_go: function(url) {
  if(_val('#input_tasks_search')) {
   $('#search .blue_button').html('<div class="upload"></div>');
   nav.go('', url);
  } else {
   $('#input_tasks_search').focus();
  }
 },
 _check: function(id, answer_poll) {
  var comment = $('#get_comment_box_message').text();
  var error_text = '';
  $('#task'+id).find('.image_section').hide();
  $('#task'+id).find('.image .ajax_loader').css('opacity', 1);
  $('#task_button_control_1'+id).hide();
  $('#task_all_error_msg'+id).hide();
  _wndBlue._closed();
  $.get('/tasks/check.task', {
   id: id,
   answer_poll: answer_poll,
   comment: comment
  }, function(data) {
   var response = $.parseJSON(data);
   var error_text = response.error_text;
   $('#task'+id).find('.image_section').show();
   $('#task'+id).find('.image .ajax_loader').css('opacity', 0);
   $('#task_button_control_1'+id).show();
   if(error_text == 'login') {
    nav.go('', '/');
    return;
   }
   if(error_text && error_text.indexOf('привязать страницу ВК') > -1) {
    users._add_vk();
   } else if(error_text) {
    $('#task_all_error_msg'+id).show().html(error_text);
   } else if(response.success == 1) {
    var text = $('#task'+id+' .task_my_mini_description').html();
    var response_points = response.points+' '+declOfNum(response.points, ['балл', 'балла', 'баллов']);
    _money._plus(response.points);
    _wndBottom._show(
     'Вы получили баллы.', 
     'На Ваш счет '+declOfNum(response.points, ['зачислен', 'зачислено', 'зачислено'])+' <a href="/settings/balance" onclick="nav.go(this); return false"><b>'+response_points+'</b></a> за выполнение задания «'+text+'».', {
      img: '/images/wnd_bottom/coins.png?1',
      tm: 5000
     });
     $('#task'+id).hide();
     $('#tasks_bar_num').text($('#tasks_bar_num').text() * 1 - 1);
     $('#tasks_bar_word').text(declOfNum($('#tasks_bar_num').text(), ['задание', 'задания', 'заданий']));
   } else {
    $('#task_all_error_msg'+id).show().html('Неизвестная ошибка.');
   }
  });
 },
 _task_go: function(id) {
  if($('#info_msg_header').html()) {
   users._add_vk();
   return false;
  }
  
  var task_wnd_open_val = $('#task_wnd_open_val').val();
  
  if(task_wnd_open_val) {
   return false;
  }
  
  if(!task_wnd_open_val) {
   var tget = window.open('/tasks/go?id='+id, 'get_task', 'width=860, height=500, top='+((screen.height-500)/2)+',left='+((screen.width-860)/2)+', resizable=yes, scrollbars=yes, status=yes');
   var tget_interval = setInterval(function() {
    try {
     var win_task_error = tget.task_error;
     if(win_task_error != undefined) {
      if(win_task_error) {
       clearInterval(tget_interval);
       $('#task_wnd_open_val').val('');
       _wndBlue._closed();
       $('#task_all_error_msg'+id).show().html(win_task_error);
       return false;
      }
     }
    } catch(e) {}
    
    if(tget.closed) {
     clearInterval(tget_interval);
     $('#task_wnd_open_val').val('');
     tasks._check(id);
    }
   }, 10);
   $('#task_wnd_open_val').val(1);
  }
 },
 _task_go_poll: function(id, url) {
  if($('#info_msg_header').html()) {
   users._add_vk();
   return false;
  }
  
  var task_wnd_open_val = $('#task_wnd_open_val').val();
  
  if(task_wnd_open_val) {
   return false;
  }
  
  if(!task_wnd_open_val) {
   var tget = window.open('/tasks/go?id='+id+'&answer_poll=1', 'get_task', 'width=860, height=500, top='+((screen.height-500)/2)+',left='+((screen.width-860)/2)+', resizable=yes, scrollbars=yes, status=yes');
   var tget_interval = setInterval(function() {
    try {
     var win_task_error = tget.task_error;
     if(win_task_error != undefined) {
      if(win_task_error) {
       clearInterval(tget_interval);
       $('#task_wnd_open_val').val('');
       _wndBlue._closed();
       $('#task_all_error_msg'+id).show().html(win_task_error);
       return false;
      }
     }
    } catch(e) {}
    
    if(tget.closed) {
     clearInterval(tget_interval);
     $('#task_wnd_open_val').val('');
     tasks._box_poll_check(id, url);
    }
   }, 10);
   $('#task_wnd_open_val').val(1);
  }
 },
 _box_poll_check: function(id, url) {
  _wndBlue._show(500, {title: 'Проверка на честность', onContent: function() {
   radiobtn._new('radiobtn1', {title: 'За <b>первый вариант</b> проголосовали <b>больше</b>, чем за второй'});
   radiobtn._new('radiobtn2', {title: 'За <b>первый вариант</b> проголосовали <b>меньше</b>, чем за второй'});
   radiobtn._new('radiobtn3', {title: 'За <b>первый вариант</b> и <b>второй вариант</b> проголосовали одинаково'});
  }, text: '\
   <div id="task_del_box">Если Вы действительно <b>проголосовали</b> в опросе, то Вас не затруднит <b>выбрать</b> правильный вариант.\
    <div id="box_poll_check">\
     <div id="box_poll_check_title">В опросе <a href="javascript://"><a href="http://vk.com/wall'+url+'" target="_blank">http://vk.com/wall'+url+'</a></a>:</div>\
     <div id="box_poll_check_content">\
      <div onclick="tasks._check('+id+', 1)" id="radiobtn1"></div>\
      <div onclick="tasks._check('+id+', 2)" id="radiobtn2"></div>\
      <div onclick="tasks._check('+id+', 3)" id="radiobtn3"></div>\
     </div>\
    </div>\
   </div>\
  '});
 },
 _get_comment: function(id, ptext, purl) {
  nav.loader(1);
  $.get('/tasks/get.comment', {
   id: id
  }, function(data) {
   nav.loader();
   _wndBlue._show(550, {title: 'Оставить комментарий', text: '\
    <div id="get_comment_box">\
     '+ptext+' <a href="javascript://" onclick="tasks._task_go('+id+')">'+purl+'</a>:\
     <div id="get_comment_box_message">'+data+'</div>\
     <div onclick="tasks._check('+id+');" id="done_box_get_comment" class="blue_button_wrap small_blue_button"><div class="blue_button"><b>Нажмите сюда</b>, если оставили комментарий</div></div>\
    </div>\
   ', _wsend: 80, footer: 2});
  });
 },
 _get_complaints: function(id) {
  $('#complaints_get_task'+id+' .blue_button').html('<div class="upload"></div>');
  $.getJSON('/complaints/get', {id: id}, function(data) {
   var response = data;
   var error_text = response.error_text;
   $('#complaints_get_task'+id+' .blue_button').html('Выписать штрафы');
   if(error_text == 'login') {
    nav.go('', '/');
    return;
   }
   if(error_text) {
    _wndBlue._show(450, {title: 'Ошибка', text: '<div id="task_get_complaints_get">'+error_text+'</div>'});
   } else if(response.success == 1) {
    if(response.users) {
     if(response.points) {
      _wndBlue._show(450, {title: 'Сканирование завершено', text: '<div id="task_get_complaints_get"><b>Проверка успешно завершена!</b> <br /> '+declOfNum(response.users, ['Оштрафован', 'Оштрафованы', 'Оштрафованы'])+' '+response.users+' '+declOfNum(response.users, ['человек', 'человека', 'человек'])+'. <br /> На Ваш счет возвращено <b>'+response.points+' '+declOfNum(response.points, ['балл', 'балла', 'баллов'])+'</b>.</div>'});
      _money._plus(response.points);
     } else {
      _wndBlue._show(450, {title: 'Сканирование завершено', text: '<div id="task_get_complaints_get"><b>Проверка успешно завершена!</b> <br /> '+declOfNum(response.users, ['Оштрафован', 'Оштрафованы', 'Оштрафованы'])+' '+response.users+' '+declOfNum(response.users, ['человек', 'человека', 'человек'])+'.</div>'});
     }
    } else {
     _wndBlue._show(450, {title: 'Сканирование завершено', text: '<div id="task_get_complaints_get"><b>Проверка успешно завершена!</b> <br /> Никто не оштрафован.</div>'});
    }
   } else {
    _wndBlue._show(450, {title: 'Ошибка', text: '<div id="task_get_complaints_get">Неизвестная ошибка.</div>'});
   }
  });
 }
}

var blacklist = {
 _add: function(obj) {
  var url = _val('#url_blacklist_task') ? $('#url_blacklist_task').val() : '';
  var description = $('#text_blacklist_task').val();
  
  $('#url_blacklist_task').removeClass('field_error_i');
  
  if(!url.match(/^http(s)?\:\/\/vk\.com\//) && !url.match(/^vk\.com\//)) {
   $('body, html').animate({scrollTop: 0}, 200);
   $('#tasks_blacklist_error_add').show().html('Проверьте правильность введенной ссылки.');
   $('#url_blacklist_task').focus();
   $('#url_blacklist_task').addClass('field_error_i');
   return;
  }
  
  $('#blacklist_user_add_button .blue_button').html('<div class="upload"></div>');
  $('#tasks_blacklist_info_add').hide();
  $('#tasks_blacklist_error_add').hide();
  $.post('/tasks/add.blacklist', {
   url: url,
   description: description,
   ssid: obj.ssid
  }, function(data) {
   var response = $.parseJSON(data);
   var error_text = response.error_text;
   $('#blacklist_user_add_button .blue_button').html('Отправить жалобу');
   if(error_text == 'login') {
    nav.go('', '/');
    return;
   }
   
   if(error_text) {
    $('body, html').animate({scrollTop: 0}, 200);
    $('#tasks_blacklist_error_add').show().html(error_text);
   } else if(response.success == 1) {
    $('#blacklist_user_add_button .blue_button').html('<div class="upload"></div>');
    nav.go('', '/tasks/blacklist', function() {
     cnt_black._show({text: 'Ваша жалоба <span>отправлена на рассмотрение</span>. <br />Как только мы её рассмотрим, Вы будете оповещены.'});
    });
    $('body, html').animate({scrollTop: 0}, 200);
   } else {
    $('body, html').animate({scrollTop: 0}, 200);
    $('#tasks_blacklist_error_add').show().html('Неизвестная ошибка.');
   }
  });
 }
}

var refs = {
 _next: function() {
  var page_num_id = $('#next_page_small_d');
  var page_num = page_num_id.text();
  $('#next_page_small_t').show().html('<div class="upload"></div>');
  $.get('/settings/ref.next', {
   page: page_num
  }, function(data) {
   var response = data;
   $('#next_page_small_t').html('Показать еще рефералов');
   if(response) {
    $('#next_page_small_c').append(response);
    page_num_id.text(page_num_id.text() * 1 + 1);
   } else {
    $('#next_page_small_t').hide();
   }
  });
 }
}

var support = {
 _new: function() {
  $('#support_add_field_theme, #support_add_field_text').removeClass('field_error_i');
  $('#support_images_attach_img_field_ids').val('');
  var title = _val('#support_add_field_theme') ? $('#support_add_field_theme').val() : '';
  var message = _val('#support_add_field_text') ? $('#support_add_field_text').val() : '';
  $('.support_images_attach_img_field_id').each(function() {
   var id = $(this).val() ? $(this).val()+',' : '';
   var values = $('#support_images_attach_img_field_ids').val();
   $('#support_images_attach_img_field_ids').val(values+''+id);
  });
  
  if(title.length < 2) {
   $('#support_add_field_theme').focus();
   $('#support_add_field_theme').addClass('field_error_i');
   return;
  } else if(message.length < 2) {
   $('#support_add_field_text').focus();
   $('#support_add_field_text').addClass('field_error_i');
   return;
  }
  
  $('#support_add_content_buttons_left .blue_button').html('<div class="upload"></div>');
  $('#error_msg_support_error').html('');
  $.post('/support/add.post', {
   title: title,
   message: message,
   photo_attaches: $('#support_images_attach_img_field_ids').val(),
   ssid: $('#ssid').val()
  }, function(data) {
   var response = $.parseJSON(data);
   var error_text = response.error_text;
   $('#support_add_content_buttons_left .blue_button').html('Отправить');
   if(error_text == 'login') {
    nav.go('', '/');
    return;
   }
   if(error_text) {
    if(error_text.indexOf('короткий заголовок') > -1) {
     $('#support_add_field_theme').addClass('field_error_i');
    } else if(error_text.indexOf('короткий текст') > -1) {
     $('#support_add_field_text').addClass('field_error_i');
    } else {
     nav.error_head(error_text);
    }
   } else if(response.success == 1) {
    $('#support_add_content_buttons_left .blue_button').html('<div class="upload"></div>');
    nav.go('', '/support/question?id='+response.id);
   } else {
    $('#error_msg_support_error').html('Неизвестная ошибка.');
   }
  });
 },
 _add_comment: function(id) {
  $('#support_question_add_comment_field_text').removeClass('field_error_i');
  $('#support_images_attach_img_field_ids').val('');
  var message = _val('#support_question_add_comment_field_text') ? $('#support_question_add_comment_field_text').val() : '';
  $('#error_msg_support_error').html('');
  $('.support_images_attach_img_field_id').each(function() {
   var id = $(this).val() ? $(this).val()+',' : '';
   var values = $('#support_images_attach_img_field_ids').val();
   $('#support_images_attach_img_field_ids').val(values+''+id);
  });
  
  if(message.length < 2) {
   $('#support_question_add_comment_field_text').focus();
   $('#support_question_add_comment_field_text').addClass('field_error_i');
   return;
  }
  
  $('#support_question_add_comment_txt_buttons_left .blue_button').html('<div class="upload"></div>');
  $.post('/support/add.comment', {
   id: id,
   photo_attaches: $('#support_images_attach_img_field_ids').val(),
   text: message
  }, function(data) {
   var response = $.parseJSON(data);
   var error_text = response.error_text;
   if(error_text == 'login') {
    nav.go('', '/');
    return;
   }
   
   $('#support_question_add_comment_txt_buttons_left .blue_button').html('Отправить');
   if(error_text) {
    if(error_text.indexOf('короткий комментарий') > -1) {
     $('#support_question_add_comment_field_text').addClass('field_error_i');
    } else {
     nav.error_head(error_text);
    }
   } else if(response.success == 1) {
    $('#support_question_add_comment_txt_buttons_left .blue_button').html('<div class="upload"></div>');
    nav.go('', '/support/question?id='+id);
   } else {
    $('#error_msg_support_error').html('Неизвестная ошибка.');
   }
  });
 },
 _delete_post: function(id) {
   var text = 'Вы действительно хотите удалить вопрос? Это действие нельзя будет отменить.';
  _wndBlue._show(410, {title: 'Удаление вопроса', text: '<div id="task_del_box">'+text+'</div>', _wsend: 80, _tsend: 'Удалить', _send: function() {
   $('#box_button_blue .blue_button').html('<div class="upload"></div>');
   $.getJSON('/support/del.post', {
    id: id
   }, function(data) {
    $('#box_button_blue .blue_button').html('<div class="upload"></div>');
    _wndBlue._closed();
    nav.go('', '/support?que_del=1');
   });
  }});
 },
 _rate_comment:function(id, type) {
  $.getJSON('/support/rate.comment', {
   id: id,
   type: type
  }, function(data) {
   var response = data;
   var error_text = response.error_text;
   if(error_text == 'login') {
    nav.go('', '/');
    return;
   }
   if(response.success == 1) {
    if(type == 1) {
     $('#rate_support_comment'+id).html('<span class="rate_result_comment_support">Вы оставили положительный отзыв</span>');
    } else {
     $('#rate_support_comment'+id).html('<span class="rate_result_comment_support">Вы оставили негативный отзыв</span>');
    }
   } else {
    nav.error_head('Неизвестная ошибка.');
   }
  });
 },
 upload_img: function(type) {
  if($('#support_images_attach').find('img').size() >= 5) {
   _wndBlue._show(410, {title: 'Ошибка', text: '<div id="task_del_box">Вы можете прикрепить к вопросу не более 5 файлов.</div>'});
   return false;
  }
  
  var file_split = $('#support_add_img_file').val().split('\\');
  var filename = file_split[file_split.length - 1];
  $('#support_upload_iframe').contents().text('');
  $('#support_upload_iframe_submit').trigger('click');
  if(type == 'comment') {
   $('#support_grad_progress').show().html('\
    <div class="progress_grad_support_overflow progress_grad_support_overflow_comment">\
    <div class="progress_grad"></div><div class="progress_grad_support_overflow_left">'+filename+'</div>\
    </div>\
   ');
  } else {
   $('#support_grad_progress').show().html('\
    <div class="progress_grad_support_overflow">\
    <div class="progress_grad"></div><div class="progress_grad_support_overflow_left">'+filename+'</div>\
    </div>\
   ');
  }
  var iframe_content_interval = setInterval(function() {
   var iframe_content = $('#support_upload_iframe').contents().text();
   if(iframe_content) {
    $('#support_grad_progress').hide();
    var iframe_content_json = $.parseJSON(iframe_content);
    var iframe_content_json_error = iframe_content_json.error_text;
    var iframe_content_json_big_img = iframe_content_json.result_big_file;
    var iframe_content_json_mini_img = iframe_content_json.result_mini_file;
    var iframe_content_json_id = iframe_content_json.id;
    if(iframe_content_json.error_text == 'login') {
     nav.go('', '/');
    }
    
    if(iframe_content_json.error_text) {
     nav.error_head(iframe_content_json.error_text);
    } else if(iframe_content_json.success == 1) {
     $('#support_images_attach').show().append('\
     <span id="support_images_attach_img_wrap_id'+iframe_content_json_id+'">\
      <input type="hidden" value="'+iframe_content_json_id+'" class="support_images_attach_img_field_id"> \
      <div class="support_images_attach_img_wrap">\
       <div class="support_images_attach_img_inner">\
        <div onclick="support.del_img('+iframe_content_json_id+')" class="support_images_attach_img_inner_bdel"><div class="support_images_attach_img_inner_bdel_closed"></div></div>\
       </div>\
       <a href="/images/support/uploads/'+iframe_content_json_big_img+'" target="_blank"><img src="/images/support/uploads/'+iframe_content_json_mini_img+'"></a>\
      </div>\
     </div>\
     ');
    }
    clearInterval(iframe_content_interval);
   }
  }, 10);
 },
 del_img: function(id) {
  $('#support_images_attach_img_wrap_id'+id).remove();
 }
}

var balance = {
 _next: function() {
  var page_num_id = $('#next_page_small_d');
  var page_num = page_num_id.text();
  $('#next_page_small_t').show().html('<div class="upload"></div>');
  $.get('/settings/balance.next', {
   page: page_num
  }, function(data) {
   var response = data;
   $('#next_page_small_t').html('Показать предыдущие платежи');
   if(response) {
    $('#next_page_small_c').append(response);
    page_num_id.text(page_num_id.text() * 1 + 1);
   } else {
    $('#next_page_small_t').hide();
   }
  });
 }
}

var pay_smsm = 0;
var pay = {
 _show_type: function() {
  var template = '\
  <div id="balance_pay_white">\
   <div  style="display: none" class="balance_pay_type_o">\
    <div class="balance_pay_type_l"><div class="balance_pay_type_img balance_pay_type_img_sms"></div></div>\
    <div class="balance_pay_type_r">\
     <div class="balance_pay_type_r_title">Через мобильный телефон</div>\
     <div class="balance_pay_type_r_list">Временно не доступна</div>\
    </div>\
   </div>\
   <div onclick="pay._type_psystems()" class="balance_pay_type_o">\
    <div class="balance_pay_type_l"><div class="balance_pay_type_img balance_pay_type_img_psystems"></div></div>\
    <div class="balance_pay_type_r">\
     <div class="balance_pay_type_r_title">Платежные системы</div>\
     <div class="balance_pay_type_r_list">WebMoney, Visa QIWI Wallet</div>\
    </div>\
   </div>\
     <div style="display: none" onclick="pay._type_waytopay()" class="balance_pay_type_o">\
    <div class="balance_pay_type_l"><div class="balance_pay_type_img balance_pay_type_img_psystems"></div></div>\
    <div class="balance_pay_type_r">\
     <div class="balance_pay_type_r_title">WayToPay</div>\
     <div class="balance_pay_type_r_list">Не работает! В рзработке!"</div>\
    </div>\
   </div>\
   <div id="pay_box_descr"><b>Баллы</b> - валюта для оплаты заданий Piar.Name.</div>\
  </div>\
  ';
  _wndWhite._show(530, {title: 'Пополнение баланса', text: template});
 },
 _type_waytopay: function() {
  var template = '\
  <div id="balance_pay_white">\
   <div id="prepend_psystem">Вы можете приобрести баллы с помощью электронных платёжных систем.</div>\
   <div id="balance_pay_psystems_change">\
    <div onclick="pay._type_waytopay_webmoney();" class="balance_pay_psystems_change">\
     <div class="balance_pay_psystems_change_l"><div class="webmoney_logo"></div></div>\
     <div class="balance_pay_psystems_change_r">WebMoney</div>\
    </div>\
    <div  style="display: none" class="balance_pay_psystems_change">\
     <div class="balance_pay_psystems_change_l"><div class="yandex_logo"></div></div>\
     <div class="balance_pay_psystems_change_r">Яндекс.Деньги</div>\
    </div>\
    <div   onclick="pay._type_qiwi();" class="balance_pay_psystems_change">\
     <div class="balance_pay_psystems_change_l"><div class="qiwi_logo"></div></div>\
     <div class="balance_pay_psystems_change_r">Visa QIWI Wallet</div>\
    </div>\
    <div style="display: none"  class="balance_pay_psystems_change">\
     <div class="balance_pay_psystems_change_l"><div class="qiwi_logo"></div></div>\
     <div class="balance_pay_psystems_change_r">Голоса ВКонтакте</div>\
    </div>\
   <div style="margin-top: 10px;" onclick="pay._show_type();" class="return_button">Выбрать другой способ оплаты</div>\
   </div>\
  </div>\
  \
  ';
 
  _wndWhite._show(530, {title: 'Оплата через платёжную систему', text: template});
 },
 _type_psystems: function() {
  var template = '\
  <div id="balance_pay_white">\
   <div id="prepend_psystem">Вы можете приобрести баллы с помощью электронных платёжных систем.</div>\
   <div id="balance_pay_psystems_change">\
    <div onclick="pay._type_webmoney();" class="balance_pay_psystems_change">\
     <div class="balance_pay_psystems_change_l"><div class="webmoney_logo"></div></div>\
     <div class="balance_pay_psystems_change_r">WebMoney</div>\
    </div>\
    <div style="display: none"  onclick="pay._type_waytopay_ya();" class="balance_pay_psystems_change">\
     <div class="balance_pay_psystems_change_l"><div class="yandex_logo"></div></div>\
     <div class="balance_pay_psystems_change_r">Яндекс.Деньги</div>\
    </div>\
    <div  onclick="pay._type_qiwi();" class="balance_pay_psystems_change">\
     <div class="balance_pay_psystems_change_l"><div class="qiwi_logo"></div></div>\
     <div class="balance_pay_psystems_change_r">Visa QIWI Wallet</div>\
    </div>\
    <div style="display: none"  class="balance_pay_psystems_change">\
     <div class="balance_pay_psystems_change_l"><div class="qiwi_logo"></div></div>\
     <div class="balance_pay_psystems_change_r">Голоса ВКонтакте</div>\
    </div>\
   <div style="margin-top: 10px;" onclick="pay._show_type();" class="return_button">Выбрать другой способ оплаты</div>\
   </div>\
  </div>\
  \
  ';
  _wndWhite._show(530, {title: 'Оплата через платёжную систему', text: template});
 },
 _type_webmoney: function() {
  var template = '\
  <div id="balance_pay_white">\
   <div align="center" id="balance_pay_loader">\
    <div class="progress7"></div>\
    <div class="progress7_text">Ожидаем завершения оплаты.. <a href="javascript://" onclick="pay._type_loader_hide();">Отмена</a></div>\
   </div>\
   <div id="balance_pay_no_loader">\
    <div id="pay_error_msgm" class="error_msgm">Не удалось обнаружить Ваш платёж. <a href="javascript://" onclick="pay._check_webmoney();">Перепроверить</a>.</div>\
    <div id="balance_pay_inner">\
     <div style="overflow: hidden">\
      <div style="float: left">\
       <div onclick="$(\'#box_white_pay_count\').val(500)" class="pay_radio_btn pay_radio_btn_y" id="radiobtn1"></div>\
       <div onclick="$(\'#box_white_pay_count\').val(1000)" class="pay_radio_btn pay_radio_btn_y" id="radiobtn2"></div>\
       <div onclick="$(\'#box_white_pay_count\').val(1500)" class="pay_radio_btn pay_radio_btn_y" id="radiobtn3"></div>\
       <div onclick="$(\'#box_white_pay_count\').val(2000)" class="pay_radio_btn pay_radio_btn_y" id="radiobtn4"></div>\
      </div>\
      <div style="float: right; margin-top: -5px; margin-right: 15px;">\
       <div class="webmoney_logo_normal"></div>\
      </div>\
     </div>\
     <input type="hidden" id="box_white_pay_count">\
     <div onclick="$(\'.other_pay_balance\').show(); $(\'#box_white_pay_count\').val(\'\');" class="pay_radio_btn" id="radiobtn5"></div>\
     <div id="pay_button_get" class="blue_button_wrap small_blue_button"><div class="blue_button">Перейти к оплате</div></div>\
     <div style="margin-left: 5px;" onclick="pay._type_psystems();" class="return_button">Вернуться к выбору платёжной системы</div>\
    </div>\
   </div>\
  </div>\
  \
  ';
  _wndWhite._show(530, {title: 'Оплата через WebMoney', onShow: function() {
   radiobtn._new('radiobtn1', {title: '<div class="pay_title_balance">500 баллов</div><div class="pay_title_balance_rur">100 рублей</div>'});
   radiobtn._new('radiobtn2', {title: '<div class="pay_title_balance">1000 баллов</div><div class="pay_title_balance_rur">200 рублей</div>'});
   radiobtn._new('radiobtn3', {title: '<div class="pay_title_balance">1500 баллов</div><div class="pay_title_balance_rur">300 рублей</div>'});
   radiobtn._new('radiobtn4', {title: '<div class="pay_title_balance">2000 баллов</div><div class="pay_title_balance_rur">400 рублей</div>'});
   radiobtn._new('radiobtn5', {title: '<div class="pay_title_no_balance">Другое количество <div class="other_pay_balance"><input iplaceholder="Введите количество баллов" id="other_pay_balance" type="text"> <span id="pay_sum_rur_real"></span></div></div>'});
   $('#other_pay_balance').keyup(function() {
    if(!$(this).val().match(/^([0-9]+)$/)) {
     $(this).val('');
     $('#pay_sum_rur_real').hide();
    } else {
     var result_points = Math.floor(($(this).val() * 1) * 0.2 * 10)/10;
     var result_points_desc = Math.floor(($(this).val() * 1) * 0.2);
     $('#pay_sum_rur_real').show().html('Стоимость <b>'+result_points+' '+declOfNum(result_points_desc, ['рубль', 'рубля', 'рублей'])+'</b>');
     $('#box_white_pay_count').val($(this).val());
    }
   });
   $('#other_pay_balance').keydown(function(event) {
    var keyCode = event.which;
    if(keyCode == 13) $('#pay_button_get').click();
   });
   _placeholder('#other_pay_balance');
   $('.pay_radio_btn_y').click(function() {
    $('.other_pay_balance').hide();
   });
   $('#pay_button_get').click(function() {
    var points = $('#box_white_pay_count').val() * 1;
    if(!points) {
     return false;
    } else {
     if(!window.open) {
      alert('Включите всплывающие окна в настройках Вашего браузера.');
      return false;
     }
     var pget = window.open('/pay/?type=webmoney&points='+points);
     pay._type_loader_show();
     var pget_interval = setInterval(function() {
      if(pget.closed) {
       pay._check_webmoney();
       clearInterval(pget_interval);
      }
     }, 10);
    }
   });
   $('#wnd_white_main').css({position: 'fixed', top: ($(window).height() - $('#wnd_white_main').height())/2, left:($(window).width() - 500)/2, width: 500});
  }, text: template});
 },
    _type_waytopay_ya: function() {
  var template = '\
  <div id="balance_pay_white">\
   <div align="center" id="balance_pay_loader">\
    <div class="progress7"></div>\
    <div class="progress7_text">Ожидаем завершения оплаты.. <a href="javascript://" onclick="pay._type_loader_hide();">Отмена</a></div>\
   </div>\
   <div id="balance_pay_no_loader">\
    <div id="pay_error_msgm" class="error_msgm">Не удалось обнаружить Ваш платёж. <a href="javascript://" onclick="pay._check_webmoney();">Перепроверить</a>.</div>\
    <div id="balance_pay_inner">\
     <div style="overflow: hidden">\
      <div style="float: left">\
       <div onclick="$(\'#box_white_pay_count\').val(500)" class="pay_radio_btn pay_radio_btn_y" id="radiobtn1"></div>\
       <div onclick="$(\'#box_white_pay_count\').val(1000)" class="pay_radio_btn pay_radio_btn_y" id="radiobtn2"></div>\
       <div onclick="$(\'#box_white_pay_count\').val(1500)" class="pay_radio_btn pay_radio_btn_y" id="radiobtn3"></div>\
       <div onclick="$(\'#box_white_pay_count\').val(2000)" class="pay_radio_btn pay_radio_btn_y" id="radiobtn4"></div>\
      </div>\
      <div style="float: right; margin-top: -5px; margin-right: 15px;">\
       <div class="yandex_logo_normal"></div>\
      </div>\
     </div>\
     <input type="hidden" id="box_white_pay_count">\
     <div onclick="$(\'.other_pay_balance\').show(); $(\'#box_white_pay_count\').val(\'\');" class="pay_radio_btn" id="radiobtn5"></div>\
     <div id="pay_button_get" class="blue_button_wrap small_blue_button"><div class="blue_button">Перейти к оплате</div></div>\
     <div style="margin-left: 5px;" onclick="pay._type_psystems();" class="return_button">Вернуться к выбору платёжной системы</div>\
    </div>\
   </div>\
  </div>\
  \
  ';
  _wndWhite._show(530, {title: 'Оплата через WayToPay Yandex', onShow: function() {
   radiobtn._new('radiobtn1', {title: '<div class="pay_title_balance">500 баллов</div><div class="pay_title_balance_rur">100 рублей</div>'});
   radiobtn._new('radiobtn2', {title: '<div class="pay_title_balance">1000 баллов</div><div class="pay_title_balance_rur">200 рублей</div>'});
   radiobtn._new('radiobtn3', {title: '<div class="pay_title_balance">1500 баллов</div><div class="pay_title_balance_rur">300 рублей</div>'});
   radiobtn._new('radiobtn4', {title: '<div class="pay_title_balance">2000 баллов</div><div class="pay_title_balance_rur">400 рублей</div>'});
   radiobtn._new('radiobtn5', {title: '<div class="pay_title_no_balance">Другое количество <div class="other_pay_balance"><input iplaceholder="Введите количество баллов" id="other_pay_balance" type="text"> <span id="pay_sum_rur_real"></span></div></div>'});
   $('#other_pay_balance').keyup(function() {
    if(!$(this).val().match(/^([0-9]+)$/)) {
     $(this).val('');
     $('#pay_sum_rur_real').hide();
    } else {
     var result_points = Math.floor(($(this).val() * 1) * 0.2 * 10)/10;
     var result_points_desc = Math.floor(($(this).val() * 1) * 0.2);
     $('#pay_sum_rur_real').show().html('Стоимость <b>'+result_points+' '+declOfNum(result_points_desc, ['рубль', 'рубля', 'рублей'])+'</b>');
     $('#box_white_pay_count').val($(this).val());
    }
   });
   $('#other_pay_balance').keydown(function(event) {
    var keyCode = event.which;
    if(keyCode == 13) $('#pay_button_get').click();
   });
   _placeholder('#other_pay_balance');
   $('.pay_radio_btn_y').click(function() {
    $('.other_pay_balance').hide();
   });
   $('#pay_button_get').click(function() {
    var points = $('#box_white_pay_count').val() * 1;
    if(!points) {
     return false;
    } else {
     if(!window.open) {
      alert('Включите всплывающие окна в настройках Вашего браузера.');
      return false;
     }
     var pget = window.open('/pay/?type=waytopay_ya&points='+points);
     //pay._type_loader_show();

    }
   });
   $('#wnd_white_main').css({position: 'fixed', top: ($(window).height() - $('#wnd_white_main').height())/2, left:($(window).width() - 500)/2, width: 500});
  }, text: template});
 },
  _type_waytopay_qiwi: function() {
  var template = '\
  <div id="balance_pay_white">\
   <div align="center" id="balance_pay_loader">\
    <div class="progress7"></div>\
    <div class="progress7_text">Ожидаем завершения оплаты.. <a href="javascript://" onclick="pay._type_loader_hide();">Отмена</a></div>\
   </div>\
   <div id="balance_pay_no_loader">\
    <div id="pay_error_msgm" class="error_msgm">Не удалось обнаружить Ваш платёж. <a href="javascript://" onclick="pay._check_webmoney();">Перепроверить</a>.</div>\
    <div id="balance_pay_inner">\
     <div style="overflow: hidden">\
      <div style="float: left">\
       <div onclick="$(\'#box_white_pay_count\').val(500)" class="pay_radio_btn pay_radio_btn_y" id="radiobtn1"></div>\
       <div onclick="$(\'#box_white_pay_count\').val(1000)" class="pay_radio_btn pay_radio_btn_y" id="radiobtn2"></div>\
       <div onclick="$(\'#box_white_pay_count\').val(1500)" class="pay_radio_btn pay_radio_btn_y" id="radiobtn3"></div>\
       <div onclick="$(\'#box_white_pay_count\').val(2000)" class="pay_radio_btn pay_radio_btn_y" id="radiobtn4"></div>\
      </div>\
      <div style="float: right; margin-top: -5px; margin-right: 15px;">\
       <div class="qiwi_logo_normal"></div>\
      </div>\
     </div>\
     <input type="hidden" id="box_white_pay_count">\
     <div onclick="$(\'.other_pay_balance\').show(); $(\'#box_white_pay_count\').val(\'\');" class="pay_radio_btn" id="radiobtn5"></div>\
     <div id="pay_button_get" class="blue_button_wrap small_blue_button"><div class="blue_button">Перейти к оплате</div></div>\
     <div style="margin-left: 5px;" onclick="pay._type_psystems();" class="return_button">Вернуться к выбору платёжной системы</div>\
    </div>\
   </div>\
  </div>\
  \
  ';
  _wndWhite._show(530, {title: 'Оплата через WayToPay Qiwi', onShow: function() {
   radiobtn._new('radiobtn1', {title: '<div class="pay_title_balance">500 баллов</div><div class="pay_title_balance_rur">100 рублей</div>'});
   radiobtn._new('radiobtn2', {title: '<div class="pay_title_balance">1000 баллов</div><div class="pay_title_balance_rur">200 рублей</div>'});
   radiobtn._new('radiobtn3', {title: '<div class="pay_title_balance">1500 баллов</div><div class="pay_title_balance_rur">300 рублей</div>'});
   radiobtn._new('radiobtn4', {title: '<div class="pay_title_balance">2000 баллов</div><div class="pay_title_balance_rur">400 рублей</div>'});
   radiobtn._new('radiobtn5', {title: '<div class="pay_title_no_balance">Другое количество <div class="other_pay_balance"><input iplaceholder="Введите количество баллов" id="other_pay_balance" type="text"> <span id="pay_sum_rur_real"></span></div></div>'});
   $('#other_pay_balance').keyup(function() {
    if(!$(this).val().match(/^([0-9]+)$/)) {
     $(this).val('');
     $('#pay_sum_rur_real').hide();
    } else {
     var result_points = Math.floor(($(this).val() * 1) * 0.2 * 10)/10;
     var result_points_desc = Math.floor(($(this).val() * 1) * 0.2);
     $('#pay_sum_rur_real').show().html('Стоимость <b>'+result_points+' '+declOfNum(result_points_desc, ['рубль', 'рубля', 'рублей'])+'</b>');
     $('#box_white_pay_count').val($(this).val());
    }
   });
   $('#other_pay_balance').keydown(function(event) {
    var keyCode = event.which;
    if(keyCode == 13) $('#pay_button_get').click();
   });
   _placeholder('#other_pay_balance');
   $('.pay_radio_btn_y').click(function() {
    $('.other_pay_balance').hide();
   });
   $('#pay_button_get').click(function() {
    var points = $('#box_white_pay_count').val() * 1;
    if(!points) {
     return false;
    } else {
     if(!window.open) {
      alert('Включите всплывающие окна в настройках Вашего браузера.');
      return false;
     }
     var pget = window.open('/pay/?type=waytopay_qiwi&points='+points);
    // pay._type_loader_show();
 
    }
   });
   $('#wnd_white_main').css({position: 'fixed', top: ($(window).height() - $('#wnd_white_main').height())/2, left:($(window).width() - 500)/2, width: 500});
  }, text: template});
 },
 
  _type_waytopay_webmoney: function() {
  var template = '\
  <div id="balance_pay_white">\
   <div align="center" id="balance_pay_loader">\
    <div class="progress7"></div>\
    <div class="progress7_text">Ожидаем завершения оплаты.. <a href="javascript://" onclick="pay._type_loader_hide();">Отмена</a></div>\
   </div>\
   <div id="balance_pay_no_loader">\
    <div id="pay_error_msgm" class="error_msgm">Не удалось обнаружить Ваш платёж. <a href="javascript://" onclick="pay._check_webmoney();">Перепроверить</a>.</div>\
    <div id="balance_pay_inner">\
     <div style="overflow: hidden">\
      <div style="float: left">\
       <div onclick="$(\'#box_white_pay_count\').val(500)" class="pay_radio_btn pay_radio_btn_y" id="radiobtn1"></div>\
       <div onclick="$(\'#box_white_pay_count\').val(1000)" class="pay_radio_btn pay_radio_btn_y" id="radiobtn2"></div>\
       <div onclick="$(\'#box_white_pay_count\').val(1500)" class="pay_radio_btn pay_radio_btn_y" id="radiobtn3"></div>\
       <div onclick="$(\'#box_white_pay_count\').val(2000)" class="pay_radio_btn pay_radio_btn_y" id="radiobtn4"></div>\
      </div>\
      <div style="float: right; margin-top: -5px; margin-right: 15px;">\
       <div class="webmoney_logo_normal"></div>\
      </div>\
     </div>\
     <input type="hidden" id="box_white_pay_count">\
     <div onclick="$(\'.other_pay_balance\').show(); $(\'#box_white_pay_count\').val(\'\');" class="pay_radio_btn" id="radiobtn5"></div>\
     <div id="pay_button_get" class="blue_button_wrap small_blue_button"><div class="blue_button">Перейти к оплате</div></div>\
     <div style="margin-left: 5px;" onclick="pay._type_psystems();" class="return_button">Вернуться к выбору платёжной системы</div>\
    </div>\
   </div>\
  </div>\
  \
  ';
  _wndWhite._show(530, {title: 'Оплата через WayToPay WebMoney', onShow: function() {
   radiobtn._new('radiobtn1', {title: '<div class="pay_title_balance">500 баллов</div><div class="pay_title_balance_rur">100 рублей</div>'});
   radiobtn._new('radiobtn2', {title: '<div class="pay_title_balance">1000 баллов</div><div class="pay_title_balance_rur">200 рублей</div>'});
   radiobtn._new('radiobtn3', {title: '<div class="pay_title_balance">1500 баллов</div><div class="pay_title_balance_rur">300 рублей</div>'});
   radiobtn._new('radiobtn4', {title: '<div class="pay_title_balance">2000 баллов</div><div class="pay_title_balance_rur">400 рублей</div>'});
   radiobtn._new('radiobtn5', {title: '<div class="pay_title_no_balance">Другое количество <div class="other_pay_balance"><input iplaceholder="Введите количество баллов" id="other_pay_balance" type="text"> <span id="pay_sum_rur_real"></span></div></div>'});
   $('#other_pay_balance').keyup(function() {
    if(!$(this).val().match(/^([0-9]+)$/)) {
     $(this).val('');
     $('#pay_sum_rur_real').hide();
    } else {
     var result_points = Math.floor(($(this).val() * 1) * 0.2 * 10)/10;
     var result_points_desc = Math.floor(($(this).val() * 1) * 0.2);
     $('#pay_sum_rur_real').show().html('Стоимость <b>'+result_points+' '+declOfNum(result_points_desc, ['рубль', 'рубля', 'рублей'])+'</b>');
     $('#box_white_pay_count').val($(this).val());
    }
   });
   $('#other_pay_balance').keydown(function(event) {
    var keyCode = event.which;
    if(keyCode == 13) $('#pay_button_get').click();
   });
   _placeholder('#other_pay_balance');
   $('.pay_radio_btn_y').click(function() {
    $('.other_pay_balance').hide();
   });
   $('#pay_button_get').click(function() {
    var points = $('#box_white_pay_count').val() * 1;
    if(!points) {
     return false;
    } else {
     if(!window.open) {
      alert('Включите всплывающие окна в настройках Вашего браузера.');
      return false;
     }
     var pget = window.open('/pay/?type=waytopay_webmoney&points='+points);
    // pay._type_loader_show();
   
    }
   });
   $('#wnd_white_main').css({position: 'fixed', top: ($(window).height() - $('#wnd_white_main').height())/2, left:($(window).width() - 500)/2, width: 500});
  }, text: template});
 },
 _type_loader_show: function() {
  $('#balance_pay_no_loader').hide();
  $('#balance_pay_loader').show();
 },
 _type_loader_hide: function() {
  $('#balance_pay_no_loader').show();
  $('#balance_pay_loader').hide();
 },
 _check_webmoney: function() {
  pay._type_loader_show();
  $.getJSON('/pay/webmoney/check.php', function(data) {
   var response = data;
   var _error = data._error;
   if(_error) {
    pay._type_loader_hide();
    $('#pay_error_msgm').show();
   } else if(response.success == 1) {
    _wndWhite._closed();
    _money._plus(response.points);
    _wndBottom._show(
     'Вы получили баллы.', 
     'На Ваш счет '+declOfNum(response.points, ['зачислен', 'зачислено', 'зачислено'])+' <a href="/settings/balance" onclick="nav.go(this); return false"><b>'+response.points+' '+declOfNum(response.points, ['балл', 'балла', 'баллов'])+'</b></a>.', 
     {
      img: '/images/new_points.jpg'
    });
   } else {
    pay._type_loader_hide();
    $('#pay_error_msgm').show();
   }
  });
 },
 _type_qiwi: function() {
  var template = '\
  <div id="balance_pay_white">\
   <div align="center" id="balance_pay_loader">\
    <div class="progress7"></div>\
    <div class="progress7_text">Ожидаем завершения оплаты.. <a href="javascript://" onclick="pay._type_loader_hide();">Отмена</a></div>\
   </div>\
   <div id="balance_pay_no_loader">\
    <div id="pay_error_msgm" class="error_msgm">Не удалось обнаружить Ваш платёж. <a href="javascript://" onclick="pay._check_webmoney();">Перепроверить</a>.</div>\
    <div id="balance_pay_inner">\
     <div style="overflow: hidden">\
      <div style="float: left">\
       <div onclick="$(\'#box_white_pay_count\').val(500)" class="pay_radio_btn pay_radio_btn_y" id="radiobtn1"></div>\
       <div onclick="$(\'#box_white_pay_count\').val(1000)" class="pay_radio_btn pay_radio_btn_y" id="radiobtn2"></div>\
       <div onclick="$(\'#box_white_pay_count\').val(1500)" class="pay_radio_btn pay_radio_btn_y" id="radiobtn3"></div>\
       <div onclick="$(\'#box_white_pay_count\').val(2000)" class="pay_radio_btn pay_radio_btn_y" id="radiobtn4"></div>\
      </div>\
      <div style="float: right; margin-right: 15px;">\
       <div class="qiwi_logo_normal"></div>\
      </div>\
     </div>\
     <input type="hidden" id="box_white_pay_count">\
     <div onclick="$(\'.other_pay_balance\').show(); $(\'#box_white_pay_count\').val(\'\');" class="pay_radio_btn" id="radiobtn5"></div>\
     <div id="pay_button_get" class="blue_button_wrap small_blue_button"><div class="blue_button">Продолжить</div></div>\
     <div style="margin-left: 5px;" onclick="pay._type_psystems();" class="return_button">Вернуться к выбору платёжной системы</div>\
    </div>\
   </div>\
  </div>\
  \
  ';
  _wndWhite._show(530, {title: 'Оплата через Visa QIWI Wallet', onShow: function() {
   radiobtn._new('radiobtn1', {title: '<div class="pay_title_balance">500 баллов</div><div class="pay_title_balance_rur">100 рублей</div>'});
   radiobtn._new('radiobtn2', {title: '<div class="pay_title_balance">1000 баллов</div><div class="pay_title_balance_rur">200 рублей</div>'});
   radiobtn._new('radiobtn3', {title: '<div class="pay_title_balance">1500 баллов</div><div class="pay_title_balance_rur">300 рублей</div>'});
   radiobtn._new('radiobtn4', {title: '<div class="pay_title_balance">2000 баллов</div><div class="pay_title_balance_rur">400 рублей</div>'});
   radiobtn._new('radiobtn5', {title: '<div class="pay_title_no_balance">Другое количество <div class="other_pay_balance"><input iplaceholder="Введите количество баллов" id="other_pay_balance" type="text"> <span id="pay_sum_rur_real"></span></div></div>'});
   $('#other_pay_balance').keyup(function() {
    if(!$(this).val().match(/^([0-9]+)$/)) {
     $(this).val('');
     $('#pay_sum_rur_real').hide();
    } else {
     var result_points = Math.floor(($(this).val() * 1) * 0.2 * 10)/10;
     var result_points_desc = Math.floor(($(this).val() * 1) * 0.2);
     $('#pay_sum_rur_real').show().html('Стоимость <b>'+result_points+' '+declOfNum(result_points_desc, ['рубль', 'рубля', 'рублей'])+'</b>');
     $('#box_white_pay_count').val($(this).val());
    }
   });
   $('#other_pay_balance').keydown(function(event) {
    var keyCode = event.which;
    if(keyCode == 13) $('#pay_button_get').click();
   });
   _placeholder('#other_pay_balance');
   $('.pay_radio_btn_y').click(function() {
    $('.other_pay_balance').hide();
   });
   $('#pay_button_get').click(function() {
    var points = $('#box_white_pay_count').val() * 1;
    if(!points) {
     return false;
    } else {
     pay._type_qiwi_number(points);
    }
   });
   $('#wnd_white_main').css({position: 'fixed', top: ($(window).height() - $('#wnd_white_main').height())/2, left:($(window).width() - 500)/2, width: 500});
  }, text: template});
 },
 _type_qiwi_number: function(points) {
  var template = '\
   <div id="balance_pay_white">\
    <div align="center" id="balance_pay_loader">\
     <div class="progress7"></div>\
     <div class="progress7_text">Ожидаем завершения оплаты.. <a href="javascript://" onclick="pay._type_loader_hide();">Отмена</a></div>\
    </div>\
    <div id="balance_pay_no_loader">\
     Пожалуйста, введите свой <b>номер Visa QIWI Wallet</b>:\
     <div id="pay_error_msgm" class="error_msgm">Не удалось обнаружить Ваш платёж. <a href="javascript://" onclick="pay._check_qiwi();">Перепроверить</a>.</div>\
     <div style="margin-left: 10px; margin; margin-top: 15px;">\
     <div class="overflow_field">\
      <div style="margin-top: 2px; margin-right: 10px; text-align: right" class="label">Номер:</div>\
      <div class="field">\
       <input id="visa_qiwi_wallet_num" value="+7" style="width: 200px;" type="text">\
       <div style="font-size: 10px; margin-top: 5px; color: gray; width: 200px;">Обратите внимание: оплата доступна только для абонентов, чьи номера начинаются на <b>+7</b>.</div>\
       <div></div>\
       <div style="margin-top: 10px;" id="pay_button_get" class="blue_button_wrap small_blue_button"><div class="blue_button">Перейти к оплате</div></div>\
       <div style="margin-left: 5px;" onclick="pay._type_qiwi();" class="return_button">назад</div>\
      </div>\
     </div>\
     </div>\
    </div>\
   </div>\
  ';
  _wndWhite._show(530, {title: 'Оплата через Visa QIWI Wallet', text: template, onShow: function() {
   $('#pay_button_get').click(function() {
    var num = $('#visa_qiwi_wallet_num').val();
    var num_m = num.replace(/^\+7/gi, '');
    if(!window.open) {
     alert('Включите всплывающие окна в настройках Вашего браузера.');
     return false;
    }
    var pget = window.open('/pay/?type=qiwi&number='+num_m+'&points='+points);
    pay._type_loader_show();
    var pget_interval = setInterval(function() {
     if(pget.closed) {
      pay._check_qiwi();
      clearInterval(pget_interval);
     }
    }, 10);
   });
  }});
 },
 _check_qiwi: function() {
  pay._type_loader_show();
  $.getJSON('/pay/qiwi/check.php', function(data) {
   var response = data;
   var _error = data._error;
   if(_error) {
    pay._type_loader_hide();
    $('#pay_error_msgm').show();
   } else if(response.success == 1) {
    _wndWhite._closed();
    _money._plus(response.points);
    _wndBottom._show(
     'Вы получили баллы.', 
     'На Ваш счет '+declOfNum(response.points, ['зачислен', 'зачислено', 'зачислено'])+' <a href="/settings/balance" onclick="nav.go(this); return false"><b>'+response.points+' '+declOfNum(response.points, ['балл', 'балла', 'баллов'])+'</b></a>.', 
     {
      img: '/images/new_points.jpg'
    });
   } else {
    pay._type_loader_hide();
    $('#pay_error_msgm').show();
   }
  });
 },
 _show_sms_edit: function() {
  var template = '\
   <div id="balance_pay_white">\
    Пожалуйста, выберите <b>страну и оператора</b> для оплаты через SMS:\
    <div style="margin-left: 10px; margin; margin-top: 15px;">\
    <div class="overflow_field">\
     <div style="margin-top: 2px; margin-right: 10px; text-align: right" class="label">Страна:</div>\
     <div class="field"><div id="select_country_pay">...</div></div>\
    </div>\
    <div style="margin-top: 10px;" class="overflow_field">\
     <div id="of_operator_pay" style="margin-top: 2px; margin-right: 10px; text-align: right" class="label">Оператор:</div>\
     <div class="field">\
      <div id="select_operator_pay">...</div>\
      <div style="margin-top: 10px;" id="pay_button_get" class="blue_button_wrap small_blue_button"><div class="blue_button">Продолжить</div></div>\
      <div style="margin-left: 5px;" onclick="pay._show_type();" class="return_button">назад</div>\
     </div>\
    </div>\
    </div>\
   </div>\
  ';
  _wndWhite._closed();
  nav.loader(1);
  $.getJSON('/pay/sms/lib.php', function(data) {
   var response = data;
   var country_lenght = response.length;
   nav.loader('');
   _wndWhite._show(530, {title: 'Оплата через мобильный телефон', onShow: function() {
    // список стран
    var country_name_array = [];
    for(i = 0; i < country_lenght; i++) {
     var country_code = response[i].country;
     var country_name = response[i].country_name;
     var country_down = response[i].country_down;
     var country_name_array_temp = [country_code, country_name];
     country_name_array.push(country_name_array_temp);
    }
    country_name_array.push('RU');
    select._new('select_country_pay', {width: 200, del_end: 1}, country_name_array);
    // список операторов определенной страны
    pay._show_sms_operator(response, 'RU');
    $('#select_select_country_pay a').click(function() {
     pay._show_sms_operator(response, $('#select_value_select_country_pay').val());
    });
    $('#select_value_select_country_pay').val('RU');
    $('#select_value_select_operator_pay').val('beeline');
    $('#pay_button_get').click(function() {
     pay._type_sms($('#select_value_select_country_pay').val(), $('#select_value_select_operator_pay').val());
    });
   }, text: template});
  });
 },
 _show_sms_operator: function(response, country) {
  var operators_list = ['RU', 'UA', 'BY'];
  var operators_name_array = [];
  for(i = 0; i < operators_list.length; i++) {
   if(operators_list[i] == country) {
    var operators_lenght = response[i].providers.length;
    for(j = 0; j < operators_lenght; j++) {
     var operator_code = response[i].providers[j].code;
     var operator_name = response[i].providers[j].name;
     var operators_name_array_temp = [operator_code, operator_name];
     operators_name_array.push(operators_name_array_temp);
    }
   }
  }
  operators_name_array.push('beeline');
  if(operators_name_array.length == 1) {
   select._new('select_operator_pay', {width: 200}, [[0, 'Будет выбран автоматически'], 0]);
  } else {
   select._new('select_operator_pay', {width: 200, del_end: 1}, operators_name_array);
  }
 },
 _type_sms: function(country, operator) {
  if(country == 'RU' && !operator) operator = 'beeline';
  else if(country == 'UA' && !operator) operator = 'kyivstar';
  else operator = operator;
  var template = '\
  <div id="balance_pay_white">\
   <div id="prepend_psystem">Пожалуйста, выберите желаемое <b>количество баллов</b>:</div>\
   <div align="center" id="balance_pay_loader">\
    <div class="progress7"></div>\
    <div class="progress7_text">Ожидаем завершения оплаты.. <a href="javascript://" onclick="pay._type_loader_hide();">Отмена</a></div>\
   </div>\
   <div id="balance_pay_no_loader">\
    <div id="pay_error_msgm" class="error_msgm"></div>\
    <div id="balance_pay_inner">\
     <div style="overflow: hidden">\
      <div style="float: left">\
       <div onclick="$(\'#sms_points_num\').val(15)"" class="pay_radio_btn pay_radio_btn_y" id="radiobtn1"></div>\
       <div onclick="$(\'#sms_points_num\').val(150)"" class="pay_radio_btn pay_radio_btn_y" id="radiobtn2"></div>\
       <div onclick="$(\'#sms_points_num\').val(300)"" class="pay_radio_btn pay_radio_btn_y" id="radiobtn3"></div>\
      </div>\
     </div>\
     <input type="hidden" id="box_white_pay_count">\
     <div style="margin-bottom: 15px;">Указаны приблизительные цены. На самом деле они могут быть ниже или выше. <br /> Точную стоимость, Вы можете узнать, нажав на кнопку "Перейти к оплате".</div>\
     <div id="pay_button_get" class="blue_button_wrap small_blue_button"><div class="blue_button">Перейти к оплате</div></div>\
     <div style="margin-left: 5px;" onclick="pay._show_type();" class="return_button">Выбрать другой способ оплаты</div>\
    </div>\
   </div>\
  </div>\
  <input type="hidden" id="sms_points_num">\
  \
  ';
  _wndWhite._show(530, {title: 'Оплата через мобильный телефон', onShow: function() {
   // 0.1, 1, 2, 4
   if(country == 'RU') {
    var btn1 = '~20 рублей';
    var btn2 = '~60 рублей';
    var btn3 = '~85 рублей';
   } else if(country == 'UA') {
    var btn1 = '~1 гривен';
    var btn2 = '~12 гривен';
    var btn3 = '~20 гривен';
   } else if(country == 'BY') {
    var btn1 = '~6900 белорусских рублей';
    var btn2 = '~15900 белорусских рублей';
    var btn3 = '~19900 белорусских рублей';
   }
   
   radiobtn._new('radiobtn1', {title: '<div class="pay_title_balance">15 баллов</div><div class="pay_title_balance_rur">'+btn1+'</div>'});
   radiobtn._new('radiobtn2', {title: '<div class="pay_title_balance">150 баллов</div><div class="pay_title_balance_rur">'+btn2+'</div>'});
   radiobtn._new('radiobtn3', {title: '<div class="pay_title_balance">300 баллов</div><div class="pay_title_balance_rur">'+btn3+'</div>'});
   $('#pay_button_get').click(function() {
    var points = $('#sms_points_num').val() * 1;
    if(!points) {
     return false;
    } else {
     pay._text_sms(country, operator, $('#sms_points_num').val());
    }
   });
   $('#wnd_white_main').css({position: 'fixed', top: ($(window).height() - $('#wnd_white_main').height())/2, left:($(window).width() - 500)/2, width: 500});
  }, text: template});
 },
 _text_sms: function(country, operator, points) {
  $('#pay_button_get .blue_button').html('<div class="upload"></div>');
  $('#pay_error_msgm').hide();
  $.getJSON('/pay/sms/info.php', {
   country: country,
   operator: operator,
   points: points
  }, function(data) {
   $('#pay_button_get .blue_button').html('Перейти к оплате');
   var response = data;
   var error_text = response.error_text;
   if(response.success == 1) {
    var num = response.num;
    var text = response.text;
    var price = response.price;
    var template = '\
     <div id="pay_box_user_info">\
      Отправьте смс с текстом <b>'+text+'</b> на номер <b>'+num+'</b>.\
      <br />\
      Стоимость услуги <b>'+price+'</b>.\
      <br /> <br />\
      После отправки смс, на Ваш счет будет зачислено <b>'+points+' баллов</b>.\
     </div>\
     <div id="pay_box_user_info_support">\
      Если в течение <b>24 часов</b> баллы не поступят на Ваш счет, Вы можете обратиться в <a href="/support/new" onclick="nav.go(this); return false">поддержку</a>.\
     </div>\
     <div id="pay_box_descr"><b>Не закрывайте</b> это окно до тех пор, пока баллы не поступят на Ваш счет.</div>\
     <input value="'+response.s_invoice+'" id="s_invoice" type="hidden">\
     <input value="'+response.s_value+'" id="s_value" type="hidden">\
     <input value="'+response.s_sign+'" id="s_sign" type="hidden">\
    ';
    _wndWhite._show(530, {title: 'Оплата через мобильный телефон', text: template, onShow: function() {
     setTimeout(function() {
      pay._sms_check();
     }, 5000);
    }});
   }
   
   if(error_text) {
    $('#pay_error_msgm').show().html(error_text);
   }
  });
 },
 _sms_check: function() {
  $.getJSON('/pay/sms/check.php', {
   s_invoice: $('#s_invoice').val(),
   s_sign: $('#s_sign').val()
  }, function(data) {
   var response = data;
   var error_text = response.error_text;
   
   if(response.success == 1) {
    _wndWhite._closed();
    _money._plus(response.points);
    _wndBottom._show(
     'Вы получили баллы.', 
     'На Ваш счет '+declOfNum(response.points, ['зачислен', 'зачислено', 'зачислено'])+' <a href="/settings/balance" onclick="nav.go(this); return false"><b>'+response.points+' '+declOfNum(response.points, ['балл', 'балла', 'баллов'])+'</b></a>.', 
     {
      img: '/images/new_points.jpg'
    });
   } else {
    setTimeout(function() {
     pay._sms_check();
    }, 3000);
   }
  });
 }
}

var restore = {
 change_type: function(val) {
  $('#restore_type_val').val(val);
  if(val == 1) {
   $('#restore_next_button .blue_button').html('<div class="upload"></div>');
   nav.go('', '/restore?type=email');
  } else if(val == 2) {
   $('#restore_next_button .blue_button').html('<div class="upload"></div>');
   nav.go('', '/restore?type=vk');
  }
 },
 restore_email: function() {
  $('#restore_form_er_wrap').html('');
  $('#restore_field_code_o').hide();
  var email = $('#restore_field_email').val();
  var code = $('#restore_field_code').val();
  if(!email) {
   $('#restore_field_email').focus();
  } else {
   $('#restore_next_button .blue_button').html('<div class="upload"></div>');
   $.getJSON('/restore/email', {
    email: email,
    code: code
   }, function(data) {
    var response = data;
    var error_text = response.error_text;
    $('#restore_next_button .blue_button').html('Продолжить');
    
    if(error_text) {
     $('#restore_form_er_wrap').html('<div id="restore_form_er" class="error_msg">'+error_text+'</div>');
    } else if(response.email) {
     $('#restore_form_er_wrap').html('<div id="restore_form_er" class="info_restore_msg">На Ваш e-mail <b>'+response.email+'</b>, указанный при регистрации, отправлен проверочный код. Введите его в форму ниже и нажмите "Продолжить".</div>');
     $('#restore_field_code_o').show('toggle');
    } else if(response.success == 1) {
     $('#restore_types_checks').html('<div id="restore_form_er" class="info_restore_msg"><b>Заявка на восстановление доступа одобрена!</b><br /> <div style="padding-left: 5px; padding-top: 5px;">Логин для входа: <b>'+response.ulogin+'</b> <br /> Пароль для входа: <b>'+response.upassword+'</b></div></div> <div id="restore_g_help">В дальнейшем, Вы можете сменить пароль на свой, зайдя в аккаунт и перейдя в раздел <b>Мои настройки</b>.</div>');
    } else {
     alert(response.text);
    }
   });
  }
 },
 restore_vk: function() {
  $('#help_r_vk').show();
  $('#restore_form_er_wrap').html('');
  $('#restore_field_code_o').hide();
  var vkid = $('#restore_field_email').val();
  if(!vkid) {
   $('#restore_field_email').focus();
  } else {
   $('#restore_next_button .blue_button').html('<div class="upload"></div>');
   $.getJSON('/restore/vk', {
    vkid: vkid
   }, function(data) {
    var response = data;
    var error_text = response.error_text;
    $('#restore_next_button .blue_button').html('Продолжить');
    
    if(error_text) {
     $('#restore_form_er_wrap').html('<div id="restore_form_er" class="error_msg">'+error_text+'</div>');
    } else if(response.vstatus) {
     $('#help_r_vk').hide();
     $('#restore_form_er_wrap').html('<div id="restore_form_er" class="info_restore_msg">Чтобы убедиться, что владельцем данной страницы являетесь именно Вы, установите в статус следующий текст: <div style="text-align: center; padding-top: 5px; font-size: 14px; font-weight: bold;">'+response.text+'</div></div>');
     $('#restore_field_code_o').show('toggle');
    } else if(response.success == 1) {
     $('#restore_types_checks').html('<div id="restore_form_er" class="info_restore_msg"><b>Заявка на восстановление доступа одобрена!</b><br /> <div style="padding-left: 5px; padding-top: 5px;">Логин для входа: <b>'+response.ulogin+'</b> <br /> Пароль для входа: <b>'+response.upassword+'</b></div></div> <div id="restore_g_help">В дальнейшем, Вы можете сменить пароль на свой, зайдя в аккаунт и перейдя в раздел <b>Мои настройки</b>.</div>');
    } else {
     alert(response.text);
    }
   });
  }
 }
}

function myrand(c_min, c_max){
  return Math.round(Math.random() * (c_max - c_min) + c_min);
}

var captcha_box = {
 _show: function(fn) {
  var _title = 'Введите код с картинки';
  var _rand = myrand(1111111111, 999999999);
  var template = '\
   <div id="captcha_box">\
    <div class="img">\
     <img src="/secure?captcha_key='+_rand+'">\
    </div>\
    <div class="field">\
     <input iplaceholder="Введите код сюда" value="" maxlength="6" type="text" id="captcha_text">\
    </div>\
   </div>\
  ';
  _wndBlue._show(350, {title: _title, text: template, onContent: function() {
   _placeholder('#captcha_text');
   $('#captcha_key').val(_rand);
   $('#captcha_box').find('img').click(function() {
    captcha_box._reload();
   });
   // отправка по Enter
   $('#captcha_text').keydown(function(event) {
    var keyCode = event.which;
    if(keyCode == 13) {
     $('#box_button_blue .blue_button').html('<div class="upload"></div>');
     $('#captcha_code').val($('#captcha_text').val());
     fn();
    }
   });
   $('#box_button_blue').click(function() {
    $('#captcha_code').val($('#captcha_text').val());
    $('#box_button_blue .blue_button').html('<div class="upload"></div>');
   });
  }, _wsend: 95, _tsend: 'Отправить', _send: fn});
 },
 _reload: function() {
  var rand = myrand(1111111111, 999999999);
  $('#captcha_key').val(rand);
  $('#captcha_box img').attr('src', '/secure?captcha_key='+rand);
 }
}

// конфиги страницы
var page_name = iPopular.page_name;
var section = iPopular.section;

// функция склонения числительных
function declOfNum(number, titles) {
 cases = [2, 0, 1, 1, 1, 2];
 return titles[(number%100>4 && number%100<20)? 2 : cases[(number%10<5)?number%10:5]];
}

// определение склонения при добавлении заданий
function _decl_add_task_form(pname, psection) {
 $('#add_task_amount').keyup(function() {
  if(!$(this).val().match(/^([0-9]+)$/)) {
   $(this).val('');
  }
  var _amount = $(this).val() * 1;
  if(pname == 'add_task' || pname == 'add_task') {
   $('#amount_right').text(declOfNum(_amount, ['балл', 'балла', 'баллов']));
  }
 });
 $('#add_task_count').keyup(function() {
  if(!$(this).val().match(/^([0-9]+)$/)) {
   $(this).val('');
  }
  var _count = $(this).val() * 1;
  if((pname == 'add_task' && psection == 'likes') || (pname == 'add_task' && psection == 0)) {
   $('#count_right').text(declOfNum(_count, ['отметка «Мне нравится»', 'отметки «Мне нравится»', 'отметок «Мне нравится»']));
  } else if(pname == 'add_task' && psection == 'reposts') {
   $('#count_right').text(declOfNum(_count, ['репост', 'репоста', 'репостов']));
  } else if(pname == 'add_task' && psection == 'friends') {
   $('#count_right').text(declOfNum(_count, ['подписчик', 'подписчика', 'подписчиков']));
  } else if(pname == 'add_task' && psection == 'groups') {
   $('#count_right').text(declOfNum(_count, ['вступивший', 'вступивших', 'вступивших']));
  } else if(pname == 'add_task' && psection == 'comments') {
   $('#count_right').text(declOfNum(_count, ['комментарий', 'комментария', 'комментариев']));
  } else if(pname == 'add_task' && psection == 'polls') {
   $('#count_right').text(declOfNum(_count, ['голос', 'голоса', 'голосов']));
  }
 });
}

var ajax_nav = '';
var nav = {
 go: function(a, b, ufn, back) {
  var url = b ? b : $(a).attr('href');
  nav.loader_page();
  $.get(url, function(data) {
   var ajax_nav = 1;
   var response = data;
   var title_page = response.match(/<title>(.*?)<\/title>/i);
   var content_page = response.match(/<div id="page">([\s\S]*)<\/div>/i);
   var content_script = response.match(/<script id="mainscripts" type="text\/javascript">([\s\S]*)<\/script>/i);
   var content_script_result = content_script ? content_script[1] : '';
   var page_name_reg = /page_name: \'(.*?)\'/;
   var page_name = page_name_reg.exec(response);
   var section_reg = /section: \'(.*?)\'/;
   var section = section_reg.exec(response);
   var page_name_result = page_name ? page_name[1] : '';
   var section_result = section ? section[1] : '';
   if(content_page) {
    ufn ? ufn() : '';
    // закрываем боксы
    _wndWhite._closed();
    _wndBlue._closed();
    // изменяем title
    document.title = title_page[1].toString();
    // up
    //$('body, html').animate({scrollTop: 0}, 200);
    // загружаем содержимое
    $('#page').html(content_page[1].toString());
    // live
    $('#live_counter').html("<a href='http://www.liveinternet.ru/click' "+
    "target=_blank><img src='//counter.yadro.ru/hit?t44.6;r"+
    escape(document.referrer)+((typeof(screen)=="undefined")?"":
    ";s"+screen.width+"*"+screen.height+"*"+(screen.colorDepth?
    screen.colorDepth:screen.pixelDepth))+";u"+escape(document.URL)+
    ";"+Math.random()+
    "' alt='' title='LiveInternet' "+
    "border='0' width='31' height='31'><\/a>");
    if(content_script_result) {
     eval(content_script_result);
    }
    if($(window).scrollTop() > 1) {
     $('#header').addClass('noactive');
     $('#info_msg_header').hide();
    } else {
     $('#header').removeClass('noactive');
     $('#info_msg_header').show();
    }
    
    function _placeholder(a) {
     var _placeholder = $(a).attr('iplaceholder');
     var _value = $(a).val();
     if(!_value) {
      setTimeout(function() {$(a).val(_placeholder)}, 0);
      $(a).addClass('placeholder');
     }
     $(a).focus(function() {
      $(a).removeClass('placeholder');
      if($(this).val() == _placeholder) $(this).val('');
     });
     $(a).blur(function() {
      if(!$(this).val()) {
       $(this).val(_placeholder);
       $(a).addClass('placeholder');
      }
     });
    }
    function _val(a) {
     return $(a).attr('class').indexOf('placeholder') > -1 ? 0 : 1;
    }
    // склонения при добавлении задания
    _decl_add_task_form(page_name_result, section_result);
    // меняем url
    if(!back) {
     window.history.ready = true;
     history.pushState ? history.pushState({}, '', url) : location.hash = url;
    }
    if($('#hpush_url_nav_val').html()) {
     $('#hpush_url_nav_val').html(url);
    } else {
     $('body').append('<div style="display: none" id="hpush_url_nav_val">'+url+'</div>');
    }
   } else {
    nav.error_head('Сервис временно недоступен. Попробуйте позже.');
    nav.loader_page_hide();
   }
  });
 },
 loader: function(a) {
  a ? $('#loading').show().css({position: 'fixed', top: ($(window).height()/2 - 64), left: ($(window).width() - 64)/2}) : $('#loading').hide();
 },
 loader_page: function() {
  $('#head_loader').css('opacity', 1);
 },
 loader_page_hide: function() {
  $('#head_loader').css('opacity', 0);
 },
 error_head: function(text) {
  $('#error_head').fadeIn(400).html(text);
  setTimeout(function() {
   $('#error_head').fadeOut(400);
  }, 3500);
 }
}