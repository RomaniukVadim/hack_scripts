// Чат
$(document).ready(function(){
	function smile_insert_init(){
		chat_textarea = $('#chat_question_add_comment_field_text');
		$('[data-emoji]').unbind('click');
		$('[data-emoji]').bind('click', function(){
			emoji = $(this).data('emoji');
			if(chat_textarea.hasClass('placeholder')) chat_textarea.html('');
			console.log(emoji);
			emoji_symbol = findSurrogatePair('0x'+emoji);
			console.log(emoji_symbol);
			//if(emoji_symbol[0] == 'd7c9') emoji_symbol[0] = 'd83d';
			emoji_symbol = String.fromCharCode(parseInt(emoji_symbol[0],16), parseInt(emoji_symbol[1],16));
			chat_textarea.append('<img src="/images/blank.gif" class="emoji emoji'+emoji+'" emoji="'+emoji_symbol+'" />');
			chat_textarea.removeClass('placeholder');
		});
	}
	smile_insert_init();

	$('.chat_smile_button').click(function(){
		if($('.emoji-menu').hasClass('active')){
			$('.emoji-menu').hide();
			$('.emoji-menu').removeClass('active');
		} else {
			position = $('.chat_smile_button').position();
			$('.emoji-menu').css('top', position.top+20).css('left', position.left);
			$('.emoji-menu').show();
			$('.emoji-menu').addClass('active');
		}
	});

	allowed_emoji = ["2600","2601","2614","26c4","26a1","1f300","1f301","1f302","1f303","1f304","1f305","1f306","1f307","1f308","2744","26c5","1f309","1f30a","1f30b","1f30c","1f30f","1f311","1f314","1f313","1f319","1f315","1f31b","1f31f","1f320","1f550","1f551","1f552","1f553","1f554","1f555","1f556","1f557","1f558","1f559","1f55a","1f55b","231a","231b","23f0","23f3","2648","2649","264a","264b","264c","264d","264e","264f","2650","2651","2652","2653","26ce","1f340","1f337","1f331","1f341","1f338","1f339","1f342","1f343","1f33a","1f33b","1f334","1f335","1f33e","1f33d","1f344","1f330","1f33c","1f33f","1f352","1f34c","1f34e","1f34a","1f353","1f349","1f345","1f346","1f348","1f34d","1f347","1f351","1f34f","1f440","1f442","1f443","1f444","1f445","1f484","1f485","1f486","1f487","1f488","1f464","1f466","1f467","1f468","1f469","1f46a","1f46b","1f46e","1f46f","1f470","1f471","1f472","1f473","1f474","1f475","1f476","1f477","1f478","1f479","1f47a","1f47b","1f47c","1f47d","1f47e","1f47f","1f480","1f481","1f482","1f483","1f40c","1f40d","1f40e","1f414","1f417","1f42b","1f418","1f428","1f412","1f411","1f419","1f41a","1f41b","1f41c","1f41d","1f41e","1f420","1f421","1f422","1f424","1f425","1f426","1f423","1f427","1f429","1f41f","1f42c","1f42d","1f42f","1f431","1f433","1f434","1f435","1f436","1f437","1f43b","1f439","1f43a","1f42e","1f430","1f438","1f43e","1f432","1f43c","1f43d","1f620","1f629","1f632","1f61e","1f635","1f630","1f612","1f60d","1f624","1f61c","1f61d","1f60b","1f618","1f61a","1f637","1f633","1f603","1f605","1f606","1f601","1f602","1f60a","263a","1f604","1f622","1f62d","1f628","1f623","1f621","1f60c","1f616","1f614","1f631","1f62a","1f60f","1f613","1f625","1f62b","1f609","1f63a","1f638","1f639","1f63d","1f63b","1f63f","1f63e","1f63c","1f640","1f645","1f646","1f647","1f648","1f64a","1f649","1f64b","1f64c","1f64d","1f64e","1f64f","1f3e0","1f3e1","1f3e2","1f3e3","1f3e5","1f3e6","1f3e7","1f3e8","1f3e9","1f3ea","1f3eb","26ea","26f2","1f3ec","1f3ef","1f3f0","1f3ed","2693","1f3ee","1f5fb","1f5fc","1f5fd","1f5fe","1f5ff","1f45e","1f45f","1f460","1f461","1f462","1f463","1f453","1f455","1f456","1f451","1f454","1f452","1f457","1f458","1f459","1f45a","1f45b","1f45c","1f45d","1f4b0","1f4b1","1f4b9","1f4b2","1f4b3","1f4b4","1f4b5","1f4b8","1f1e81f1f3","1f1e91f1ea","1f1ea1f1f8","1f1eb1f1f7","1f1ec1f1e7","1f1ee1f1f9","1f1ef1f1f5","1f1f01f1f7","1f1f71f1fa","1f1fa1f1f8","1f525","1f526","1f527","1f528","1f529","1f52a","1f52b","1f52e","1f52f","1f530","1f531","1f489","1f48a","1f170","1f171","1f18e","1f17e","1f380","1f381","1f382","1f384","1f385","1f38c","1f386","1f388","1f389","1f38d","1f38e","1f393","1f392","1f38f","1f387","1f390","1f383","1f38a","1f38b","1f391","1f4df","260e","1f4de","1f4f1","1f4f2","1f4dd","1f4e0","2709","1f4e8","1f4e9","1f4ea","1f4eb","1f4ee","1f4f0","1f4e2","1f4e3","1f4e1","1f4e4","1f4e5","1f4e6","1f4e7","1f520","1f521","1f522","1f523","1f524","2712","1f4ba","1f4bb","270f","1f4ce","1f4bc","1f4bd","1f4be","1f4bf","1f4c0","2702","1f4cd","1f4c3","1f4c4","1f4c5","1f4c1","1f4c2","1f4d3","1f4d6","1f4d4","1f4d5","1f4d7","1f4d8","1f4d9","1f4da","1f4db","1f4dc","1f4cb","1f4c6","1f4ca","1f4c8","1f4c9","1f4c7","1f4cc","1f4d2","1f4cf","1f4d0","1f4d1","1f3bd","26be","26f3","1f3be","26bd","1f3bf","1f3c0","1f3c1","1f3c2","1f3c3","1f3c4","1f3c6","1f3c8","1f3ca","1f683","1f687","24c2","1f684","1f685","1f697","1f699","1f68c","1f68f","1f6a2","2708","26f5","1f689","1f680","1f6a4","1f695","1f69a","1f692","1f691","1f693","26fd","1f17f","1f6a5","1f6a7","1f6a8","2668","26fa","1f3a0","1f3a1","1f3a2","1f3a3","1f3a4","1f3a5","1f3a6","1f3a7","1f3a8","1f3a9","1f3aa","1f3ab","1f3ac","1f3ad","1f3ae","1f004","1f3af","1f3b0","1f3b1","1f3b2","1f3b3","1f3b4","1f0cf","1f3b5","1f3b6","1f3b7","1f3b8","1f3b9","1f3ba","1f3bb","1f3bc","303d","1f4f7","1f4f9","1f4fa","1f4fb","1f4fc","1f48b","1f48c","1f48d","1f48e","1f48f","1f490","1f491","1f492","1f51e","a9","ae","2122","2139","2320e3","3120e3","3220e3","3320e3","3420e3","3520e3","3620e3","3720e3","3820e3","3920e3","3020e3","1f51f","1f4f6","1f4f3","1f4f4","1f354","1f359","1f370","1f35c","1f35e","1f373","1f366","1f35f","1f361","1f358","1f35a","1f35d","1f35b","1f362","1f363","1f371","1f372","1f367","1f356","1f365","1f360","1f355","1f357","1f368","1f369","1f36a","1f36b","1f36c","1f36d","1f36e","1f36f","1f364","1f374","2615","1f378","1f37a","1f375","1f376","1f377","1f37b","1f379","2197","2198","2196","2199","2934","2935","2194","2195","2b06","2b07","27a1","2b05","25b6","25c0","23e9","23ea","23eb","23ec","1f53a","1f53b","1f53c","1f53d","2b55","274c","274e","2757","2049","203c","2753","2754","2755","3030","27b0","27bf","2764","1f493","1f494","1f495","1f496","1f497","1f498","1f499","1f49a","1f49b","1f49c","1f49d","1f49e","1f49f","2665","2660","2666","2663","1f6ac","1f6ad","267f","1f6a9","26a0","26d4","267b","1f6b2","1f6b6","1f6b9","1f6ba","1f6c0","1f6bb","1f6bd","1f6be","1f6bc","1f6aa","1f6ab","2714","1f191","1f192","1f193","1f194","1f195","1f196","1f197","1f198","1f199","1f19a","1f201","1f202","1f232","1f233","1f234","1f235","1f236","1f21a","1f237","1f238","1f239","1f22f","1f23a","3299","3297","1f250","1f251","2795","2796","2716","2797","1f4a0","1f4a1","1f4a2","1f4a3","1f4a4","1f4a5","1f4a6","1f4a7","1f4a8","1f4a9","1f4aa","1f4ab","1f4ac","2728","2734","2733","26aa","26ab","1f534","1f535","1f532","1f533","2b50","2b1c","2b1b","25ab","25aa","25fd","25fe","25fb","25fc","1f536","1f537","1f538","1f539","2747","1f4ae","1f4af","21a9","21aa","1f503","1f50a","1f50b","1f50c","1f50d","1f50e","1f512","1f513","1f50f","1f510","1f511","1f514","2611","1f518","1f516","1f517","1f519","1f51a","1f51b","1f51c","1f51d","2705","270a","270b","270c","1f44a","1f44d","261d","1f446","1f447","1f448","1f449","1f44b","1f44f","1f44c","1f44e","1f450"];
	emojis = {};
	eachmoji   =  ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F'];
	emojis.smile = ['1F60', '1F61', '1F62', '1F63', '1F64'];
	emojis.flower = ['1F30', '1F31', '1F32', '1F33', '1F34', '1F35', '1F36', '1F37', '1F38', '1F39', 
					'1F3A', '1F3B', '1F3C', '1F3D', '1F3E', '1F3F', '1F40', '1F41', '1F42', '1F43',
					'1F44', '1F45', '1F46', '1F47', '1F48', '1F49', '1F4A', '1F4B', '1F4C', '1F4D',
					'1F4E', '1F4F', '1F50'];
	emojis.bell =  ['1F51', '1F52', '1F53', '1F54', '1F55', '1F56', '1F57', '1F58', '1F59', '1F5A',
					'1F5B', '1F5C', '1F5D', '1F5E', '1F5F' ]; /*, '260', '261', '262', '263', '264',
					'265', '266', '267', '268', '269', '26A', '26B', '26C', '26D', '26E', '26F'];*/
	emojis.car =   ['1F68', '1F69', '1F6A', '1F6B', '1F6C', '1F6D', '1F6E', '1F6F'];
	emojis.grid = emojis.smile.concat(emojis.flower).concat(emojis.bell).concat(emojis.car);

	$('[data-emoji-tab]').click(function(){
		tab = $(this).data('emojiTab');
		$('.icon-smile-selected').removeClass('icon-smile-selected').addClass('icon-smile'),
		$('.icon-flower-selected').removeClass('icon-flower-selected').addClass('icon-flower'),
		$('.icon-bell-selected').removeClass('icon-bell-selected').addClass('icon-bell'),
		$('.icon-car-selected').removeClass('icon-car-selected').addClass('icon-car'),
		$('.icon-grid-selected').removeClass('icon-grid-selected').addClass('icon-grid');
		$('.icon-'+tab).removeClass('icon-'+tab).addClass('icon-'+tab+'-selected');
		emoji_parse_append(tab);
	});
	function emoji_parse_append(tab){
		emojilist = '';
		$.each(emojis[tab], function(index, emoji_start){
			$.each(eachmoji, function(index, emoji_end){
				emojiid = (emoji_start+emoji_end).toLowerCase();
				if($.inArray(emojiid, allowed_emoji) !== -1){
					emojilist += '<a href="javascript:"><span class="emoji emoji'+emojiid+'" data-emoji="'+emojiid+'"></span></a>';
				}
			});
		});
		$('.emoji-items').html(emojilist);
		smile_insert_init();
	}
	emoji_parse_append('smile');
});


chat = {};
chat._add_comment = function(obj){
	if($(obj).find('.blue_button').hasClass('disabled')) return;
	$(obj).find('.blue_button').addClass('disabled');
	$(obj).parent().find('.chat_result').remove();
	$textarea = $('#chat_question_add_comment_field_text');
	if($textarea.hasClass('placeholder')){
		 $(obj).find('.blue_button').removeClass('disabled');
		 return;
	}
	$html = $textarea.html();
	$emojis = $textarea.find('.emoji');
	$.each($emojis, function(index, value){
		$emoji = $(this).attr('emoji');
		$(this).after($emoji);
		$(this).remove();
	});
	$text = $textarea.text();
	$textarea.html($html);

	$.post('/chat/send', {message: $text}, function(data) {
   		var response = JSON.parse(data);
   		if(response.error) {
   			$(obj).parent().append('<div class="chat_result">'+response.error+'</div>');
			$(obj).find('.blue_button').removeClass('disabled');
   		 	return;
   		} else {
   			//chat._refresh();
  			$textarea.html('');
  			$textarea.blur();
			$(obj).find('.blue_button').removeClass('disabled');
   		}
  	});
};
chat._del_comment = function(btn){
	
	$id_com = btn.id;
	//alert($id_com);
$.post('/chat/del', {id: $id_com}, function(data) {
   		var response = JSON.parse(data);
   		if(response.error) {
   			$(btn).parent().append('<div class="chat_result">'+response.error+'</div>');
   		 	return;
   		} else {
   			$(btn).parent().append('<div class="chat_del_result"> Это сообщение вами удаленно </div>');
   		}
  	});
};
chat._refresh = function(){
	$lastid = $('#chat_last_id').val();
	$.post('/chat/get', {lastid: $lastid}, function(data) {
		if(data == 0) return;
   		var response = JSON.parse(data);
   		if(response.result) {
  			$('.chat_comment_list').append(response.result);
			$('#chat_last_id').val(response.lastid);
  			chat_pull_down();
  			return;
   		} else {
   			return;
   		}
   	});
};

chat._full_refresh = function(){
	$.get('/chat/get', function(data) {
		if(data == 0) return;
   		var response = JSON.parse(data);
   		if(response.result) {
  			$('.chat_comment_list').html(response.result);
			$('#chat_last_id').val(response.lastid);
  			chat_pull_down();
  			return;
   		} else {
   			return;
   		}
   	});
};

function findSurrogatePair(point) {
  // assumes point > 0xffff
  var offset = point - 0x10000,
      lead = 0xd800 + (offset >> 10),
      trail = 0xdc00 + (offset & 0x3ff);
  return [lead.toString(16), trail.toString(16)];
}

function chat_pull_down(){
  	var messages    = $('.chat_comment_list');
  	var height = messages[0].scrollHeight;
  	messages.scrollTop(height);
}
