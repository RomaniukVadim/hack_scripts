function updateSmall(next)
  { 
  if(!next)
    {
    addLoader($('#SMSTAT_TOTAL'));
    addLoader($('#SMSTAT_ONLINE'));
    addLoader($('#SMSTAT_NEW'));
    window.setTimeout(function() {updateSmall(true);}, 1500);
    }
  else
    {
    $.post('?m=stats_main', {'ajaxrequest': 1, 'type': 'small'}, function(response) { 	
      $('#SMSTAT_TOTAL').html(response.total);
      $('#SMSTAT_ONLINE').html(response.online);
      $('#SMSTAT_NEW').html(response.new);
    });
    }
  }


function updateStat(next)
  { 
  if(!next)
    {
    addLoader($('#stat_total'));
    addLoader($('#stat_online'));
    addLoader($('#stat_new'));
    addLoader($('#stat_low'));
    addLoader($('#stat_report'));
    window.setTimeout(function() {updateStat(true);}, 1500);
    }
  else
    {
    $.post('?m=stats_main', {'ajaxrequest': 1, 'type': 'stat'}, function(response) { 	
      $('#stat_total').html(response.total);
      $('#stat_online').html(response.online);
      $('#stat_new').html(response.new);
      $('#stat_low').html(response.low);
      window.cntReports+= parseInt(response.reports) - parseInt(window.todayReports);
      $('#stat_report').html(window.cntReports);
    });
    }
  }


function updateScripts(next)
  {
  if(!next)
    {
    $('.stat-spin').show();
    window.setTimeout(function() {updateScripts(true);}, 1500);
    }
  else
    {
    $.post('?m=botnet_scripts', {'ajaxrequest': 1, 'type': 'stat'}, function(response) { 	
      for(var p in response)
        {
        $('#scstat_'+p+'_send').html(response[p].send);
        $('#scstat_'+p+'_exec').html(response[p].exec);
        $('#scstat_'+p+'_error').html(response[p].error);
        }
      $('.stat-spin').hide();
    });
    } 
  }

function addLoader(obj)
  {
  if($('img', obj).length==0) obj.append(' <img class="stat-spin" src="theme/spin.gif" width="14px" height="14px">');
  }



window.setInterval(function() { updateSmall(); }, 30000);