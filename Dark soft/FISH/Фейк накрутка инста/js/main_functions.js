$('#category').on('change', function() {
	var selected = this.value;

	dataString = 'action=get-services&category-id='+selected;
	$.ajax({
		type: "POST",
		url: "requests.php",
		data: dataString,
		cache: false,
		success: function(data){
			if(data) {
				$("#service").html('<option disabled selected>Выберите вид накрутки.</option>');
				$("#service").append(data);
			} else {
				$("#service").html('<option selected="true" style="display:none;">Здесь пока ничего нет.</option>');
			}
		}
	});
});

function getBalance() {
	dataString = 'action=get-user-balance';
	$.ajax({
		type: "POST",
		url: "requests.php",
		data: dataString,
		cache: false,
		success: function(data){
			if(data) {
				$("#user-balance").html(data);
				$("#current-balance").html(data);
			}
		}
	});
}

function generateNewAPI() {
	dataString = 'action=generate-new-api';
	$.ajax({
		type: "POST",
		url: "requests.php",
		data: dataString,
		cache: false,
		beforeSend: function(){
			$("#user-api").val('Создание нового ключа API..');
		},
		success: function(data){
			if(data) {
				$("#user-api").val(data);
			}
		}
	});
}

function selectService(ServiceID) {
	dataString = 'action=select-service&service-id='+ServiceID;
	$.ajax({
		type: "POST",
		url: "requests.php",
		data: dataString,
		cache: false,
		success: function(data){
			if(data) {
				if(data == 'hashtag') {
					$("#additional").html('<div class="form-group"><div class="form-tip">Хэштег</div><input type="text" name="hashtag" class="input-md round form-control def-text" placeholder="Хэштег" required></div>');
				} else if(data == 'comments') {
					$("#additional").html('<div class="form-group"><div class="form-tip">Комментарии</div><textarea name="comments" class="input-md round form-control def-text" style="resize: none;" rows="8" placeholder="Комментарии (по одному в строке)" required></textarea></div>');
				} else if(data == 'mentions') {
					$("#additional").html('<div class="form-group"><div class="form-tip">Имя пользователя</div><input type="text" name="mentions_username" class="input-md round form-control def-text" placeholder="Упоминаемый пользователь" required></div>');
				} else {
					$("#additional").html('');
					$("#order_quantity").prop("readonly", false);
				}
			} else {
				$("#additional").html('');
				$("#order_quantity").prop("readonly", false);
			}
		}
	});
	
	var autoModeAllowedForServices = [3,4,5,6,7,11,12,14,30,31,32,33,36,40,41,43,44,48,49,52,64,66,71,72,73,92,94];
	var iServiceId = parseInt(ServiceID);
	var $form = $('form#new-order');
	if (autoModeAllowedForServices.indexOf(iServiceId) !== -1){
		$form.find('.form-group[data-name=order-mode]').show();
	} else {
		$form.find('.form-group[data-name=order-mode]').find('select[name=mode]').val('link').change();
		$form.find('.form-group[data-name=order-mode]').hide();
	}
	
}

function reloadService() {
	$("#service").html('<option disabled checked>Выберите вид накрутки.</option><option style="display:none;">Выберите категорию.</option>');
}

function nullQuantity() {
	$("#quantity").val(0);
}

function orderModeUpdated(el, ServiceID){
	var $form = $(el).parents('form');
	if ($(el).val()==='auto'){
		$form.find('.form-group[data-name=link]').find('.form-tip').html('Ссылка на аккаунт');
		$form.find('.form-group[data-name=link]').find('input[name=link]').attr('placeholder','Ссылка на аккаунт');
		$form.find('.form-group[data-name=posts-count]').show();
		$form.find('.form-group[data-name=quantity]').find('.form-tip').html('Количество накрутки на одну');
		$form.find('.form-group[data-name=quantity]').find('input[name=quantity]').attr('placeholder','Сколько крутить на одну');
		$form.find('.form-group[data-name=dispersion]').show();
		$form.find('.form-group[data-name=posts-exists-count]').show();
	} else {
		$form.find('.form-group[data-name=link]').find('.form-tip').html('Ссылка');
		$form.find('.form-group[data-name=link]').find('input[name=link]').attr('placeholder','Ссылка');
		$form.find('.form-group[data-name=posts-count]').hide();
		$form.find('.form-group[data-name=quantity]').find('.form-tip').html('Количество');
		$form.find('.form-group[data-name=quantity]').find('input[name=quantity]').attr('placeholder','Сколько крутить');
		$form.find('.form-group[data-name=dispersion]').hide();
		$form.find('.form-group[data-name=posts-exists-count]').hide();
	}
	
	
}

function removeQuantity() {
	$("#min_quantity").html("0");
	$("#max_quantity").html("0");
	$("#price").html("0");
	$("#desc").fadeOut();
}

function updateMinQuantity(ServiceID) {
	dataString = 'action=get-min-quantity&service-id='+ServiceID;
	$.ajax({
		type: "POST",
		url: "requests.php",
		data: dataString,
		cache: false,
		success: function(data){
			if(data) {
				$("#min_quantity").html(data);
			}
		}
	});
}


function eAjax(el, action){
	if ($(el).hasClass('disable')) return false;
	$(el).addClass('disable');
	var params = JSON.parse($(el).attr('data-params') || '{}');
	var data = $.extend(params, {action: action});
	$.ajax({
		type: "POST",
		url: "requests.php",
		data: data,
		cache: false,
		success: function(html){
			$('#m_service').html(html);
			$(el).removeClass('disable');
		}
	});
	return false;
}

function updatePrice(ServiceID, Quantity, PostsCount) {
	var dataString = 'action=get-price&service-id='+ServiceID+'&quantity='+Quantity+'&postsCount='+PostsCount;
	if(Quantity > 0) {
		$.ajax({
			type: "POST",
			url: "requests.php",
			data: dataString,
			cache: false,
			success: function(data){
				if(data) {
					$("#price").html(data);
				}
			}
		});
	} else {
		$("#price").html(0);
	}
}

function updateMaxQuantity(ServiceID) {
	dataString = 'action=get-max-quantity&service-id='+ServiceID;
	$.ajax({
		type: "POST",
		url: "requests.php",
		data: dataString,
		cache: false,
		dataType: "json",
		success: function(data){
			if(data) {
				$("#max_quantity").html(data.par1);
			}
		}
	});
}

function updateLinkMaxQuantity(ServiceID, Link) {
	dataString = 'action=get-link-quantity&service-id='+ServiceID+'&link='+Link;
	$.ajax({
		type: "POST",
		url: "requests.php",
		data: dataString,
		cache: false,
		success: function(data){
			if(data) {
				$("#max_quantity").html(data);
			}
		}
	});
}

function updateDescription(ServiceID) {
	dataString = 'action=get-description&service-id='+ServiceID;
	$.ajax({
		type: "POST",
		url: "requests.php",
		data: dataString,
		cache: false,
		dataType: "json",
		success: function(data){
			if(data) {
				var dataMax = String(data.max).replace(/(\d)(?=(\d{3})+(\D|$))/g, '$1 ');
				$("#min_quantity").html(data.min);
				$("#max_quantity").html(dataMax);
			    $("#desc").fadeIn();
				$("#desc").html(data.des);
			}
		}
	});
}

$("#show-order-example").click(function() {
	if($("#example-create-order").is(':visible')) {
		$("#show-order-example").html('Показать пример.');
		$("#example-create-order").hide( "slow" );
	} else {
		$("#show-order-example").html('Скрыть пример.');
		$("#example-create-order").show( "slow" );
	}
});

$("#show-referral-url").click(function() {
	if($("#referral-url").is(':visible')) {
		$("#show-referral-url").html('Показать партнерскую ссылку.');
		$("#referral-url").hide( "slow" );
	} else {
		$("#show-referral-url").html('Скрыть партнерскую ссылку.');
		$("#referral-url").show( "slow" );
	}
});
