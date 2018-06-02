<?php
$tasks_blacklist_new = ($ugroup != 4 && $ugroup != 3) ? '' : $tasks_blacklist->_new();
$support_new = ($ugroup == 4 || $ugroup == 5) ? my_support_new(1) : my_support_new();
?>
    <div id="left">
     <? if($udel) { ?> 
     <div id="account_is_deleted_left">
      Аккаунт удален.
      <br />
      <a href="javascript://" onclick="users.delete_account();">Восстановить.</a>
     </div>
     <? } elseif($user_logged) { ?>
     
     <div id="menu_left">
      <a<? if($page_name == 'add_task') echo ' class="active"'; ?> href="/tasks/add" onclick="nav.go(this); return false;">
       <div class="midiv_new"><div class="menu_icon_overflow"><div class="menu_left_micon_left"><div class="<? if($page_name == 'add_task') echo 'menu_left_icons_img_active'; else echo 'menu_left_icons_img'; ?> menu_left_icons_img_new_task"></div></div><div class="menu_left_micon_right">Новое задание</div></div></div>
      </a>
	    <div class="hr"></div>
      <a<? if($page_name == 'tasks') echo ' class="active"'; ?> href="/tasks" onclick="nav.go(this); return false;">
       <div class="midiv_new"><div class="menu_icon_overflow"><div class="menu_left_micon_left"><div class="<? if($page_name == 'tasks') echo 'menu_left_icons_img_active'; else echo 'menu_left_icons_img'; ?> menu_left_icons_img_all_task"></div></div><div class="menu_left_micon_right">Все задания</div></div></div>
      </a>
      <a<? if($page_name == 'my_tasks') echo ' class="active"'; ?> href="/tasks/my" onclick="nav.go(this); return false;">
       <div class="midiv_new"><div class="menu_icon_overflow"><div class="menu_left_micon_left"><div class="<? if($page_name == 'my_tasks') echo 'menu_left_icons_img_active'; else echo 'menu_left_icons_img'; ?> menu_left_icons_img_my_tasks"></div></div><div class="menu_left_micon_right">Мои задания</div></div></div>
      </a>
    
      <a<? if($page_name == 'my_complaints') echo ' class="active"'; ?> href="/complaints" onclick="nav.go(this, '', function() {setTimeout(function() {$('#mleft_complaints').html('')}, 10)}); return false;">
       <div class="midiv_new"><div class="menu_icon_overflow"><div class="menu_left_micon_left"><div class="<? if($page_name == 'my_complaints') echo 'menu_left_icons_img_active'; else echo 'menu_left_icons_img'; ?> menu_left_icons_img_complaints"></div></div><div class="menu_left_micon_right">Мои штрафы  <span id="mleft_complaints"><? if($unew_complaints) { ?>(<b><? echo $unew_complaints; ?></b>)<? } ?></span></div></div></div>
      </a>
      <a<? if($page_name == 'blacklist_task') echo ' class="active"'; ?> href="<? if($blacklist_notif) echo '/tasks/blacklist?list=1'; else echo '/tasks/blacklist'; ?>" onclick="nav.go(this, '', function() {setTimeout(function() {$('#mleft_blacklist').html('')}, 10)}); return false;">
       <div class="midiv_new"><div class="menu_icon_overflow"><div class="menu_left_micon_left"><div class="<? if($page_name == 'blacklist_task') echo 'menu_left_icons_img_active'; else echo 'menu_left_icons_img'; ?> menu_left_icons_img_blacklist"></div></div><div class="menu_left_micon_right">Мои жалобы <span id="mleft_blacklist"><? if($blacklist_notif) echo '(<b>'.$blacklist_notif.'</b>)'; ?></span></div></div></div>
      </a>
      <a<? if($page_name == 'my_stats') echo ' class="active"'; ?> href="/stats?id=<? echo $user_id; ?>" onclick="nav.go(this); return false;">
       <div class="midiv_new"><div class="menu_icon_overflow"><div class="menu_left_micon_left"><div class="<? if($page_name == 'my_stats') echo 'menu_left_icons_img_active'; else echo 'menu_left_icons_img'; ?> menu_left_icons_img_stats"></div></div><div class="menu_left_micon_right">Моя статистика</div></div></div>
      </a>
      <a<? if($page_name == 'my_settings' && !$_GET['menu']) echo ' class="active"'; ?> href="/settings" onclick="nav.go(this); return false">
       <div class="midiv_new"><div class="menu_icon_overflow"><div class="menu_left_micon_left"><div class="<? if($page_name == 'my_settings' && !$_GET['menu']) echo 'menu_left_icons_img_active'; else echo 'menu_left_icons_img'; ?> menu_left_icons_img_settings"></div></div><div class="menu_left_micon_right">Мои настройки</div></div></div>
      </a>
	  <a<? if($page_name == 'cupons') echo ' class="active"'; ?> href="/cupons/" onclick="nav.go(this); return false;">
       <div class="midiv_new"><div class="menu_icon_overflow"><div class="menu_left_micon_left"><div class="<? if($page_name == 'cupons') echo 'menu_left_icons_img_active'; else echo 'menu_left_icons_img'; ?> menu_left_icons_img_cup"></div></div><div class="menu_left_micon_right">Мои купоны</div></div></div>
      </a>
      <div class="big_tooltip_wrap_border" id="support_start_message_w">
       <div class="big_tooltip_wrap" id="support_start_message">
        <div class="big_tooltip_narrow"><div class="big_tooltip_narrow_c"></div></div>
        <div class="big_tooltip">
         <div class="big_tooltip_head">
          <div class="big_tooltip_head_title">Новое сообщение</div>
          <div class="big_tooltip_head_closed"><div onclick="$('#support_start_message_w').hide()" class="icons_tab icons_tab_del1"></div></div>
         </div>
         <div class="big_tooltip_message">Агент поддержки написал Вам новое личное <b>сообщение</b>.</div>
         <div align="center"><div class="notifier_agent_start"></div></div>
        </div>
       </div>
      </div>
      <div class="hr"></div>
	    <a<? if($page_name == 'support') echo ' class="active"'; ?> href="<? if($ugroup == 4 || $ugroup == 5) { ?>/support/questions<? } elseif($support_new) { ?>/support<? } else { ?>/support/new<? } ?>" onclick="nav.go(this, '', function() {setTimeout(function() {$('#support_start_message_w').hide();}, 10);}); return false">
       <div class="midiv_new"><div class="menu_icon_overflow"><div class="menu_left_micon_left"><div class="<? if($page_name == 'support') echo 'menu_left_icons_img_active'; else echo 'menu_left_icons_img'; ?> menu_left_icons_img_que"></div></div><div class="menu_left_micon_right">Помощь <? if($support_new) echo '(<b>'.$support_new.'</b>)'; ?></div></div></div>
      </a>
	  <div class="hr"></div>
      <a<? if($page_name == 'my_settings' && $_GET['menu'] == 1) echo ' class="active"'; ?> href="/settings/ref?menu=1" onclick="nav.go(this); return false">
       <div class="midiv_new<? if($page_name == 'my_settings' && $_GET['menu'] == 1) echo ''; else echo ' midiv_new_friends'; ?>"><div class="menu_icon_overflow"><div class="menu_left_micon_left"><div class="<? if($page_name == 'my_settings' && $_GET['menu'] == 1) echo 'menu_left_icons_img_active'; else echo 'menu_left_icons_img'; ?> menu_left_icons_img_ref"></div></div><div class="menu_left_micon_right">Пригласить друга<? if($page_name == 'my_settings' && $_GET['menu'] == 1) echo ''; else echo '<div id="menu_left_get_friends">и получить <b style="font-size: 10px;">15 баллов</b></div>'; ?></div></div></div>
      </a>
    
     
     <? if($ugroup == 4 || $ugroup == 3) { ?> 
      <a<? if($page_name == 'admin.tasks.blacklist_add') echo ' class="active"'; ?> href="<? if($tasks_blacklist_new) echo '/admin/modules/tasks/blacklist?section=new'; else echo '/admin/modules/tasks/blacklist'; ?>" onclick="nav.go(this); return false">
       <div class="midiv_new"><div class="menu_icon_overflow"><div class="menu_left_micon_left"><div class="<? if($page_name == 'admin.tasks.blacklist_add') echo 'menu_left_icons_img_active'; else echo 'menu_left_icons_img'; ?> menu_left_icons_img_ablacklist"></div></div><div class="menu_left_micon_right">Черный список <? if($tasks_blacklist_new) echo '(<b>'.$tasks_blacklist_new.'</b>)'; ?></div></div></div>
      </a><? } ?> <? if($ugroup == 4) { ?> 
      <a<? if($page_name == 'admin.users') echo ' class="active"'; ?> href="/admin/modules/users/" onclick="nav.go(this); return false">
       <div class="midiv_new"><div class="menu_icon_overflow"><div class="menu_left_micon_left"><div class="<? if($page_name == 'admin.users') echo 'menu_left_icons_img_active'; else echo 'menu_left_icons_img'; ?> menu_left_icons_img_ausers"></div></div><div class="menu_left_micon_right">Пользователи</div></div></div>
      </a><? } ?> <? if($ugroup == 4) { ?> 
      <a<? if($page_name == 'site_page_add' || $page_name == 'site_page_all') echo ' class="active"'; ?> href="/admin/modules/pages/" onclick="nav.go(this); return false">
       <div class="midiv_new"><div class="menu_icon_overflow"><div class="menu_left_micon_left"><div class="<? if($page_name == 'site_page_add' || $page_name == 'site_page_all') echo 'menu_left_icons_img_active'; else echo 'menu_left_icons_img'; ?> menu_left_icons_img_apages"></div></div><div class="menu_left_micon_right">Страницы</div></div></div>
      </a><? } ?> <? if($ugroup == 4) { ?> 
      <a<? if($page_name == 'admin_see_stats') echo ' class="active"'; ?> href="/admin/modules/stats/" onclick="nav.go(this); return false">
       <div class="midiv_new"><div class="menu_icon_overflow"><div class="menu_left_micon_left"><div class="<? if($page_name == 'admin_see_stats') echo 'menu_left_icons_img_active'; else echo 'menu_left_icons_img'; ?> menu_left_icons_img_astats"></div></div><div class="menu_left_micon_right">Статистика</div></div></div>
      </a><? } ?>
     </div>
     <div id="balance_left_block">
      <a href="/settings/balance" onclick="nav.go(this); return false" class="balance_menu_left">У Вас <b class="balance_menu_left_num"><? echo $upoints; ?></b> <span class="balance_menu_left_num_desc"><? echo declOfNum(abs($upoints), array('балл', 'балла', 'баллов')); ?></span></a> 
      <br />
      <a href="javascript://" onclick="pay._show_type();">Пополнить баланс ⇒</a><br>
     </div>
     
     <? if($redis->hget('blog_menu_left', 'id')) { ?><div id="menu_new_left">
      <div id="menu_new_left_title">Новости</div>
      <div id="menu_new_left_text">
       <? echo stripslashes(fxss($redis->hget('blog_menu_left', 'title'))); ?> 
       <br />
       <a href="/blog?id=<? echo $redis->hget('blog_menu_left', 'id'); ?>" onclick="nav.go(this); return false">подробнее »</a>
      </div>
     </div><? } ?> 
     <? } else { ?>
     
     <div class="login_field">
      <div class="label">Логин:</div>
      <div class="field"><input id="ulogin" type="text"></div>
     </div>
     <div class="login_field top">
      <div class="label">Пароль:</div>
      <div class="field"><input id="upassword" type="password"></div>
     </div>
     <div id="login_button_control">
      <div onclick="users._post_login()" id="login_in" class="blue_button_wrap"><div class="blue_button">Войти в систему</div></div>
      <div id="login_rpswd">
       <a href="/restore" onclick="nav.go(this); return false">Забыли пароль?</a>
      </div>
     </div>
     <? } ?>
     
    </div>