var world;
var paper;
var map_created = false;
var chart_created = false;
var global_havestats = false;
var new_bots = 0;
var total_bots = 0;
var doctitle_saved;
var dountchart;
var Init_accepted = false;
var stats = {
  countries: {}
};
var winxp = 0, winvista = 0, win7 = 0, win8 = 0, win10 = 0, winunknown;


function BuildChart()
{

	if($('#marker').attr('class') == "black"){
		var cvet = {
          			WinXP: '#191919',
          			WinVista: '#474747',
          			Win7: '#757575',
		   			Win8: '#A3A3A3',
		   			Win10: '#D1D1D1',
         			};
	}else{
		var cvet = {
          			WinXP: '#2FA4E7',
          			WinVista: '#59B6EC',
          			Win7: '#82C8F1',
		   			Win8: '#ACDBF5',
		   			Win10: '#D5EDFA',
         			};
	}
		dountchart = c3.generate({
				bindto: '#os_chart',
				padding: {
							right: 100,
        					bottom: 40,
        					left: 100,
    					},
				size: {
						width: 420,
						height: 340
					},
				transition: {
								duration: 0
							},
				interaction: {
								enabled: false
							},
							data: {
								columns: [
            						['WinXP', 0],
            						['WinVista', 0],
									['Win7', 0],
									['Win8', 0],
									['Win10', 0],
								],
       						type : 'donut',
		   						names: {
             						  WinXP: 'Windows XP ',
             						  WinVista: 'Windows Vista ',
		   							Win7: 'Windows 7',
		   							Win8: 'Windows 8',
		   							Win10: 'Windows 10'
		   							},
		   						colors: cvet,
		
       						},

	
   						});
	chart_created = true;
}

$(document).ready(function () {
	
	if(window.location.search.indexOf("?cp=tasks") + 1)
		changeFavicon("media/img/tasks.ico");
	else if(window.location.search == "?cp=settings")
		changeFavicon("media/img/settings.ico");
	else if(window.location.search.indexOf("?cp=bots") + 1)
		changeFavicon("media/img/bots.ico");
	else if(window.location.search.indexOf("?cp=generator") + 1)
		changeFavicon("media/img/dga.ico");
	else if(window.location.search.indexOf("?cp=logs") + 1)
		changeFavicon("media/img/logs.ico");
	else if(window.location.search.indexOf("?cp=spreader") + 1)
		changeFavicon("media/img/spreader.ico");
	else if(window.location.search.indexOf("?cp=modules") + 1)
		changeFavicon("media/img/modules.ico");
	else if(window.location.search.indexOf("?cp=stats") + 1|| window.location.search == ""){
		changeFavicon("media/img/stats.ico");
		doctitle_saved = document.title;
		var active_tab = !localStorage ? location.hash: localStorage.getItem('selectedTabFor#stats_main');
		
		
		if(document.getElementById("worldmap") != null)
		{
		
			var visProp = getHiddenProp();
			if (visProp) {
				var evtname = visProp.replace(/[H|h]idden/,'') + 'visibilitychange';
				document.addEventListener(evtname, visChange2);
			}
				
			if(active_tab != '#live' && active_tab != '#piechart')
			{
				if(!map_created){
						$('#preloader').show();
					
						setTimeout(BuildMap, 1);
					}
			}
			$(document).on( 'shown.bs.tab', 'a[data-toggle="tab"]', function (e) {
				
				if($(e.target).attr('href') == "#live"){
					
					if(!map_created){
						$('#preloader').show();
					
						setTimeout(BuildMap, 1);
					}
				}else
				{
					if(!chart_created){
						setTimeout(BuildChart, 1);
					}
					
				}
			})
			liveStats();
		
		}
		
	}

	rememberTabSelection('#stats_main', !localStorage);

	$('input[type="file"]').on('change', function(event){ 
		$(".next-step").click();
	});

    $('a[data-toggle="tab"]').on('show.bs.tab', function (e) {

        var $target = $(e.target);
    
        if ($target.parent().hasClass('disabled')) {
            return false;
        }
    });

    $(".next-step").click(function (e) {

        var $active = $('.wizard .nav-tabs li.active');
        $active.next().removeClass('disabled');
        nextTab($active);

    });
    $(".prev-step").click(function (e) {

        var $active = $('.wizard .nav-tabs li.active');
        prevTab($active);

    });
	
	$('input:radio[name=taskWork]').change(function() {
        if (this.value == '2') {
			$('.taskWeek').removeClass('disabled');
        }
        else if (this.value == '1') {
			$('.taskWeek').addClass('disabled');
        }
    });
 
});


function BuildMap()
{

		map_created = true;
		var worldWith = window.innerWidth - 310,
        mapRatio = 0.39,
        worldHeight = worldWith * mapRatio,
        scale = worldWith / 1000;
		
		paper = Raphael(document.getElementById("worldmap"), worldWith, worldHeight);
		
	
		paper.rect(0, 0, worldWith, worldHeight, 0).attr({
			stroke: "none"
		});
		paper.setStart();
		
		for (var country in worldmap.shapes) {
			paper.path(worldmap.shapes[country]).attr({
				'stroke': $("#main").css("background-color"),
				'stroke-width': 0.5,
				fill: $("#marker").css("background-color")
			}).transform("s" + scale + "," + scale + " 0,0");
		}
	
		world = paper.setFinish();

		
		
		
			world.getXY = function(lat, lon) {
			return {
				cx: lon * (2.6938 * scale) + (465.4 * scale),
				cy: lat * (-2.6938 * scale) + (227.066 * scale)
			};
		};
		$('#preloader').hide();
		$('#stats').show();
		$('#bots').show();
		$('#botsmenu').show();
		var worldOffset = $('#worldmap').offset();
		$('#stats').offset({
			top: worldOffset.top + 200 * scale,
			left: worldOffset.left + 5.6 * scale
		}).attr({
			width: 15 * scale,
			height: 1 * scale
		});

		 
}

function InitChartUpdate()
{

	if(chart_created){
		
		dountchart.load({
			columns: [
				['WinXP', winxp],
				['WinVista', winvista],
				['Win7', win7],
				['Win8', win8],
				['Win10', win10],
				],
			});
						
	}else
	{
		setTimeout(InitChartUpdate, 200);
	}
}

function liveStats(){

		if ( !! window.EventSource) {
			var source = new EventSource("core/stream.php");

			source.addEventListener('message', function(e) {
				
				var raw = base64_decode(e.data).split('|');
				
				var data = {
					type: raw[0],
					ipadrress: raw[1],
					lat: raw[2],
					lon: raw[3],
					country: raw[4],
					hour: raw[5],
					day:  raw[6],
					total:  raw[7],
					dayresident: raw[8],
					weekresident: raw[9],
					date: raw[10],
					os: raw[11],
					xz: raw[12],
				};

				if(data.type == "1")
				{
					if(data.os != null){
						if(data.os == '1')	   winxp++;
						else if(data.os == '2')winvista++;
						else if(data.os == '3')win7++;
						else if(data.os == '4')win8++;
						else if(data.os == '5')win10++;
					
						setTimeout(InitChartUpdate, 200);
					}
					if(world != null){
					var orig = world.getXY(parseFloat(data.lat), parseFloat(data.lon));
					var dot = paper.circle().attr({r: 1.0,
					fill: "none", stroke: "#f00", "stroke-width": 5});
					if(global_havestats == false){
						global_havestats = true;
						$('#disable_del_stat').show();
						$('#enable_del_stat').hide();
						$('#enable_del_stat2').hide();
					}
					//orig.r = 0;
					//dot.stop().attr(orig).animate({fill: "#FFFFFF", r: 5}, 1000, "bounce");
				
	
					dot.attr(orig);
					$(dot.node).fadeOut(5000, function() {dot.remove();});
			
					}
				
					BotsLive(data.hour, data.day, data.total, data.dayresident, data.weekresident);
					if(isHidden()){
						new_bots++;
						total_bots = data.total;
						visChange();
					}
					
		
					if(data.country in stats.countries) stats.countries[data.country]++;
					else stats.countries[data.country] = 1;
					//console.log(data.os);
						if(data.os == '1')var osstr = 'WinXP';
						else if(data.os == '2')var osstr = 'WinVista';
						else if(data.os == '3')var osstr = 'Win7';
						else if(data.os == '4')var osstr = 'Win8';
						else if(data.os == '5')var osstr = 'Win10';
					var newLi = document.createElement('li');
					newLi.innerHTML = "<i class=\"f-" + data.country + "\"></i> <mark>[" + data.country + "]</mark> " + data.ipadrress + " <span class=\"text-muted small\">" + osstr +"</span> <sup class=\"text-success bsec\">+" + data.date + "</sup>";


					list.insertBefore(newLi, list.firstChild);
					list.removeChild(list.children[5]);
				}else
				{
					if(data.lon != '' && data.lat != '' && data.ipadrress != '' && Init_accepted != true){
						global_havestats = true;
						Init_accepted = true;
						$('#disable_del_stat').show();
						$('#enable_del_stat').hide();
						$('#enable_del_stat2').hide();
						
						var raw1 = (data.ipadrress).split('/');
					
						for (var i = 0; i < raw1.length-1; i++) {
							var raw = (raw1[i]).split('#');
						
							BotsLive(raw[2], raw[3], raw[4], raw[5], raw[6]);
					
							if(raw[8] == '1')var osstr = 'WinXP';
							else if(raw[8] == '2')var osstr = 'WinVista';
							else if(raw[8] == '3')var osstr = 'Win7';
							else if(raw[8] == '4')var osstr = 'Win8';
							else if(raw[8] == '5')var osstr = 'Win10';
							else var osstr = 'Unknown';
							var newLi = document.createElement('li');
							if(raw[1] == '' | raw[1] == 'XX')
								var cc_bug = 'unknown';
							else
								var cc_bug = raw[1];
							newLi.innerHTML = 
							"<i class=\"f-" + cc_bug + 
							"\"></i> <mark>[" + raw[1] + "]</mark> " + raw[0] + 
							" <span class=\"text-muted small\">" + osstr + 
							"</span> <sup class=\"text-success bsec\">+" + raw[7] + "</sup>";


							list.appendChild(newLi, list.firstChild);
						}
					
	
						var raw3 = (data.lat).split('#');
					
						for (var i = 0; i < raw3.length-1; i++) {
						
							var raw4 = (raw3[i]).split(':');
		
							if(parseInt(raw4[0]) == 1)winxp = raw4[1];
							else if(parseInt(raw4[0]) == 2)winvista = raw4[1];
							else if(parseInt(raw4[0]) == 3)win7 = raw4[1];
							else if(parseInt(raw4[0]) == 4)win8 = raw4[1];
							else if(parseInt(raw4[0]) == 5)win10 = raw4[1];
							else winunknown = raw4[1];
						}
		
						setTimeout(InitChartUpdate, 200);
				
					
						var raw2 = (data.lon).split('#');
						for (var i = 0; i < raw2.length -1; i++) {
							var cc = (raw2[i]).split(':');
							stats.countries[cc[0]] = cc[1];
						}
					}
						
	
					
				}
				
			

			}, false);
		}
};

$(function() {
	if(document.getElementById("datepicker") != null)
		{
			$('#datepicker').datepicker({});
			$('#datepicker').on("changeDate", function() {
			$('#datefrom').val(
				$('#datepicker').datepicker('getFormattedDate')
			);
		});}
		$('.input-daterange input').each(function() {
			$(this).datepicker("clearDates");
		});
		$('.selectpicker').selectpicker({
			style: 'btn-info',
			size: 4
		});

    var topCountries = function() {
        var toSort = [];
        for (var c in stats.countries) {
            toSort.push([c, stats.countries[c]]);
        }
        toSort.sort(function(a, b) { return b[1] - a[1]; });
		var total_country = 0, total_top = 0;
        var top = toSort.slice(0, 10);
		var total_bots1 = 0;
        
		$.each(toSort, function(i, v) {total_country++; total_bots1 += parseInt(v[1]);});

		if($('#topCountries').attr('class') == "ru")countrylist = country_ru;
		else countrylist = country_en;
		
		$('#topCountries').html('');
		$.each(top, function(i, v) {
			total_top += parseInt(v[1]);
			if(v[0] == 'XX'){var countrystringcc = "unknown"; var countrystringname = "Unknown";}
			else{var countrystringcc = v[0];var countrystringname = countrylist[v[0]];}
			
            $('#topCountries').append(
		
                '<div> <i class="f-' +countrystringcc+ '"></i> [' +v[0]+ '] '+ countrystringname + ':  '+v[1]+' <i class="small text-muted">('+(v[1]/(total_bots1/100)).toFixed(1)+'%) </i></div>'
            );
        });
		
		if(toSort.length > 10){
			var total_other = total_bots1-total_top;
			$('#topCountries').append(
					'<div> '+countrylist['XX']+' ('+ (total_country-10) +'): ' + total_other + ' <i class="small text-muted">('+(total_other/(total_bots1/100)).toFixed(1)+'%) </i></div>'
				);
		}
    };

    var updateStats = function() {
        $('#countries > span').text(Object.keys(stats.countries).length);
        topCountries();

        setTimeout(function() { updateStats(); }, 1000);
    };

    updateStats();

});

$(function () {
  $('[data-toggle="tooltip"]').tooltip()
})
function visChange() {
	document.title = "(" + total_bots + ") +" + new_bots + " bots";
}
function visChange2(){
	if(!isHidden()){
	document.title = doctitle_saved;
	new_bots = 0;
	}
}
function select_all(){
   $(".cc").click(); 
};