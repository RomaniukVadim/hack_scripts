  <div id="task_navigation_mn">
   <div onclick="nav.go('', '<? echo querys('/tasks', 'sort'); ?>amount')" class="mnav">по цене</div>
   <div onclick="nav.go('', '<? echo querys('/tasks', 'sort'); ?>popular')" class="mnav">по популярности</div>
   <div onclick="nav.go('', '<? echo querys('/tasks', 'sort'); ?>date')" class="mnav">по дате добавления</div>
  </div>
  <div id="admin_users_navigation_mn">
   <div onclick="nav.go('', '<? echo querys('/admin/modules/users/', 'sort'); ?>asc')" class="mnav">по дате регистрации — <b>A</b></div>
   <div onclick="nav.go('', '<? echo querys('/admin/modules/users/', 'sort'); ?>desc')" class="mnav">по дате регистрации — <b>D</b></div>
   <div onclick="nav.go('', '<? echo querys('/admin/modules/users/', 'sort'); ?>min_points')" class="mnav">по баллам — <b>A</b></div>
   <div onclick="nav.go('', '<? echo querys('/admin/modules/users/', 'sort'); ?>max_points')" class="mnav">по баллам — <b>D</b></div>
  </div>
  <script id="mainscripts" type="text/javascript">
   $(function() {
    // склонения при добавлении задания
    _decl_add_task_form(iPopular.page_name, iPopular.section);
    // placeholder к полям
    _placeholder('#input_tasks_search'); // поиск
    _placeholder('#input_field_url_blacklist');
    _placeholder('#input_field_text_blacklist');
    _placeholder('#url_blacklist_task');
    _placeholder('#support_add_content_field_theme');
    _placeholder('#support_add_field_theme');
    _placeholder('#support_add_field_text');
    _placeholder('#support_question_add_comment_field_text');
    // определение цены
    $('#add_task_amount, #add_task_count').keyup(function() {
     var amount = $('#add_task_amount').val() * 1;
     var count = $('#add_task_count').val() * 1;
     var points = amount * count;
     var points_comission = points + Math.round((points / 100) * 5);
     if(points_comission >= 1) {
      $('#add_task_save_body_points_result').html('— <b>'+points_comission+'</b> '+declOfNum(points_comission, ['балл', 'балла', 'баллов']));
     } else if(points_comission == 0) {
      $('#add_task_save_body_points_result').html('— <b>0</b> баллов');
     } else {
      $('#add_task_save_body_points_result').html('— <b>0</b> баллов');
     }
    });
    // авторизация по нажатию на Enter
    $('#ulogin, #upassword').keydown(function(event) {
     var keyCode = event.which;
     if(keyCode == 13) {
      users._post_login();
      return false;
     }
    });
    // изменения пароля по нажатию на Enter
    $('#my_settings_change_password_content input').keydown(function(event) {
     var keyCode = event.which;
     if(keyCode == 13) {
      $('#settings_password_button').click();
      return false;
     }
    });
    // изменения логина по нажатию на Enter
    $('#my_settings_account_content input').keydown(function(event) {
     var keyCode = event.which;
     if(keyCode == 13) {
      $('#settings_login_button').click();
      return false;
     }
    });
    // поиск задания по нажатию на Enter
    $('#input_tasks_search').keydown(function(event) {
     var keyCode = event.which;
     if(keyCode == 13) {
      $('#search .blue_button_wrap').click();
      return false;
     }
    });
    // blacklist_add
    $('#input_field_url_blacklist, #input_field_text_blacklist').keydown(function(event) {
     var keyCode = event.which;
     if(keyCode == 13) {
      $('.admin_tasks_blacklist_button_add').click();
      return false;
     }
    });
    // добавление задания по нажатию на Enter
    $('#form_add_task input[type="text"]').keydown(function(event) {
     var keyCode = event.which;
     if(keyCode == 13) {
      $('#add_task_button').click();
      return false;
     }
    });<? if($page_name == 'add_task' || $page_name == 'my_tasks') { ?> 
    tasks._tcategories($('#tasks_my_categories_hide').html(), 0);<? } ?> 
    if(ajax_nav) {
     stManager.load('/css/main.css?<? echo $rand_script; ?>', 'css');<? if($ugroup == 4 || $ugroup == 3 || $ugroup == 5) { ?> 
     stManager.load('/css/admin.css?<? echo $rand_script; ?>', 'css');<? } ?> 
     stManager.load('/js/all.js?<? echo $rand_script; ?>', 'js');<? if($ugroup == 4 || $ugroup == 3 || $ugroup == 5) { ?> 
     stManager.load('/js/admin.panel.js?<? echo $rand_script; ?>', 'js');<? } ?> <? if($page_name == 'site_page_add' || $page_name == 'site_page_all') { ?> 
     stManager.load('/js/wiki.js?<? echo $rand_script; ?>', 'js');<? } ?> 
    } <? if($page_name == 'site_page_add' || $page_name == 'site_page_all' || $page_name == 'blog_add') { ?> 
    $("#editor").wysibb();<? } ?> 
    $('#support_question_add_comment_field_text').autoResize({limit: 500, animate:false});
    $('#admin_site_page_add_text_field').autoResize({limit: 2000, animate:false});<? if($page_name == 'tasks') { ?> 
    minSelect._new('tasks_navigation', 150, '<b><? echo $sort_text; ?></b>', $('#task_navigation_mn').html());<? } ?> <? if($ugroup == 4 && $page_name == 'admin.users') { ?>
    minSelect._new('admin_users_navigation', 170, '<? echo $sort_text; ?>', $('#admin_users_navigation_mn').html());<? } ?> 
    $(window).scroll(function() {
     if($(this).scrollTop() > 1) {
      $('#header').addClass('noactive');
      $('#info_msg_header').hide();
     } else {
      $('#header').removeClass('noactive');
      $('#info_msg_header').show();
     }
    });
    // прокрутка до таблицы моих жалоб
    if($("#admin_tasks_blacklist_table").html()) {<? if($_GET['list'] == 1) { ?>$('html, body').animate({scrollTop: $("#admin_tasks_blacklist_table").offset().top}, 500);<? } ?>} <? if($_POST['activated_email'] == 1) { ?> 
    cnt_black._show({title: 'Регистрация подтверждена.', text: 'Теперь Вы можете пользоваться всеми полномочиями сайта.'});<? } ?> 
    _placeholder('#admin_site_page_add_short_url');
    _placeholder('#admin_site_page_add_name');
    $('#admin_site_page_add_short_url_f').click(function() {
     $('#admin_site_page_add_short_url').focus();
    });
    $('#task_add_categories').click(function() {
     $('.big_tooltip_wrap').hide();
     big_tooltip._show('tooltip_task_add_cat', 260, 250);
    });
    $('#add_task_url').click(function() {
     $('.big_tooltip_wrap').hide();
     big_tooltip._show('tooltip_task_add_url', 260, 250);
    });
    $('#add_task_url').keydown(function() {
     if(!$(this).val().match(/^http(s)?\:\/\/vk\.com\//) && !$('#add_task_url').val().match(/^vk\.com\//)) {
      var add_task_url = false;
     } else {
      $('.big_tooltip_wrap').hide();
     }
    });
    $('#add_task_comments_value, .add_task_comment').click(function() {
     $('.big_tooltip_wrap').hide();
    });
    $('#add_task_amount').click(function() {
     $('.big_tooltip_wrap').hide();
     big_tooltip._show('tooltip_task_add_amount', 70, 60);
    });
    $('#add_task_count').click(function() {
     $('.big_tooltip_wrap').hide();
     big_tooltip._show('tooltip_task_add_count', 70, 60);
    });
    $('#tooltip_task_add_url, #tooltip_task_add_amount, #tooltip_task_add_count').hover(function() {
     $('.big_tooltip_wrap').fadeOut(300);
    });
    if($('#editor_html').val()) {
     $("#editor").htmlcode($('#editor_html').val());
    }<? if($ugroup == 4 || $ugroup == 5) { ?> 
    if($('#list_edit_que_status').html()) {
     minSelect._new('edit_que_navigation', 150, 'Изменить статус', $('#list_edit_que_status').html());
    }<? } ?> <? if($page_name == 'restore') { ?> 
    radiobtn._new('restore_radiobtn1', {title: 'Восстановить через E-mail'});
    radiobtn._new('restore_radiobtn2', {title: 'Восстановить с помощью страницы ВКонтакте'});
    $('#restore_field_code_o input[type="text"]').click(function() {
     $(this).css('border', '1px solid #C0CAD5');
    });
    $('#restore_field_email, #restore_field_code').keydown(function(event) {
     var keyCode = event.which;
     if(keyCode == 13) $('#restore_next_button').click();
    });<? } ?> 
   });
 
  </script>