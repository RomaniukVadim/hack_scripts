  <div id="black_bg"></div>
  <div id="error_head"></div>
  <div id="loading"><div id="load"></div></div>
  <div id="loading_page"></div><? if(!$uvk_id && $user_logged) { ?> 
  <div id="info_msg_header">
   Чтобы выполнять задания, необходимо <a href="javascript://" onclick="users._add_vk();"><b>привязать страницу ВКонтакте</b></a> к Вашему аккаунту.
   <br />
   <a href="javascript://" onclick="users._add_vk();">Привязать страницу прямо сейчас »</a>
  </div><? } ?> 
  <div id="header">
   <div id="inner">
    <div id="head_loader"><div class="upload"></div></div>
    <div id="logo">
     <a href="/tasks" onclick="nav.go(this); _head._up(); return false">
      <div class="logo"></div>
     </a>
    </div>
    <div class="menu">
     <a href="/top" onclick="nav.go(this); return false"><div>ТОП-100 активных</div></a>
	      <a href="/chat"><div>чат</div></a>
     <a href="/blog" onclick="nav.go(this); return false"><div>новости сайта</div></a>
     <? if($user_logged) { ?><a href="/support/new" onclick="nav.go(this); return false"><div>помощь</div></a><? } ?> 
	    <a href="/game" onclick="nav.go(this); return false"><div>игра</div></a>
     <? if($user_logged || $udel) { ?><a href="/logout?hash=<? echo $user_uhash; ?>" onclick="nav.go(this); return false"><div>выйти</div></a><? } ?> 
    </div>
   </div>
  </div>
  <div id="header_bottom"></div>