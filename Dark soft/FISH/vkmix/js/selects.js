/*!

 * selects.js

*/

var select = {
 _new: function(id, obj, list) {
  var active_select = list[list.length - 1];
  template_list = '';
  
  if(active_select > -1) {
   var length_list = list.length - 1;
   var title_select = list[0][1];
   var value_select = active_select;
  } else {
   var length_list = list.length;
   var title_select = list[0][1];
   var value_select = 0;
  }
  
  var length_list_r = obj.del_end ? length_list - 1 : length_list;
  
  for(i = 0; i < length_list_r; i++) {
   var key = list[i][0];
   var value = list[i][1];
   var mousedown = list[i][2] ? list[i][2] : '';
   template_list += '\
    <a class="select_value_'+key+'" href="javascript://" onmousedown="'+mousedown+'"><div>'+value+'</div></a>\
   ';
  }
  
  template_select = '\
   <div id="select_'+id+'" class="select" style="width: '+obj.width+'px;">\
    <div class="title">\
     <div class="select_title_wrap"><div class="select_title">'+title_select+'</div></div>\
     <div class="upnarrow"><div class="upnarrow_wrap"><div class="upnarrow_img"></div></div></div>\
    </div>\
    <div style="width: '+obj.width+'px" class="list">\
     <div class="navigate">'+template_list+' <span class="navigate_append"></span></div>\
    </div>\
   </div>\
   <input value="'+value_select+'" type="hidden" id="select_value_'+id+'">\
   <input type="hidden" id="select_value_h_'+id+'">\
   \
  ';
  $('#'+id).html(template_select);
  // присваиваем title
  var title_value_text = $('#select_'+id).find('.select_value_'+active_select).text();
  select._set_title({id: id, title: title_value_text ? title_value_text : title_select});
  $('#select_'+id).find('.title').click(function() {
   select._get(id, 'opened') ? select._closed(id) : select._show(id);
   value_id = select._get(id, 'value');
   if(value_id && $('#select_'+id).find('.select_value_'+value_id+' div').text()) {
    $('#select_'+id).find('.select_value_'+value_id+' div').addClass('active')
   } else {
    $('#select_'+id).find('.select_value_0 div').addClass('active')
   }
  });
  $('#select_'+id).find('.navigate a').click(function() {
   // получаем значение активированного пункта
   var value_select = $(this).attr('class').replace('select_value_', '');
   var value_select_result = value_select;
   // заносим значение в input
   $('#select_value_'+id).val(value_select_result);
   // присваиваем class активному пункту
   $('#select_'+id).find('.navigate a').find('div').removeClass('active');
   $(this).find('div').addClass('active');
   // назначаем title
   $('#select_'+id).find('.title .select_title').text($(this).text());
   // закрываем список
   select._closed(id);
  });
  // сбрасываем активность пунктов при наведении
  $('#select_'+id).find('.navigate a').hover(function() {$('#select_'+id).find('div').removeClass('active');});
  // проверка на использование
  $('#select_'+id).hover(function() {$('#select_value_h_'+id).val(1)}, function() {$('#select_value_h_'+id).val(0);});
  // закрываем, если не используется
  $('body').bind('click', function() {
   if($('#select_value_h_'+id).val() == 0) {
    select._closed(id);
   }
  });
  // делаем активной правую часть со стрелкой
  $('#select_'+id).find('.title').hover(function() {
   var active_class = 'active';
   $(this).find('.upnarrow_wrap').addClass(active_class);
  }, function() {
   var active_class = 'active';
   $(this).find('.upnarrow_wrap').removeClass(active_class);  
  }); 
 },
 _get: function(id, name) {
  switch(name) {
   case 'opened':
    return $('#select_'+id).attr('class').indexOf('show') > - 1 ? 1 : 0;
   case 'value':
    return $('#select_value_'+id).val();
  }
 },
 _set_title: function(obj) {
   $('#select_value_'+obj.id).val(obj.value);
   $('#select_'+obj.id).find('.title .select_title').text(obj.title);
 },
 _show: function(id) {
  $('#select_'+id).addClass('show');
 },
 _closed: function(id) {
  $('#select_'+id).removeClass('show');
 }
}

var minSelect = {
 _new: function(id, width, title, text) {
  var template = '\
  <div class="minselect_wrap">\
   <a id="aminselect_'+id+'" href="javascript://">'+title+'</a>\
   <div style="width: '+width+'px" id="minselect_'+id+'" class="minselect">\
    <div class="minselect_title_wrap"><div class="minselect_title">'+title+'</div></div>\
    <div class="minselect_content">'+text+'</div>\
   </div>\
  </div>\
  ';
  $('#'+id).html(template);
  $('#aminselect_'+id+', #minselect_'+id).click(function() {
   $('#minselect_'+id).toggle();
  });
  $('#minselect_'+id).hover(function() {}, function(){
   $('#minselect_'+id).fadeOut(200);
  });
  $('#minselect_'+id).find('.mnav').click(function() {
   setTimeout(function(){
    $('#minselect_'+id).hide();
   }, 10);
  });
 }
}

var radiobtn = {
 _new: function(id, obj) {
  var template = '\
   <div class="radiobtn_o">\
    <div class="radiobtn radiobtn_no"></div>\
    <div class="radiobtn_t">'+obj.title+'</div>\
   </div>\
  ';
  $('#'+id).html(template);
  $('.radiobtn_o').click(function() {
   $('.radiobtn_o').find('.radiobtn').removeClass('radiobtn_active').addClass('radiobtn_no');
   $(this).find('.radiobtn').removeClass('radiobtn_no').addClass('radiobtn_active');
  });
 },
 _check: function() {
 
 }
}