var admin_tasks_blacklist = {
 _add: function() {
  var url = _val('#input_field_url_blacklist') ? $('#input_field_url_blacklist').val() : '';
  var description = _val('#input_field_text_blacklist') ? $('#input_field_text_blacklist').val() : '';
  $('.admin_tasks_blacklist_button_add .blue_button').html('<div class="upload"></div>');
  $('#admin_tasks_blacklist_error_add').hide();
  $.post('/admin/modules/tasks/blacklist/add.post.php', {
   url: url,
   description: description
  }, function(data) {
   var response = $.parseJSON(data);
   var error_text = response.error_text;
   $('.admin_tasks_blacklist_button_add .blue_button').html('Добавить в черный список');
   if(error_text == 'login') {
    nav.go('', '/');
    return;
   }
   
   if(error_text) {
    $('#admin_tasks_blacklist_error_add').show().html(error_text);
   } else if(response.success == 1) {
    $('.admin_tasks_blacklist_button_add .blue_button').html('<div class="upload"></div>');
    nav.go('', '/admin/modules/tasks/blacklist');
   } else {
    $('#admin_tasks_blacklist_error_add').show().html('Неизвестная ошибка.');
   }
  });
 },
 _delete: function(id) {
  $('#error_msg_blacklist').hide();
  $('.admin_tasks_blacklist_button_del'+id+' .blue_button').html('<div class="upload"></div>');
  $.getJSON('/admin/modules/tasks/blacklist/delete.php', {
   id: id
  }, function(data) {
   var response = data;
   var error_text = response.error_text;
   $('.admin_tasks_blacklist_button_del'+id+' .blue_button').html('Удалить');
   if(error_text == 'login') {
    nav.go('', '/');
    return;
   }
   
   if(error_text) {
    $('body, html').animate({scrollTop: 0}, 200);
    $('#error_msg_blacklist').show().html(error_text);
   } else if(response.success == 1) {
    $('#blacklist_tr'+id).fadeOut(200);
   } else {
    $('body, html').animate({scrollTop: 0}, 200);
    $('#error_msg_blacklist').show().html('Неизвестная ошибка.');
   }
  });
 },
 _reject: function(id) {
  $('#error_msg_blacklist').hide();
  $('.admin_tasks_blacklist_button_rej'+id+' .blue_button').html('<div class="upload"></div>');
  $.getJSON('/admin/modules/tasks/blacklist/reject.php', {
   id: id
  }, function(data) {
   var response = data;
   var error_text = response.error_text;
   $('.admin_tasks_blacklist_button_rej'+id+' .blue_button').html('Отклонить');
   if(error_text == 'login') {
    nav.go('', '/');
    return;
   }
   
   if(error_text) {
    $('body, html').animate({scrollTop: 0}, 200);
    $('#error_msg_blacklist').show().html(error_text);
   } else if(response.success == 1) {
    $('#blacklist_tr'+id).fadeOut(200);
   } else {
    $('body, html').animate({scrollTop: 0}, 200);
    $('#error_msg_blacklist').show().html('Неизвестная ошибка.');
   }
  });
 },
 _consider: function(id) {
  $('#error_msg_blacklist').hide();
  $('.admin_tasks_blacklist_button_con'+id+' .blue_button').html('<div class="upload"></div>');
  $.getJSON('/admin/modules/tasks/blacklist/consider.php', {
   id: id
  }, function(data) {
   var response = data;
   var error_text = response.error_text;
   $('.admin_tasks_blacklist_button_con'+id+' .blue_button').html('Принять');
   if(error_text == 'login') {
    nav.go('', '/');
    return;
   }
   
   if(error_text) {
    $('body, html').animate({scrollTop: 0}, 200);
    $('#error_msg_blacklist').show().html(error_text);
   } else if(response.success == 1) {
    $('.admin_tasks_blacklist_button_con'+id+' .blue_button').html('<div class="upload"></div>');
    nav.go('', '/admin/modules/tasks/blacklist/');
   } else {
    $('body, html').animate({scrollTop: 0}, 200);
    $('#error_msg_blacklist').show().html('Неизвестная ошибка.');
   }
  });
 }
}

var admin_tasks = {
 _delete: function(id, text) {
  $('.task_button_delete_admin'+id+' .blue_button').html('<div class="upload"></div>');
  $.getJSON('/admin/modules/tasks/delete.php', {
   id: id
  }, function(data) {
   var response = data;
   var error_text = response.error_text;
   $('.task_button_delete_admin'+id+' .blue_button').html(text);
   if(error_text == 'login') {
    nav.go('', '/');
    return;
   }
   if(error_text) {
    $('#task'+id).hide();
    $('#del_table'+id).show().html(error_text);
   } else if(response.success) {
    if(response.type == 'delete') {
     $('#admin_tasks_del_fd'+id).show().html('<div class="admin_tasks_del_f">Задание <b>удалено</b></div>');
     $('.task_button_delete_admin'+id+' .blue_button').html('Восстановить задание');
    } else {
     $('#admin_tasks_del_fd'+id).hide();
     $('.task_button_delete_admin'+id+' .blue_button').html('Удалить задание');
    }
   } else {
    $('#task'+id).hide();
    $('#del_table'+id).show().html('Неизвестная ошибка.'); 
   }
  });
 },
 _logs_edits: function(id) {
  nav.loader(1);
  $.get('/admin/modules/tasks/logs_edits.php', {
   id: id
  }, function(data) {
   var response = data;
   nav.loader();
   if(response == 'login') {
    nav.go('', '/');
    return;
   }
   _wndBlue._show(500, {title: 'История редактирований задания', text: '<div id="admin_edit_info_box_info"> Показываются точные изменения задания как пользователем, так и администратором. <br /> Зеленым цветом обозначаются новые изменения, черным — предыдущие. </div><div id="admin_edit_info_history_box">'+response+'</div>', onContent: function() {
    function page_click() {
     $('#admin_edit_info_box_pages a').click(function() {
      var href = $(this).attr('href');
      $('.pages_ajax').show();
      $.get(href, function(data) {
       $('#admin_edit_info_history_box').html(data);
       nav.loader_page_hide();
       page_click();
      });
      return false;
     });
    }
    page_click();
   }});
  });
 },
 _logs_dels: function(id) {
  nav.loader(1);
  $.get('/admin/modules/tasks/logs_dels.php', {
   id: id
  }, function(data) {
   var response = data;
   nav.loader();
   if(response == 'login') {
    nav.go('', '/');
    return;
   }
   _wndBlue._show(500, {title: 'История удалений и восстановлений задания', text: '<div id="admin_edit_info_history_box">'+response+'</div>', onContent: function() {
    function page_click() {
     $('#admin_edit_info_box_pages a').click(function() {
      var href = $(this).attr('href');
      $('.pages_ajax').show();
      $.get(href, function(data) {
       $('#admin_edit_info_history_box').html(data);
       nav.loader_page_hide();
       page_click();
      });
      return false;
     });
    }
    page_click();
   }});
  });
 }
}

var admin_users = {
 _search: function(url) {
  if(_val('#input_tasks_search')) {
   $('#search .blue_button').html('<div class="upload"></div>');
   nav.go('', url);
  } else {
   $('#input_tasks_search').focus();
  }
 },
 _edit_balance: function(uid) {
  nav.loader(1);
  $.get('/admin/modules/users/history.balance.php', {
   uid: uid
  }, function(data) {
   var response = data;
   nav.loader();
   if(response == 'login') {
    nav.go('', '/');
    return;
   }
   _wndBlue._show(500, {title: 'Изменение баланса', onContent: function() {
    radiobtn._new('radiobtn1', {title: 'Пополнить баланс'});
    radiobtn._new('radiobtn2', {title: 'Списать с баланса'});
    _placeholder('#settings_balance_admin_users_add_field_input');
    function page_click() {
     $('#settings_balance_admin_users_pages a').click(function() {
      var href = $(this).attr('href');
      $('.pages_ajax').show();
      $.get(href, function(data) {
       $('#settings_balance_admin_users').html(data);
       nav.loader_page_hide();
       page_click();
      });
      return false;
     });
    }
    page_click();
    $('#settings_balance_admin_users_add_field_input').keydown(function(event) {
     var keyCode = event.which;
     if(keyCode == 13) {
      $('#settings_balance_admin_users_add_button').click();
      return false;
     }
    });
    $('#settings_balance_admin_users_add_button').click(function() {
     $('#settings_balance_admin_users_add_error_w').html('');
     $('#settings_balance_admin_users_add_button .blue_button').html('<div class="upload"></div>');
     $.post('/admin/modules/users/edit.balance.php', {
      uid: uid,
      type: $('#settings_balance_admin_users_add_field_input_type').val(),
      num: $('#settings_balance_admin_users_add_field_input').val()
     }, function(data) {
      var response = $.parseJSON(data);
      var error_text = response.error_text;
      $('#settings_balance_admin_users_add_button .blue_button').html('Изменить');
      if(error_text == 'login') {
       nav.go('', '/');
       return;
      }
      if(error_text) {
       $('#settings_balance_admin_users_add_error_w').html('<div id="settings_balance_admin_users_add_error">'+error_text+'</div>');
      } else if(response.success == 1) {
       $('#settings_balance_admin_users_add_error_w').html('<div id="settings_balance_admin_users_add_success">Баланс успешно изменен.</div>');
       _placeholder('#settings_balance_admin_users_add_field_input');
       $('#settings_balance_admin_users_add_field_input').val('');
      } else {
       $('#settings_balance_admin_users_add_error_w').html('<div id="settings_balance_admin_users_add_error">Неизвестная ошибка.</div>');
      }
     });
    });
   }, text: '\
    <div id="settings_balance_admin_users_add">\
    <div onclick="$(\'#settings_balance_admin_users_add_field_input_type\').val(1)" id="radiobtn1"></div>\
    <br />\
    <div onclick="$(\'#settings_balance_admin_users_add_field_input_type\').val(2)" id="radiobtn2"></div>\
    <br />\
    <div id="settings_balance_admin_users_add_f">\
     <div id="settings_balance_admin_users_add_field"><input id="settings_balance_admin_users_add_field_input_type" type="hidden"><input iplaceholder="кол-во" id="settings_balance_admin_users_add_field_input" type="text"></div>\
     <div id="settings_balance_admin_users_add_button_l">\
      <div id="settings_balance_admin_users_add_button" class="blue_button_wrap small_blue_button"><div class="blue_button">Изменить</div></div>\
      <span id="settings_balance_admin_users_add_error_w"></span>\
     </div>\
    </div>\
    </div>\
    <div id="settings_balance_admin_users">\
     '+response+'\
    </div>\
   '});
  });
 },
 _history_login: function(uid) {
  nav.loader(1);
  $.get('/admin/modules/users/history.login.php', {
   uid: uid
  }, function(data) {
   var response = data;
   nav.loader();
   if(response == 'login') {
    nav.go('', '/');
    return;
   }
   _wndBlue._show(500, {title: 'История активности', text: '<div id="admin_history_login_box">'+response+'</div>', onContent: function() {
    function page_click() {
     $('#admin_history_login_pages a').click(function() {
      var href = $(this).attr('href');
      $('.pages_ajax').show();
      $.get(href, function(data) {
       $('#admin_history_login_box').html(data);
       nav.loader_page_hide();
       page_click();
      });
      return false;
     });
    }
    page_click();
   }});
  });
 },
 _edit_info: function(uid) {
  nav.loader(1);
  $.get('/admin/modules/users/edit.info.php', {
   uid: uid
  }, function(data) {
   var response = data;
   nav.loader();
   if(response == 'login') {
    nav.go('', '/');
    return;
   }
   _wndBlue._show(500, {title: 'Редактирование информации', text: '<div id="admin_edit_info_box">'+response+'</div>', footer_left: '<a href="javascript://" onclick="admin_users._history_edit_info('+uid+')">История редактирований информации</a>', _wsend: 110, _tsend: 'Редактировать', _send: function() {
    var name = _val('#user_edit_info_name') ? $('#user_edit_info_name').val() : '';
    var last_name = _val('#user_edit_info_last_name') ? $('#user_edit_info_last_name').val() : '';
    var avatar = _val('#user_edit_info_avatar') ? $('#user_edit_info_avatar').val() : '';
    var login = _val('#user_edit_info_login') ? $('#user_edit_info_login').val() : '';
    var email = _val('#user_edit_info_email') ? $('#user_edit_info_email').val() : '';
    var id_vk = _val('#user_edit_info_vk') ? $('#user_edit_info_vk').val() : '';
    var gender = $('#select_value_user_edit_info_gender').val();
    $('#admin_user_edit_info_error').hide();
    $('#box_button_blue .blue_button').html('<div class="upload"></div>');
    $.post('/admin/modules/users/edit.info.php', {
     edit_post: 1,
     uid: uid,
     name: name,
     last_name: last_name,
     avatar: avatar,
     login: login,
     email: email,
     id_vk: id_vk,
     gender: gender
    }, function(data) {
     var response = $.parseJSON(data);
     var error_text = response.error_text;
     $('#box_button_blue .blue_button').html('Редактировать');
     if(error_text == 'login') {
      nav.go('', '/');
      return;
     }
     
     if(error_text) {
      $('#admin_user_edit_info_error').show().html('<div class="error_msgm">'+error_text+'</div>');
     } else if(response.success == 1) {
      $('#admin_user_edit_info_error').show().html('<div class="msg">Изменения сохранены.</div>');
     } else {
      $('#admin_user_edit_info_error').show().html('<div class="error_msgm">Неизвестная ошибка.</div>');
     }
    });
    }, onContent: function() {
    _placeholder('#user_edit_info_name');
    _placeholder('#user_edit_info_last_name');
    _placeholder('#user_edit_info_login');
    _placeholder('#user_edit_info_email');
    _placeholder('#user_edit_info_avatar');
    _placeholder('#user_edit_info_vk');
    select._new('user_edit_info_gender', {width: 240}, [[0, '- Не выбран -'], [2, 'мужской'], [1, 'женский'], $('#user_edit_info_gender_value').val()]);
    $('#admin_edit_info_box input').keydown(function(event) {
     var keyCode = event.which;
     if(keyCode == 13) {
      $('#box_button_blue').click();
      return false;
     }
    });
   }});
  });
 },
 _history_edit_info: function(uid) {
  _wndBlue._closed();
  nav.loader(1);
  $.get('/admin/modules/users/edit.info.php', {
   edit_history: 1,
   uid: uid
  }, function(data) {
   var response = data;
   nav.loader();
   if(response == 'login') {
    nav.go('', '/');
    return;
   }
   _wndBlue._show(500, {title: 'История редактирований информации', text: '<div id="admin_edit_info_box_info">Показываются точные изменения информации в профиле как пользователем, так и администратором. <br /> Зеленым цветом обозначаются новые изменения, черным — предыдущие. </div><div id="admin_edit_info_history_box">'+response+'</div>', onContent: function() {
    function page_click() {
     $('#admin_edit_info_box_pages a').click(function() {
      var href = $(this).attr('href');
      $('.pages_ajax').show();
      $.get(href, function(data) {
       $('#admin_edit_info_history_box').html(data);
       nav.loader_page_hide();
       page_click();
      });
      return false;
     });
    }
    page_click();
   }});
  });
 },
 _blocked: function(uid) {
  _wndBlue._show(500, {title: 'Заблокировать аккаунт', text: '<div id="admin_edit_info_box_info">Вы можете заблокировать аккаунт пользователя без возможности разблокировки для него. Пожалуйста, <b>указывайте точную причину блокировки</b>.</div><div id="admin_user_banned_textarea"><div id="user_error_msg_blocked" class="error_msg"></div><textarea id="admin_user_banned_textarea_field" iplaceholder="Введите причину блокировки. Она будет видна пользователю."></textarea></div>', onContent: function() {
   _placeholder('#admin_user_banned_textarea_field');
  }, footer_left: '<a href="javascript://" onclick="admin_users._blocked_history('+uid+')">История блокировок аккаунта</a>', _wsend: 115, _tsend: 'Заблокировать', _send: function() {
   $('#box_button_blue .blue_button').html('<div class="upload"></div>');
   $('#user_error_msg_blocked').hide();
   $.getJSON('/admin/modules/users/blocked.php', {
    uid: uid,
    text: _val('#admin_user_banned_textarea_field') ? $('#admin_user_banned_textarea_field').val() : ''
   }, function(data) {
    var response = data;
    var error_text = response.error_text;
    $('#box_button_blue .blue_button').html('Заблокировать');
    
    if(error_text == 'login') {
     nav.go('', '/');
     return;
    }
    
    if(error_text) {
     $('#user_error_msg_blocked').show().html(error_text);
    } else if(response.success == 1) {
     _wndBlue._closed();
     $('#overflow_field_user_mini_text'+uid).html('<div class="user_list_ban_status">заблокирован</div>');
     $('#user_blocked_span'+uid).html('<a href="javascript://" onclick="admin_users._unblocked('+uid+')">Разблокировать аккаунт</a>');
    } else {
     $('#user_error_msg_blocked').show().html('Неизвестная ошибка.');
    }
   });
  }});
 },
 _unblocked: function(uid) {
  nav.loader(1);
  $.getJSON('/admin/modules/users/blocked.php', {
   uid: uid,
   type: 2
  }, function(data) {
   var response = data;
   var error_text = response.error_text;
   nav.loader();
   
   if(error_text == 'login') {
    nav.go('', '/');
    return;
   }
   
   if(response.success == 1) {
    $('#overflow_field_user_mini_text'+uid).html('<div class="user_list_ok_status">активен</div>');
    $('#user_blocked_span'+uid).html('<a href="javascript://" onclick="admin_users._blocked('+uid+')">Заблокировать аккаунт</a>');
   }
  });
 },
 _blocked_history: function(uid) {
  _wndBlue._closed();
  nav.loader(1);
  $.get('/admin/modules/users/blocked.php', {
   history: 1,
   uid: uid
  }, function(data) {
   var response = data;
   nav.loader();
   if(response == 'login') {
    nav.go('', '/');
    return;
   }
   _wndBlue._show(500, {title: 'История блокировок', text: '<div id="admin_edit_info_history_box">'+response+'</div>', onContent: function() {
    function page_click() {
     $('#admin_edit_info_box_pages a').click(function() {
      var href = $(this).attr('href');
      $('.pages_ajax').show();
      $.get(href, function(data) {
       $('#admin_edit_info_history_box').html(data);
       nav.loader_page_hide();
       page_click();
      });
      return false;
     });
    }
    page_click();
   }});
  });
 }
}

var admin_support = {
 _edit_status: function(status, id) {
  if(status == 0) {
   $('#support_title_head_left_status').html('Рассматривается.');
  } else if(status == 1) {
   $('#support_title_head_left_status').html('Есть <b>новый ответ</b>.');
  } else {
   $('#support_title_head_left_status').html('Есть ответ.');
  }
  $.get('/admin/modules/support/edit_status.php', {
   id: id,
   status: status
  }, function(data) {});
 }
}

var admin_pages = {
 _add: function() {
  $('#admin_site_page_add_error').html('');
  $('#admin_site_page_add_save_button .blue_button').html('<div class="upload"></div>');
  $.post('/admin/modules/pages/post_add.php', {
   short_url: _val('#admin_site_page_add_short_url') ? $('#admin_site_page_add_short_url').val() : '',
   name: _val('#admin_site_page_add_name') ? $('#admin_site_page_add_name').val() : '',
   text: $('#editor').bbcode()
  }, function(data) {
   var response = $.parseJSON(data);
   var error_text = response.error_text;
   $('#admin_site_page_add_save_button .blue_button').html('Создать страницу');
   if(error_text == 'login') {
    nav.go('', '/');
    return;
   }
     
   if(error_text) {
    $('#admin_site_page_add_error').html(error_text);
   } else if(response.success == 1) {
    $('#admin_site_page_add_save_button .blue_button').html('<div class="upload"></div>');
    nav.go('', '/page/'+response.short_url);
   } else {
    $('#admin_site_page_add_error').html('Неизвестная ошибка.');
   }
  });
 },
 _edit: function(id) {
  $('#admin_site_page_add_error').html('');
  $('#admin_site_page_edit_save_button .blue_button').html('<div class="upload"></div>');
  $.post('/admin/modules/pages/post_edit.php', {
   id: id,
   name: _val('#admin_site_page_add_name') ? $('#admin_site_page_add_name').val() : '',
   text: $('#editor').bbcode()
  }, function(data) {
   var response = $.parseJSON(data);
   var error_text = response.error_text;
   $('#admin_site_page_edit_save_button .blue_button').html('Сохранить изменения');
   if(error_text == 'login') {
    nav.go('', '/');
    return;
   }
     
   if(error_text) {
    $('#admin_site_page_add_error').html(error_text);
   } else if(response.success == 1) {
    $('#admin_site_page_add_error').html('<span style="color: green !important">Изменения успешно сохранены.</span>');
   } else {
    $('#admin_site_page_add_error').html('Неизвестная ошибка.');
   }
  });
 },
 _add_img: function() {
  $('#pages_upload_iframe').contents().text('');
  $('#pages_upload_iframe_submit').trigger('click');
  nav.loader(1);
  var iframe_content_interval = setInterval(function() {
   var iframe_content = $('#pages_upload_iframe').contents().text();
   if(iframe_content) {
    nav.loader('');
    var iframe_content_json = $.parseJSON(iframe_content);
    var iframe_content_json_error = iframe_content_json.error_text;
    if(iframe_content_json_error) {
     nav.error_head(iframe_content_json_error);
    } else if(iframe_content_json.success == 1) {
     $('#editor').execCommand('add_image', {img_url: '/images/uploads/'+iframe_content_json.result_big_file});
    } else {
     nav.error_head('Неизвестная ошибка.');
    }
    clearInterval(iframe_content_interval);
   }
  }, 10);
 },
 _delete: function(id) {
  nav.loader_page();
  $.getJSON('/admin/modules/pages/post_del.php', {
   id: id
  }, function(data) {
   var response = data;
   var error_text = response.error_text;
   nav.loader_page_hide();
   if(error_text == 'login') {
    nav.go('', '/');
    return;
   }
   
   if(error_text) {
    nav.error_head(error_text);
   } else if(response.success == 1) {
    $('#admin_page_overflow'+id).hide();
   } else {
    nav.error_head('Неизвестная ошибка.');
   }
  });
 },
 _return: function(id) {
  $('#admin_page_overflow'+id).find('.admin_page_overflow_control a').html('<div class="upload"></div>');
  $.getJSON('/admin/modules/pages/post_return.php', {
   id: id
  }, function(data) {
   var response = data;
   var error_text = response.error_text;
   $('#admin_page_overflow'+id).find('.admin_page_overflow_control a').html('Восстановить');
   if(error_text == 'login') {
    nav.go('', '/');
    return;
   }
   
   if(error_text) {
    nav.error_head(error_text);
   } else if(response.success == 1) {
    $('#admin_page_overflow'+id).hide();
   } else {
    nav.error_head('Неизвестная ошибка.');
   }
  });
 }
}

var admin_blog = {
 _add: function() {
  var title = _val('#admin_site_page_add_name') ? $('#admin_site_page_add_name').val() : '';
  var text = $('#editor').bbcode();
  var check_allow_admin = $('#blog_allow_admin').attr('checked') ? 1 : 0;
  var check_left_menu = $('#blog_left_menu').attr('checked') ? 1 : 0;
  
  $('#admin_site_page_add_error').hide();
  $('#admin_site_page_add_save_button .blue_button').html('<div class="upload"></div>');
  $.post('/blog/add.post', {
   title: title,
   text: text,
   check_allow_admin: check_allow_admin,
   check_left_menu: check_left_menu
  }, function(data) {
   var response = $.parseJSON(data);
   var error_text = response.error_text;
   $('#admin_site_page_add_save_button .blue_button').html('Создать новость');
   if(error_text == 'login') {
    nav.go('', '/');
    return;
   }
   
   if(error_text) {
    $('#admin_site_page_add_error').show().html(error_text);
   } else if(response.success == 1) {
    $('#admin_site_page_add_save_button .blue_button').html('<div class="upload"></div>');
    nav.go('', '/blog?id='+response.id);
   } else {
    $('#admin_site_page_add_error').show().html('Неизвестная ошибка.');
   }
  });
 },
 _edit: function(id) {
  var title = _val('#admin_site_page_add_name') ? $('#admin_site_page_add_name').val() : '';
  var text = $('#editor').bbcode();
  var check_allow_admin = $('#blog_allow_admin').attr('checked') ? 1 : 0;
  var check_left_menu = $('#blog_left_menu').attr('checked') ? 1 : 0;
  
  $('#admin_site_page_add_error').hide();
  $('#admin_site_page_edit_save_button .blue_button').html('<div class="upload"></div>');
  $.post('/blog/edit.post', {
   id: id,
   title: title,
   text: text,
   check_allow_admin: check_allow_admin,
   check_left_menu: check_left_menu
  }, function(data) {
   var response = $.parseJSON(data);
   var error_text = response.error_text;
   $('#admin_site_page_edit_save_button .blue_button').html('Сохранить изменения');
   if(error_text == 'login') {
    nav.go('', '/');
    return;
   }
   if(error_text) {
    $('#admin_site_page_add_error').show().html(error_text);
   } else if(response.success == 1) {
    $('#admin_site_page_add_error').show().html('<span style="color: green !important">Изменения успешно сохранены.</span>');
   } else {
    $('#admin_site_page_add_error').show().html('Неизвестная ошибка.');
   }
  });
 },
 _delete: function(id) {
  nav.loader_page();
  $.getJSON('/blog/del.post', {
   id: id
  }, function(data) {
   var response = data;
   var error_text = response.error_text;
   nav.loader_page_hide();
   if(error_text == 'login') {
    nav.go('', '/');
    return;
   }
   
   if(error_text) {
    nav.error_head(error_text);
   } else if(response.success == 1) {
    $('body, html').animate({scrollTop: 0}, 200);
    $('#blog_id_del_append').html('<div style="margin-bottom: 10px;" class="error_msg">Новость удалена и доступна только администраторам.</div>');
    $('#span_blog_id_del').hide();
   } else {
    nav.error_head('Неизвестная ошибка.');
   }
  });
 }
}