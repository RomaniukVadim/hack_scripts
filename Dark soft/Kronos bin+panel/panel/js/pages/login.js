$(document).ready(function() {

	//------------- Login page simple functions -------------//
 	$("html").addClass("loginPage");

 	wrapper = $(".login-wrapper");
 	barBtn = $("#bar .btn");

 	//change the tabs
 	barBtn.click(function() {
	  btnId = $(this).attr('id');
	  wrapper.attr("data-active", btnId);
	  $("#bar").attr("data-active", btnId);
	});

 	//show register tab
	$("#register").click(function() {
	  btnId = "reg";
	  wrapper.attr("data-active", btnId);
	  $("#bar").attr("data-active", btnId);
	});

	//check if user is change remove avatar
	var userField = $("input#user");
	var avatar = $("#avatar>img");

	//if user change email or username change avatar
	userField.change(function() {
		if($(this).val() === 'suggeelson@suggeelson.com') {
			avatar.attr('src', 'images/avatars/suggebig.jpg')
		} else {
			avatar.attr('src', 'images/avatars/no_avatar.jpg')
		}
	});

	//------------- Validation -------------//
	$("#login-form").validate({ 
		rules: {
			user: {
				required: true,
				minlength: 3
			}, 
			password: {
				required: true,
				minlength: 4
			}, 	
			security_number: {
				required: true,
				minlength: 8
			}
		}, 
		messages: {
			user: {
				required: "Please provide a username",
				minlength: "Username must be at least 3 characters long"
			},
			password: {
				required: "Please provide a password",
				minlength: "Your password must be at least 5 characters long"
			},
			security_number: {
				required: "Please provide a security code",
				minlength: "Code must be at least 8 characters long"
			}
		},
		submitHandler: function(form){
	       
	        	form.submit();
	       
		}
	});

});